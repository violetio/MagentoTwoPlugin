<?php

namespace Violet\VioletConnect\Model\ResourceModel\VioletEntity;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    public function _construct()
    {
        $this->_init(
            'Violet\VioletConnect\Model\VioletEntity',
            'Violet\VioletConnect\Model\ResourceModel\VioletEntity'
        );
    }
}
