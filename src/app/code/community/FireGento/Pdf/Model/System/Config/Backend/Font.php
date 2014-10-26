<?php

class FireGento_Pdf_Model_System_Config_Backend_Font
    extends Mage_Adminhtml_Model_System_Config_Backend_File
{
    private $allowedExtensions
        = array(
            'otf',
            'ttf',
        );

    protected function _getAllowedExtensions()
    {
        return $this->allowedExtensions;
    }
}
