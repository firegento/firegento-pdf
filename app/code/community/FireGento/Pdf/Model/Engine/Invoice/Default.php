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
 * Default invoice rendering engine.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_Pdf_Model_Engine_Invoice_Default extends FireGento_Pdf_Model_Abstract
{
    public $encoding;
    public $pagecounter;

    public function __construct()
    {
        parent::__construct();
        $this->setMode('invoice');
    }

    /**
     * Return PDF document
     *
     * @param  array $invoices
     * @return Zend_Pdf
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
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
                Mage::app()->setCurrentStore($invoice->getStoreId());
            }
            $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
            $pdf->pages[] = $page;

            $order = $invoice->getOrder();

            /* add logo */
            $this->insertLogo($page, $invoice->getStore());

            /* add billing address */
            $this->y = 692;
            $this->insertBillingAddress($page, $order);

            // Add sender address
            $this->y = 705;
            $this->_insertSenderAddessBar($page);

            /* add header */
            $this->y = 592;
            $this->insertHeader($page, $order, $invoice);

            // Add footer
            $this->_addFooter($page, $invoice->getStore());

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
            $page = $this->insertTotals($page, $invoice);

            /* add note */
            if ($mode == 'invoice') {
                $this->insertNote($page, $order, $invoice);
            }
        }

        $this->_afterGetPdf();

        return $pdf;
    }

    /**
     * Insert Notice after Totals
     *
     * @param Zend_Pdf_Page $page Current Page Object of Zend_PDF
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return void
     */
    protected function insertNote($page, &$order, &$invoice)
    {
        $fontSize = 10;
        $font = $this->_setFontRegular($page, $fontSize);
        $this->y = $this->y - 60;

        $notes = array();

        $result = new Varien_Object();
        $result->setNotes($notes);
        Mage::dispatchEvent('firegento_pdf_invoice_insert_note', array('order' => $order, 'invoice' => $invoice, 'result' => $result));
        $notes = array_merge($notes, $result->getNotes());

        $notes[] = Mage::helper('firegento_pdf')->__('Invoice date is equal to delivery date.');

        // Get free text notes.
        $note = Mage::getStoreConfig('sales_pdf/invoice/note');
        if (!empty($note)) {
            $tmpNotes = explode("\n", $note);
            $notes = array_merge($notes, $tmpNotes);
        }

        // Draw notes on invoice.
        foreach ($notes as $note) {
            // prepare the text so that it fits to the paper
            $note = $this->_prepareText($note, $page, $font, $fontSize);
            $tmpNotes = explode("\n", $note);
            foreach ($tmpNotes as $tmpNote) {
                $page->drawText($tmpNote, $this->margin['left'], $this->y + 30, $this->encoding);
                $this->Ln(15);
            }
        }
    }

    /**
     * Insert Table Header for Items
     *
     * @param Zend_Pdf_Page $page  Current Page Object of Zend_PDF
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

        $singlePrice = Mage::helper('firegento_pdf')->__('Price (excl. tax)');
        $page->drawText($singlePrice, $this->margin['right'] - 153 - $this->widthForStringUsingFontSize($singlePrice, $font, 9), 	$this->y, $this->encoding);

        $page->drawText(Mage::helper('firegento_pdf')->__('Qty'),         $this->margin['left'] + 360,     $this->y, $this->encoding);

        $taxLabel = Mage::helper('firegento_pdf')->__('Tax');
        $page->drawText($taxLabel, $this->margin['right'] - 65 - $this->widthForStringUsingFontSize($taxLabel, $font, 9), $this->y, $this->encoding);

        $totalLabel = Mage::helper('firegento_pdf')->__('Total');
        $page->drawText($totalLabel, $this->margin['right'] - 10 - $this->widthForStringUsingFontSize($totalLabel, $font, 10),     $this->y, $this->encoding);
    }

    /**
     * Generate new PDF page.
     *
     * @param array $settings Page settings
     * @return Zend_Pdf_Page
     */
    public function newPage(array $settings = array())
    {
        $pdf = $this->_getPdf();

        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $this->pagecounter++;
        $pdf->pages[] = $page;

        $this->_addFooter($page, Mage::app()->getStore());

        $this->y = 800;
        $this->_setFontRegular($page, 9);

        return $page;
    }

    /**
     * ...
     *
     * @param Zend_Pdf_Page $page Current page object of Zend_Pdf
     * @param array $draw
     * @param array $pageSettings
     * @return Zend_Pdf_Page
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

    /**
     * Return status of the engine.
     *
     * @return bool
     */
    public function test()
    {
        return true;
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
            'model' => 'firegento_pdf/items_default',
            'renderer' => null
        );
        $this->_renderers['grouped'] = array(
            'model' => 'firegento_pdf/items_grouped',
            'renderer' => null
        );
        $this->_renderers['bundle'] = array(
            'model' => 'firegento_pdf/items_bundle',
            'renderer' => null
        );
    }
}
