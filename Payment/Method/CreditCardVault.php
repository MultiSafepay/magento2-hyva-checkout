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
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magewirephp\Magewire\Component;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardRecurringConfigProvider;
use MultiSafepay\ConnectCore\Util\JsonHandler;

class CreditCardVault extends Component
{
    private CheckoutSession $checkoutSession;
    private PaymentTokenRepositoryInterface $paymentTokenRepository;
    private CustomerSession $customerSession;
    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;
    private JsonHandler $jsonHandler;

    public ?string $publicHash = '';

    /**
     * @param CheckoutSession $checkoutSession
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param CustomerSession $customerSession
     * @param JsonHandler $jsonHandler
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        CustomerSession $customerSession,
        JsonHandler $jsonHandler
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->customerSession = $customerSession;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function mount(): void
    {
        $quote = $this->checkoutSession->getQuote();
        $this->publicHash = $quote->getPayment()->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH);
    }

    /**
     * @return array
     */
    public function getStoredTokens(): array
    {
        if (!$this->customerSession->isLoggedIn()) {
            return [];
        }

        /** @var SearchCriteriaBuilder $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilderFactory->create();

        $searchCriteria->addFilter(PaymentTokenInterface::IS_VISIBLE, 1);
        $searchCriteria->addFilter(PaymentTokenInterface::IS_ACTIVE, 1);
        $searchCriteria->addFilter(PaymentTokenInterface::CUSTOMER_ID, $this->customerSession->getCustomerId());
        $searchCriteria->addFilter(PaymentTokenInterface::PAYMENT_METHOD_CODE, CreditCardRecurringConfigProvider::CODE);

        $tokens = $this->paymentTokenRepository->getList($searchCriteria->create())->getItems();
        $tokenList = [];

        foreach ($tokens as $token) {
            $tokenDetails = $this->jsonHandler->readJSON($token->getTokenDetails());
            $tokenDetails['public_hash'] = $token->getPublicHash();

            $tokenList[] = $tokenDetails;
        }

        return $tokenList;
    }
}
