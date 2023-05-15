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

namespace MultiSafepay\MagewireCheckout\Payment\Method;

use Exception;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\QuoteManagement;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\AcceptableException;
use Magewirephp\Magewire\Exception\ValidationException;
use MultiSafepay\ConnectFrontend\Controller\Connect\Redirect;
use Psr\Http\Client\ClientExceptionInterface;
use Rakit\Validation\Validator;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;

class MultiSafepayIdeal extends Component\Form
{
    public string $issuer = '';

    protected SessionCheckout $sessionCheckout;
    protected CartManagementInterface $quoteManagement;
    protected CartRepositoryInterface $quoteRepository;
    protected IdealConfigProvider $idealConfigProvider;

    /**
     * @param Validator $validator
     * @param SessionCheckout $sessionCheckout
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteManagement $quoteManagement
     * @param IdealConfigProvider $idealConfigProvider
     */
    public function __construct(
        Validator $validator,
        SessionCheckout $sessionCheckout,
        CartRepositoryInterface $quoteRepository,
        QuoteManagement $quoteManagement,
        IdealConfigProvider $idealConfigProvider
    ) {
        parent::__construct($validator);

        $this->sessionCheckout = $sessionCheckout;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->idealConfigProvider = $idealConfigProvider;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function mount(): void
    {
         if ($issuer = $this->sessionCheckout->getQuote()->getPayment()->getAdditionalInformation('issuer_id')) {
            $this->issuer = (string)$issuer;
        }
    }

    /**
     * @return array
     */
    public function getIssuers(): array
    {
        try {
            return $this->idealConfigProvider->getIssuers();
        } catch (ClientExceptionInterface $e) {
            return [];
        }
    }

    /**
     * @param $value
     * @return string
     */
    public function updatedIssuer($value): string
    {
        try {
            
            $quote = $this->sessionCheckout->getQuote();
            $quote->getPayment()->setAdditionalInformation(
                ['issuer_id' => $this->issuer, 'transaction_type' => 'direct']
            );
            
            $this->quoteRepository->save($quote);
     
        } catch (LocalizedException $exception) {
            $this->dispatchErrorMessage($exception->getMessage());
        }

        return $value;
    }
}
