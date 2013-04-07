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
 * Dummy data helper for translation issues.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_Pdf_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Return the order id or false if order id should not be displayed on document.
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $mode
     * @return mixed
     */
    public function putOrderId(Mage_Sales_Model_Order $order, $mode = 'invoice')
    {
        switch ($mode) {
            case 'invoice':
                if (Mage::getStoreConfigFlag(Mage_Sales_Model_Order_Pdf_Abstract::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID, $order->getStoreId())) {
                    return $order->getRealOrderId();
                }
                break;

            case 'shipment':
                if (Mage::getStoreConfigFlag(Mage_Sales_Model_Order_Pdf_Abstract::XML_PATH_SALES_PDF_SHIPMENT_PUT_ORDER_ID, $order->getStoreId())) {
                    return $order->getRealOrderId();
                }
                break;

            case 'creditmemo':
                if (Mage::getStoreConfigFlag(Mage_Sales_Model_Order_Pdf_Abstract::XML_PATH_SALES_PDF_CREDITMEMO_PUT_ORDER_ID, $order->getStoreId())) {
                    return $order->getRealOrderId();
                }
                break;
        }
        return false;
    }

    /**
     * Return scaled image sizes based on an path to an image file.
     *
     * @param string $image Url to image file.
     * @param int $maxWidth
     * @param int $maxHeight
     * @return array with 2 elements - width and height.
     */
    public function getScaledImageSize($image, $maxWidth, $maxHeight)
    {
        list($width, $height) = getimagesize($image);

        if ($height > $maxHeight or $width > $maxWidth) {
            // Calculate max variance to match dimensions.
            $widthVar = $width / $maxWidth;
            $heightVar = $height / $maxHeight;

            // Calculate scale factor to match dimensions.
            if ($widthVar > $heightVar) {
                $scale = $maxWidth / $width;
            } else {
                $scale = $maxHeight / $height;
            }

            // Calculate new dimensions.
            $height = round($height * $scale);
            $width  = round($width * $scale);
        }

        return array($width, $height);
    }
}