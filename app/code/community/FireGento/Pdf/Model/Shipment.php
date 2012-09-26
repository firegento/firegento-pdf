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
 * @copyright 2012 FireGento Team (http://www.firegento.de). All rights served.
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
 * @copyright 2012 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_Pdf_Model_Shipment extends Mage_Sales_Model_Order_Pdf_Shipment
{
    public $margin;
    public $colors;
    public $encoding;
    public $pagecounter;
    public $mode;

    protected $imprint;

    public function __construct()
    {
        parent::__construct();

        $this->encoding = 'UTF-8';

        $this->colors['black'] = new Zend_Pdf_Color_GrayScale(0);
        $this->colors['grey1'] = new Zend_Pdf_Color_GrayScale(0.9);

        $this->margin['left'] = 45;
        $this->margin['right'] = 540;

        $storeId = $this->getStoreId();
        $imprint =  Mage::getStoreConfig('general/imprint', $storeId);

        if(!empty($imprint)) {
            $this->imprint = $imprint;
        } else {
            $this->imprint = false;
        }

        $this->setMode('shipment');
    }

    public function getPdf($invoices = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('shipment');

        $mode = $this->getMode();

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);

        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        $this->pagecounter = 1;

        foreach ($invoices as $invoice) {
            Mage::app()->getLocale()->emulate(Mage::app()->getStore()->getId());
            $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
            $pdf->pages[] = $page;

            $order = $invoice->getOrder();

            /* add logo */
            $this->insertLogo($page, $invoice->getStore());

            /* add billing address */
            $this->y = 692;
            $this->insertShippingAddress($page, $order);

            /* add sender address */
            $this->y = 705;
            $this->insertSenderAddress($page);

            /* add header */
            $this->y = 592;
            $this->insertHeader($page, $order, $invoice);

            /* add footer if GermanSetup is installed */
            if ($this->imprint) {
                $this->y = 110;
                $this->insertFooter($page, $invoice);
            }

            /* add page counter */
            $this->y = 110;
            $this->insertPageCounter($page);

            /* add table header */
            $this->_setFontRegular($page, 9);
            $this->y = 562;
            $this->insertTableHeader($page);

            $this->y -=20;

            $position = 0;

            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }

                if ($this->y < 200) {
                    $page = $this->newPage(array());
                }

                $position++;
                $page = $this->_drawItem($item, $page, $order, $position);
            }
        }

        $this->_afterGetPdf();

        return $pdf;
    }

    protected function insertNote($page)
    {
        $this->_setFontRegular($page, 10);
        $this->y = $this->y - 60;
        $maturity = Mage::helper('firegento_pdf')->__('Invoice maturity: %s days', Mage::getStoreConfig('sales_pdf/invoice/maturity'));

        if (!empty($maturity)) {
            $page->drawText($maturity, $this->margin['left'], $this->y + 50, $this->encoding);
        }

        $this->Ln(15);

        $notice = Mage::helper('firegento_pdf')->__('Invoice date is equal to delivery date');
        $page->drawText($notice, $this->margin['left'], $this->y + 50, $this->encoding);

        $note = Mage::getStoreConfig('sales_pdf/invoice/note');

        if (!empty($note)) {
            $page->drawText($note, $this->margin['left'], $this->y + 30, $this->encoding);
        }
    }

    protected function insertPageCounter(&$page)
    {
        $font = $this->_setFontRegular($page, 9);
        $page->drawText(Mage::helper('firegento_pdf')->__('Page').' '.$this->pagecounter, $this->margin['right'] - 23 - $this->widthForStringUsingFontSize($this->pagecounter, $font, 9), $this->y, $this->encoding);
    }

    protected function insertFooter(&$page, $invoice = null)
    {
        $page->setLineColor($this->colors['black']);
        $page->setLineWidth(0.5);
        $page->drawLine($this->margin['left'] - 20, $this->y - 5, $this->margin['right'] + 30, $this->y - 5);

        $this->Ln(15);
        $this->insertFooterAddress($page);

        $fields = array(
            'telephone' => Mage::helper('firegento_pdf')->__('Telephone:'),
            'fax' => Mage::helper('firegento_pdf')->__('Fax:'),
            'email' => Mage::helper('firegento_pdf')->__('E-Mail:'),
            'web' => Mage::helper('firegento_pdf')->__('Web:')
        );
        $this->insertFooterBlock($page, $fields, 85, 40);

        $fields = array(
            'bankname' => Mage::helper('firegento_pdf')->__('Bank name:'),
            'bankaccount' => Mage::helper('firegento_pdf')->__('Account:'),
            'bankcodenumber' => Mage::helper('firegento_pdf')->__('Bank number:'),
            'bankaccountowner' => Mage::helper('firegento_pdf')->__('Account owner:'),
            'swift' => Mage::helper('firegento_pdf')->__('SWIFT:'),
            'iban' => Mage::helper('firegento_pdf')->__('IBAN:')
        );
        $this->insertFooterBlock($page, $fields, 215, 50);

        $fields = array(
            'taxnumber' => Mage::helper('firegento_pdf')->__('Tax number:'),
            'vatid' => Mage::helper('firegento_pdf')->__('VAT-ID:'),
            'hrb' => Mage::helper('firegento_pdf')->__('Register number:'),
            'ceo' => Mage::helper('firegento_pdf')->__('CEO:')
        );
        $this->insertFooterBlock($page, $fields, 355, 60);
    }

    protected function insertTableHeader(&$page)
    {
        $page->setFillColor($this->colors['grey1']);
        $page->setLineColor($this->colors['grey1']);
        $page->setLineWidth(1);
        $page->drawRectangle($this->margin['left'], $this->y, $this->margin['right'] - 10, $this->y - 15);

        $page->setFillColor($this->colors['black']);
        $font = $this->_setFontRegular($page, 9);

        $font = $page->getFont();
        $size = $page->getFontSize();

        $this->y -= 11;
        $page->drawText(Mage::helper('firegento_pdf')->__('No.'), 			$this->margin['left'], 	$this->y, $this->encoding);
        $page->drawText(Mage::helper('firegento_pdf')->__('Description'), 	$this->margin['left'] + 105, $this->y, $this->encoding);

        $page->drawText(Mage::helper('firegento_pdf')->__('Amount'), 		$this->margin['left'] + 450, 	$this->y, $this->encoding);
    }

    protected function insertHeader(&$page, $order, $invoice)
    {
        $page->setFillColor($this->colors['black']);

        $mode = $this->getMode();

        $this->_setFontBold($page, 15);

        $page->drawText(Mage::helper('firegento_pdf')->__( ($mode == 'shipment') ? 'Shipment' : 'Creditmemo' ), $this->margin['left'], $this->y, $this->encoding);

        $this->_setFontRegular($page);

        $this->y += 34;
        $rightoffset = 180;

        $page->drawText(($mode == 'shipment') ? Mage::helper('firegento_pdf')->__('Shipment number:') : Mage::helper('firegento_pdf')->__('Creditmemo number:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
        $this->Ln();
        $page->drawText(Mage::helper('firegento_pdf')->__('Customer number:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
        $this->Ln();

        $yPlus = 30;

        if(Mage::getStoreConfig('sales_pdf/invoice/showcustomerip')) {
            $page->drawText(Mage::helper('firegento_pdf')->__('Customer IP:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
            $this->Ln();
            $yPlus = 45;
        }

        $page->drawText(Mage::helper('firegento_pdf')->__('Invoice date:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);

        $this->y += $yPlus;
        $rightoffset = 60;
        $page->drawText($invoice->getIncrementId(), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
        $this->Ln();

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
        if(Mage::getStoreConfig('sales_pdf/invoice/showcustomerip')) {
            $customerIP = $order->getData('remote_ip');
            $font = $this->_setFontRegular($page, 10);
            $page->drawText($customerIP, ($this->margin['right'] - $rightoffset - $this->widthForStringUsingFontSize($customerIP, $font, 10)), $this->y, $this->encoding);
            $this->Ln();
        }

        $invoiceDate = Mage::helper('core')->formatDate($invoice->getCreatedAtDate(), 'medium', false);
        $page->drawText($invoiceDate, ($this->margin['right'] - $rightoffset - $this->widthForStringUsingFontSize($invoiceDate, $font, 10)), $this->y, $this->encoding);

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

    protected function insertFooterBlock(&$page, $fields, $colposition = 0, $valadjust = 30)
    {
        $this->_setFontRegular($page, 7);
        $y = $this->y;

        $valposition = $colposition + $valadjust;

        if (is_array($fields)) {
            foreach ($fields as $field => $label) {
                if (empty($this->imprint[$field])) {
                    continue;
                }
                $page->drawText($label , $this->margin['left'] + $colposition, $y, $this->encoding);
                $page->drawText( $this->imprint[$field], $this->margin['left'] + $valposition, $y, $this->encoding);
                $y -= 12;
            }
        }
    }

    protected function insertFooterAddress(&$page, $store = null)
    {
        $address = $this->imprint['company_first'] . "\n";

        if (array_key_exists('company_second', $this->imprint)) {
            $address .= $this->imprint['company_second'] . "\n";
        }

        $address .= $this->imprint['street']."\n";
        $address .= $this->imprint['zip']." ";
        $address .= $this->imprint['city']."\n";

        $this->_setFontRegular($page, 7);
        $y = $this->y;
        foreach (explode("\n", $address) as $value) {
            if ($value!=='') {
                $page->drawText(trim(strip_tags($value)), $this->margin['left'] - 20, $y, $this->encoding);
                $y -= 12;
            }
        }
    }

    protected function insertLogo(&$page, $store = null)
    {
        $image = Mage::getStoreConfig('sales_pdf/invoice/logo', $store);
        if ($image) {
            $image = Mage::getStoreConfig('system/filesystem/media', $store) . '/sales/store/logo/' . $image;
            if (is_file($image)) {
                $dimensions  = getimagesize($image);
                $image = Zend_Pdf_Image::imageWithPath($image);

                $logo_width  = $dimensions[0];
                $logo_height = $dimensions[1];

                $logo_width  = !empty($logo_width) ? $logo_width : 200;
                $logo_height = !empty($logo_height) ? $logo_height : 50;

                $x1 = (int)(535 - $logo_width / 2);
                $y1 = (int)(825 - $logo_height / 2);

                $this->_headerTextX = $x1 + 5;
                $page->drawImage($image, $x1, $y1, 535, 825);
            }
        }
    }

    protected function Ln($height=15)
    {
        $this->y -= $height;
    }

    protected function _setFontRegular($object, $size = 10)
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $object->setFont($font, $size);
        return $font;
    }

    protected function _setFontBold($object, $size = 10)
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $object->setFont($font, $size);
        return $font;
    }

    protected function _setFontItalic($object, $size = 10)
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
        $object->setFont($font, $size);
        return $font;
    }

    protected function _drawItem(Varien_Object $item, Zend_Pdf_Page $page, Mage_Sales_Model_Order $order, $position = 1)
    {
        $type = $item->getOrderItem()->getProductType();

        $renderer = $this->_getRenderer($type);
        $renderer->setOrder($order);
        $renderer->setItem($item);
        $renderer->setPdf($this);
        $renderer->setPage($page);
        $renderer->setRenderedModel($this);

        $renderer->draw($position);
        return $renderer->getPage();
    }

    public function setMode($mode = 'shipment')
    {
        $this->mode = $mode;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function newPage(array $settings = array())
    {
        $pdf = $this->_getPdf();

        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;

        if ($this->imprint) {
            $this->y = 100;
            $this->insertFooter($page);
        }

        $this->pagecounter++;
        $this->y = 110;
        $this->insertPageCounter($page);

        $this->y = 800;
        $this->_setFontRegular($page, 9);

        return $page;
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

            if ($this->y - $itemsProp['shift'] < 200) {
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

    protected function insertSenderAddress($page)
    {
        if($senderAddress = Mage::getStoreConfig('sales_pdf/invoice/senderaddress')) {
            $this->_setFontRegular($page, 7);
            $page->drawText($senderAddress,  $this->margin['left'], $this->y, $this->encoding);
        }
        return;
    }
}