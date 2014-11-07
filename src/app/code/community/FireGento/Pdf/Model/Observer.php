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
/**
 * FireGento Pdf observer.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.com)
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_Pdf_Model_Observer
{
    /**
     * Add notes to invoice document.
     *
     * @param  Varien_Event_Observer $observer observer object
     *
     * @return FireGento_Pdf_Model_Observer
     */
    public function addInvoiceNotes(Varien_Event_Observer $observer)
    {
        $this->addInvoiceDateNotice($observer);
        $this->addInvoiceMaturity($observer);
        $this->addPaymentMethod($observer);
        $this->addShippingMethod($observer);
        $this->addInvoiceComments($observer);

        return $this;
    }

    /**
     * add invoice date notice to pdf
     *
     * @param  Varien_Event_Observer $observer observer object
     *
     * @return $this
     */
    public function addInvoiceDateNotice(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfigFlag('sales_pdf/invoice/show_date_notice')) {
            return $this;
        }

        $result = $observer->getResult();
        $notes = $result->getNotes();
        $notes[] = Mage::helper('firegento_pdf')->__('Invoice date is equal to delivery date.');
        $result->setNotes($notes);
        return $this;
    }

    /**
     * Add maturity to invoice notes.
     *
     * @param  Varien_Event_Observer $observer observer object
     *
     * @return FireGento_Pdf_Model_Observer
     */
    public function addInvoiceMaturity(Varien_Event_Observer $observer)
    {
        $result = $observer->getResult();
        $notes = $result->getNotes();

        $maturity = Mage::getStoreConfig('sales_pdf/invoice/maturity');
        if (!empty($maturity) || 0 < $maturity) {
            $maturity = Mage::helper('firegento_pdf')->__(
                'Invoice maturity: %s days', Mage::getStoreConfig('sales_pdf/invoice/maturity')
            );
        } elseif ('0' === $maturity) {
            $maturity = Mage::helper('firegento_pdf')->__('Invoice is payable immediately');
        }

        $notes[] = $maturity;
        $result->setNotes($notes);
        return $this;
    }

    /**
     * Add payment method to invoice notes.
     *
     * @param  Varien_Event_Observer $observer observer object
     *
     * @return FireGento_Pdf_Model_Observer
     */
    public function addPaymentMethod(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('sales_pdf/invoice/payment_method_position')
            != FireGento_Pdf_Model_System_Config_Source_Payment::POSITION_NOTE
        ) {
            return $this;
        }

        $result = $observer->getResult();
        $notes = $result->getNotes();
        $notes[] = Mage::helper('firegento_pdf')->__(
            'Payment method: %s', $observer->getOrder()->getPayment()->getMethodInstance()->getTitle()
        );
        $result->setNotes($notes);
        return $this;
    }

    /**
     * Add shipping method to invoice notes.
     *
     * @param  Varien_Event_Observer $observer observer object
     *
     * @return FireGento_Pdf_Model_Observer
     */
    public function addShippingMethod(Varien_Event_Observer $observer)
    {
        $invoice = $observer->getInvoice();
        $shipment = $observer->getShipment();
        if (empty($invoice) && empty($shipment)
            || !empty($invoice) && Mage::getStoreConfig('sales_pdf/invoice/shipping_method_position')
            != FireGento_Pdf_Model_System_Config_Source_Shipping::POSITION_NOTE
            || !empty($shipment) && Mage::getStoreConfig('sales_pdf/shipment/shipping_method_position')
            != FireGento_Pdf_Model_System_Config_Source_Shipping::POSITION_NOTE
        ) {
            return $this;
        }

        $result = $observer->getResult();
        $notes = $result->getNotes();
        $notes[] = Mage::helper('firegento_pdf')->__(
            'Shipping method: %s', $observer->getOrder()->getShippingDescription()
        );
        $result->setNotes($notes);
        return $this;
    }


    /**
     * Add the invoice comments
     *
     * @param  Varien_Event_Observer $observer observer object
     *
     * @return FireGento_Pdf_Model_Observer
     */
    public function addInvoiceComments(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfigFlag('sales_pdf/invoice/show_comments')) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = $observer->getInvoice();

        /** @var Mage_Sales_Model_Resource_Order_Invoice_Comment_Collection $commentsCollection */
        $commentsCollection = $invoice->getCommentsCollection();
        $commentsCollection->addVisibleOnFrontFilter();

        $result = $observer->getResult();
        $notes = $result->getNotes();

        foreach ($commentsCollection as $comment) {
            /** @var $comment Mage_Sales_Model_Order_Invoice_Comment */
            $notes[] = $comment->getComment();
        }

        $result->setNotes($notes);
        return $this;
    }

    /**
     * Adds a barcode representing the order number to the shipment if activated.
     *
     * @param  Varien_Event_Observer $observer observer which is passed by magento
     *
     * @return FireGento_Pdf_Model_Observer
     */
    public function addBarcode(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfigFlag('sales_pdf/shipment/order_id_as_barcode')) {
            return $this;
        }
        $page = $observer->getPage();
        $order = $observer->getOrder();

        $barcodeConfig = array(
            'drawText' => false,
            'orientation' => 90,
            'text' => $order->getIncrementId()
        );
        $rendererConfig = array(
            'verticalPosition' => 'middle',
            'moduleSize' => 0.9
        );
        // create dummy Zend_Pdf object, which just stores the current page, so that we can pass it in
        // Zend_Barcode_Renderer_Pdf->setResource()
        $pdf = new Zend_Pdf();
        $pdf->pages[] = $page;
        /** @var $renderer Zend_Barcode_Renderer_Pdf */
        $renderer = Zend_Barcode::factory('code128', 'pdf', $barcodeConfig, $rendererConfig)->setResource($pdf, 0);
        // calculate left offset so that barcode is printed on the right with a little margin
        $leftOffset = $page->getWidth() - $renderer->getBarcode()->getWidth(true) * $renderer->getModuleSize() - 10;
        $renderer->setLeftOffset($leftOffset);
        $renderer->draw();
        return $this;
    }
}
