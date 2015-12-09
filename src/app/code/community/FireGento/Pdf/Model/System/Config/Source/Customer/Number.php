<?php
/**
 * This file is part of the FIREGENTO project.
 *
 * FireGento_Pdf is free software; you can redistribute it and/or
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
 * @copyright 2015 FireGento Team (http://www.firegento.com)
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     x.x.x
 */
/**
 * Customer number source model.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2015 FireGento Team (http://www.firegento.com)
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     x.x.x
 */
class FireGento_Pdf_Model_System_Config_Source_Customer_Number
{
    /**
     * Databasefield name for customers increment_id
     */
    const CUSTOMER_NUMBER_FIELD_INCREMENT_ID = 'increment_id';
    /**
     * Return array of possible positions.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $selectOptions = array(
            '' => Mage::helper('firegento_pdf')->__('Standard (entity_id)'),
            self::CUSTOMER_NUMBER_FIELD_INCREMENT_ID => Mage::helper('firegento_pdf')->__('Customer Increment ID (increment_id)')
        );
        $options = array();
        foreach ($selectOptions as $k => $v) {
            $options[] = array(
                'value' => $k,
                'label' => $v
            );
        }
        return $options;
    }
}
