<?php

require('Mage/Adminhtml/controllers/Sales/InvoiceController.php');

class FireGento_Pdf_Adminhtml_Sales_InvoiceController
    extends Mage_Adminhtml_Sales_InvoiceController
{
    public function pdfinvoicesAction()
    {
        $invoicesIds = $this->getRequest()->getPost('invoice_ids');
        if (sizeof($invoicesIds) > 1) {
            $invoices = Mage::getResourceModel('sales/order_invoice_collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in' => $invoicesIds))
                ->load();
            if (!isset($pdf)) {
                $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
            } else {
                $pages = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
                $pdf->pages = array_merge($pdf->pages, $pages->pages);
            }
            return $this->_prepareDownloadResponse(
                Mage::helper('firegento_pdf')
                    ->getExportFilenameForMultipleDocuments('invoice'),
                $pdf->render(), 'application/pdf'
            );
        } else if (sizeof($invoicesIds) == 1) {
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

        $this->_redirect('*/*/');
    }
}
