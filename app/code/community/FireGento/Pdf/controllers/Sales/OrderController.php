<?php
/**
 * This file is part of the FIREGENTO project.
 *
 * FireGento_GermanSetup is free software; you can redistribute it and/or
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
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
/**
 * Sales orders controller
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */

require_once  'Mage/Sales/controllers/OrderController.php';

class FireGento_Pdf_Sales_OrderController extends Mage_Sales_OrderController
{
    /**
     * Print PDF Invoice Action
     *
     * @return void
     */
    public function printInvoiceAction()
    {
        $invoiceId = (int)$this->getRequest()->getParam('invoice_id');
        if ($invoiceId) {
            $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
            $order = $invoice->getOrder();
        } else {
            $orderId = (int)$this->getRequest()->getParam('order_id');
            $order = Mage::getModel('sales/order')->load($orderId);
        }

        if ($this->_canViewOrder($order)) {
            if (isset($orderId)) {
                // Create a pdf file from all invoices of requested order.
                $invoices = Mage::getResourceModel('sales/order_invoice_collection')
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('order_id', $orderId)
                    ->load();
            } else {
                // Create a single invoice pdf.
                $invoices = array($invoice);
            }

            // Store current area and set to adminhtml for invoice generation.
            $currentArea = Mage::getDesign()->getArea();
            Mage::getDesign()->setArea('adminhtml');

            $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
            $this->_prepareDownloadResponse('invoice' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') .
                '.pdf', $pdf->render(), 'application/pdf');

            // Restore area.
            Mage::getDesign()->setArea($currentArea);

        } else {
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $this->_redirect('*/*/history');
            } else {
                $this->_redirect('sales/guest/form');
            }
        }
    }
}