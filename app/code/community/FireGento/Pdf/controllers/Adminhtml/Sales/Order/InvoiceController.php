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
 * @copyright 2012 FireGento Team (http://www.firegento.de). All rights served.
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
 * @copyright 2012 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */

include_once("Mage/Adminhtml/controllers/Sales/Order/InvoiceController.php");

class FireGento_Pdf_Adminhtml_Sales_Order_InvoiceController extends Mage_Adminhtml_Sales_Order_InvoiceController
{
    public function  printAction()
    {
        if ($invoiceId = $this->getRequest()->getParam('invoice_id')) {
            if ($invoice = Mage::getModel('sales/order_invoice')->load($invoiceId)) {
                $engine = Mage::getStoreConfig('order/pdf/engine');
                if ($engine) {
                    $pdf = Mage::getModel($engine);
                    if ($pdf && $pdf->test()) {
                        $pdf = $pdf->getPdf($invoice);
                        $this->_prepareDownloadResponse('invoice' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') .
                            '.pdf', $pdf->render(), 'application/pdf');
                    }
                } else {
                    $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($invoice));
                    $this->_prepareDownloadResponse('invoice' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') .
                        '.pdf', $pdf->render(), 'application/pdf');
                }
            } else {
                $this->_forward('noRoute');
            }
        }
    }

    public function pdfinvoicesAction()
    {
        //TODO Engine einbauen
        $invoicesIds = $this->getRequest()->getPost('invoice_ids');
        if (!empty($invoicesIds)) {
            $engine = Mage::getStoreConfig('order/pdf/engine');
            $invoices = Mage::getResourceModel('sales/order_invoice_collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in' => $invoicesIds))
                ->load();
            if (!isset($pdf)) {
                if ($engine) {
                    $pdf = Mage::getModel($engine);
                    if ($pdf && $pdf->test()) {
                        $pdf = $pdf->getPdf($invoices);
                    }
                } else {
                    $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
                }
            } else {
                if ($engine) {
                    $pdf = Mage::getModel($engine);
                    if ($pdf && $pdf->test()) {
                        $pdf = $pdf->getPdf($invoices);
                    }
                } else {
                    $pages = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
                }
                $pdf->pages = array_merge($pdf->pages, $pages->pages);
            }

            return $this->_prepareDownloadResponse('invoice' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') .
                '.pdf', $pdf->render(), 'application/pdf');
        }
        $this->_redirect('*/*/');
    }
}
