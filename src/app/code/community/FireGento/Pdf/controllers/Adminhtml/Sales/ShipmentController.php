<?php

require('Mage/Adminhtml/controllers/Sales/ShipmentController.php');

class FireGento_Pdf_Adminhtml_Sales_ShipmentController
    extends Mage_Adminhtml_Sales_ShipmentController
{
    public function pdfshipmentsAction()
    {
        $shipmentIds = $this->getRequest()->getPost('shipment_ids');
        if (sizeof($shipmentIds) > 1) {
            $shipments = Mage::getResourceModel('sales/order_shipment_collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in' => $shipmentIds))
                ->load();
            if (!isset($pdf)) {
                $pdf = Mage::getModel('sales/order_pdf_shipment')->getPdf($shipments);
            } else {
                $pages = Mage::getModel('sales/order_pdf_shipment')->getPdf($shipments);
                $pdf->pages = array_merge($pdf->pages, $pages->pages);
            }
            return $this->_prepareDownloadResponse(
                Mage::helper('firegento_pdf')
                    ->getExportFilenameForMultipleDocuments('shipment'),
                $pdf->render(), 'application/pdf'
            );
        } else if (sizeof($shipmentIds) == 1) {
            $shipmentId = $shipmentIds[0];
            if ($shipment = Mage::getModel('sales/order_shipment')
                ->load($shipmentId)
            ) {
                $pdf = Mage::getModel('sales/order_pdf_shipment')
                    ->getPdf(array($shipment));
                return $this->_prepareDownloadResponse(
                    Mage::helper('firegento_pdf')
                        ->getExportFilename('shipment', $shipment),
                    $pdf->render(), 'application/pdf'
                );
            }
        }

        $this->_redirect('*/*/');
    }
}
