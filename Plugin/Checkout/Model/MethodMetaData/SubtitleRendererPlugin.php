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

namespace MultiSafepay\HyvaCheckout\Plugin\Checkout\Model\MethodMetaData;

use Hyva\Checkout\Model\MethodMetaData\SubtitleRenderer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class SubtitleRendererPlugin
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Make sure that nothing gets rendered if the instructions have not been filled in the configuration
     *
     * @param SubtitleRenderer $subject
     * @param string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRender(SubtitleRenderer $subject, string $result): string
    {
        if (!str_contains($result, 'multisafepay')) {
            return $result;
        }

        $instructions = $this->scopeConfig->getValue(strtolower($result), ScopeInterface::SCOPE_STORE);

        if (empty($instructions)) {
            return '';
        }

        return $instructions;
    }
}
