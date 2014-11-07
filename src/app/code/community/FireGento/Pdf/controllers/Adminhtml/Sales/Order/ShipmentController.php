<?php

require('Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php');

class FireGento_Pdf_Adminhtml_Sales_Order_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController
{

    /**
     * Create pdf for current shipment
     */
    public function printAction()
    {
        /** @see Mage_Adminhtml_Sales_Order_InvoiceController */
        if ($shipmentId = $this->getRequest()->getParam('invoice_id')) { // invoice_id o_0
            if ($shipment = Mage::getModel('sales/order_shipment')->load($shipmentId)) {
                $pdf = Mage::getModel('sales/order_pdf_shipment')->getPdf(array($shipment));
                $this->_prepareDownloadResponse(
                    Mage::helper('firegento_pdf')->getExportFilename('shipment', $shipment),
                    $pdf->render(), 'application/pdf'
                );
            }
        } else {
            $this->_forward('noRoute');
        }
    }

}
