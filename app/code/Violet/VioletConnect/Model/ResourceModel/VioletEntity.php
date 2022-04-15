<?php

namespace Violet\VioletConnect\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class VioletEntity extends AbstractDb
{
    public function _construct()
    {
        $this->_init('violetconnect', 'id');
    }
}
