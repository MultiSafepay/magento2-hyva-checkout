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

declare(strict_types=1);

namespace MultiSafepay\MagewireCheckout\Payment;

use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Magewire\Checkout\Payment\MethodList;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;
use MultiSafepay\ConnectCore\Config\Config;

class MethodListExtended extends MethodList
{
    /**
     * @var string|null
     */
    public ?string $method = null;

    /**
     * @var QuoteRepository
     */
    protected QuoteRepository $quoteRepository;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param SessionCheckout $sessionCheckout
     * @param CartRepositoryInterface $cartRepository
     * @param EvaluationResultFactory $evaluationResultFactory
     * @param Config $config
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        SessionCheckout $sessionCheckout,
        CartRepositoryInterface $cartRepository,
        EvaluationResultFactory $evaluationResultFactory,
        Config $config,
        QuoteRepository $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->config = $config;
        parent::__construct(
            $sessionCheckout,
            $cartRepository,
            $evaluationResultFactory
        );
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        try {
            $method = $this->sessionCheckout->getQuote()->getPayment()->getMethod();

            if ($this->config->getPreselectedMethod() && !$method) {
                $this->updatedMethod($this->config->getPreselectedMethod());
                $method = $this->config->getPreselectedMethod();
            }
        } catch (LocalizedException $exception) {
            $method = null;
        }

        $this->method = $method;
    }
}
