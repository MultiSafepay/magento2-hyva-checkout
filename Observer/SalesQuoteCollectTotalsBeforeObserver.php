<?php

declare(strict_types=1);

namespace MultiSafepay\HyvaCheckout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use MultiSafepay\ConnectCore\Config\Config;

class SalesQuoteCollectTotalsBeforeObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getData('quote');

        $payment = $quote->getPayment();

        if ($payment->getMethod() !== null) {
            return;
        }

        $preselectedMethod = $this->config->getPreselectedMethod($quote->getStoreId());

        if (!$preselectedMethod) {
            return;
        }

        $payment->setMethod($preselectedMethod);
    }
}
