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

namespace MultiSafepay\HyvaCheckout\Plugin\Vault\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Vault\Model\Method\Vault;

class VaultTitlePlugin
{
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Modify the title of the vault payment method to include the original payment method's title.
     *
     * @param Vault $subject
     * @param string|null $result
     * @return string
     */
    public function afterGetTitle(Vault $subject, ?string $result): ?string
    {
        $configPath = 'hyva_themes_checkout/general/checkout';
        $checkoutType = $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE);

        if ($checkoutType === 'magento_luma') {
            return $result;
        }

        $code = $subject->getCode();

        if (!str_contains($code, 'multisafepay') || !str_contains($code, 'vault')) {
            return $result;
        }

        $baseMethodCode = str_replace('_vault', '', $code);

        $configPath = "payment/{$baseMethodCode}/title";
        $title = $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE);

        if ($title) {
            return (string) __('Stored Methods (%1)', $title);
        }

        return $result;
    }
}
