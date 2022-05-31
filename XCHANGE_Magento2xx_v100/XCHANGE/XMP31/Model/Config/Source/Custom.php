<?php

/*** Location: magento2_root/app/code/Vendorname/Extensionname/Model/Config/Source/Custom.php ***********/
namespace XCHANGE\XMP31\Model\Config\Source;

class Custom implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {

        return [
            ['value' => 0, 'label' => __('SANDBOX')],
            ['value' => 1, 'label' => __('PRODUCTION')],
        ];
    }
}
