<?php

require('Mage/Adminhtml/controllers/Sales/OrderController.php');

class FireGento_Pdf_Adminhtml_Sales_OrderController
    extends Mage_Adminhtml_Sales_OrderController
{
    public function pdfinvoicesAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids');
        $flag = false;
        if (!empty($orderIds)) {
            $invoice = null;
            foreach ($orderIds as $orderId) {
                $invoices = Mage::getResourceModel('sales/order_invoice_collection')
                    ->setOrderFilter($orderId)
                    ->load();
                if ($invoices->getSize() == 1) {
                    $invoice = $invoices->getFirstItem();
                }
                if ($invoices->getSize() > 0) {
                    $flag = true;
                    if (!isset($pdf)) {
                        $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
                    } else {
                        $pages = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
                        $pdf->pages = array_merge($pdf->pages, $pages->pages);
                        // there is more than one invoice
                        $invoice = null;
                    }
                }
            }
            if ($flag) {
                if ($invoice != null) {
                    return $this->_prepareDownloadResponse(
                        Mage::helper('firegento_pdf')
                            ->getExportFilename('invoice', $invoice),
                        $pdf->render(), 'application/pdf'
                    );
                }
                return $this->_prepareDownloadResponse(
                    Mage::helper('firegento_pdf')
                        ->getExportFilenameForMultipleDocuments('invoice'),
                    $pdf->render(), 'application/pdf'
                );
            } else {
                $this->_getSession()->addError(
                    $this->__('There are no printable documents related to selected orders.')
                );
                $this->_redirect('*/*/');
            }
        }
        $this->_redirect('*/*/');
    }

    public function pdfshipmentsAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids');
        $flag = false;
        if (!empty($orderIds)) {
            $shipment = null;
            foreach ($orderIds as $orderId) {
                $shipments = Mage::getResourceModel('sales/order_shipment_collection')
                    ->setOrderFilter($orderId)
                    ->load();
                if ($shipments->getSize() == 1) {
                    $shipment = $shipments->getFirstItem();
                }
                if ($shipments->getSize() > 0) {
                    $flag = true;
                    if (!isset($pdf)) {
                        $pdf = Mage::getModel('sales/order_pdf_shipment')->getPdf($shipments);
                    } else {
                        $pages = Mage::getModel('sales/order_pdf_shipment')->getPdf($shipments);
                        $pdf->pages = array_merge($pdf->pages, $pages->pages);
                        // there is more than one invoice
                        $shipment = null;
                    }
                }
            }
            if ($flag) {
                if ($shipment != null) {
                    return $this->_prepareDownloadResponse(
                        Mage::helper('firegento_pdf')
                            ->getExportFilename('shipment', $shipment),
                        $pdf->render(), 'application/pdf'
                    );
                }
                return $this->_prepareDownloadResponse(
                    Mage::helper('firegento_pdf')
                        ->getExportFilenameForMultipleDocuments('shipment'),
                    $pdf->render(), 'application/pdf'
                );
            } else {
                $this->_getSession()->addError(
                    $this->__('There are no printable documents related to selected orders.')
                );
                $this->_redirect('*/*/');
            }
        }
        $this->_redirect('*/*/');
    }

    public function pdfcreditmemosAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids');
        $flag = false;
        if (!empty($orderIds)) {
            $creditmemo = null;
            foreach ($orderIds as $orderId) {
                $creditmemos = Mage::getResourceModel('sales/order_creditmemo_collection')
                    ->setOrderFilter($orderId)
                    ->load();
                if ($creditmemos->getSize() == 1) {
                    $creditmemo = $creditmemos->getFirstItem();
                }
                if ($creditmemos->getSize() > 0) {
                    $flag = true;
                    if (!isset($pdf)) {
                        $pdf = Mage::getModel('sales/order_pdf_creditmemo')->getPdf($creditmemos);
                    } else {
                        $pages = Mage::getModel('sales/order_pdf_creditmemo')->getPdf($creditmemos);
                        $pdf->pages = array_merge($pdf->pages, $pages->pages);
                        // there is more than one invoice
                        $creditmemo = null;
                    }
                }
            }
            if ($flag) {
                if ($creditmemo != null) {
                    return $this->_prepareDownloadResponse(
                        Mage::helper('firegento_pdf')
                            ->getExportFilename('creditmemo', $creditmemo),
                        $pdf->render(), 'application/pdf'
                    );
                }
                return $this->_prepareDownloadResponse(
                    Mage::helper('firegento_pdf')
                        ->getExportFilenameForMultipleDocuments('creditmemo'),
                    $pdf->render(), 'application/pdf'
                );
            } else {
                $this->_getSession()->addError(
                    $this->__('There are no printable documents related to selected orders.')
                );
                $this->_redirect('*/*/');
            }
        }
        $this->_redirect('*/*/');
    }
}
