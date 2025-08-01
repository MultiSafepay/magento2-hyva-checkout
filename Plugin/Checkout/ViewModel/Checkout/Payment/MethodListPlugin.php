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

namespace MultiSafepay\HyvaCheckout\Plugin\Checkout\ViewModel\Checkout\Payment;

use Hyva\Checkout\ViewModel\Checkout\Payment\MethodList;

class MethodListPlugin
{
    /**
     * After plugin for the getList method to filter out recurring payment methods.
     *
     * @param MethodList $subject
     * @param ?array $result
     * @return ?array
     */
    public function afterGetList(MethodList $subject, ?array $result): ?array
    {
        return array_filter($result, function ($method) {
            $code = $method->getCode();
            return !str_contains($code, 'multisafepay') || !str_contains($code, 'recurring');
        });
    }
}
