<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

namespace MultiSafepay\MagewireCheckout\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\MagewireCheckout\Util\VersionUtil;

class OrderRequestObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * OrderRequestObserver constructor.
     *
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
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

        /** @var OrderRequest $orderRequest */
        $orderRequest = $observer->getData('orderRequest');

        $pluginDetails = $orderRequest->getPluginDetails();

        if ($pluginDetails === null) {
            $this->logger->logInfoForOrder(
                $order->getIncrementId(),
                'plugin details object not found, could not prepend Magewire Checkout',
                Logger::INFO
            );
        }

        $applicationName = $pluginDetails->getApplicationName();
        $pluginDetails->addApplicationName($applicationName . ' - Magewire Checkout');

        $pluginVersion = $pluginDetails->getPluginVersion()->getPluginVersion();
        $pluginDetails->addPluginVersion($pluginVersion . ' - ' . VersionUtil::VERSION);

        $magewireCheckoutVersion = 'unknown';

        if (method_exists('\Composer\InstalledVersions', 'getVersion')) {
            $magewireCheckoutVersion = \Composer\InstalledVersions::getVersion('hyva-themes/checkout-default');
        }

        $applicationVersion = $pluginDetails->getApplicationVersion();
        $pluginDetails->addApplicationVersion($applicationVersion . ' - ' . $magewireCheckoutVersion);
    }
}
