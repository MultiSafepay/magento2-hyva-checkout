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
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\PaymentToken;

class MethodListPlugin
{
    protected CustomerSession $customerSession;
    private PaymentTokenManagementInterface $paymentTokenManagement;

    /**
     * @param CustomerSession $customerSession
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     */
    public function __construct(
        CustomerSession $customerSession,
        PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        $this->customerSession = $customerSession;
        $this->paymentTokenManagement = $paymentTokenManagement;
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
        $allowedVaultMethods = $isGuest ? [] : $this->getAllowedVaultMethods();

        return array_filter($result, function ($method) use ($isGuest, $allowedVaultMethods) {
            $code = $method->getCode();

            if (!str_contains($code, 'multisafepay')) {
                return true;
            }

            if (str_contains($code, 'recurring')) {
                return false;
            }

            if (!str_contains($code, 'vault')) {
                return true;
            }

            return $this->handleVaultMethod($method, $isGuest, $allowedVaultMethods);
        });
    }

    /**
     * Retrieve allowed vault methods for the logged-in customer.
     *
     * @return array
     */
    private function getAllowedVaultMethods(): array
    {
        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) {
            return [];
        }

        $allowedMethods = [];
        $paymentTokens = $this->paymentTokenManagement->getListByCustomerId($customerId);

        /** @var PaymentToken $token */
        foreach ($paymentTokens as $token) {

            if (!$token->getIsActive()) {
                continue;
            }

            if (preg_match('/^multisafepay_(.+?)_recurring$/', $token->getPaymentMethodCode(), $matches)) {
                $allowedMethods[] = $matches[1];
            }
        }

        return $allowedMethods;
    }

    /**
     * Handle vault payment method logic.
     *
     * @param mixed $method
     * @param bool $isGuest
     * @param array $allowedVaultMethods
     * @return bool
     */
    private function handleVaultMethod($method, bool $isGuest, array $allowedVaultMethods): bool
    {
        if ($isGuest) {
            return false;
        }

        if (preg_match('/^multisafepay_(.+?)_vault$/', $method->getCode(), $matches)) {
            if (in_array($matches[1], $allowedVaultMethods)) {
                return true;
            }
        }

        return false;
    }


}
