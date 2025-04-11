<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\HyvaCheckout\Payment\Method;

use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magewirephp\Magewire\Component;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AfterpayConfigProvider;
use MultiSafepay\ConnectCore\Util\ApiTokenUtil;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\RecurringTokensUtil;
use MultiSafepay\Exception\InvalidDataInitializationException;
use Rakit\Validation\Validator;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;

class MultiSafepayRivertyComponent extends Component\Form
{
    /**
     * @var ?Quote
     */
    private ?Quote $quote = null;

    /**
     * @var ApiTokenUtil
     */
    private ApiTokenUtil $apiTokenUtil;

    /**
     * @var SessionCheckout
     */
    private SessionCheckout $sessionCheckout;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var ResolverInterface
     */
    private ResolverInterface $localeResolver;

    /**
     * @var RecurringTokensUtil
     */
    private RecurringTokensUtil $recurringTokensUtil;

    /**
     * @var JsonHandler
     */
    private JsonHandler $jsonHandler;

    /**
     * @var PaymentConfig
     */
    private PaymentConfig $paymentConfig;

    /**
     * @param Validator $validator
     * @param ApiTokenUtil $apiTokenUtil
     * @param SessionCheckout $sessionCheckout
     * @param Config $config
     * @param CartRepositoryInterface $quoteRepository
     * @param ResolverInterface $localeResolver
     * @param RecurringTokensUtil $recurringTokensUtil
     * @param JsonHandler $jsonHandler
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(
        Validator $validator,
        ApiTokenUtil $apiTokenUtil,
        SessionCheckout $sessionCheckout,
        Config $config,
        CartRepositoryInterface $quoteRepository,
        ResolverInterface  $localeResolver,
        RecurringTokensUtil $recurringTokensUtil,
        JsonHandler $jsonHandler,
        PaymentConfig $paymentConfig
    ) {
        parent::__construct($validator);
        $this->apiTokenUtil = $apiTokenUtil;
        $this->sessionCheckout = $sessionCheckout;
        $this->config = $config;
        $this->quoteRepository = $quoteRepository;
        $this->localeResolver = $localeResolver;
        $this->recurringTokensUtil = $recurringTokensUtil;
        $this->jsonHandler = $jsonHandler;
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * Get the API token
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getApiToken(): string
    {
        try {
            return $this->apiTokenUtil->getApiTokenFromCache($this->getQuote())['apiToken'] ?? '';
        } catch (InvalidDataInitializationException $exception) {
            $this->dispatchErrorMessage($exception->getMessage());
        }

        return '';
    }

    /**
     * Get the environment
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getEnvironment(): string
    {
        return $this->config->isLiveMode($this->getQuote()->getStoreId()) ? 'live' : 'test';
    }

    /**
     * Get the amount
     *
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAmount(): int
    {
        return (int)($this->getQuote()->getGrandTotal() * 100);
    }

    /**
     * Get the currency
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrency(): string
    {
        return $this->getQuote()->getCurrency()->getQuoteCurrencyCode() ?? 'EUR';
    }

    /**
     * Get the locale
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->localeResolver->getLocale();
    }

    /**
     * Get the country
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCountry(): string
    {
        return (string)$this->getQuote()->getBillingAddress()->getCountryId() ?? '';
    }

    /**
     * Get the method code
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getMethodCode(): string
    {
        return AfterpayConfigProvider::CODE;
    }

    /**
     * Get the quote
     *
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote(): Quote
    {
        if (!$this->quote) {
            $this->quote = $this->sessionCheckout->getQuote();
        }

        return $this->quote ;
    }

    /**
     * Get the gateway code
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getGatewayCode(): string
    {
        $quote = $this->getQuote();

        if ($payment = $quote->getPayment()) {
            return $payment->getMethodInstance()->getConfigData('gateway_code');
        }

        return '';
    }

    /**
     * Get the recurring tokens if they exist
     *
     * @return string|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getTokens(): ?string
    {
        $quote = $this->getQuote();

        // Don't need to add tokens if the customer is a guest
        if ($quote->getCustomerIsGuest()) {
            return null;
        }

        if ($payment = $quote->getPayment()) {
            $methodInstance = $payment->getMethodInstance();
            $storeId = (int)$quote->getStoreId();

            $isTokenizationEnabled = (bool)$methodInstance->getConfigData('tokenization', $storeId);

            if ($isTokenizationEnabled) {
                $tokenArray = $this->recurringTokensUtil->getListByGatewayCode(
                    (string)$quote->getCustomer()->getId(),
                    ['gateway_code' => $methodInstance->getConfigData('gateway_code')],
                    $storeId
                );

                return $this->jsonHandler->convertToJSON($tokenArray);
            }
        }

        return null;
    }

    /**
     * Check if payment component is enabled
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isPaymentComponentEnabled(): bool
    {
        $quote = $this->getQuote();

        if ($payment = $quote->getPayment()) {
            $paymentType = $payment->getMethodInstance()->getConfigData('payment_type');

            if ($paymentType === 'payment_component') {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the payment component data
     *
     * @param array $paymentComponentData
     * @return void
     */
    public function setPaymentComponentData(array $paymentComponentData)
    {
        $additionalInformation = [];

        if (isset($paymentComponentData['tokenize']) && $paymentComponentData['tokenize']) {
            $additionalInformation['tokenize'] = $paymentComponentData['tokenize'];
        }

        $additionalInformation['payload'] = $paymentComponentData['payload'] ?? '';
        $additionalInformation['transaction_type'] = 'direct';

        try {
            $quote = $this->getQuote();

            $quote->getPayment()->setAdditionalInformation($additionalInformation);
            $this->quoteRepository->save($quote);
        } catch (LocalizedException $exception) {
            $this->dispatchErrorMessage($exception->getMessage());
        }
    }
}
