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
 * Creditmemo model rewrite.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Model_Engine_Creditmemo_Default extends FireGento_Pdf_Model_Engine_Abstract
{

    /**
     * constructor to set mode to creditmemo
     */
    public function __construct()
    {
        parent::__construct();
        $this->setMode('creditmemo');
    }

    /**
     * Return PDF document
     *
     * @param  array $creditmemos creditmemos to generate pdfs for
     *
     * @return Zend_Pdf
     */
    public function getPdf($creditmemos = array())
    {
        $currentStore = Mage::app()->getStore()->getCode();
        $this->_beforeGetPdf();
        $this->_initRenderer('creditmemo');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);

        foreach ($creditmemos as $creditmemo) {
            // pagecounter is 0 at the beginning, because it is incremented in newPage()
            $this->pagecounter = 0;
            if ($creditmemo->getStoreId()) {
                Mage::app()->getLocale()->emulate($creditmemo->getStoreId());
                Mage::app()->setCurrentStore($creditmemo->getStoreId());
            }
            $order = $creditmemo->getOrder();
            $this->setOrder($order);

            $page = $this->newPage(array());

            $this->insertAddressesAndHeader($page, $creditmemo, $order);

            $this->_setFontRegular($page, 9);
            $this->_drawHeader($page);

            $this->y -= 20;

            $position = 0;

            foreach ($creditmemo->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /* Draw item */
                $position++;
                $this->_drawItem($item, $page, $order, $position);
                $page = end($pdf->pages);
            }

            /* add line after items */
            $page->drawLine($this->margin['left'], $this->y + 5, $this->margin['right'], $this->y + 5);

            /* Add totals */
            $page = $this->insertTotals($page, $creditmemo);

            /* add note */
            $page = $this->_insertNote($page, $order, $creditmemo);

            // Add footer
            $this->_addFooter($page, $creditmemo->getStore());

            if ($creditmemo->getStoreId()) {
                Mage::app()->getLocale()->revert();
            }
        }

        // Revert back to the original current store
        Mage::app()->setCurrentStore($currentStore);

        $this->_afterGetPdf();

        return $pdf;
    }

    /**
     * Draw table header for product items
     *
     * @param  Zend_Pdf_Page $page page to draw on
     *
     * @return void
     */
    protected function _drawHeader(Zend_Pdf_Page $page)
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
     * Initialize renderer process.
     *
     * @param  string $type renderer type to initialize
     *
     * @return void
     */
    protected function _initRenderer($type)
    {
        parent::_initRenderer($type);

        $this->_renderers['default'] = array(
            'model'    => 'firegento_pdf/items_default',
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
        $this->_renderers['ugiftcert'] = array(
            'model'    => 'firegento_pdf/items_unirgy_default',
            'renderer' => null
        );
    }

}
