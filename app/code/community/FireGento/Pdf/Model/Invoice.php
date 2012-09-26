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
 * Invoice model rewrite.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2012 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_Pdf_Model_Invoice extends Mage_Sales_Model_Order_Pdf_Abstract
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

        if (!empty($imprint)) {
            $this->imprint = $imprint;
        } else {
            $this->imprint = false;
        }

        $this->setMode('invoice');
    }

    /**
     * Generate PDF
     *
     * @param array $invoices  Invoice to render
     *
     * @return string
     */
    public function getPdf($invoices = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

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
            $this->insertBillingAddress($page, $order);

            /* add sender address */
            $this->y = 705;
            $this->insertSenderAddessBar($page);

            /* add header */
            $this->y = 592;
            $this->insertHeader($page, $order, $invoice);

            /* add footer if GermanSetup is installed */
            if ($this->imprint && Mage::getStoreConfig('sales_pdf/firegento_pdf/show_footer') == 1) {
                
                $this->y = 110;
                $this->insertFooter($page, $invoice);
                
                /* add page counter */
                $this->y = 110;
                $this->insertPageCounter($page);
                
            }

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
            
            /* add line after items */
            $page->drawLine($this->margin['left'], $this->y + 5, $this->margin['right'], $this->y + 5);

            /* add totals */
            $this->insertTotals($page, $invoice);

            /* add note */
            if ($mode == 'invoice') {
                $this->insertNote($page);
            }
        }

        $this->_afterGetPdf();

        return $pdf;
    }

    /**
     * Insert Notice after Totals
     *
     * @param objet $page  Current Page Object of Zend_PDF
     *
     * @return void
     */
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

    /**
     * Insert Page Counter the Bottom
     *
     * @param objet $page  Current Page Object of Zend_PDF
     *
     * @return void
     */
    protected function insertPageCounter(&$page)
    {
        $font = $this->_setFontRegular($page, 9);
        $page->drawText(Mage::helper('firegento_pdf')->__('Page').' '.$this->pagecounter, $this->margin['right'] - 23 - $this->widthForStringUsingFontSize($this->pagecounter, $font, 9), $this->y, $this->encoding);
    }

    /**
     * Insert Footer
     *
     * @param objet $page  Current Page Object of Zend_PDF
     *
     * @return void
     */
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
        $this->insertFooterBlock($page, $fields, 70, 40);

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

    /**
     * Insert Table Header for Items
     *
     * @param objet $page  Current Page Object of Zend_PDF
     *
     * @return void
     */
    protected function insertTableHeader(&$page)
    {
        $page->setFillColor($this->colors['grey1']);
        $page->setLineColor($this->colors['grey1']);
        $page->setLineWidth(1);
        $page->drawRectangle($this->margin['left'], $this->y, $this->margin['right'], $this->y - 15);

        $page->setFillColor($this->colors['black']);
        $font = $this->_setFontRegular($page, 9);

        $this->y -= 11;
        $page->drawText(Mage::helper('firegento_pdf')->__('Pos'),             $this->margin['left'] + 3,         $this->y, $this->encoding);
        $page->drawText(Mage::helper('firegento_pdf')->__('No.'),             $this->margin['left'] + 25,     $this->y, $this->encoding);
        $page->drawText(Mage::helper('firegento_pdf')->__('Description'),     $this->margin['left'] + 120,     $this->y, $this->encoding);

        $singlePrice = Mage::helper('firegento_pdf')->__('Price');
        $page->drawText($singlePrice, $this->margin['right'] - 153 - $this->widthForStringUsingFontSize($singlePrice, $font, 9),     $this->y, $this->encoding);

        $page->drawText(Mage::helper('firegento_pdf')->__('Qty'),         $this->margin['left'] + 360,     $this->y, $this->encoding);

        $taxLabel = Mage::helper('firegento_pdf')->__('Tax');
        $page->drawText($taxLabel, $this->margin['right'] - 65 - $this->widthForStringUsingFontSize($taxLabel, $font, 9), $this->y, $this->encoding);

        $totalLabel = Mage::helper('firegento_pdf')->__('Total');
        $page->drawText($totalLabel, $this->margin['right'] - 10 - $this->widthForStringUsingFontSize($totalLabel, $font, 10),     $this->y, $this->encoding);
    }

    /**
     * Insert Header
     *
     * @param objet $page     Current Page Object of Zend_PDF
     * @param objet $order    Order object
     * @param objet $invoice  Invoice object	  
     *
     * @return void
     */
    protected function insertHeader(&$page, $order, $invoice)
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

        $page->drawText(Mage::helper('firegento_pdf')->__('Invoice date:'), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);

        $this->y += $yPlus;
        $rightoffset = 60;
        $page->drawText($invoice->getIncrementId(), ($this->margin['right'] - $rightoffset), $this->y, $this->encoding);
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
        
        if(Mage::getStoreConfig('sales_pdf/invoice/showcustomerip')) {
            $customerIP = $order->getData('remote_ip');
            $font = $this->_setFontRegular($page, 10);
            $page->drawText($customerIP, ($this->margin['right'] - $rightoffset - $this->widthForStringUsingFontSize($customerIP, $font, 10)), $this->y, $this->encoding);
            $this->Ln();
        }

        $invoiceDate = Mage::helper('core')->formatDate($invoice->getCreatedAtDate(), 'medium', false);
        $page->drawText($invoiceDate, ($this->margin['right'] - $rightoffset - $this->widthForStringUsingFontSize($invoiceDate, $font, 10)), $this->y, $this->encoding);

    }
    
    /**
     * Insert Sender Address Bar over the Billing Address
     *
     * @param objet $page  Current Page Object of Zend_PDF
     *
     * @return void
     */
    protected function insertSenderAddessBar(&$page) {

        if(Mage::getStoreConfig('sales_pdf/firegento_pdf/sender_address_bar') != "") {
            
            $this->_setFontRegular($page, 6);
            
            $page->drawText(trim(Mage::getStoreConfig('sales_pdf/firegento_pdf/sender_address_bar')), $this->margin['left'], $this->y, $this->encoding);
            
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
     * Insert Footer
     *
     * @param object $page   Current Page Object of Zend_PDF
     * @param Array  $fields Fields of Footer
     * @param string  $colposition Starting Colposition
     * @param string  $valadjust Margin between Label and Value
     *
     * @return void
     */
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

    /**
     * Insert Addess of Owner
     *
     * @param object $page   Current Page Object of Zend_PDF
     * @param string $store  Store ID
     *
     * @return void
     */
    protected function insertFooterAddress(&$page, $store = null)
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
     * Insert Shop Logo
     *
     * @param object $page   Current Page Object of Zend_PDF
     * @param string $store  Store ID
     *
     * @return void
     */
    protected function insertLogo(&$page, $store = null)
    {
        $maxwidth = 300;
        $maxheight = 100;

        $image = Mage::getStoreConfig('sales/identity/logo', $store);
        if ($image and file_exists(Mage::getBaseDir('media', $store) . '/sales/store/logo/' . $image)) {
            $image = Mage::getBaseDir('media', $store) . '/sales/store/logo/' . $image;

            $size = getimagesize($image);

            $width = $size[0];
            $height = $size[1];

            if ($width > $height) {
                $ratio = $width / $height;
            }
            elseif ($height > $width) {
                $ratio = $height / $width;
            }
            else {
                $ratio = 1;
            }

            if ($height > $maxheight or $width > $maxwidth) {
                if ($height > $maxheight) {
                    $height = $maxheight;
                    $width = round($maxheight * $ratio);
                }

                if ($width > $maxwidth) {
                    $width = $maxheight;
                    $height = round($maxwidth * $ratio);
                }
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
                $position['y1'] = 742;
                $position['x2'] = $position['x1'] + $width;
                $position['y2'] = $position['y1'] + $height;

                $page->drawImage($image, $position['x1'], $position['y1'], $position['x2'], $position['y2']);
            
            }
        }
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
            if (((float)$item['tax_amount'] > 0)&&((float)$item['row_invoiced'] > 0)) {
                $_percent = round((float)$item['tax_amount'] / (float)$item['row_invoiced'] * 100,0);
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

    /**
     * Set next Line Position
     *
     * @param string $height   Line-Height
     *
     * @return void
     */
    protected function Ln($height=15)
    {
        $this->y -= $height;
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
     * Set default font
     *
     * @param object $object   Current Page Object of Zend_PDF
     * @param string $size     Font size
     *
     * @return void
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
     * Set Mode
     *
     * @param string $mode     PDF Mode
     *
     * @return void
     */
    public function setMode($mode = 'invoice')
    {
        $this->mode = $mode;
    }

    /**
     * Get Mode
     *
     * @return string $mode    PDF-Mode
     */
    public function getMode()
    {
        return $this->mode;
    }
	
    /**
     * Generate new PDF Page
     *
     * @param array $setting   page settings
     *
     * @return object $page    PDF page object
     */
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

    /**
     * 
     *
     * @param object $page     Current Page Object of Zend_PDF
     * @param array  $draw     
     * @param array  $pageSettings     
     *
     * @return object $page  PDF Page Object
     */
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
                    $fontSize  = empty($column['font_size']) ? 7 : $column['font_size'];
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

}