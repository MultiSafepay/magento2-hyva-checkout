<?php

declare(strict_types=1);

namespace MultiSafepay\HyvaCheckout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Quote\Model\Quote;
use MultiSafepay\ConnectCore\Config\Config;

class SalesQuoteCollectTotalsBeforeObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var PaymentInterfaceFactory
     */
    private PaymentInterfaceFactory $paymentFactory;

    /**
     * @param Config $config
     * @param PaymentInterfaceFactory $paymentFactory
     */
    public function __construct(
        Config $config,
        PaymentInterfaceFactory $paymentFactory
    ) {
        $this->config = $config;
        $this->paymentFactory = $paymentFactory;
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

        if ($quote->getPayment()->getMethod() !== null) {
            return;
        }

        $preselectedMethod = $this->config->getPreselectedMethod($quote->getStoreId());

        if (!$preselectedMethod) {
            return;
        }

        $payment = $this->paymentFactory->create();
        $payment->setMethod($preselectedMethod);

        $quote->setPayment($payment);
    }
}
