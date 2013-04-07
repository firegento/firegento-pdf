<?php
/**
 * This file is part of the FIREGENTO project.
 *
 * FireGento_GermanSetup is free software; you can redistribute it and/or
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
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
/**
 * Shipment model rewrite.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_Pdf_Model_Shipment extends FireGento_Pdf_Model_Abstract
{

    public function __construct()
    {
        parent::__construct();
        $this->setMode('shipment');
    }

    /**
     * Return PDF document
     *
     * @param  array $shipments
     * @return Zend_Pdf
     */
    public function getPdf($shipments = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('shipment');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);

        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        // pagecounter is 0 at the beginning, because it is incremented in newPage()
        $this->pagecounter = 0;

        foreach ($shipments as $shipment) {
            if ($shipment->getStoreId()) {
                Mage::app()->getLocale()->emulate($shipment->getStoreId());
                Mage::app()->setCurrentStore($shipment->getStoreId());
            }
            $page = $this->newPage();

            $order = $shipment->getOrder();

            /* add logo */
            $this->insertLogo($page, $shipment->getStore());

            // Add shipping address
            $this->y = 692;
            $this->insertShippingAddress($page, $order);

            /* add sender address */
            $this->y = 705;
            $this->_insertSenderAddessBar($page);

            /* add header */
            $this->y = 592;
            $this->insertHeader($page, $order, $shipment);

            // Add footer
            $this->_addFooter($page, $shipment->getStore());

            /* add table header */
            $this->_setFontRegular($page, 9);
            $this->y = 562;
            $this->insertTableHeader($page);

            $this->y -=20;

            $position = 0;

            foreach ($shipment->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }

                if ($this->y < 100) {
                    $page = $this->newPage(array());
                }

                $position++;
                $page = $this->_drawItem($item, $page, $order, $position);
            }

            /* add note */
            $page = $this->_insertNote($page, $order, $creditmemo);
        }

        $this->_afterGetPdf();

        return $pdf;
    }

    protected function insertTableHeader(&$page)
    {
        $page->setFillColor($this->colors['grey1']);
        $page->setLineColor($this->colors['grey1']);
        $page->setLineWidth(1);
        $page->drawRectangle($this->margin['left'], $this->y, $this->margin['right'] - 10, $this->y - 15);

        $page->setFillColor($this->colors['black']);
        $font = $this->_setFontRegular($page, 9);

        $this->y -= 11;
        $page->drawText(Mage::helper('firegento_pdf')->__('No.'),            $this->margin['left'],       $this->y, $this->encoding);
        $page->drawText(Mage::helper('firegento_pdf')->__('Description'),    $this->margin['left'] + 105, $this->y, $this->encoding);

        $page->drawText(Mage::helper('firegento_pdf')->__('Qty'),         $this->margin['left'] + 450, $this->y, $this->encoding);
    }

    protected function insertHeader(&$page, $order, $shipment)
    {
        $page->setFillColor($this->colors['black']);

        $mode = $this->getMode();

        $this->_setFontBold($page, 15);

        $page->drawText(Mage::helper('firegento_pdf')->__( ($mode == 'shipment') ? 'Shipment' : 'Creditmemo' ), $this->margin['left'], $this->y, $this->encoding);

        $this->_setFontRegular($page);

        $this->y += 60;
        $rightoffset = 180;

        $page->drawText(($mode == 'shipment') ? Mage::helper('firegento_pdf')->__('Shipment number:') : Mage::helper('firegento_pdf')->__('Creditmemo number:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
        $this->Ln();
        $yPlus = 15;

        $putOrderId = $this->_putOrderId($order);
        if ($putOrderId) {
            $page->drawText(Mage::helper('firegento_pdf')->__('Order number:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
            $this->Ln();
            $yPlus += 15;
        }

        if ($order->getCustomerId() != '') {
            $page->drawText(Mage::helper('firegento_pdf')->__('Customer number:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
            $this->Ln();
            $yPlus += 15;
        }

        if (!Mage::getStoreConfigFlag('sales/general/hide_customer_ip', $order->getStoreId())) {
            $page->drawText(Mage::helper('firegento_pdf')->__('Customer IP:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
            $this->Ln();
            $yPlus += 15;
        }

        $page->drawText(Mage::helper('firegento_pdf')->__('Shipping date:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
        $this->Ln();
        $yPlus += 15;

        $this->y += $yPlus;

        $rightoffset = 10;
        $font = $this->_setFontRegular($page, 10);

        $incrementId = $shipment->getIncrementId();
        $page->drawText($shipment->getIncrementId(), ($this->margin['right'] - $rightoffset - $this->widthForStringUsingFontSize($incrementId, $font, 10)), $this->y, $this->encoding);
        $this->Ln();

        if ($putOrderId) {
            $page->drawText($putOrderId, ($this->margin['right'] - $rightoffset - $this->widthForStringUsingFontSize($putOrderId, $font, 10)), $this->y, $this->encoding);
            $this->Ln();
        }

        $prefix = Mage::getStoreConfig('sales_pdf/invoice/customeridprefix');

        if (!empty($prefix)) {
            if (($order->getCustomerId())) {
                $customerid = $prefix . $order->getCustomerId();
            } else {
                $customerid = Mage::helper('firegento_pdf')->__('Guestorder');
            }

        } else {
            if ($order->getCustomerId()) {
                $customerid = $order->getCustomerId();
            } else {
                $customerid = Mage::helper('firegento_pdf')->__('Guestorder');
            }
        }

        $rightoffset = 10;

        $font = $this->_setFontRegular($page, 10);
        $page->drawText($customerid, ($this->margin['right'] - $rightoffset - $this->widthForStringUsingFontSize($customerid, $font, 10)), $this->y, $this->encoding);
        $this->Ln();
        if (!Mage::getStoreConfigFlag('sales/general/hide_customer_ip', $order->getStoreId())) {
            $customerIP = $order->getData('remote_ip');
            $font = $this->_setFontRegular($page, 10);
            $page->drawText($customerIP, ($this->margin['right'] - $rightoffset - $this->widthForStringUsingFontSize($customerIP, $font, 10)), $this->y, $this->encoding);
            $this->Ln();
        }

        $shipmentDate = Mage::helper('core')->formatDate($shipment->getCreatedAtDate(), 'medium', false);
        $page->drawText($shipmentDate, ($this->margin['right'] - $rightoffset - $this->widthForStringUsingFontSize($shipmentDate, $font, 10)), $this->y, $this->encoding);

    }

    protected function insertShippingAddress(&$page, $order)
    {
        $this->_setFontRegular($page, 9);

        $billing = $this->_formatAddress($order->getShippingAddress()->format('pdf'));

        foreach ($billing as $line) {
            $page->drawText(trim(strip_tags($line)), $this->margin['left'], $this->y, $this->encoding);
            $this->Ln(12);
        }
    }

    public function drawLineBlocks(Zend_Pdf_Page $page, array $draw, array $pageSettings = array())
    {
        foreach ($draw as $itemsProp) {
            if (!isset($itemsProp['lines']) || !is_array($itemsProp['lines'])) {
                Mage::throwException(Mage::helper('sales')->__('Invalid draw line data. Please define "lines" array'));
            }
            $lines  = $itemsProp['lines'];
            $height = isset($itemsProp['height']) ? $itemsProp['height'] : 10;

            if (empty($itemsProp['shift'])) {
                $shift = 0;
                foreach ($lines as $line) {
                    $maxHeight = 0;
                    foreach ($line as $column) {
                        $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                        if (!is_array($column['text'])) {
                            $column['text'] = array($column['text']);
                        }
                        $top = 0;
                        foreach ($column['text'] as $part) {
                            $top += $lineSpacing;
                        }

                        $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                    }
                    $shift += $maxHeight;
                }
                $itemsProp['shift'] = $shift;
            }

            if ($this->y - $itemsProp['shift'] < 100) {
                $page = $this->newPage($pageSettings);
            }

            foreach ($lines as $line) {
                $maxHeight = 0;
                foreach ($line as $column) {
                    $fontSize  = empty($column['font_size']) ? 9 : $column['font_size'];
                    if (!empty($column['font_file'])) {
                        $font = Zend_Pdf_Font::fontWithPath($column['font_file']);
                        $page->setFont($font, $fontSize);
                    }
                    else {
                        $fontStyle = empty($column['font']) ? 'regular' : $column['font'];
                        switch ($fontStyle) {
                            case 'bold':
                                $font = $this->_setFontBold($page, $fontSize);
                                break;
                            case 'italic':
                                $font = $this->_setFontItalic($page, $fontSize);
                                break;
                            default:
                                $font = $this->_setFontRegular($page, $fontSize);
                                break;
                        }
                    }

                    if (!is_array($column['text'])) {
                        $column['text'] = array($column['text']);
                    }

                    $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                    $top = 0;
                    foreach ($column['text'] as $part) {
                        $feed = $column['feed'];
                        $textAlign = empty($column['align']) ? 'left' : $column['align'];
                        $width = empty($column['width']) ? 0 : $column['width'];
                        switch ($textAlign) {
                            case 'right':
                                if ($width) {
                                    $feed = $this->getAlignRight($part, $feed, $width, $font, $fontSize);
                                }
                                else {
                                    $feed = $feed - $this->widthForStringUsingFontSize($part, $font, $fontSize);
                                }
                                break;
                            case 'center':
                                if ($width) {
                                    $feed = $this->getAlignCenter($part, $feed, $width, $font, $fontSize);
                                }
                                break;
                        }
                        $page->drawText($part, $feed, $this->y-$top, 'UTF-8');
                        $top += $lineSpacing;
                    }

                    $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                }
                $this->y -= $maxHeight;
            }
        }

        return $page;
    }

    /**
     * Initialize renderer process.
     *
     * @param string $type
     * @return void
     */
    protected function _initRenderer($type)
    {
        parent::_initRenderer($type);

        $this->_renderers['default'] = array(
            'model' => 'firegento_pdf/items_shipment_default',
            'renderer' => null
        );
        $this->_renderers['bundle'] = array(
            'model' => 'firegento_pdf/items_shipment_bundle',
            'renderer' => null
        );
    }

    /**
     * Return status of the engine.
     *
     * @return bool
     */
    public function test()
    {
        return true;
    }

}

