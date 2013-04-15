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

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);

        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        // pagecounter is 0 at the beginning, because it is incremented in newPage()
        $this->pagecounter = 0;

        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
                Mage::app()->setCurrentStore($invoice->getStoreId());
            }
            $page = $this->newPage();

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

                if ($this->y < 100) {
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
            $this->_insertNote($page, $order, $invoice);
        }

        $this->_afterGetPdf();

        return $pdf;
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
