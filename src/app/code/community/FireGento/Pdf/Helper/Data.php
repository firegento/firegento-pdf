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
 * Dummy data helper for translation issues.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_FIREGENTO_PDF_LOGO_POSITION = 'sales_pdf/firegento_pdf/logo_position';
    const XML_PATH_SALES_PDF_INVOICE_SHOW_CUSTOMER_NUMBER = 'sales_pdf/invoice/show_customer_number';
    const XML_PATH_SALES_PDF_SHIPMENT_SHOW_CUSTOMER_NUMBER = 'sales_pdf/shipment/show_customer_number';
    const XML_PATH_SALES_PDF_CREDITMEMO_SHOW_CUSTOMER_NUMBER = 'sales_pdf/creditmemo/show_customer_number';
    const XML_PATH_SALES_PDF_INVOICE_SHOW_CUSTOMER_VATNUMBER = 'sales_pdf/invoice/show_customer_vatnumber';
    const XML_PATH_SALES_PDF_SHIPMENT_SHOW_CUSTOMER_VATNUMBER = 'sales_pdf/shipment/show_customer_vatnumber';
    const XML_PATH_SALES_PDF_CREDITMEMO_SHOW_CUSTOMER_VATNUMBER = 'sales_pdf/creditmemo/show_customer_vatnumber';
    const XML_PATH_SALES_PDF_INVOICE_GUEST_ORDER_CUSTOMER_NUMBER = 'sales_pdf/invoice/guestorder_customer_number';
    const XML_PATH_SALES_PDF_SHIPMENT_GUEST_ORDER_CUSTOMER_NUMBER = 'sales_pdf/shipment/guestorder_customer_number';
    const XML_PATH_SALES_PDF_CREDITMEMO_GUEST_ORDER_CUSTOMER_NUMBER = 'sales_pdf/creditmemo/guestorder_customer_number';
    const XML_PATH_SALES_PDF_INVOICE_FILENAME_EXPORT_PATTERN = 'sales_pdf/invoice/filename_export_pattern';
    const XML_PATH_SALES_PDF_SHIPMENT_FILENAME_EXPORT_PATTERN = 'sales_pdf/shipment/filename_export_pattern';
    const XML_PATH_SALES_PDF_CREDITMEMO_FILENAME_EXPORT_PATTERN = 'sales_pdf/creditmemo/filename_export_pattern';
    const XML_PATH_SALES_PDF_INVOICE_FILENAME_EXPORT_PATTERN_FOR_MULTIPLE_DOCUMENTS = 'sales_pdf/invoice/filename_export_pattern_for_multiple_documents';
    const XML_PATH_SALES_PDF_SHIPMENT_FILENAME_EXPORT_PATTERN_FOR_MULTIPLE_DOCUMENTS = 'sales_pdf/shipment/filename_export_pattern_for_multiple_documents';
    const XML_PATH_SALES_PDF_CREDITMEMO_FILENAME_EXPORT_PATTERN_FOR_MULTIPLE_DOCUMENTS = 'sales_pdf/creditmemo/filename_export_pattern_for_multiple_documents';
    const XML_PATH_SALES_PDF_FIREGENTO_PDF_PAGE_SIZE = 'sales_pdf/firegento_pdf/page_size';

    const XML_PATH_COLOR_TEXT         = 'sales_pdf/firegento_pdf_colors/text';
    const XML_PATH_COLOR_LABELS       = 'sales_pdf/firegento_pdf_colors/labels';
    const XML_PATH_COLOR_TABLE_HEADER = 'sales_pdf/firegento_pdf_colors/table_header';
    const XML_PATH_COLOR_FOOTER       = 'sales_pdf/firegento_pdf_colors/footer';

    const XML_PATH_REGULAR_FONT = 'sales_pdf/firegento_pdf_fonts/regular_font';
    const XML_PATH_BOLD_FONT = 'sales_pdf/firegento_pdf_fonts/bold_font';
    const XML_PATH_ITALIC_FONT = 'sales_pdf/firegento_pdf_fonts/italic_font';

    const FONT_PATH_IN_MEDIA = '/firegento_pdf/fonts';

    /**
     * Return the order id or false if order id should not be displayed on document.
     *
     * @param  Mage_Sales_Model_Order $order order to get id from
     * @param  string                 $mode  differ between creditmemo, invoice, etc.
     *
     * @return mixed
     */
    public function putOrderId(Mage_Sales_Model_Order $order, $mode = 'invoice')
    {
        switch ($mode) {
            case 'invoice':
                $putOrderIdOnInvoice = Mage::getStoreConfigFlag(
                    Mage_Sales_Model_Order_Pdf_Abstract::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID,
                    $order->getStoreId()
                );
                if ($putOrderIdOnInvoice) {
                    return $order->getRealOrderId();
                }
                break;

            case 'shipment':
                $putOrderIdOnShipment = Mage::getStoreConfigFlag(
                    Mage_Sales_Model_Order_Pdf_Abstract::XML_PATH_SALES_PDF_SHIPMENT_PUT_ORDER_ID,
                    $order->getStoreId()
                );
                if ($putOrderIdOnShipment) {
                    return $order->getRealOrderId();
                }
                break;

            case 'creditmemo':
                $putOrderIdOnCreditmemo = Mage::getStoreConfigFlag(
                    Mage_Sales_Model_Order_Pdf_Abstract::XML_PATH_SALES_PDF_CREDITMEMO_PUT_ORDER_ID,
                    $order->getStoreId()
                );
                if ($putOrderIdOnCreditmemo) {
                    return $order->getRealOrderId();
                }
                break;
        }

        return false;
    }

    /**
     * Whether the logo should be shown in full width.
     *
     * @param  mixed $store store to get information from
     *
     * @return bool whether the logo should be shown in full width
     */
    public function isLogoFullWidth($store)
    {
        $configSetting = Mage::getStoreConfig(
            self::XML_PATH_FIREGENTO_PDF_LOGO_POSITION, $store
        );
        $fullWidth = FireGento_Pdf_Model_System_Config_Source_Logo::FULL_WIDTH;

        return $configSetting == $fullWidth;
    }

    /**
     * Whether the customer number should be shown.
     *
     * @param  string $mode  the mode of this document like invoice, shipment or creditmemo
     * @param  mixed  $store store to get information from
     *
     * @return bool whether the customer number should be shown
     */
    public function showCustomerNumber($mode = 'invoice', $store = null)
    {
        switch ($mode) {
            case 'invoice':
                return Mage::getStoreConfigFlag(
                    self::XML_PATH_SALES_PDF_INVOICE_SHOW_CUSTOMER_NUMBER,
                    $store
                );
            case 'shipment':
                return Mage::getStoreConfigFlag(
                    self::XML_PATH_SALES_PDF_SHIPMENT_SHOW_CUSTOMER_NUMBER,
                    $store
                );
            case 'creditmemo':
                return Mage::getStoreConfigFlag(
                    self::XML_PATH_SALES_PDF_CREDITMEMO_SHOW_CUSTOMER_NUMBER,
                    $store
                );
        }

        return true; // backwards compatibility
    }

    /**
     * Whether the customer VAT number should be shown.
     *
     * @param  string $mode  the mode of this document like invoice, shipment or creditmemo
     * @param  mixed  $store store to get information from
     *
     * @return bool whether the customer number should be shown
     */
    public function showCustomerVATNumber($mode = 'invoice', $store = null)
    {
        switch ($mode) {
            case 'invoice':
                return Mage::getStoreConfigFlag(
                    self::XML_PATH_SALES_PDF_INVOICE_SHOW_CUSTOMER_VATNUMBER,
                    $store
                );
            case 'shipment':
                return Mage::getStoreConfigFlag(
                    self::XML_PATH_SALES_PDF_SHIPMENT_SHOW_CUSTOMER_VATNUMBER,
                    $store
                );
            case 'creditmemo':
                return Mage::getStoreConfigFlag(
                    self::XML_PATH_SALES_PDF_CREDITMEMO_SHOW_CUSTOMER_VATNUMBER,
                    $store
                );
        }

        return true; // backwards compatibility
    }

    /**
     * Get customer number for guest orders.
     *
     * @param  string $mode  the mode of this document like invoice, shipment or creditmemo
     * @param  mixed  $store store to get information from
     *
     * @return string customer number for guest orders
     */
    public function getGuestorderCustomerNo($mode = 'invoice', $store = null)
    {
        switch ($mode) {
            case 'invoice':
                return trim(
                    Mage::getStoreConfigFlag(
                        self::XML_PATH_SALES_PDF_INVOICE_GUEST_ORDER_CUSTOMER_NUMBER,
                        $store
                    )
                );
            case 'shipment':
                return trim(
                    Mage::getStoreConfigFlag(
                        self::XML_PATH_SALES_PDF_SHIPMENT_GUEST_ORDER_CUSTOMER_NUMBER,
                        $store
                    )
                );
            case 'creditmemo':
                return trim(
                    Mage::getStoreConfigFlag(
                        self::XML_PATH_SALES_PDF_CREDITMEMO_GUEST_ORDER_CUSTOMER_NUMBER,
                        $store
                    )
                );
        }

        return true; // backwards compatibility
    }

    /**
     * Return scaled image sizes based on an path to an image file.
     *
     * @param  string $image     Url to image file.
     * @param  int    $maxWidth  max width the image can have
     * @param  int    $maxHeight max height the image can have
     *
     * @return array  with 2 elements - width and height.
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
            $width = round($width * $scale);
        }

        return array($width, $height);
    }

    /**
     * Return export pattern config value
     *
     * @param  string $type the type of this document like invoice, shipment or creditmemo
     *
     * @return string
     */
    public function getExportPattern($type)
    {
        switch ($type) {
            case 'invoice':
                return Mage::getStoreConfig(
                    self::XML_PATH_SALES_PDF_INVOICE_FILENAME_EXPORT_PATTERN
                );
            case 'shipment':
                return Mage::getStoreConfig(
                    self::XML_PATH_SALES_PDF_SHIPMENT_FILENAME_EXPORT_PATTERN
                );
            case 'creditmemo':
                return Mage::getStoreConfig(
                    self::XML_PATH_SALES_PDF_CREDITMEMO_FILENAME_EXPORT_PATTERN
                );
        }

        return true;
    }

    /**
     * Return export pattern for multiple documents config value
     *
     * @param  string $type the type of this document like invoice, shipment or creditmemo
     *
     * @return string
     */
    public function getExportPatternForMultipleDocuments($type)
    {
        switch ($type) {
            case 'invoice':
                return Mage::getStoreConfig(
                    self::XML_PATH_SALES_PDF_INVOICE_FILENAME_EXPORT_PATTERN_FOR_MULTIPLE_DOCUMENTS
                );
            case 'shipment':
                return Mage::getStoreConfig(
                    self::XML_PATH_SALES_PDF_SHIPMENT_FILENAME_EXPORT_PATTERN_FOR_MULTIPLE_DOCUMENTS
                );
            case 'creditmemo':
                return Mage::getStoreConfig(
                    self::XML_PATH_SALES_PDF_CREDITMEMO_FILENAME_EXPORT_PATTERN_FOR_MULTIPLE_DOCUMENTS
                );
        }

        return true;
    }

    /**
     * Gets the variables which can be used as a placeholder in the filename.
     *
     * @param  Mage_Core_Model_Abstract $model the model instance
     *
     * @return array with the variables which can be use as placeholders in the filename
     */
    public function getModelVars($model)
    {
        if (!$model instanceof Mage_Sales_Model_Order) {
            switch ($model) {
                case $model instanceof Mage_Sales_Model_Order_Invoice:
                    $specificVars = array(
                        '{{invoice_id}}' => $model->getIncrementId()
                    );
                    break;
                case $model instanceof Mage_Sales_Model_Order_Shipment:
                    $specificVars = array(
                        '{{shipment_id}}' => $model->getIncrementId()
                    );
                    break;
                case $model instanceof Mage_Sales_Model_Order_Creditmemo:
                    $specificVars = array(
                        '{{creditmemo_id}}' => $model->getIncrementId()
                    );
            }

            $order = $model->getOrder();
            $commonVars = array(
                '{{order_id}}'           => $order->getIncrementId(),
                '{{customer_id}}'        => $order->getCustomerId(),
                '{{customer_name}}'      => $order->getCustomerName(),
                '{{customer_firstname}}' => $order->getCustomerFirstname(),
                '{{customer_lastname}}'  => $order->getCustomerLastname()
            );

            return array_merge($specificVars, $commonVars);
        } else {
            return array(
                '{{order_id}}'           => $model->getIncrementId(),
                '{{customer_id}}'        => $model->getCustomerId(),
                '{{customer_name}}'      => $model->getCustomerName(),
                '{{customer_firstname}}' => $model->getCustomerFirstname(),
                '{{customer_lastname}}'  => $model->getCustomerLastname()
            );
        }
    }

    /**
     * The filename of the exported file.
     *
     * @param  string                   $type  the type of this document like invoice, shipment or creditmemo
     * @param  Mage_Core_Model_Abstract $model the model instance
     *
     * @return string the filename of the exported file
     */
    public function getExportFilename($type, $model)
    {
        $type = (!$type) ? 'invoice' : $type;
        $pattern = $this->getExportPattern($type);
        if (!$pattern) {
            if ($type == 'shipment') {
                $pattern = 'packingslip';
            } else {
                $pattern = $type;
            }

            $date = Mage::getSingleton('core/date');
            $pattern .= $date->date('Y-m-d_H-i-s');
        }

        if (substr($pattern, -4) != '.pdf') {
            $pattern = $pattern . '.pdf';
        }

        $path = strftime($pattern, strtotime($model->getCreatedAt()));
        $vars = $this->getModelVars($model);

        return strtr($path, $vars);
    }

    /**
     * The filename of the exported file if multiple documents are printed at once.
     *
     * @param string $type the type of this document like invoice, shipment or creditmemo
     *
     * @return string the filename of the exported file
     */
    public function getExportFilenameForMultipleDocuments($type)
    {
        $type = (!$type) ? 'invoice' : $type;
        $pattern = $this->getExportPatternForMultipleDocuments($type);
        if (!$pattern) {
            if ($type == 'shipment') {
                $pattern = 'packingslip';
            } else {
                $pattern = $type;
            }

            $date = Mage::getSingleton('core/date');
            $pattern .= $date->date('Y-m-d_H-i-s');
        }

        if (substr($pattern, -4) != '.pdf') {
            $pattern = $pattern . '.pdf';
        }

        return strftime($pattern);
    }

    /**
     * Returns the path where the fonts reside.
     *
     * @return string the path where the fonts reside
     */
    public function getFontPath()
    {
        return Mage::getBaseDir('media') . self::FONT_PATH_IN_MEDIA;
    }

    public function getPageSizeConfigPath()
    {
        return Mage::getStoreConfig(self::XML_PATH_SALES_PDF_FIREGENTO_PDF_PAGE_SIZE);
    }

    /**
     * Get configured PDF color
     *
     * @param string $path System config path
     * @return Zend_Pdf_Color_Html
     */
    protected function getColor($path)
    {
        return new Zend_Pdf_Color_Html('#' . trim($path), '#');
    }
    /**
     * Get text color
     *
     * @return Zend_Pdf_Color_Html
     */
    public function getTextColor()
    {
        return $this->getColor(Mage::getStoreConfig(self::XML_PATH_COLOR_TEXT));
    }
    /**
     * Get table header color
     *
     * @return Zend_Pdf_Color_Html
     */
    public function getHeaderColor()
    {
        return $this->getColor(Mage::getStoreConfig(self::XML_PATH_COLOR_TABLE_HEADER));
    }
    /**
     * Get footer color
     *
     * @return Zend_Pdf_Color_Html
     */
    public function getFooterColor()
    {
        return $this->getColor(Mage::getStoreConfig(self::XML_PATH_COLOR_FOOTER));
    }
    /**
     * Get label color
     *
     * @return Zend_Pdf_Color_Html
     */
    public function getLabelColor()
    {
        return $this->getColor(Mage::getStoreConfig(self::XML_PATH_COLOR_LABELS));
    }
}
