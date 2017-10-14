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
/**
 * Shipment model rewrite.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Model_Engine_Shipment_Default
    extends FireGento_Pdf_Model_Engine_Abstract
{

    /**
     * constructor to set shipping mode
     */
    public function __construct()
    {
        parent::__construct();
        $this->setMode('shipment');
    }

    /**
     * Return PDF document
     *
     * @param  array $shipments list of shipments to generate pdfs for
     *
     * @return Zend_Pdf
     */
    public function getPdf($shipments = array())
    {
        $currentStore = Mage::app()->getStore()->getCode();
        $this->_beforeGetPdf();
        $this->_initRenderer('shipment');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);

        foreach ($shipments as $shipment) {
            // pagecounter is 0 at the beginning, because it is incremented in newPage()
            $this->pagecounter = 0;
            if ($shipment->getStoreId()) {
                Mage::app()->getLocale()->emulate($shipment->getStoreId());
                Mage::app()->setCurrentStore($shipment->getStoreId());
            }
            $order = $shipment->getOrder();
            $this->setOrder($order);

            $page = $this->newPage(array());

            $this->insertAddressesAndHeader($page, $shipment, $order);

            $this->_setFontRegular($page, 9);
            $this->insertTableHeader($page);

            $this->y -= 20;

            $position = 0;

            foreach ($shipment->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }

                if ($this->y < 50
                    || (Mage::getStoreConfig('sales_pdf/firegento_pdf/show_footer')
                        == 1
                        && $this->y < 100)
                ) {
                    $page = $this->newPage(array());
                }

                $position++;
                $page = $this->_drawItem($item, $page, $order, $position);
            }

            /* add shipment tracks */
            $page = $this->_printShipmentTracks($page, $order, $shipment);

            /* add note */
            $page = $this->_insertNote($page, $order, $shipment);

            // Add footer
            $this->_addFooter($page, $shipment->getStore());

            if ($shipment->getStoreId()) {
                Mage::app()->getLocale()->revert();
            }
        }

        // Revert back to the original current store
        Mage::app()->setCurrentStore($currentStore);

        $this->_afterGetPdf();

        return $pdf;
    }

    /**
     * Inserts the customer's shipping address.
     *
     * @param  Zend_Pdf_Page          &$page current page object of Zend_Pdf
     * @param  Mage_Sales_Model_Order $order order object
     *
     * @return void
     */
    protected function _insertCustomerAddress(&$page, $order)
    {
        $this->_setFontRegular($page, 9);
        $shipping = $this->_formatAddress($order->getShippingAddress()
                ->format('pdf'));
        foreach ($shipping as $line) {
            $page->drawText(trim(strip_tags($line)), $this->margin['left'],
                $this->y, $this->encoding);
            $this->Ln(12);
        }
    }

    /**
     * insert the table header of the shipment
     *
     * @param Zend_Pdf_Page $page page to write on
     */
    protected function insertTableHeader($page)
    {
        $page->setFillColor($this->colors['header']);
        $page->setLineColor($this->colors['header']);
        $page->setLineWidth(1);
        $page->drawRectangle($this->margin['left'], $this->y,
            $this->margin['right'] - 10, $this->y - 15);

        $page->setFillColor($this->colors['black']);
        $this->_setFontRegular($page, 9);

        $this->y -= 11;
        $page->drawText(
            Mage::helper('firegento_pdf')->__('No.'),
            $this->margin['left'],
            $this->y,
            $this->encoding
        );
        $page->drawText(
            Mage::helper('firegento_pdf')->__('Description'),
            $this->margin['left'] + 105,
            $this->y,
            $this->encoding
        );

        $page->drawText(
            Mage::helper('firegento_pdf')->__('Qty'),
            $this->margin['left'] + 450,
            $this->y,
            $this->encoding
        );
    }

    /**
     * insert address into pdf
     *
     * @param Zend_Pdf_Page          $page  to insert addres into
     * @param Mage_Sales_Model_Order $order order to get address from
     */
    protected function insertShippingAddress($page, $order)
    {
        $this->_setFontRegular($page, 9);

        $billing = $this->_formatAddress($order->getShippingAddress()
                ->format('pdf'));

        foreach ($billing as $line) {
            $page->drawText(trim(strip_tags($line)), $this->margin['left'],
                $this->y, $this->encoding);
            $this->Ln(12);
        }
    }

    /**
     * This will print all the shipment tracks.
     *
     * @param Zend_Pdf_Page $page
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Shipment $shipment
     *
     * @return Zend_Pdf_Page
     */
    protected function _printShipmentTracks($page, $order, $shipment)
    {
        if ( ! Mage::getStoreConfigFlag('sales_pdf/shipment/show_tracking_numbers') || $order->getIsVirtual()) {
            return $page;
        }
        $tracks = array();
        if ($shipment) {
            $tracks = $shipment->getAllTracks();
        }
        if (empty($tracks)) {
            return $page;
        }
        $this->y -= 20;
        $page->setFillColor($this->colors['header']);
        $page->setLineColor($this->colors['header']);
        $page->setLineWidth(1);
        $page->drawRectangle($this->margin['left'], $this->y, $this->margin['right'] - 10, $this->y - 15);
        $page->setFillColor($this->colors['black']);
        $this->_setFontRegular($page, 9);
        $this->y -= 11;
        $page->drawText(Mage::helper('sales')->__('Carrier'), $this->margin['left'], $this->y, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('Number'), 290, $this->y, 'UTF-8');
        $page->setFillColor($this->colors['text']);
        $this->y -= 18;
        foreach ($tracks as $track) {
            $maxTitleLen    = 45;
            $endOfTitle     = strlen($track->getTitle()) > $maxTitleLen ? '...' : '';
            $truncatedTitle = substr($track->getTitle(), 0, $maxTitleLen) . $endOfTitle;
            $page->drawText($truncatedTitle, $this->margin['left'], $this->y, 'UTF-8');
            $page->drawText($track->getNumber(), 290, $this->y, 'UTF-8');
            $this->y -= 18;
        }

        return $page;
    }

    /**
     * Initialize renderer process.
     *
     * @param  string $type type to be initialized
     *
     * @return void
     */
    protected function _initRenderer($type)
    {
        parent::_initRenderer($type);

        $this->_renderers['default'] = array(
            'model'    => 'firegento_pdf/items_shipment_default',
            'renderer' => null
        );
        $this->_renderers['bundle'] = array(
            'model'    => 'firegento_pdf/items_shipment_bundle',
            'renderer' => null
        );
        $this->_renderers['ugiftcert'] = array(
            'model'    => 'firegento_pdf/items_unirgy_shipment_default',
            'renderer' => null
        );
    }

}
