<?php

namespace XCHANGE\XMP31\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;



class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup,
                            ModuleContextInterface $context){


        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0)
        {
             // gca do something!
        }

        #! Work on T_Reserve/tx# first. Come back for T_Finalize + license
        //--------------------------------------
        $installer->getConnection()->addColumn($installer->getTable("sales_order_grid"), "XCHANGE_response", [
           'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
           'comment' => 'XCHANGE_response from server.'
         ]);

        $installer->getConnection()->addColumn($installer->getTable("sales_order"), "XCHANGE_response", [
           'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
           'comment' => 'XCHANGE_response from server.'
         ]);

        #! Derived from UpgradeSchema - might need more testing/work.
        //--------------------------------------
        $installer->getConnection()->addColumn($installer->getTable("sales_order"), "XCHANGE_license_info", [
           'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
           'comment' => 'XCHANGE_license from server.'
         ]);
        /*** ***********/
        $installer->getConnection()->addColumn($installer->getTable("sales_order_grid"), "XCHANGE_license_info", [
           'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
           'comment' => 'XCHANGE_license from server.'
         ]);

        $tableName = $installer->getTable('aaa_XCHANGE_order');
        if ($installer->getConnection()->isTableExists($tableName) != true)
        {
            // Create table
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'ID'
                )
                ->addColumn(
                    'title',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'Title'
                )
                ->addColumn(
                    'summary',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'Summary'
                )
                ->addColumn(
                    'status',
                    Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => '0'],
                    'Status'
                )
                ->setComment('XMP Table!')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }

}
