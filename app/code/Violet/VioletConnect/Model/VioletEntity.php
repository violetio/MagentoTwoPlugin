<?php

namespace Violet\VioletConnect\Model;

use Magento\Framework\Model\AbstractModel;

class VioletEntity extends AbstractModel
{
    public function _construct()
    {
        $this->_init('Violet\VioletConnect\Model\ResourceModel\VioletEntity');
    }
}
