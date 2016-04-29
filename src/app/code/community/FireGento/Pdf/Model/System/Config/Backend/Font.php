<?php
/**
 * This file is part of a FireGento e.V. module.
 *
 * This FireGento e.V. module is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * PHP version 5
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2014 FireGento Team (http://www.firegento.com)
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */
/**
 * Class FireGento_Pdf_Model_System_Config_Backend_Font
 *
 * @category FireGento
 * @package  FireGento_Pdf
 * @author   FireGento Team <team@firegento.com>
 */
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
