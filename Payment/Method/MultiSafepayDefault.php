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

declare(strict_types=1);

namespace MultiSafepay\MagewireCheckout\Payment\Method;

use Exception;
use Magento\Checkout\Model\Session as checkoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\QuoteManagement;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\AcceptableException;
use Rakit\Validation\Validator;

class MultiSafepayDefault extends Component\Form
{
    protected $loader = ['placeOrder'];

    protected checkoutSession $checkoutSession;
    protected CartManagementInterface $quoteManagement;
    protected CartRepositoryInterface $quoteRepository;

    /**
     * @param Validator $validator
     * @param checkoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteManagement $quoteManagement
     */
    public function __construct(
        Validator $validator,
        checkoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        QuoteManagement $quoteManagement
    ) {
        parent::__construct($validator);

        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
    }

    /**
     * Place the order and redirect
     *
     * @throws AcceptableException
     * @throws LocalizedException
     */
    public function placeOrder(): void
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (LocalizedException $localizedException) {
            $this->error('', $localizedException->getMessage());
        }

        try {
            $shippingAddress = $quote->getShippingAddress();

            if ($shippingAddress->getSameAsBilling()) {
                $billingAddress = clone $shippingAddress;

                $billingAddress->setSameAsBilling('0');
                $billingAddress->unsAddressId();
                $billingAddress->setAddressType(QuoteAddress::ADDRESS_TYPE_BILLING);

                $quote->setBillingAddress($billingAddress);
                $this->quoteRepository->save($quote);
            }

            $order = $this->quoteManagement->submit($quote);
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        } catch (Exception $exception) {
            $this->error($quote->getPayment()->getMethod() ?? '', $exception->getMessage());
            throw new AcceptableException(__('Something went wrong'));
        }

        $this->redirect('/multisafepay/connect/redirect');
    }

    /**
     * Retrieve the payment method code from the quote
     *
     * @return string
     */
    public function getPaymentMethodCode(): string
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            return $quote->getPayment()->getMethod();
        } catch (LocalizedException $localizedException) {
            $this->error('', $localizedException->getMessage());
        }
        
        return '';
    }
}
