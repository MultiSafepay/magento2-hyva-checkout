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

namespace MultiSafepay\HyvaCheckout\Payment\Method;

use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;

class BnplinstmComponent extends MultiSafepayPaymentComponent
{
    /**
     * Get the method code
     *
     * @return string
     */
    public function getMethodCode(): string
    {
        return VisaConfigProvider::CODE;
    }
}
