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

namespace MultiSafepay\HyvaCheckout\Observer;

use Exception;
use Hyva\Checkout\Model\CheckoutInformation\Luma;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\HyvaCheckout\Util\VersionUtil;

class OrderRequestObserver implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * OrderRequestObserver constructor.
     *
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Hook into the order request event to modify or add data
     *
     * @param Observer $observer
     * @throws Exception
     */
    public function execute(Observer $observer): void
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        $checkoutField = $this->scopeConfig->getValue(
            'hyva_themes_checkout/general/checkout',
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        // Early return for Luma Checkout
        if ($checkoutField === Luma::NAMESPACE) {
            return;
        }

        /** @var OrderRequest $orderRequest */
        $orderRequest = $observer->getData('orderRequest');

        $pluginDetails = $orderRequest->getPluginDetails();

        if ($pluginDetails === null) {
            $this->logger->logInfoForOrder(
                $order->getIncrementId(),
                'plugin details object not found, could not prepend Hyva Checkout'
            );
        }

        $applicationName = $pluginDetails->getApplicationName();
        $pluginDetails->addApplicationName($applicationName . ' - Hyva Checkout');

        $pluginVersion = $pluginDetails->getPluginVersion()->getPluginVersion();
        $pluginDetails->addPluginVersion($pluginVersion . ' - ' . VersionUtil::VERSION);

        $hyvaCheckoutVersion = 'unknown';

        if (method_exists('\Composer\InstalledVersions', 'getVersion')) {
            $hyvaCheckoutVersion = \Composer\InstalledVersions::getVersion('hyva-themes/magento2-hyva-checkout');
        }

        $applicationVersion = $pluginDetails->getApplicationVersion();
        $pluginDetails->addApplicationVersion($applicationVersion . ' - ' . $hyvaCheckoutVersion);
    }
}
