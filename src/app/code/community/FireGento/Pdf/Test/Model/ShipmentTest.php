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
 * Test class for shipments.
 *
 * @category FireGento
 * @package  FireGento_Pdf
 * @author   FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Test_Model_ShipmentTest extends FireGento_Pdf_Test_Model_SalesObjectTestAbstract
{
    protected $_class = 'FireGento_Pdf_Model_Shipment';

    public function getEngineXmlConfigPath()
    {
        return 'sales_pdf/shipment/engine';
    }

    public function getExpectedDefaultEngineClass()
    {
        return 'Mage_Sales_Model_Order_Pdf_Shipment';
    }
    
    public function getOrderObjectClassName()
    {
        return 'Mage_Sales_Model_Order_Shipment';
    }
}
