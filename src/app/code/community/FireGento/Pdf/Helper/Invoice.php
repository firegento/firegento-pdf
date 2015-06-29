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
 * Helper for invoice creation.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Helper_Invoice extends Mage_Core_Helper_Abstract
{

    /**
     * Gets the notes for the shipping country of the given order.
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array the notes for the shipping country of the given order - may be empty!
     */
    public function getShippingCountryNotes(Mage_Sales_Model_Order $order)
    {
        if (!$order->getIsVirtual()) {
            $shippingCountryId = $order->getShippingAddress()->getCountryId();
            $countryNotes = unserialize(Mage::getStoreConfig('sales_pdf/invoice/shipping_country_notes'));

            $shippingCountryNotes = array();
            foreach ($countryNotes as $countryNote) {
                if ($countryNote['country'] == $shippingCountryId) {
                    $shippingCountryNotes[] = $countryNote['note'];
                }
            }

            return $shippingCountryNotes;
        }

        return array();
    }

}
