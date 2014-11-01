<?php

require('Mage/Adminhtml/controllers/Sales/Order/CreditmemoController.php');

class FireGento_Pdf_Adminhtml_Sales_Order_CreditmemoController extends Mage_Adminhtml_Sales_Order_CreditmemoController
{

    /**
     * Create pdf for current creditmemo
     */
    public function printAction()
    {
        $this->_initCreditmemo();
        /** @see Mage_Adminhtml_Sales_Order_InvoiceController */
        if ($creditmemoId = $this->getRequest()->getParam('creditmemo_id')) {
            if ($creditmemo = Mage::getModel('sales/order_creditmemo')->load($creditmemoId)) {
                $pdf = Mage::getModel('sales/order_pdf_creditmemo')->getPdf(array($creditmemo));
                $this->_prepareDownloadResponse(
                    Mage::helper('firegento_pdf')->getExportFilename('creditmemo', $creditmemo),
                    $pdf->render(), 'application/pdf'
                );
            }
        }
        else {
            $this->_forward('noRoute');
        }
    }

}