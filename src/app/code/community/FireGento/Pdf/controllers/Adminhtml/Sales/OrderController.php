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

require('Mage/Adminhtml/controllers/Sales/OrderController.php');

/**
 * Adminhtml Order controller to serve invoices, creditmemos, etc. as pdf or zip
 *
 * @category FireGento
 * @package  FireGento_Pdf
 * @author   FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Adminhtml_Sales_OrderController
    extends Mage_Adminhtml_Sales_OrderController
{
    /**
     * serve invoices from one or multiple orders as pdf or zip
     */
    public function pdfinvoicesAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids');
        if (!empty($orderIds)) {
            if (Mage::helper('firegento_pdf')->isServeInvoiceAsZip()) {
                $this->serviceInvoiceAsZip($orderIds);

                return;
            }
            $this->serveInvoicesAsPdf($orderIds);

            return;
        }
        $this->_redirect('*/*/');
    }

    /**
     * serve shipments for one or multiple orders as pdf or zip
     *
     * @return Mage_Core_Controller_Varien_Action
     * @throws Zend_Pdf_Exception
     */
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

    /**
     * service creditmemos as pdf or zip for one or multiple orders
     *
     * @return Mage_Core_Controller_Varien_Action
     * @throws Zend_Pdf_Exception
     */
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
                        /* there is more than one invoice */
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

    /**
     * service invoices as pdf
     *
     * @param  int[] $orderIds orders which invoices should be served
     *
     * @return Mage_Core_Controller_Varien_Action
     * @throws Zend_Pdf_Exception
     */
    private function serveInvoicesAsPdf($orderIds)
    {
        $anyInvoiceFound = false;
        $invoice = null;
        foreach ($orderIds as $orderId) {
            $invoices = Mage::getResourceModel('sales/order_invoice_collection')
                ->setOrderFilter($orderId)
                ->load();
            if ($invoices->getSize() == 1) {
                $invoice = $invoices->getFirstItem();
            }
            if ($invoices->getSize() > 0) {
                $anyInvoiceFound = true;
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
        if ($anyInvoiceFound) {
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

    /**
     * service invoices as zip
     *
     * @param int[] $orderIds orders which invoices should be served
     */
    private function serviceInvoiceAsZip($orderIds)
    {
        $orders = Mage::getResourceModel('sales/order_collection');
        $orders->addFieldToFilter('entity_id', array('in', $orderIds));

        $file = tempnam("tmp", "zip");
        Mage::getModel('firegento_pdf/zippedInvoice')->createFromOrders($file, $orders);

        $name = 'invoice' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.zip';

        $this->getResponse()->setHeader('Content-Type', 'application/zip', true);
        $this->getResponse()->setHeader('Content-Length', filesize($file), true);
        $this->getResponse()->setHeader('Content-Disposition', 'attachment; filename=' . $name, true);

        $this->getResponse()->setBody(file_get_contents($file));

        unlink($file);
    }
}
