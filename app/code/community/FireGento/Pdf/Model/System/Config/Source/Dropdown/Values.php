<?php

class FireGento_Pdf_Model_System_Config_Source_Dropdown_Values
{
    public function toOptionArray()
    {
		$helper = Mage::helper('firegento_pdf');
		
        return array(
            array(
                'value' => 'left',
                'label' => $helper->__('Left'),
            ),
            array(
                'value' => 'center',
                'label' => $helper->__('Center'),
            ),
			array(
                'value' => 'right',
                'label' => $helper->__('Right'),
            ),
        );
    }
}