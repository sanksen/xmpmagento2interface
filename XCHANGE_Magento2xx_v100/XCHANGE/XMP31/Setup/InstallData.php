<?php

namespace XCHANGE\XMP31\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;


class InstallData implements InstallDataInterface
{


    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }



    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        // Remove old dev
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY,'XCHANGE_Internal_Sku');
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY,'XMP_id');
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY,'XMP_Internal_Sku');

        // Add attrib.
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY,'XCHANGE_Internal_Sku');
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'XCHANGE_Internal_Sku', // Custom Attribute Code...
            [
                'group' => 'XCHANGE MARKET',
                'type' => 'varchar',
                'backend' => '',
                'frontend' => '',
                'label' => 'XCHANGE_Internal_Sku',
                'input' => 'input',
                'class' => '',
                'source' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => true
            ]
        );


   }

}
