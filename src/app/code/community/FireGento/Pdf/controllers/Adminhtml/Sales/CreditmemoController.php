<?php

require('Mage/Adminhtml/controllers/Sales/CreditmemoController.php');

class FireGento_Pdf_Adminhtml_Sales_CreditmemoController
    extends Mage_Adminhtml_Sales_CreditmemoController
{
    public function pdfcreditmemosAction()
    {
        $creditmemosIds = $this->getRequest()->getPost('creditmemo_ids');
        if (sizeof($creditmemosIds) > 1) {
            $invoices = Mage::getResourceModel('sales/order_creditmemo_collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in' => $creditmemosIds))
                ->load();
            if (!isset($pdf)) {
                $pdf = Mage::getModel('sales/order_pdf_creditmemo')->getPdf($invoices);
            } else {
                $pages = Mage::getModel('sales/order_pdf_creditmemo')->getPdf($invoices);
                $pdf->pages = array_merge($pdf->pages, $pages->pages);
            }
            return $this->_prepareDownloadResponse(
                Mage::helper('firegento_pdf')
                    ->getExportFilenameForMultipleDocuments('creditmemo'),
                $pdf->render(), 'application/pdf'
            );
        } else if (sizeof($creditmemosIds) == 1) {
            $creditmemoId = $creditmemosIds[0];
            if ($invoice = Mage::getModel('sales/order_creditmemo')
                ->load($creditmemoId)
            ) {
                $pdf = Mage::getModel('sales/order_pdf_creditmemo')
                    ->getPdf(array($invoice));
                return $this->_prepareDownloadResponse(
                    Mage::helper('firegento_pdf')
                        ->getExportFilename('creditmemo', $invoice),
                    $pdf->render(), 'application/pdf'
                );
            }
        }

        $this->_redirect('*/*/');
    }
}
