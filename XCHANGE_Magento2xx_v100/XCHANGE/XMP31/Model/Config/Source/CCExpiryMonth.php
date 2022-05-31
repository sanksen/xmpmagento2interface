<?php

/*** Location: magento2_root/app/code/Vendorname/Extensionname/Model/Config/Source/Custom.php ***********/
namespace XCHANGE\XMP31\Model\Config\Source;

class CCExpiryMonth implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '01', 'label' => __('JAN')],
            ['value' => '02', 'label' => __('FEB')],
            ['value' => '03', 'label' => __('MAR')],
            ['value' => '04', 'label' => __('APR')],
            ['value' => '05', 'label' => __('MAY')],
            ['value' => '06', 'label' => __('JUN')],
            ['value' => '07', 'label' => __('JUL')],
            ['value' => '08', 'label' => __('AUG')],
            ['value' => '09', 'label' => __('SEP')],
            ['value' => '10', 'label' => __('OCT')],
            ['value' => '11', 'label' => __('NOV')],
            ['value' => '12', 'label' => __('DEC')],
        ];
    }
}
