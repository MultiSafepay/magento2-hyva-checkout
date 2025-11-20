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

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magewirephp\Magewire\Component;
use MultiSafepay\ConnectCore\Util\JsonHandler;

class PaymentVault extends Component
{
    public const VAULT_CODE = '';

    private CheckoutSession $checkoutSession;
    private PaymentTokenRepositoryInterface $paymentTokenRepository;
    private CustomerSession $customerSession;
    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;
    private JsonHandler $jsonHandler;
    private CartRepositoryInterface $quoteRepository;

    public ?string $publicHash = '';

    /**
     * @param CheckoutSession $checkoutSession
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param CustomerSession $customerSession
     * @param JsonHandler $jsonHandler
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        CustomerSession $customerSession,
        JsonHandler $jsonHandler,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->customerSession = $customerSession;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->jsonHandler = $jsonHandler;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Mounts the component and retrieves the public hash from the quote's payment information.
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function mount(): void
    {
        $quote = $this->checkoutSession->getQuote();
        $this->publicHash = $quote->getPayment()->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH);

        $tokens = $this->getStoredTokens();
        if (!empty($tokens)) {
            $this->publicHash = $tokens[0]['public_hash'];
            $quote->getPayment()->setAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH, $this->publicHash);
            $quote->getPayment()->setAdditionalInformation(PaymentTokenInterface::CUSTOMER_ID, $quote->getCustomerId());

            $this->quoteRepository->save($quote);
        }
    }

    /**
     * Retrieves the stored payment tokens for the logged-in customer.
     *
     * @return array
     */
    public function getStoredTokens(): array
    {
        if (!$this->customerSession->isLoggedIn()) {
            return [];
        }

        $searchCriteria = $this->searchCriteriaBuilderFactory->create();

        $searchCriteria->addFilter(PaymentTokenInterface::IS_VISIBLE, 1);
        $searchCriteria->addFilter(PaymentTokenInterface::IS_ACTIVE, 1);
        $searchCriteria->addFilter(PaymentTokenInterface::CUSTOMER_ID, $this->customerSession->getCustomerId());
        $searchCriteria->addFilter(PaymentTokenInterface::PAYMENT_METHOD_CODE, static::VAULT_CODE);

        $tokens = $this->paymentTokenRepository->getList($searchCriteria->create())->getItems();
        $tokenList = [];

        foreach ($tokens as $token) {
            $tokenDetails = $this->jsonHandler->readJSON($token->getTokenDetails());
            $tokenDetails['public_hash'] = $token->getPublicHash();

            $tokenList[] = $tokenDetails;
        }

        return $tokenList;
    }

    /**
     * Updates the quote with the public hash and customer ID
     *
     * @param $value
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updated($value)
    {
        $quote = $this->checkoutSession->getQuote();
        $quote->getPayment()->setAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH, $this->publicHash);
        $quote->getPayment()->setAdditionalInformation(PaymentTokenInterface::CUSTOMER_ID, $quote->getCustomerId());

        $this->quoteRepository->save($quote);

        return $value;
    }
}
