<?php

declare(strict_types=1);

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
