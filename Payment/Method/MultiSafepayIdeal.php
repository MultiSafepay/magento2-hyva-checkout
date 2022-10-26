<?php
/**
 * @author Hyvä Themes <info@hyva.io>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
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

    protected $loader = ['placeOrder'];

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
        $this->issuer = $this->sessionCheckout->getQuote()->getPayment()->getAdditionalInformation()['issuer_id'];
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
     * @throws AcceptableException
     * @throws ValidationException
     */
    public function placeOrder(): void
    {
        try {
            $quote = $this->sessionCheckout->getQuote();
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
            $this->sessionCheckout->setLastRealOrderId($order->getIncrementId());
        } catch (Exception $exception) {
            $this->error('multisafepay_ideal', $exception->getMessage());
            throw new AcceptableException(__('Something went wrong'));
        }

        $this->redirect('/multisafepay/connect/redirect');
    }

    /**
     * @param $value
     * @return string
     */
    public function updatedIssuer($value): string
    {
        try {
            if ($this->issuer) {
                $quote = $this->sessionCheckout->getQuote();
                $quote->getPayment()->setAdditionalInformation(
                    ['issuer_id' => $this->issuer, 'transaction_type' => 'direct']
                );
                $this->quoteRepository->save($quote);
            }
        } catch (LocalizedException $exception) {
            $this->dispatchErrorMessage($exception->getMessage());
        }

        return $value;
    }
}
