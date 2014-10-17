<?php
/**
 * Created by PhpStorm.
 * User: akn
 * Date: 7/31/14
 * Time: 10:39 AM
 */

class FireGento_Pdf_Model_System_Config_Source_Imprint {

    public function toOptionArray() {
        $imprint = Mage::getStoreConfig('general/imprint');
        $result = array();
        foreach ($imprint as $imprintKey => $imprintValue) {
            $result[] = array ('value' => $imprintKey, 'label' => $imprintKey);
        }
        return $result;
    }
}