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

namespace MultiSafepay\HyvaCheckout\Controller\HyvaCheckout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use MultiSafepay\ConnectCore\Util\ApiTokenUtil;

class ApiToken extends Action
{
    private JsonFactory $resultJsonFactory;
    private CheckoutSession $checkoutSession;
    private ApiTokenUtil $apiTokenUtil;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        ApiTokenUtil $apiTokenUtil
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->apiTokenUtil = $apiTokenUtil;
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $result->setHeader('Pragma', 'no-cache', true);

        try {
            $quote = $this->checkoutSession->getQuote();
            $tokenData = $this->apiTokenUtil->getApiTokenFromCache($quote);
            $apiToken = $tokenData['apiToken'] ?? '';

            if (!$apiToken) {
                return $result->setHttpResponseCode(500)->setData([
                    'success' => false,
                    'message' => 'Could not retrieve API token'
                ]);
            }

            return $result->setData([
                'success' => true,
                'apiToken' => $apiToken
            ]);
        } catch (\Throwable $e) {
            return $result->setHttpResponseCode(500)->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
