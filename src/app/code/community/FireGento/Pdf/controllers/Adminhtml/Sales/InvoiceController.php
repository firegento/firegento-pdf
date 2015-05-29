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
require('Mage/Adminhtml/controllers/Sales/InvoiceController.php');

/**
 * Adminhtml Order controller to serve invoices
 *
 * @category FireGento
 * @package  FireGento_Pdf
 * @author   FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Adminhtml_Sales_InvoiceController
    extends Mage_Adminhtml_Sales_InvoiceController
{
    /**
     * controller action to serve one or multiple invoices
     */
    public function pdfinvoicesAction()
    {
        $invoicesIds = $this->getRequest()->getPost('invoice_ids');
        if (count($invoicesIds) == 0) {
            $this->_redirect('*/*/');
        }

        if (Mage::helper('firegento_pdf')->isServeInvoiceAsZip()) {
            $this->serveInvoicesAsZip();

            return;
        }

        $this->serveInvoicesAsPdf();

        return;
    }

    /**
     * Returns all invoices packed into a zip container
     */
    private function serveInvoicesAsZip()
    {
        $invoiceIds = $this->getRequest()->getParam('invoice_ids');
        $invoices = Mage::getResourceModel('sales/order_invoice_collection');
        $invoices->addFieldToFilter('entity_id', array('in', $invoiceIds));

        $file = tempnam("tmp", "zip");
        Mage::getModel('firegento_pdf/zippedInvoice')->create($file, $invoices);

        $name = 'invoice' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.zip';

        $this->getResponse()->setHeader('Content-Type', 'application/zip', true);
        $this->getResponse()->setHeader('Content-Length', filesize($file), true);
        $this->getResponse()->setHeader('Content-Disposition', 'attachment; filename=' . $name, true);

        $this->getResponse()->setBody(file_get_contents($file));

        unlink($file);
    }

    /**
     * serve invoices as pdf
     *
     * @return Mage_Core_Controller_Varien_Action
     * @throws Zend_Pdf_Exception
     */
    private function serveInvoicesAsPdf()
    {
        $invoicesIds = $this->getRequest()->getPost('invoice_ids');
        if (sizeof($invoicesIds) > 1) {
            return $this->serveMultipleInvoicesAsPdf($invoicesIds);
        }

        return $this->serveOneInvoiceAsPdf($invoicesIds);
    }

    /**
     * serve multiple invoices as pdf
     *
     * @param  int[] $invoicesIds invoices to add to the pdf
     *
     * @return Mage_Core_Controller_Varien_Action
     * @throws Zend_Pdf_Exception
     */
    private function serveMultipleInvoicesAsPdf($invoicesIds)
    {
        $invoices = Mage::getResourceModel('sales/order_invoice_collection')
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', array('in' => $invoicesIds))
            ->load();

        $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);

        return $this->_prepareDownloadResponse(
            Mage::helper('firegento_pdf')
                ->getExportFilenameForMultipleDocuments('invoice'),
            $pdf->render(), 'application/pdf'
        );
    }

    /**
     * serve a single invoice as pdf
     *
     * @param  int[] $invoicesIds invoice to add to the pdf
     *
     * @return Mage_Core_Controller_Varien_Action
     * @throws Zend_Pdf_Exception
     */
    private function serveOneInvoiceAsPdf($invoicesIds)
    {
        $invoiceId = $invoicesIds[0];
        if ($invoice = Mage::getModel('sales/order_invoice')
            ->load($invoiceId)
        ) {
            $pdf = Mage::getModel('sales/order_pdf_invoice')
                ->getPdf(array($invoice));

            return $this->_prepareDownloadResponse(
                Mage::helper('firegento_pdf')
                    ->getExportFilename('invoice', $invoice),
                $pdf->render(), 'application/pdf'
            );
        }
    }
}
