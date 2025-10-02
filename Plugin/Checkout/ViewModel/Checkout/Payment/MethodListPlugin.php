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

namespace MultiSafepay\HyvaCheckout\Plugin\Checkout\ViewModel\Checkout\Payment;

use Hyva\Checkout\ViewModel\Checkout\Payment\MethodList;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Vault\Model\ResourceModel\PaymentToken\CollectionFactory as TokenCollectionFactory;

class MethodListPlugin
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var TokenCollectionFactory
     */
    protected TokenCollectionFactory $tokenCollectionFactory;

    /**
     * @param CustomerSession $customerSession
     * @param TokenCollectionFactory $tokenCollectionFactory
     */
    public function __construct(
        CustomerSession $customerSession,
        TokenCollectionFactory $tokenCollectionFactory
    ) {
        $this->customerSession = $customerSession;
        $this->tokenCollectionFactory = $tokenCollectionFactory;
    }

    /**
     * After plugin for the getList method to filter out recurring payment methods.
     *
     * @param MethodList $subject
     * @param ?array $result
     * @return ?array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(MethodList $subject, ?array $result): ?array
    {
        if (!$result) {
            return $result;
        }

        $isGuest = !$this->customerSession->isLoggedIn();
        $customerId = $isGuest ? null : $this->customerSession->getCustomerId();

        // If customer is logged in, fetch their saved payment tokens
        $allowedVaultMethods = [];
        if ($customerId) {
            $tokenCollection = $this->tokenCollectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('is_active', 1);

            foreach ($tokenCollection as $token) {
                $allowedVaultMethods[] = $token->getPaymentMethodCode(); // e.g., 'vault_visa'
            }
        }

        return array_filter($result, function ($method) use ($isGuest, $allowedVaultMethods) {
            $code = $method->getCode();

            // Always exclude recurring methods
            if (str_contains($code, 'multisafepay') && str_contains($code, 'recurring')) {
                return false;
            }

            // Exclude vault methods for guests
            if ($isGuest && str_contains($code, 'vault')) {
                return false;
            }

            // For logged-in users, only allow vault methods they own
            if (!$isGuest && str_contains($code, 'vault') && !in_array($code, $allowedVaultMethods)) {
                return false;
            }

            return true;
        });
    }
}
