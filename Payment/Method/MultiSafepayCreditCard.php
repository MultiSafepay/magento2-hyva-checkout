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
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\ApiTokenUtil;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\RecurringTokensUtil;
use Rakit\Validation\Validator;

class MultiSafepayCreditCard extends Component\Form
{
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
     * @var Logger
     */
    private Logger $logger;

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
     * @var string
     */
    public string $payload = '';

    /**
     * @var string
     */
    public string $apiToken = '';

    /**
     * @var int
     */
    public int $amount = 0;

    /**
     * @var Quote|null
     */
    public ?Quote $quote = null;

    /**
     * @var string
     */
    public string $environment = '';

    /**
     * @var string
     */
    public string $currency = '';

    /**
     * @var string
     */
    public string $gatewayCode = '';

    /**
     * @var string
     */
    public string $locale = '';

    /**
     * @var string
     */
    public string $country = '';

    /**
     * @var string|null
     */
    public ?string $tokens = null;

    /**
     * @var JsonHandler
     */
    private JsonHandler $jsonHandler;

    /**
     * @param Validator $validator
     * @param ApiTokenUtil $apiTokenUtil
     * @param SessionCheckout $sessionCheckout
     * @param Config $config
     * @param Logger $logger
     * @param CartRepositoryInterface $quoteRepository
     * @param ResolverInterface $localeResolver
     * @param RecurringTokensUtil $recurringTokensUtil
     * @param JsonHandler $jsonHandler
     */
    public function __construct(
        Validator $validator,
        ApiTokenUtil $apiTokenUtil,
        SessionCheckout $sessionCheckout,
        Config $config,
        Logger $logger,
        CartRepositoryInterface $quoteRepository,
        ResolverInterface $localeResolver,
        RecurringTokensUtil $recurringTokensUtil,
        JsonHandler $jsonHandler
    ) {
        parent::__construct($validator);
        $this->apiTokenUtil = $apiTokenUtil;
        $this->sessionCheckout = $sessionCheckout;
        $this->config = $config;
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->localeResolver = $localeResolver;
        $this->recurringTokensUtil = $recurringTokensUtil;
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * @return void
     */
    public function mount(): void
    {
        $this->apiToken = $this->apiTokenUtil->getApiTokenFromCache($this->getQuote())['apiToken'] ?? '';
        $this->environment = $this->config->isLiveMode($this->getQuote()->getStoreId()) ? 'live' : 'test';
        $this->amount = (int)$this->getQuote()->getGrandTotal() * 100;
        $this->currency = $this->getQuote()->getCurrency()->getQuoteCurrencyCode() ?? 'EUR';
        $this->gatewayCode = $this->getGatewayCode();
        $this->locale = $this->localeResolver->getLocale();
        $this->country = $this->getQuote()->getBillingAddress()->getCountryId() ?? '';
        $this->tokens = $this->getTokens();
    }

    /**
     * @return Quote|null
     */
    private function getQuote(): ?Quote
    {
        if (!$this->quote) {
            try {
                $this->quote = $this->sessionCheckout->getQuote();
            } catch (NoSuchEntityException | LocalizedException $exception) {
                $this->logger->logPaymentComponentException($exception);
            }
        }

        return $this->quote;
    }

    /**
     * Get the gateway code
     *
     * @return string
     */
    private function getGatewayCode(): string
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
     */
    private function getTokens(): ?string
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
     * @param array $paymentComponentData
     * @return void
     */
    public function setPaymentComponentData(array $paymentComponentData) {
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
