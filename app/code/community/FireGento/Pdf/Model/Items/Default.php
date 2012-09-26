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
 * Default item model rewrite.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2012 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_Pdf_Model_Items_Default extends Mage_Sales_Model_Order_Pdf_Items_Invoice_Default
{
    public function draw($position = 1)
    {
        $order  = $this->getOrder();
        $item   = $this->getItem();
        $pdf    = $this->getPdf();
        $page   = $this->getPage();
        $lines  = array();

        $fontSize = 9;

        // draw Position Number
        $lines[0]= array(array(
            'text'  => $position,
            'feed'  => $pdf->margin['left'] + 10,
            'align' => 'right',
            'font_size' => $fontSize
        ));

        // draw SKU
        $lines[0][] = array(
            'text'  => Mage::helper('core/string')->str_split($this->getSku($item), 30),
            'feed'  => $pdf->margin['left'] + 25,
            'font_size' => $fontSize
        );

        // draw Product name
        $lines[0][]= array(
            'text' => Mage::helper('core/string')->str_split($item->getName(), 30, true, true),
            'feed' => $pdf->margin['left'] + 120,
            'font_size' => $fontSize
        );

        // draw QTY
        $lines[0][] = array(
            'text'  => $item->getQty() * 1,
            'feed'  => $pdf->margin['right'] - 120,
            'align' => 'right',
            'font_size' => $fontSize
        );

        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                // draw options label
                $lines[][] = array(
                    'text' => Mage::helper('core/string')->str_split(strip_tags($option['label']), 40, false, true),
                    'font' => 'bold',
                    'feed' => $pdf->margin['left'] + 120
                );

                // draw options value
                if ($option['value']) {
                    $_printValue = isset($option['print_value']) ? $option['print_value'] : strip_tags($option['value']);
                    $values = explode(', ', $_printValue);
                    foreach ($values as $value) {
                        $lines[][] = array(
                            'text' => Mage::helper('core/string')->str_split($value, 60, true, true),
                            'feed' => $pdf->margin['left'] + 120
                        );
                    }
                }
            }
        }

        // draw Price
        $lines[0][] = array(
            'text'  => $order->formatPriceTxt($item->getPrice()),
            'feed'  => $pdf->margin['right'] - 160,
            'align' => 'right',
            'font_size' => $fontSize
        );

        // draw Tax
        $lines[0][] = array(
            'text'  => $order->formatPriceTxt($item->getTaxAmount()),
            'feed'  => $pdf->margin['right'] - 60,
            'align' => 'right',
            'font_size' => $fontSize
        );

        // draw Subtotal
        $lines[0][] = array(
            'text' => $order->formatPriceTxt(($item->getPrice() * $item->getQty() * 1) + $item->getTaxAmount()),
            'feed'  => $pdf->margin['right'] - 10,
            'align' => 'right',
            'font_size' => $fontSize
        );

        $lineBlock = array(
            'lines'  => $lines,
            'height' => 15
        );

        $page = $pdf->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $this->setPage($page);
    }
}