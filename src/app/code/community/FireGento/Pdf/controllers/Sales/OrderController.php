<?php
/**
 * This file is part of the FIREGENTO project.
 *
 * FireGento_Pdf is free software; you can redistribute it and/or
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
 * @version   $Id:$
 * @since     0.1.0
 */

require_once 'Mage/Sales/controllers/OrderController.php';

/**
 * Sales orders controller
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.com)
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_Pdf_Sales_OrderController extends Mage_Sales_OrderController
{
    protected $_types
        = array(
            'invoice', 'creditmemo', 'shipment'
        );

    /**
     * Print PDF Invoice Action
     *
     * it changes the standard action with html output to pdf output
     *
     * @return void
     */
    public function printInvoiceAction()
    {
        $this->printDocument('invoice');
    }

    /**
     * Print PDF Creditmemo action
     *
     * it changes the standard action with html output to pdf output
     *
     * @return void
     */

    public function printCreditmemoAction()
    {
        $this->printDocument('creditmemo');
    }

    /**
     * Print PDF Shipment Action
     *
     * it changes the standard action with html output to pdf output
     *
     * @return void
     */
    public function printShipmentAction()
    {
        $this->printDocument('shipment');
    }

    /**
     * Create invoice, creditmemo or shipment pdf
     *
     * @param string $type which document should be created? invoice, creditmemo or shipment
     */
    public function printDocument($type)
    {
        if (!in_array($type, $this->_types)) {
            Mage::throwException('Type not found in type table.');
        }
        /* @var $order Mage_Sales_Model_Order */
        $documentId = (int)$this->getRequest()->getParam($type . '_id');
        $document = null;
        if ($documentId) {
            /* @var $document Mage_Sales_Model_Abstract */
            $document = Mage::getModel('sales/order_' . $type);
            $document->load($documentId);
            $order = $document->getOrder();
        } else {
            $orderId = (int)$this->getRequest()->getParam('order_id');
            $order = Mage::getModel('sales/order')->load($orderId);
        }

        if ($this->_canViewOrder($order)) {
            if (isset($orderId)) {
                // Create a pdf file from all $type s of requested order.
                /* @var $documentsCollection Mage_Sales_Model_Resource_Order_Collection_Abstract */
                $documentsCollection = Mage::getResourceModel('sales/order_' . $type . '_collection');
                $documentsCollection
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('order_id', $orderId)
                    ->load();
            } else {
                // Create a single $type pdf.
                $documentsCollection = array($document);
            }

            // Store current area and set to adminhtml for $type generation.
            $currentArea = Mage::getDesign()->getArea();
            Mage::getDesign()->setArea('adminhtml');

            /* @var $pdfGenerator Mage_Sales_Model_Order_Pdf_Abstract */
            $pdfGenerator = Mage::getModel('sales/order_pdf_' . $type);
            $pdf = $pdfGenerator->getPdf($documentsCollection);
            $this->_prepareDownloadResponse(
                $type . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') .
                '.pdf', $pdf->render(), 'application/pdf'
            );

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
