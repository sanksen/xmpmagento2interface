<?php

/*** Location: magento2_root/app/code/Vendorname/Extensionname/Model/Config/Source/Custom.php ***********/
namespace XCHANGE\XMP31\Model\Config\Source;

class CCExpiryYear implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 17, 'label' => __('2017')],
            ['value' => 18, 'label' => __('2018')],
            ['value' => 19, 'label' => __('2019')],
            ['value' => 20, 'label' => __('2020')],
            ['value' => 21, 'label' => __('2021')],
            ['value' => 22, 'label' => __('2022')],
            ['value' => 23, 'label' => __('2023')],
            ['value' => 24, 'label' => __('2024')],
            ['value' => 25, 'label' => __('2025')],
            ['value' => 26, 'label' => __('2026')],
            ['value' => 27, 'label' => __('2027')],
            ['value' => 28, 'label' => __('2028')],
            ['value' => 29, 'label' => __('2029')],
            ['value' => 30, 'label' => __('2030')],
        ];
    }
}
