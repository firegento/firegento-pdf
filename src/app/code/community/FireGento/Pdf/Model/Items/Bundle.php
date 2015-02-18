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
 * Bundle item model rewrite.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Model_Items_Bundle extends Mage_Bundle_Model_Sales_Order_Pdf_Items_Invoice
{
    /**
     * Draw item line.
     *
     * @param  int $position position of the product
     *
     * @return void
     */
    public function draw($position = 1)
    {
        $order = $this->getOrder();
        $item = $this->getItem();
        $pdf = $this->getPdf();
        $page = $this->getPage();

        $fontSize = 9;

        $items = $this->getChilds($item);

        $_prevOptionId = '';
        $drawItems = array();

        foreach ($items as $_item) {
            $line = array();

            $attributes = $this->getSelectionAttributes($_item);
            if (is_array($attributes)) {
                $optionId = $attributes['option_id'];
            } else {
                $optionId = 0;
            }

            if (!isset($drawItems[$optionId])) {
                $drawItems[$optionId] = array(
                    'lines'  => array(),
                    'height' => 15
                );
            }

            if ($_item->getOrderItem()->getParentItem()) {
                if ($_prevOptionId != $attributes['option_id']) {
                    $line[0] = array(
                        'font'  => 'italic',
                        'text'  => Mage::helper('core/string')->str_split($attributes['option_label'], 45, true, true),
                        'feed'  => $pdf->margin['left'] + 130,
                        'font_size' => $fontSize
                    );

                    $drawItems[$optionId] = array(
                        'lines'  => array($line),
                        'height' => 15
                    );

                    $line = array();

                    $_prevOptionId = $attributes['option_id'];
                }
            }

            // draw SKUs
            if (!$_item->getOrderItem()->getParentItem()) {
                $text = array();
                foreach (Mage::helper('core/string')->str_split($item->getSku(), 17) as $part) {
                    $text[] = $part;
                }

                // draw Position Number
                $line[] = array(
                    'text'      => $position,
                    'feed'      => $pdf->margin['left'] + 10,
                    'align'     => 'right',
                    'font_size' => $fontSize
                );

                $line[] = array(
                    'text'      => $text,
                    'feed'      => $pdf->margin['left'] + 25,
                    'font_size' => $fontSize
                );
            }

            /* in case Product name is longer than 80 chars - it is written in a few lines */
            if ($_item->getOrderItem()->getParentItem()) {
                $name = $this->getValueHtml($_item);
            } else {
                $name = $_item->getName();
            }
            $line[] = array(
                'text'  => Mage::helper('core/string')->str_split($name, 35, true, true),
                'feed'  => $pdf->margin['left'] + 130,
                'font_size' => $fontSize
            );

            // draw prices
            if ($this->canShowPriceInfo($_item)) {
                $columns = array();
                // prepare qty
                $columns['qty'] = array(
                    'text'      => $item->getQty() * 1,
                    'align'     => 'right',
                    'font_size' => $fontSize,
                    '_width' => 30
                );

                // prepare price
                $columns['price'] = array(
                    'text'      => $order->formatPriceTxt($item->getPrice()),
                    'align'     => 'right',
                    'font_size' => $fontSize,
                    '_width'    => 60
                );

                // prepare price_incl_tax
                $columns['price_incl_tax'] = array(
                    'text'      => $order->formatPriceTxt($item->getPriceInclTax()),
                    'align'     => 'right',
                    'font_size' => $fontSize,
                    '_width'    => 60
                );

                // prepare tax
                $columns['tax'] = array(
                    'text'      => $order->formatPriceTxt($item->getTaxAmount() + $item->getHiddenTaxAmount()),
                    'align'     => 'right',
                    'font_size' => $fontSize,
                    '_width'    => 50
                );

                // prepare tax_rate
                $columns['tax_rate'] = array(
                    'text'      => round($item->getOrderItem()->getTaxPercent(), 2) . '%',
                    'align'     => 'right',
                    'font_size' => $fontSize,
                    '_width'    => 50
                );

                // prepare subtotal
                $columns['subtotal'] = array(
                    'text'      => $order->formatPriceTxt($item->getRowTotal()),
                    'align'     => 'right',
                    'font_size' => $fontSize,
                    '_width'    => 50
                );

                // prepare subtotal_incl_tax
                $columns['subtotal_incl_tax'] = array(
                    'text'      => $order->formatPriceTxt($item->getRowTotalInclTax()),
                    'align'     => 'right',
                    'font_size' => $fontSize,
                    '_width'    => 70
                );

                // draw columns in specified order
                $columnsOrder = explode(',', Mage::getStoreConfig('sales_pdf/invoice/item_price_column_order'));
                // draw starting from right
                $columnsOrder = array_reverse($columnsOrder);
                $columnOffset = 0;
                foreach ($columnsOrder as $columnName) {
                    $columnName = trim($columnName);
                    if (array_key_exists($columnName, $columns)) {
                        $column = $columns[$columnName];
                        $column['feed'] = $pdf->margin['right'] - $columnOffset;
                        $columnOffset += $column['_width'];
                        unset($column['_width']);
                        $line[] = $column;
                    }
                }
            }

            $drawItems[$optionId]['lines'][] = $line;
        }

        // custom options
        $options = $item->getOrderItem()->getProductOptions();
        if ($options) {
            if (isset($options['options'])) {
                foreach ($options['options'] as $option) {
                    $lines = array();
                    $lines[][] = array(
                        'text' => Mage::helper('core/string')->str_split(strip_tags($option['label']), 40, true, true),
                        'font' => 'italic',
                        'feed' => 35
                    );

                    if (isset($option['value'])) {
                        $text = array();
                        $_printValue = isset($option['print_value'])
                            ? $option['print_value']
                            : strip_tags($option['value']);
                        $values = explode(', ', $_printValue);
                        foreach ($values as $value) {
                            foreach (Mage::helper('core/string')->str_split($value, 30, true, true) as $_value) {
                                $text[] = $_value;
                            }
                        }

                        $lines[][] = array(
                            'text' => $text,
                            'feed' => 40
                        );
                    }

                    $drawItems[] = array(
                        'lines'  => $lines,
                        'height' => 15
                    );
                }
            }
        }

        $page = $pdf->drawLineBlocks($page, $drawItems, array('table_header' => true));
        $this->setPage($page);
    }
}
