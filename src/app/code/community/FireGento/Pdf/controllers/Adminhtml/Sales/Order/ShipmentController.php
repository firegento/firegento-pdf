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

require('Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php');

/**
 * Class FireGento_Pdf_Adminhtml_Sales_Order_ShipmentController
 *
 * @category FireGento
 * @package  FireGento_Pdf
 * @author   FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Adminhtml_Sales_Order_ShipmentController
    extends Mage_Adminhtml_Sales_Order_ShipmentController
{

    /**
     * Create pdf for current shipment
     */
    public function printAction()
    {
        /** @see Mage_Adminhtml_Sales_Order_InvoiceController */
        if ($shipmentId = $this->getRequest()->getParam('invoice_id')
        ) { // invoice_id o_0
            if ($shipment = Mage::getModel('sales/order_shipment')
                ->load($shipmentId)
            ) {
                $pdf = Mage::getModel('sales/order_pdf_shipment')
                    ->getPdf(array($shipment));
                $this->_prepareDownloadResponse(
                    Mage::helper('firegento_pdf')
                        ->getExportFilename('shipment', $shipment),
                    $pdf->render(), 'application/pdf'
                );
            }
        } else {
            $this->_forward('noRoute');
        }
    }

}
