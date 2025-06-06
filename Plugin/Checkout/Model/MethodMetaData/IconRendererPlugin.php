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
use Magento\Framework\Escaper;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Util\GenericGatewayUtil;

class IconRendererPlugin
{
    /**
     * @var GatewayConfig
     */
    private GatewayConfig $gatewayConfig;

    /**
     * @var GenericGatewayUtil
     */
    private GenericGatewayUtil $genericGatewayUtil;

    /**
     * @var Escaper
     */
    private Escaper $escaper;

    /**
     * @param GatewayConfig $gatewayConfig
     * @param GenericGatewayUtil $genericGatewayUtil
     * @param Escaper $escaper
     */
    public function __construct(
        GatewayConfig $gatewayConfig,
        GenericGatewayUtil $genericGatewayUtil,
        Escaper $escaper
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->genericGatewayUtil = $genericGatewayUtil;
        $this->escaper = $escaper;
    }

    /**
     * Changes the svg icon path depending on the payment method and icon type configuration
     *
     * @param IconRenderer $subject
     * @param string $path
     * @param array $attributes
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeRenderAsSvg(IconRenderer $subject, string $path, array $attributes = []): array
    {
        $cardPaymentIcon = $this->renderCardPaymentIcon($path);

        if ($cardPaymentIcon) {
            return [$cardPaymentIcon, $attributes];
        }

        return [$path, $attributes];
    }

    /**
     * Change the image icon path depending on the payment method
     *
     * @param IconRenderer $subject
     * @param $result
     * @return string
     * @throws FileSystemException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRenderAsImage(IconRenderer $subject, $result): string
    {
        return $this->renderGenericGatewayIcon($result);
    }

    /**
     * For card payment the icon that needs to be rendered should be determined by the icon type configuration value
     *
     * @param string $path
     * @return string
     */
    private function renderCardPaymentIcon(string $path): string
    {
        if ($path !== 'multisafepay/multisafepay_creditcard_default') {
            return '';
        }

        $this->gatewayConfig->setMethodCode(CreditCardConfigProvider::CODE);

        $iconType = $this->gatewayConfig->getValue(Config::PAYMENT_ICON);

        if (!isset($iconType) || !$iconType) {
            return '';
        }

        return 'multisafepay/multisafepay_creditcard_' . $iconType;
    }

    /**
     * Generic gateway icons that need to be rendered have a different path, because they are uploaded by the user
     *
     * @param string $url
     * @return string
     * @throws NoSuchEntityException
     * @throws FileSystemException
     */
    private function renderGenericGatewayIcon(string $url): string
    {
        if (strpos($url, 'multisafepay_genericgateway_') === false) {
            return $url;
        }

        for ($count = 1; $count <= 6; $count++) {
            if (strpos($url, 'multisafepay_genericgateway_' . $count) !== false) {
                $genericGatewayImage = $this->genericGatewayUtil->getGenericFullImagePath(
                    'multisafepay_genericgateway_' . $count
                );

                if ($genericGatewayImage) {
                    return '<img src="' . $this->escaper->escapeUrl($genericGatewayImage) . '" />';
                }

                return '';
            }
        }

        return '';
    }
}
