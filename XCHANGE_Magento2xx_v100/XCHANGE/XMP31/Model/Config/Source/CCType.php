<?php

/*** Location: magento2_root/app/code/Vendorname/Extensionname/Model/Config/Source/Custom.php ***********/
namespace XCHANGE\XMP31\Model\Config\Source;

class CCType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'VISA', 'label' => __('VISA')],
            ['value' => 'MASTERCARD', 'label' => __('MASTERCARD')],
        ];
    }
}
