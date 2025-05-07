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

use Exception;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magewirephp\Magewire\Component;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Util\ApiTokenUtil;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\RecurringTokensUtil;
use Rakit\Validation\Validator;

class MultiSafepayPaymentComponent extends Component\Form
{
    /**
     * @var ApiTokenUtil
     */
    protected ApiTokenUtil $apiTokenUtil;

    /**
     * @var SessionCheckout
     */
    protected SessionCheckout $sessionCheckout;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var CartRepositoryInterface
     */
    protected CartRepositoryInterface $quoteRepository;

    /**
     * @var ResolverInterface
     */
    protected ResolverInterface $localeResolver;

    /**
     * @var RecurringTokensUtil
     */
    protected RecurringTokensUtil $recurringTokensUtil;

    /**
     * @var JsonHandler
     */
    protected JsonHandler $jsonHandler;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @param Validator $validator
     * @param ApiTokenUtil $apiTokenUtil
     * @param SessionCheckout $sessionCheckout
     * @param Config $config
     * @param CartRepositoryInterface $quoteRepository
     * @param ResolverInterface $localeResolver
     * @param RecurringTokensUtil $recurringTokensUtil
     * @param JsonHandler $jsonHandler
     * @param ScopeConfigInterface $scopeConfig
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
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($validator);
        $this->apiTokenUtil = $apiTokenUtil;
        $this->sessionCheckout = $sessionCheckout;
        $this->config = $config;
        $this->quoteRepository = $quoteRepository;
        $this->localeResolver = $localeResolver;
        $this->recurringTokensUtil = $recurringTokensUtil;
        $this->jsonHandler = $jsonHandler;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get the API token
     *
     * @return string
     * @throws Exception
     */
    public function getApiToken(): string
    {
        try {
            return $this->apiTokenUtil->getApiTokenFromCache($this->sessionCheckout->getQuote())['apiToken'] ?? '';
        } catch (Exception $exception) {
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
        return $this->config->isLiveMode($this->sessionCheckout->getQuote()->getStoreId()) ? 'live' : 'test';
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
        return (int)($this->sessionCheckout->getQuote()->getGrandTotal() * 100);
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
        return $this->sessionCheckout->getQuote()->getCurrency()->getQuoteCurrencyCode() ?? 'EUR';
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
        return (string)$this->sessionCheckout->getQuote()->getBillingAddress()->getCountryId() ?? '';
    }

    /**
     * Get the method code
     *
     * @return string
     */
    public function getMethodCode(): string
    {
        try {
            return $this->sessionCheckout->getQuote()->getPayment()->getMethod() ?? '';
        } catch (LocalizedException $exception) {
            $this->dispatchErrorMessage($exception->getMessage());
            return '';
        }
    }

    /**
     * Get the gateway code
     *
     * @return string
     */
    public function getGatewayCode(): string
    {
        try {
            return $this->scopeConfig->getValue(
                'payment/' . $this->getMethodCode() . '/gateway_code',
                ScopeInterface::SCOPE_STORE,
                $this->sessionCheckout->getQuote()->getStoreId() ?? null
            ) ?? '';
        } catch (LocalizedException $exception) {
            $this->dispatchErrorMessage($exception->getMessage());
            return '';
        }
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
        $quote = $this->sessionCheckout->getQuote();

        // Don't need to add tokens if the customer is a guest
        if ($quote->getCustomerIsGuest()) {
            return null;
        }

        $storeId = (int)$quote->getStoreId();

        $isTokenizationEnabled = (bool)$this->scopeConfig->getValue(
            'payment/' . $this->getMethodCode() . '/tokenization',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($isTokenizationEnabled) {
            $tokenArray = $this->recurringTokensUtil->getListByGatewayCode(
                (string)$quote->getCustomer()->getId(),
                ['gateway_code' => $this->getGatewayCode()],
                $storeId
            );

            return $this->jsonHandler->convertToJSON($tokenArray);
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
        try {
            $paymentType = $this->scopeConfig->getValue(
                'payment/' . $this->getMethodCode() . '/payment_type',
                ScopeInterface::SCOPE_STORE,
                $this->sessionCheckout->getQuote()->getStoreId() ?? null
            );

            if ($paymentType === 'payment_component') {
                return true;
            }
        } catch (LocalizedException $exception) {
            $this->dispatchErrorMessage($exception->getMessage());
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
            $quote = $this->sessionCheckout->getQuote();

            $quote->getPayment()->setAdditionalInformation($additionalInformation);
            $this->quoteRepository->save($quote);
        } catch (LocalizedException $exception) {
            $this->dispatchErrorMessage($exception->getMessage());
        }
    }
}
