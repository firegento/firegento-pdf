<?php

class FireGento_Pdf_Model_System_Config_Backend_Font
    extends Mage_Adminhtml_Model_System_Config_Backend_File
{
    private $_allowedExtensions
        = array(
            'otf',
            'ttf',
        );

    /**
     * Returns the allowed font extensions.
     *
     * @return array containing the allowed font extensions
     */
    protected function _getAllowedExtensions()
    {
        return $this->_allowedExtensions;
    }
}
