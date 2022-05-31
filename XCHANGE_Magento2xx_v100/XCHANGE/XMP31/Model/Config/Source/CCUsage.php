<?php

/*** Location: magento2_root/app/code/Vendorname/Extensionname/Model/Config/Source/Custom.php ***********/
namespace XCHANGE\XMP31\Model\Config\Source;

class CCUsage implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {

        return [
            ['value' => 0, 'label' => __('Yes')],
            ['value' => 1, 'label' => __('No')],
        ];
    }
}
