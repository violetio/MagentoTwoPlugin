<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Violet\VioletConnect\Model;

/**
 * Violet Payment Model
 */
class Violet extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'violet';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;
}
