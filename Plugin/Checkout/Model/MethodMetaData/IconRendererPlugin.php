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

use Hyva\Checkout\Model\MethodMetaData\IconRenderer;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;

class IconRendererPlugin
{
    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;

    /**
     * @param GatewayConfig $gatewayConfig
     */
    public function __construct(
        GatewayConfig $gatewayConfig
    ) {
        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * For card payment the icon that needs to be rendered should be determined by the icon type configuration value
     *
     * @param IconRenderer $subject
     * @param string $path
     * @param array $attributes
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeRenderAsSvg(IconRenderer $subject, string $path, array $attributes): array
    {
        if ($path !== 'multisafepay/multisafepay_creditcard_default') {
            return [$path, $attributes];
        }

        $this->gatewayConfig->setMethodCode(CreditCardConfigProvider::CODE);

        $iconType = $this->gatewayConfig->getValue(Config::PAYMENT_ICON);

        if (!isset($iconType) || !$iconType) {
            return [$path, $attributes];
        }

        return ['multisafepay/multisafepay_creditcard_' . $iconType, $attributes];
    }
}
