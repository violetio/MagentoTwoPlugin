<?php
namespace Violet\VioletConnect\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Violet Install Data
 *
 * @copyright  2022 Violet.io, Inc.
 * @since      1.0.7
 *
 * @codeCoverageIgnore
 */
class InstallSetup implements SchemaPatchInterface, DataPatchInterface
{
    private $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $cols = [
            [
                'api_user_created' => false,
                'configuration_state' => 1
            ]
        ];

        foreach ($cols as $col) {
            $this->moduleDataSetup->getConnection()
            ->insertForce($this->moduleDataSetup->getTable('violetconnect'), $col);
        }

        $this->moduleDataSetup->endSetup();
    }
}
