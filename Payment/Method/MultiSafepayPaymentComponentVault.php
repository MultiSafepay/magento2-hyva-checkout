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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class MultiSafepayPaymentComponentVault extends MultiSafepayPaymentComponent
{
    /**
     * Check if vault is enabled for the payment method.
     *
     * @return bool
     */
    public function isVaultEnabled(): bool
    {
        try {
            return (bool)$this->scopeConfig->getValue(
                'payment/' . $this->getMethodCode() . '_vault/active',
                ScopeInterface::SCOPE_STORE,
                $this->sessionCheckout->getQuote()->getStoreId() ?? null
            );
        } catch (NoSuchEntityException | LocalizedException $exception) {
            return false;
        }
    }

    /**
     * Updates the quote with the public hash and customer ID
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updateVaultTokenEnabler(bool $value)
    {
        $quote = $this->sessionCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE, $value);

        $this->quoteRepository->save($quote);
    }
}
