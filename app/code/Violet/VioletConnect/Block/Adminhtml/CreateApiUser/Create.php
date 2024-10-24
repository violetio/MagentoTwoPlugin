<?php

namespace Violet\VioletConnect\Block\Adminhtml\InitProductSync;

use Magento\Framework\App\Bootstrap;

class CreateApiUser extends \Magento\Backend\Block\Widget\Container
{
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {

        parent::__construct($context, $data);
    }
}
