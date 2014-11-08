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
 * @copyright 2013 FireGento Team (http://www.firegento.com)
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */

require('Mage/Adminhtml/controllers/Sales/Order/InvoiceController.php');

/**
 * Class FireGento_Pdf_Adminhtml_Sales_Order_InvoiceController
 *
 * @category FireGento
 * @package  FireGento_Pdf
 * @author   FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Adminhtml_Sales_Order_InvoiceController
    extends Mage_Adminhtml_Sales_Order_InvoiceController
{

    /**
     * Create pdf for current invoice
     */
    public function printAction()
    {
        $this->_initInvoice();
        if ($invoiceId = $this->getRequest()->getParam('invoice_id')) {
            if ($invoice = Mage::getModel('sales/order_invoice')
                ->load($invoiceId)
            ) {
                $pdf = Mage::getModel('sales/order_pdf_invoice')
                    ->getPdf(array($invoice));
                $this->_prepareDownloadResponse(
                    Mage::helper('firegento_pdf')
                        ->getExportFilename('invoice', $invoice),
                    $pdf->render(), 'application/pdf'
                );
            }
        } else {
            $this->_forward('noRoute');
        }
    }

}
