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
 * Default invoice rendering engine.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Model_Engine_Order_Default extends FireGento_Pdf_Model_Engine_Abstract
{

    /**
     * constructor to set mode to invoice
     */
    public function __construct()
    {
        parent::__construct();
        $this->setMode('order');
    }

    /**
     * Return PDF document
     *
     * @param  array $orders invoices to render pdfs for
     *
     * @return Zend_Pdf
     */
    public function getPdf($orders = array())
    {

        $this->_beforeGetPdf();

        $this->_initRenderer('order');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);

        foreach($orders as $order)
        {
            /**
             * @var Mage_Sales_Model_Order $order
             */
            // pagecounter is 0 at the beginning, because it is incremented in newPage()
            $this->pagecounter = 0;
            if ($order->getStoreId()) {
                Mage::app()->getLocale()->emulate($order->getStoreId());
                Mage::app()->setCurrentStore($order->getStoreId());
            }
            $this->setOrder($order);

            $page = $this->newPage();

            $this->insertAddressesAndHeader($page, $order, $order);

            $this->_setFontRegular($page, 9);

            $this->insertTableHeader($page);

            $this->y -= 20;

            $position = 0;
            foreach($order->getAllItems() as $item)
            {
                /**
                 * @var Mage_Sales_Model_Order_Item $item
                 */
                if($item->getParentItem()) {
                    continue;
                }

                $showFooter = Mage::getStoreConfig('sales_pdf/firegento_pdf/show_footer');
                if ($this->y < 50 || ($showFooter == 1 && $this->y < 100)) {
                    $page = $this->newPage(array());
                }

                $position++;
                $page = $this->_drawItem($item, $page, $order, $position);
            }

            /* add line after items */
            $page->drawLine($this->margin['left'], $this->y + 5, $this->margin['right'], $this->y + 5);

            /* add totals */
            $page = $this->insertTotals($page, $order);

            /* add note */
            $page = $this->_insertNote($page, $order, $order);

            // Add footer
            $this->_addFooter($page, $order->getStore());
        }

        $this->_afterGetPdf();

        return $pdf;
    }

    /**
     * Draw
     *
     * @param  Varien_Object          $item     creditmemo/shipping/invoice to draw
     * @param  Zend_Pdf_Page          $page     Current page object of Zend_Pdf
     * @param  Mage_Sales_Model_Order $order    order to get infos from
     * @param  int                    $position position in table
     *
     * @return Zend_Pdf_Page
     */
    protected function _drawItem(
        Varien_Object $item,
        Zend_Pdf_Page $page,
        Mage_Sales_Model_Order $order,
        $position = 1
    ) {
        $type = $item->getProductType();

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
     * Insert Table Header for Items
     *
     * @param  Zend_Pdf_Page &$page current page object of Zend_PDF
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
        $page->drawText(Mage::helper('firegento_pdf')->__('Pos'), $this->margin['left'] + 3, $this->y, $this->encoding);
        $page->drawText(
            Mage::helper('firegento_pdf')->__('No.'), $this->margin['left'] + 25, $this->y, $this->encoding
        );
        $page->drawText(
            Mage::helper('firegento_pdf')->__('Description'), $this->margin['left'] + 130, $this->y, $this->encoding
        );

        $columns = array();
        $columns['price'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Price'),
            '_width' => 60
        );
        $columns['price_incl_tax'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Price (incl. tax)'),
            '_width' => 60
        );
        $columns['qty'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Qty'),
            '_width' => 40
        );
        $columns['tax'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Tax'),
            '_width' => 50
        );
        $columns['tax_rate'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Tax rate'),
            '_width' => 50
        );
        $columns['subtotal'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Total'),
            '_width' => 50
        );
        $columns['subtotal_incl_tax'] = array(
            'label'  => Mage::helper('firegento_pdf')->__('Total (incl. tax)'),
            '_width' => 70
        );
        // draw price, tax, and subtotal in specified order
        $columnsOrder = explode(',', Mage::getStoreConfig('sales_pdf/invoice/item_price_column_order'));
        // draw starting from right
        $columnsOrder = array_reverse($columnsOrder);
        $columnOffset = 0;
        foreach ($columnsOrder as $columnName) {
            $columnName = trim($columnName);
            if (array_key_exists($columnName, $columns)) {
                $column = $columns[$columnName];
                $labelWidth = $this->widthForStringUsingFontSize($column['label'], $font, 9);
                $page->drawText(
                    $column['label'],
                    $this->margin['right'] - $columnOffset - $labelWidth,
                    $this->y,
                    $this->encoding
                );
                $columnOffset += $column['_width'];
            }
        }
    }

    /**
     * Insert Totals Block
     *
     * @param  object $page   Current page object of Zend_Pdf
     * @param  Mage_Sales_Model_Abstract $source
     *
     * @return Zend_Pdf_Page
     */
    protected function insertTotals($page, $source)
    {
        $this->y -= 15;

        $totalTax = 0;
        $shippingTaxRate = 0;
        $shippingTaxAmount = $source->getShippingTaxAmount();

        if ($shippingTaxAmount > 0) {
            $shippingTaxRate
                = $source->getShippingTaxAmount() * 100
                / ($source->getShippingInclTax()
                    - $source->getShippingTaxAmount());
        }

        $groupedTax = array();

        $items['items'] = array();
        foreach ($source->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $items['items'][] = $item->toArray();
        }

        array_push(
            $items['items'], array(
                'row_invoiced'     => $source->getShippingInvoiced(),
                'tax_inc_subtotal' => false,
                'tax_percent'      => $shippingTaxRate,
                'tax_amount'       => $shippingTaxAmount
            )
        );

        foreach ($items['items'] as $item) {
            $_percent = null;
            if (!isset($item['tax_amount'])) {
                $item['tax_amount'] = 0;
            }
            if (!isset($item['row_invoiced'])) {
                $item['row_invoiced'] = 0;
            }
            if (!isset($item['price'])) {
                $item['price'] = 0;
            }
            if (!isset($item['tax_inc_subtotal'])) {
                $item['tax_inc_subtotal'] = 0;
            }
            if (((float)$item['tax_amount'] > 0)
                && ((float)$item['row_invoiced'] > 0)
            ) {
                $_percent = round($item["tax_percent"], 0);
            }
            if (!array_key_exists('tax_inc_subtotal', $item)
                || $item['tax_inc_subtotal']
            ) {
                $totalTax += $item['tax_amount'];
            }
            if (($item['tax_amount']) && $_percent) {
                if (!array_key_exists((int)$_percent, $groupedTax)) {
                    $groupedTax[$_percent] = $item['tax_amount'];
                } else {
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
            /**@var Mage_Sales_Model_Order_Pdf_Total_Default $total */
            /**@var Mage_Tax_Model_Sales_Pdf_Subtotal $total */
            $total->setOrder($source)->setSource($source);

            if ($total->canDisplay())
            {
                $total->setFontSize(10);
                // fix Magento 1.8 bug, so that taxes for shipping do not appear twice
                // see https://github.com/firegento/firegento-pdf/issues/106
                $uniqueTotalsForDisplay = array_map(
                    'unserialize', array_unique(array_map('serialize',
                        $total->getTotalsForDisplay()))
                );
                foreach ($uniqueTotalsForDisplay as $totalData)
                {
                    $label = $this->fixNumberFormat($totalData['label']);
                    $lineBlock['lines'][] = array(
                        array(
                            'text'      => $label,
                            'feed'      => $this->margin['right'] - 70,
                            'align'     => 'right',
                            'font_size' => $totalData['font_size']
                        ),
                        array(
                            'text'      => $totalData['amount'],
                            'feed'      => $this->margin['right'],
                            'align'     => 'right',
                            'font_size' => $totalData['font_size']
                        ),
                    );
                }
            }
        }
        $page = $this->drawLineBlocks($page, array($lineBlock));

        return $page;
    }


    /**
     * Initialize renderer process
     *
     * @param  string $type renderer type to be initialized
     *
     * @return void
     */
    protected function _initRenderer($type)
    {
        parent::_initRenderer($type);

        $this->_renderers['default'] = array(
            'model'    => 'firegento_pdf/items_order_default',
            'renderer' => null
        );
        $this->_renderers['grouped'] = array(
            'model'    => 'firegento_pdf/items_grouped',
            'renderer' => null
        );
        $this->_renderers['bundle'] = array(
            'model'    => 'firegento_pdf/items_bundle',
            'renderer' => null
        );
        $this->_renderers['downloadable'] = array(
            'model'    => 'firegento_pdf/items_downloadable',
            'renderer' => null
        );
    }

}
