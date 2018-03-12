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
 * Shipment bundle item model.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Model_Tax_Sales_Pdf_Grandtotal extends Mage_Tax_Model_Sales_Pdf_Grandtotal
{

    const NO_SUM_ON_DETAILS = 'tax/sales_display/no_sum_on_details';
    const HIDE_GRANDTOTAL_EXCL_TAX = 'tax/sales_display/hide_grandtotal_excl_tax';

    /**
     * Check if tax amount should be included to grandtotals block
     * array(
     *  $index => array(
     *      'amount'   => $amount,
     *      'label'    => $label,
     *      'font_size'=> $font_size
     *  )
     * )
     *
     * @return array
     */
    public function getTotalsForDisplay()
    {
        $store = $this->getOrder()->getStore();
        $config = Mage::getSingleton('tax/config');
        $noDisplaySumOnDetails = Mage::getStoreConfig(self::NO_SUM_ON_DETAILS, $store);
        $hideGrandTotalExclTax = Mage::getStoreConfig(self::HIDE_GRANDTOTAL_EXCL_TAX, $store);
        if (!$config->displaySalesTaxWithGrandTotal($store)) {
            return parent::getTotalsForDisplay();
        }

        $amount = $this->getOrder()->formatPriceTxt($this->getAmount());
        $amountExclTax = $this->getAmount() - $this->getSource()->getTaxAmount();
        $amountExclTax = ($amountExclTax > 0) ? $amountExclTax : 0;
        $amountExclTax = $this->getOrder()->formatPriceTxt($amountExclTax);
        $tax = $this->getOrder()->formatPriceTxt($this->getSource()->getTaxAmount());
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;

        $totals = array();
        if (!$hideGrandTotalExclTax) {
            $totals[] = array(
                'amount' => $this->getAmountPrefix() . $amountExclTax,
                'label' => Mage::helper('tax')->__('Grand Total (Excl. Tax)') . ':',
                'font_size' => $fontSize
            );
        }

        /**
         * if display_sales_full_summary = 1
         * display each tax group
         * if no_sum_on_details is = 1 display tax total additionally
         * else display only tax total
         */
        if ($config->displaySalesFullSummary($store)) {
            $totals = array_merge($totals, $this->getFullTaxInfo());
            if (!$noDisplaySumOnDetails) {
                $totals[] = array(
                    'amount' => $this->getAmountPrefix() . $tax,
                    'label' => Mage::helper('tax')->__('Tax') . ':',
                    'font_size' => $fontSize
                );
            }
        } else {
            $totals[] = array(
                'amount' => $this->getAmountPrefix() . $tax,
                'label' => Mage::helper('tax')->__('Tax') . ':',
                'font_size' => $fontSize
            );
        }

        $totals[] = array(
            'amount' => $this->getAmountPrefix() . $amount,
            'label' => Mage::helper('tax')->__('Grand Total (Incl. Tax)') . ':',
            'font_size' => $fontSize
        );
        return $totals;
    }
}
