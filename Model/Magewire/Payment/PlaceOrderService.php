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

namespace MultiSafepay\HyvaCheckout\Model\Magewire\Payment;

use Hyva\Checkout\Model\Magewire\Payment\AbstractOrderData;
use Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use MultiSafepay\ConnectCore\Api\RedirectTokenRepositoryInterface;
use MultiSafepay\ConnectCore\Model\RedirectTokenRepository;
use MultiSafepay\ConnectCore\Observer\Gateway\RedirectTokenDataAssignObserver;

class PlaceOrderService extends AbstractPlaceOrderService
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Random
     */
    private $random;

    /**
     * @var RedirectTokenRepositoryInterface
     */
    private $redirectTokenRepository;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * PlaceOrderService constructor.
     *
     * @param CartManagementInterface $cartManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param Random $random
     * @param RedirectTokenRepository $redirectTokenRepository
     * @param UrlInterface $urlBuilder
     * @param AbstractOrderData|null $orderData
     */
    public function __construct(
        CartManagementInterface $cartManagement,
        OrderRepositoryInterface $orderRepository,
        Random $random,
        RedirectTokenRepositoryInterface $redirectTokenRepository,
        UrlInterface $urlBuilder,
        ?AbstractOrderData $orderData = null
    ) {
        parent::__construct($cartManagement, $orderData);
        $this->orderRepository = $orderRepository;
        $this->random = $random;
        $this->urlBuilder = $urlBuilder;
        $this->redirectTokenRepository = $redirectTokenRepository;
    }

    public function canPlaceOrder(): bool
    {
        return true;
    }

    /**
     * Redirect to the MultiSafepay controller
     *
     * @see https://docs.hyva.io/checkout/hyva-checkout/devdocs/payment-integration-api.html
     *
     * @param Quote $quote
     * @param int|null $orderId
     * @return string
     * @SuppressWarnings (PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function getRedirectUrl(Quote $quote, ?int $orderId = null): string
    {
        if (!$orderId) {
            return $this->urlBuilder->getUrl('multisafepay/connect/redirect');
        }

        $token = $this->random->getRandomString(32);

        if ($quote->getPayment()) {
            $quote->getPayment()->setAdditionalInformation(
                RedirectTokenDataAssignObserver::REDIRECT_TOKEN_INFO_KEY,
                $token
            );
        }

        $order = $this->orderRepository->get($orderId);
        $orderIncrementId = $order->getIncrementId();

        $this->redirectTokenRepository->create($orderIncrementId, $token);

        return $this->urlBuilder->getUrl('multisafepay/connect/redirect', ['token' => $token]);
    }
}
