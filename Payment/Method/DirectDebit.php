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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\DirectDebitConfigProvider;
use Magewirephp\Magewire\Component;
use Rakit\Validation\Validator;

class DirectDebit extends Component\Form
{
    private CheckoutSession $checkoutSession;
    private ScopeConfigInterface $scopeConfig;
    private CartRepositoryInterface $quoteRepository;

    public function __construct(
        Validator $validator,
        CheckoutSession $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($validator);
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Get the method code
     *
     * @return string
     */
    public function getMethodCode(): string
    {
        return DirectDebitConfigProvider::CODE;
    }

    /**
     * Check if vault is enabled for the payment method.
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isVaultEnabled(): bool
    {
        $quote = $this->checkoutSession->getQuote();

        if (!$quote || !$quote->getCustomerId()) {
            return false;
        }

        try {
            return (bool)$this->scopeConfig->getValue(
                'payment/' . $this->getMethodCode() . '_vault/active',
                ScopeInterface::SCOPE_STORE,
                $this->checkoutSession->getQuote()->getStoreId() ?? null
            );
        } catch (NoSuchEntityException | LocalizedException $exception) {
            return false;
        }
    }

    /**
     * Sets the vault token enabler flag in the payment's additional information for the current quote.
     *
     * @param bool $value
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updateVaultTokenEnabler(bool $value)
    {
        $quote = $this->checkoutSession->getQuote();
        $quote->getPayment()->setAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE, $value);

        $this->quoteRepository->save($quote);
    }
}
