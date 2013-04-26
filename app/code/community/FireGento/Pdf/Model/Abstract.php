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
 * Abstract pdf model.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2012 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
abstract class FireGento_Pdf_Model_Abstract extends Mage_Sales_Model_Order_Pdf_Abstract
{
    public $margin = array('left' => 45, 'right' => 540);
    public $colors = array();
    public $mode;

    protected $imprint;

    public function __construct()
    {
        parent::__construct();

        $this->encoding = 'UTF-8';

        $this->colors['black'] = new Zend_Pdf_Color_GrayScale(0);
        $this->colors['grey1'] = new Zend_Pdf_Color_GrayScale(0.9);

        $storeId = $this->getStoreId();
        $imprint =  Mage::getStoreConfig('general/imprint', $storeId);

        if (!empty($imprint)) {
            $this->imprint = $imprint;
        } else {
            $this->imprint = false;
        }
    }

    /**
     * Set pdf mode.
     *
     * @param string $mode
     * @return FireGento_Pdf_Model_Abstract
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Return pdf mode.
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set next Line Position
     *
     * @param int $height Line-Height
     * @return void
     */
    protected function Ln($height=15)
    {
        $this->y -= $height;
    }

    /**
     * Insert Sender Address Bar over the Billing Address
     *
     * @param Zend_Pdf_Page $page Current page object of Zend_Pdf
     * @return void
     */
    protected function _insertSenderAddessBar(&$page)
    {
        if (Mage::getStoreConfig('sales_pdf/firegento_pdf/sender_address_bar') != '') {
            $this->_setFontRegular($page, 6);
            $page->drawText(trim(Mage::getStoreConfig('sales_pdf/firegento_pdf/sender_address_bar')), $this->margin['left'], $this->y, $this->encoding);
        }
    }

    /**
     * Insert Shop Logo
     *
     * @param Zend_Pdf_Page $page Current Page Object of Zend_PDF
     * @param string $store Store ID
     * @return void
     */
    protected function insertLogo(&$page, $store = null)
    {
        $maxwidth = ($this->margin['right'] - $this->margin['left']);
        $maxheight = 100;

        $image = Mage::getStoreConfig('sales/identity/logo', $store);
        if ($image and file_exists(Mage::getBaseDir('media', $store) . '/sales/store/logo/' . $image)) {
            $image = Mage::getBaseDir('media', $store) . '/sales/store/logo/' . $image;

            list($width, $height) = getimagesize($image);

            if ($height > $maxheight or $width > $maxwidth) {
                // calculate max variance to match dimensions
                $widthVar = $width / $maxwidth;
                $heightVar = $height / $maxheight;

                // calculate scale factor to match dimensions
                if ($widthVar > $heightVar) {
                    $scale = $maxwidth / $width;
                } else {
                    $scale = $maxheight / $height;
                }

                // calculate new dimensions
                $height = round($height * $scale);
                $width  = round($width * $scale);
            }

            if (is_file($image)) {
                $image = Zend_Pdf_Image::imageWithPath($image);

                $logoPosition = Mage::getStoreConfig('sales_pdf/firegento_pdf/logo_position', $store);

                switch($logoPosition) {
                    case 'center':
                        $startLogoAt = $this->margin['left'] + ( ($this->margin['right'] - $this->margin['left']) / 2 ) - $width / 2;
                        break;
                    case 'right':
                        $startLogoAt = $this->margin['right'] - $width;
                        break;
                    default:
                        $startLogoAt = $this->margin['left'];
                }

                $position['x1'] = $startLogoAt;
                $position['y1'] = 720;
                $position['x2'] = $position['x1'] + $width;
                $position['y2'] = $position['y1'] + $height;

                $page->drawImage($image, $position['x1'], $position['y1'], $position['x2'], $position['y2']);
            }
        }
    }

        /**
     * Insert Billing Address
     *
     * @param object $page  Current Page Object of Zend_PDF
     * @param object $order Order object
     *
     * @return void
     */
    protected function insertBillingAddress(&$page, $order)
    {
        $this->_setFontRegular($page, 9);
        $billing = $this->_formatAddress($order->getBillingAddress()->format('pdf'));
        foreach ($billing as $line) {
            $page->drawText(trim(strip_tags($line)), $this->margin['left'], $this->y, $this->encoding);
            $this->Ln(12);
        }
    }

    /**
     * Insert Header
     *
     * @param Zend_Pdf_Page $page     Current Page Object of Zend_PDF
     * @param objet $order    Order object
     * @param objet $document Document object
     *
     * @return void
     */
    protected function insertHeader(&$page, $order, $document)
    {
        $page->setFillColor($this->colors['black']);

        $mode = $this->getMode();

        $this->_setFontBold($page, 15);

        $page->drawText(Mage::helper('firegento_pdf')->__( ($mode == 'invoice') ? 'Invoice' : 'Creditmemo' ), $this->margin['left'], $this->y, $this->encoding);

        $this->_setFontRegular($page);

        $this->y += 34;
        $rightoffset = 180;

        $page->drawText(Mage::helper('firegento_pdf')->__( ($mode == 'invoice') ? 'Invoice number:' : 'Creditmemo number:' ), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
        $this->Ln();

        $yPlus = 15;

        if($order->getCustomerId() != "") {

            $page->drawText(Mage::helper('firegento_pdf')->__('Customer number:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
            $this->Ln();

            $yPlus += 15;

        }

        if(Mage::getStoreConfig('sales_pdf/invoice/showcustomerip')) {
            $page->drawText(Mage::helper('firegento_pdf')->__('Customer IP:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
            $this->Ln();
            $yPlus += 15;
        }

        $page->drawText(Mage::helper('firegento_pdf')->__(($mode == 'invoice') ? 'Invoice date:' : 'Date:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);

        $this->y += $yPlus;
        $rightoffset = 60;
        $page->drawText($document->getIncrementId(), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
        $this->Ln();

        $rightoffset = 10;
        $font = $this->_setFontRegular($page, 10);

        if($order->getCustomerId() != "") {

            $prefix = Mage::getStoreConfig('sales_pdf/invoice/customeridprefix');

            if (!empty($prefix)) {
                $customerid = $prefix.$order->getCustomerId();
            }
            else {
                $customerid = $order->getCustomerId();
            }


            $page->drawText($customerid, ($this->margin['right'] - $rightoffset - $this->widthForStringUsingFontSize($customerid, $font, 10)), $this->y, $this->encoding);
            $this->Ln();

        }

        if (Mage::getStoreConfig('sales_pdf/invoice/showcustomerip')) {
            $customerIP = $order->getData('remote_ip');
            $font = $this->_setFontRegular($page, 10);
            $page->drawText($customerIP, ($this->margin['right'] - $rightoffset - $this->widthForStringUsingFontSize($customerIP, $font, 10)), $this->y, $this->encoding);
            $this->Ln();
        }

        $documentDate = Mage::helper('core')->formatDate($document->getCreatedAtDate(), 'medium', false);
        $page->drawText($documentDate, ($this->margin['right'] - $rightoffset - $this->widthForStringUsingFontSize($documentDate, $font, 10)), $this->y, $this->encoding);

    }

    /**
     * ...
     *
     * @param Varien_Object $item
     * @param Zend_Pdf_Page $page Current Page Object of Zend_PDF
     * @param Mage_Sales_Model_Order $order
     * @param int $position
     * @return Zend_Pdf_Page
     */
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

    /**
     * Insert Totals Block
     *
     * @param object $page   Current Page Object of Zend_PDF
     * @param object $source Fields of Footer
     *
     * @return void
     */
    protected function insertTotals($page, $source)
    {
        $this->y -=15;

        $order = $source->getOrder();
        $tax = Mage::getModel('sales/order_tax')->getCollection()->loadByOrder($order)->toArray();

        $total_tax = 0;
        $shippingTaxAmount = $order->getShippingTaxAmount();

        $groupedTax = array();

        foreach ($source->getAllItems() as $item) {
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }
            $items['items'][] = $item->getOrderItem()->toArray();
        }

        array_push($items['items'], array(
            'row_invoiced' => $order->getShippingInvoiced(),
            'tax_inc_subtotal' => false,
            'tax_percent' => '19.0000',
            'tax_amount' => $shippingTaxAmount
        ));

        foreach ($items['items'] as $item) {
            $_percent = null;
            if (!isset($item['tax_amount'])) $item['tax_amount'] = 0;
            if (!isset($item['row_invoiced'])) $item['row_invoiced'] = 0;
            if (!isset($item['price'])) $item['price'] = 0;
            if (!isset($item['tax_inc_subtotal'])) $item['tax_inc_subtotal'] = 0;
            if (isset($item['tax_percent']) && !empty($item['tax_percent'])) {
                $_percent = (int)$item['tax_percent'];
            } else if (((float)$item['tax_amount'] > 0)&&((float)$item['row_invoiced'] > 0)) {
                $_percent = round((float)$item['tax_amount'] / ((float)$item['row_invoiced'] - (float)$item['discount_invoiced']) * 100,0);
            }
            if (!array_key_exists('tax_inc_subtotal', $item) || $item['tax_inc_subtotal']) {
                $total_tax += $item['tax_amount'];
            }
            if (($item['tax_amount'])&&$_percent){
                if(!array_key_exists((int)$_percent, $groupedTax)) {
                    $groupedTax[$_percent] = $item['tax_amount'];
                }
                else {
                    $groupedTax[$_percent] += $item['tax_amount'];
                }
            }
        }

        $totals = $this->_getTotalsList($source);

        $lineBlock = array(
            'lines'  => array(),
            'height' => 20
        );

        foreach ($totals as $total) {
            $fontSize = (isset($total['font_size']) ? $total['font_size'] : 7);
            if ($fontSize < 9) {
                $fontSize = 9;
            }
            $fontWeight = (isset($total['font_weight']) ? $total['font_weight'] : 'regular');

            switch($total['source_field']) {
                case 'tax_amount':
                    foreach ($groupedTax as $taxRate => $taxValue) {
                        if(empty($taxValue)) {
                            continue;
                        }

                        $lineBlock['lines'][] = array(
                            array(
                                'text'      => Mage::helper('firegento_pdf')->__('Additional tax %s', $source->getStore()->roundPrice(number_format($taxRate, 0)).'%'),
                                'feed'      => $this->margin['left'] + 320,
                                'align'     => 'left',
                                'font_size' => $fontSize,
                                'font'      => $fontWeight
                            ),
                            array(
                                'text'      => $order->formatPriceTxt($taxValue),
                                'feed'      => $this->margin['right'] - 10,
                                'align'     => 'right',
                                'font_size' => $fontSize,
                                'font'      => $fontWeight
                            ),
                        );
                    }
                    break;

                case 'subtotal':
                    $amount = $source->getDataUsingMethod($total['source_field']);
                    $displayZero = (isset($total['display_zero']) ? $total['display_zero'] : 0);

                    if ($amount != 0 || $displayZero) {
                        $amount = $order->formatPriceTxt($amount);

                        if (isset($total['amount_prefix']) && $total['amount_prefix']) {
                            $amount = "{$total['amount_prefix']}{$amount}";
                        }

                        $label = Mage::helper('sales')->__($total['title']) . ':';

                        $lineBlock['lines'][] = array(
                            array(
                                'text'      => $label,
                                'feed'      => $this->margin['left'] + 320,
                                'align'     => 'left',
                                'font_size' => $fontSize,
                                'font'      => $fontWeight
                            ),
                            array(
                                'text'      => $amount,
                                'feed'      => $this->margin['right'] - 10,
                                'align'     => 'right',
                                'font_size' => $fontSize,
                                'font'      => $fontWeight
                            ),
                        );
                    }
                    break;

                case 'shipping_amount':
                    $amount = $source->getDataUsingMethod($total['source_field']);
                    $displayZero = (isset($total['display_zero']) ? $total['display_zero'] : 0);

                    $amount = $order->formatPriceTxt($amount);

                    if (isset($total['amount_prefix']) && $total['amount_prefix']) {
                        $amount = "{$total['amount_prefix']}{$amount}";
                    }

                    $label = Mage::helper('sales')->__($total['title']) . ':';

                    $lineBlock['lines'][] = array(
                        array(
                            'text'      => Mage::helper('firegento_pdf')->__('Shipping:'),
                            'feed'      => $this->margin['left'] + 320,
                            'align'     => 'left',
                            'font_size' => $fontSize,
                            'font'      => $fontWeight
                        ),
                        array(
                            'text'      => $amount,
                            'feed'      => $this->margin['right'] - 10,
                            'align'     => 'right',
                            'font_size' => $fontSize,
                            'font'      => $fontWeight
                        ),
                    );
                    break;

                case 'grand_total':
                    $amount = $source->getDataUsingMethod($total['source_field']);
                    $displayZero = (isset($total['display_zero']) ? $total['display_zero'] : 0);

                    if ($amount != 0 || $displayZero) {
                        $amount = $order->formatPriceTxt($amount);

                        if (isset($total['amount_prefix']) && $total['amount_prefix']) {
                            $amount = "{$total['amount_prefix']}{$amount}";
                        }

                        $label = Mage::helper('sales')->__($total['title']) . ':';

                        $lineBlock['lines'][] = array(
                            array(
                                'text'      => $label,
                                'feed'      => $this->margin['left'] + 320,
                                'align'     => 'left',
                                'font_size' => $fontSize,
                                'font'      => $fontWeight
                            ),
                            array(
                                'text'      => $amount,
                                'feed'      => $this->margin['right'] - 10,
                                'align'     => 'right',
                                'font_size' => $fontSize,
                                'font'      => $fontWeight
                            ),
                        );
                    }
                    break;

                default:
                    $amount = $source->getDataUsingMethod($total['source_field']);
                    $displayZero = (isset($total['display_zero']) ? $total['display_zero'] : 0);

                    if ($amount != 0 || $displayZero) {
                        $amount = $order->formatPriceTxt($amount);

                        if (isset($total['amount_prefix']) && $total['amount_prefix']) {
                            $amount = "{$total['amount_prefix']}{$amount}";
                        }

                        $label = Mage::helper('sales')->__($total['title']) . ':';

                        $lineBlock['lines'][] = array(
                            array(
                                'text'      => $label,
                                'feed'      => $this->margin['right'] - 100,
                                'align'     => 'right',
                                'font_size' => $fontSize,
                                'font'      => $fontWeight
                            ),
                            array(
                                'text'      => $amount,
                                'feed'      => $this->margin['right'] - 10,
                                'align'     => 'right',
                                'font_size' => $fontSize,
                                'font'      => $fontWeight
                            ),
                        );
                    }
            }
        }
        $page = $this->drawLineBlocks($page, array($lineBlock));
        return $page;
    }

    protected function _addFooter(&$page)
    {
        // Add footer if GermanSetup is installed.
        if ($this->imprint && Mage::getStoreConfig('sales_pdf/firegento_pdf/show_footer') == 1) {
            $this->y = 110;
            $this->_insertFooter($page);

            // Add page counter.
            $this->y = 110;
            $this->_insertPageCounter($page);
        }
    }

    /**
     * Insert footer
     *
     * @param Zend_Pdf_Page $page Current page object of Zend_Pdf
     * @return void
     */
    protected function _insertFooter(&$page)
    {
        $page->setLineColor($this->colors['black']);
        $page->setLineWidth(0.5);
        $page->drawLine($this->margin['left'] - 20, $this->y - 5, $this->margin['right'] + 30, $this->y - 5);

        $this->Ln(15);
        $this->_insertFooterAddress($page);

        $fields = array(
            'telephone' => Mage::helper('firegento_pdf')->__('Telephone:'),
            'fax' => Mage::helper('firegento_pdf')->__('Fax:'),
            'email' => Mage::helper('firegento_pdf')->__('E-Mail:'),
            'web' => Mage::helper('firegento_pdf')->__('Web:')
        );
        $this->_insertFooterBlock($page, $fields, 70, 40, 140);

        $fields = array(
            'bank_name' => Mage::helper('firegento_pdf')->__('Bank name:'),
            'bank_account' => Mage::helper('firegento_pdf')->__('Account:'),
            'bank_code_number' => Mage::helper('firegento_pdf')->__('Bank number:'),
            'bank_account_owner' => Mage::helper('firegento_pdf')->__('Account owner:'),
            'swift' => Mage::helper('firegento_pdf')->__('SWIFT:'),
            'iban' => Mage::helper('firegento_pdf')->__('IBAN:')
        );
        $this->_insertFooterBlock($page, $fields, 215, 50, 140);

        $fields = array(
            'tax_number' => Mage::helper('firegento_pdf')->__('Tax number:'),
            'vat_id' => Mage::helper('firegento_pdf')->__('VAT-ID:'),
            'register_number' => Mage::helper('firegento_pdf')->__('Register number:'),
            'ceo' => Mage::helper('firegento_pdf')->__('CEO:')
        );
        $this->_insertFooterBlock($page, $fields, 355, 60, $this->margin['right'] - 355 - 10);
    }

    /**
     * Insert footer block
     *
     * @param Zend_Pdf_Page $page Current page object of Zend_PDF
     * @param array $fields Fields of footer
     * @param int $colposition Starting colposition
     * @param int $valadjust Margin between label and value
     * @param int $colwidth the width of this footer block - text will be wrapped if it is broader than this width
     * @return void
     */
    protected function _insertFooterBlock(&$page, $fields, $colposition = 0, $valadjust = 30, $colwidth = null)
    {
        $fontSize = 7;
        $font = $this->_setFontRegular($page, $fontSize);
        $y = $this->y;

        $valposition = $colposition + $valadjust;

        if (is_array($fields)) {
            foreach ($fields as $field => $label) {
                if (empty($this->imprint[$field])) {
                    continue;
                }
                // draw the label
                $page->drawText($label, $this->margin['left'] + $colposition, $y, $this->encoding);
                // prepare the value: wrap it if necessary
                $val = $this->imprint[$field];
                $width = $colwidth;
                if (!empty($colwidth)) {
                    // calculate the maximum width for the value
                    $width = $this->margin['left'] + $colposition + $colwidth - ($this->margin['left'] + $valposition);
                }
                $tmpVal = $this->_prepareText($val, $page, $font, $fontSize, $width);
                $tmpVals = explode("\n", $tmpVal);
                foreach ($tmpVals as $tmpVal) {
                    $page->drawText($tmpVal, $this->margin['left'] + $valposition, $y, $this->encoding);
                    $y -= 12;
                }
            }
        }
    }

    /**
     * Insert addess of store owner
     *
     * @param Zend_Pdf_Page $page Current page object of Zend_df
     * @param string $store Store ID
     * @return void
     */
    protected function _insertFooterAddress(&$page, $store = null)
    {
        $address = $this->imprint['company_first']."\n";

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

    /**
     * Insert page counter
     *
     * @param Zend_Pdf_Page $page Current page object of Zend_Pdf
     * @return void
     */
    protected function _insertPageCounter(&$page)
    {
        $font = $this->_setFontRegular($page, 9);
        $page->drawText(Mage::helper('firegento_pdf')->__('Page').' '.$this->pagecounter, $this->margin['right'] - 23 - $this->widthForStringUsingFontSize($this->pagecounter, $font, 9), $this->y, $this->encoding);
    }

    /**
     * Set default font
     *
     * @param object $object   Current Page Object of Zend_PDF
     * @param string $size     Font size
     *
     * @return void
     */
    protected function _setFontRegular($object, $size = 10)
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $object->setFont($font, $size);
        return $font;
    }

    /**
     * Set bold font
     *
     * @param object $object   Current Page Object of Zend_PDF
     * @param string $size     Font size
     *
     * @return void
     */
    protected function _setFontBold($object, $size = 10)
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $object->setFont($font, $size);
        return $font;
    }

    /**
     * Set italic font
     *
     * @param object $object   Current Page Object of Zend_PDF
     * @param string $size     Font size
     *
     * @return void
     */
    protected function _setFontItalic($object, $size = 10)
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
        $object->setFont($font, $size);
        return $font;
    }

    /**
     * Prepares the text so that it fits to the given page's width.
     *
     * @param $text the text which should be prepared
     * @param $page the page on which the text will be rendered
     * @param $font the font with which the text will be rendered
     * @param $fontSize the font size with which the text will be rendered
     * @param $width [optional] the width for the given text, defaults to the page width
     *
     * @return string the given text wrapped by new line characters
     */
    protected function _prepareText($text, $page, $font, $fontSize, $width = null)
    {
        $lines = '';
        $currentLine = '';
        // calculate the page's width with respect to the margins
        if (empty($width)) {
            $width = $page->getWidth() - $this->margin['left'] - ($page->getWidth() - $this->margin['right']);
        }
        $textChunks = explode(' ', $text);
        foreach ($textChunks as $textChunk) {
            if ($this->widthForStringUsingFontSize($currentLine . ' ' . $textChunk, $font, $fontSize) < $width) {
                // do not add whitespace on first line
                if (!empty($currentLine)) {
                    $currentLine .= ' ';
                }
                $currentLine .= $textChunk;
            } else {
                // text is too broad, so add new line character
                $lines .= $currentLine . "\n";
                $currentLine = $textChunk;
            }
        }
        // append the last line
        $lines .= $currentLine;
        return $lines;
    }
}
