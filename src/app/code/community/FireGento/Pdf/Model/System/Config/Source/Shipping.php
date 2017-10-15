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
 * Shipping method position source model.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Model_System_Config_Source_Shipping
{
    const POSITION_HEADER = 'header';
    const POSITION_NOTE = 'note';

    /**
     * Return array of possible positions.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $positions = array(
            '' => Mage::helper('firegento_pdf')->__('Hide shipping method'),
            self::POSITION_HEADER => Mage::helper('firegento_pdf')->__('Header'),
            self::POSITION_NOTE => Mage::helper('firegento_pdf')->__('Notes area')
        );
        $options = array();
        foreach ($positions as $k => $v) {
            $options[] = array(
                'value' => $k,
                'label' => $v
            );
        }

        return $options;
    }
}
