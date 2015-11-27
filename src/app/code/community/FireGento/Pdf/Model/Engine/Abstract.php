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
 * Abstract pdf model.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
abstract class FireGento_Pdf_Model_Engine_Abstract
    extends Mage_Sales_Model_Order_Pdf_Abstract
{
    public $margin = array('left' => 45, 'right' => 540);
    public $colors = array();
    public $mode;
    public $encoding;
    public $pagecounter;

    protected $_imprint;

    /**
     * @var int correct all y values if the logo is full width and bigger than normal
     */
    protected $_marginTop = 0;

    /**
     * constructor to init settings
     */
    public function __construct()
    {
        parent::__construct();

        $this->encoding = 'UTF-8';

        $this->colors['black'] = new Zend_Pdf_Color_GrayScale(0);
        $this->colors['grey1'] = new Zend_Pdf_Color_GrayScale(0.9);

        // get the default imprint
        $this->_imprint = Mage::getStoreConfig('general/imprint');
    }

    /**
     * Draw one line
     *
     * @param  Zend_Pdf_Page $page         Current page object of Zend_Pdf
     * @param  array         $draw         items to draw
     * @param  array         $pageSettings page settings to use for new pages
     *
     * @return Zend_Pdf_Page
     */
    public function drawLineBlocks(
        Zend_Pdf_Page $page,
        array $draw,
        array $pageSettings = array()
    ) {
        foreach ($draw as $itemsProp) {
            if (!isset($itemsProp['lines']) || !is_array($itemsProp['lines'])) {
                Mage::throwException(Mage::helper('sales')
                    ->__('Invalid draw line data. Please define "lines" array'));
            }
            $lines = $itemsProp['lines'];
            $height = isset($itemsProp['height']) ? $itemsProp['height'] : 10;

            if (empty($itemsProp['shift'])) {
                $shift = 0;
                foreach ($lines as $line) {
                    $maxHeight = 0;
                    foreach ($line as $column) {
                        $lineSpacing = !empty($column['height'])
                            ? $column['height'] : $height;
                        if (!is_array($column['text'])) {
                            $column['text'] = array($column['text']);
                        }
                        $top = count($column['text']) * $lineSpacing;

                        $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                    }
                    $shift += $maxHeight;
                }
                $itemsProp['shift'] = $shift;
            }

            if ($this->y - $itemsProp['shift'] < 50
                || (Mage::getStoreConfig('sales_pdf/firegento_pdf/show_footer')
                    == 1
                    && $this->y - $itemsProp['shift'] < 100)
            ) {
                $page = $this->newPage($pageSettings);
            }

            foreach ($lines as $line) {
                $maxHeight = 0;
                foreach ($line as $column) {
                    $fontSize = empty($column['font_size']) ? 7
                        : $column['font_size'];
                    if (!empty($column['font_file'])) {
                        $font
                            = Zend_Pdf_Font::fontWithPath($column['font_file']);
                        $page->setFont($font, $fontSize);
                    } else {
                        $fontStyle = empty($column['font']) ? 'regular'
                            : $column['font'];
                        switch ($fontStyle) {
                            case 'bold':
                                $font = $this->_setFontBold($page, $fontSize);
                                break;
                            case 'italic':
                                $font = $this->_setFontItalic($page, $fontSize);
                                break;
                            default:
                                $font = $this->_setFontRegular($page,
                                    $fontSize);
                                break;
                        }
                    }

                    if (!is_array($column['text'])) {
                        $column['text'] = array($column['text']);
                    }

                    $lineSpacing = !empty($column['height']) ? $column['height']
                        : $height;
                    $top = 0;
                    foreach ($column['text'] as $part) {
                        $feed = $column['feed'];
                        $textAlign = empty($column['align']) ? 'left'
                            : $column['align'];
                        $width = empty($column['width']) ? 0 : $column['width'];
                        switch ($textAlign) {
                            case 'right':
                                if ($width) {
                                    $feed = $this->getAlignRight($part, $feed,
                                        $width, $font, $fontSize);
                                } else {
                                    $feed = $feed
                                        - $this->widthForStringUsingFontSize($part,
                                            $font, $fontSize);
                                }
                                break;
                            case 'center':
                                if ($width) {
                                    $feed = $this->getAlignCenter($part, $feed,
                                        $width, $font, $fontSize);
                                }
                                break;
                        }
                        $page->drawText($part, $feed, $this->y - $top, 'UTF-8');
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
     * Set pdf mode.
     *
     * @param  string $mode set mode to differ between creditmemo, invoice, etc.
     *
     * @return FireGento_Pdf_Model_Engine_Abstract
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Return pdf mode.
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set next line position
     *
     * @param  int $height Line-Height
     *
     * @return void
     */
    protected function Ln($height = 15)
    {
        $this->y -= $height;
    }

    /**
     * Insert sender address bar
     *
     * @param  Zend_Pdf_Page &$page Current page object of Zend_Pdf
     *
     * @return void
     */
    protected function _insertSenderAddessBar(&$page)
    {
        if (Mage::getStoreConfig('sales_pdf/firegento_pdf/sender_address_bar')
            != ''
        ) {
            $this->_setFontRegular($page, 6);
            $page->drawText(
                trim(Mage::getStoreConfig('sales_pdf/firegento_pdf/sender_address_bar')),
                $this->margin['left'] + $this->getHeaderblockOffset(),
                $this->y, $this->encoding
            );
        }
    }

    /**
     * Insert logo
     *
     * @param  Zend_Pdf_Page &$page Current page object of Zend_Pdf
     * @param  mixed         $store store to get data from
     *
     * @return void
     */
    protected function insertLogo(&$page, $store = null)
    {
        if ($this->_isLogoFullWidth($store)) {
            $this->_insertLogoFullWidth($page, $store);
        } else {
            $this->_insertLogoPositioned($page, $store);
        }
    }

    /**
     * is the setting to show the logo full width?
     *
     * @param  mixed $store store we want the config setting from
     *
     * @return bool
     */
    protected function _isLogoFullWidth($store)
    {
        return Mage::helper('firegento_pdf')->isLogoFullWidth($store);
    }

    /**
     * Inserts the logo if it is positioned left, center or right.
     *
     * @param  Zend_Pdf_Page &$page Current page object of Zend_Pdf
     * @param  mixed         $store store to get data from
     *
     * @return void
     */
    protected function _insertLogoPositioned(&$page, $store = null)
    {
        $imageRatio
            = (int)Mage::getStoreConfig('sales_pdf/firegento_pdf/logo_ratio',
            $store);
        $imageRatio = (empty($imageRatio)) ? 100 : $imageRatio;

        $maxwidth
            = ($this->margin['right'] - $this->margin['left']) * $imageRatio
            / 100;
        $maxheight = 100;

        $image = Mage::getStoreConfig('sales/identity/logo', $store);
        if ($image and file_exists(Mage::getBaseDir('media', $store)
                . '/sales/store/logo/' . $image)
        ) {
            $image = Mage::getBaseDir('media', $store) . '/sales/store/logo/'
                . $image;

            list ($width, $height) = Mage::helper('firegento_pdf')
                ->getScaledImageSize($image, $maxwidth, $maxheight);

            if (is_file($image)) {
                $image = Zend_Pdf_Image::imageWithPath($image);

                $logoPosition
                    = Mage::getStoreConfig('sales_pdf/firegento_pdf/logo_position',
                    $store);

                switch ($logoPosition) {
                    case 'center':
                        $startLogoAt
                            = $this->margin['left'] + (($this->margin['right']
                                    - $this->margin['left']) / 2) - $width / 2;
                        break;
                    case 'right':
                        $startLogoAt = $this->margin['right'] - $width;
                        break;
                    default:
                        $startLogoAt = $this->margin['left'];
                }

                $position['x1'] = $startLogoAt;
                $position['y1'] = 720;
                $position['x2'] = $position['x1'] + $width;
                $position['y2'] = $position['y1'] + $height;

                $page->drawImage($image, $position['x1'], $position['y1'],
                    $position['x2'], $position['y2']);
            }
        }
    }

    /**
     * inserts the logo from complete left to right
     *
     * @param Zend_Pdf_Page &$page current Zend_Pdf_Page object
     * @param mixed         $store store we need the config setting from
     *
     * @todo merge _insertLogoPositioned and _insertLogoFullWidth
     */
    protected function _insertLogoFullWidth(&$page, $store = null)
    {
        $imageRatio
            = (int)Mage::getStoreConfig('sales_pdf/firegento_pdf/logo_ratio',
            $store);
        $imageRatio = (empty($imageRatio)) ? 1 : $imageRatio;

        $maxwidth = 594 * $imageRatio / 100;
        $maxheight = 300;

        $image = Mage::getStoreConfig('sales/identity/logo', $store);
        if ($image and file_exists(Mage::getBaseDir('media', $store)
                . '/sales/store/logo/' . $image)
        ) {
            $image = Mage::getBaseDir('media', $store) . '/sales/store/logo/'
                . $image;

            list ($width, $height) = Mage::helper('firegento_pdf')
                ->getScaledImageSize($image, $maxwidth, $maxheight);

            if (is_file($image)) {
                $image = Zend_Pdf_Image::imageWithPath($image);

                $logoPosition
                    = Mage::getStoreConfig('sales_pdf/firegento_pdf/logo_position',
                    $store);

                switch ($logoPosition) {
                    case 'center':
                        $startLogoAt = $this->margin['left'] +
                            (($this->margin['right'] - $this->margin['left'])
                                / 2) - $width / 2;
                        break;
                    case 'right':
                        $startLogoAt = $this->margin['right'] - $width;
                        break;
                    default:
                        $startLogoAt = 0;
                }

                $position['x1'] = $startLogoAt;
                $position['y1'] = 663;
                $position['x2'] = $position['x1'] + $width;
                $position['y2'] = $position['y1'] + $height;

                $page->drawImage($image, $position['x1'], $position['y1'],
                    $position['x2'], $position['y2']);
                $this->_marginTop = $height - 130;
            }
        }
    }

    /**
     * insert customer address and all header like customer number, etc.
     *
     * @param Zend_Pdf_Page             $page   current Zend_Pdf_Page
     * @param Mage_Sales_Model_Abstract $source source for the address information
     * @param Mage_Sales_Model_Order    $order  order to print the document for
     */
    protected function insertAddressesAndHeader(
        Zend_Pdf_Page $page,
        Mage_Sales_Model_Abstract $source,
        Mage_Sales_Model_Order $order
    ) {
        // Add logo
        $this->insertLogo($page, $source->getStore());

        // Add billing address
        $this->y = 692 - $this->_marginTop;
        $this->_insertCustomerAddress($page, $order);

        // Add sender address
        $this->y = 705 - $this->_marginTop;
        $this->_insertSenderAddessBar($page);

        // Add head
        $this->y = 592 - $this->_marginTop;
        $this->insertHeader($page, $order, $source);

        /* Add table head */
        // make sure that item table does not overlap heading
        if ($this->y > 575 - $this->_marginTop) {
            $this->y = 575 - $this->_marginTop;
        }
    }

    /**
     * Inserts the customer address. The default address is the billing address.
     *
     * @param  Zend_Pdf_Page          &$page Current page object of Zend_Pdf
     * @param  Mage_Sales_Model_Order $order Order object
     *
     * @return void
     */
    protected function _insertCustomerAddress(&$page, $order)
    {
        $this->_setFontRegular($page, 9);
        $billing = $this->_formatAddress($order->getBillingAddress()
            ->format('pdf'));
        foreach ($billing as $line) {
            $page->drawText(trim(strip_tags($line)),
                $this->margin['left'] + $this->getHeaderblockOffset(), $this->y,
                $this->encoding);
            $this->Ln(12);
        }
    }

    /**
     * get the offset to position the address block left or right
     *
     * @return int
     */
    protected function getHeaderblockOffset()
    {
        if (Mage::getStoreConfig('sales_pdf/firegento_pdf/headerblocks_position')
            == FireGento_Pdf_Model_System_Config_Source_Headerblocks::LEFT
        ) {
            $offsetAdjustment = 0;
        } else {
            $offsetAdjustment = 315;
        }

        return $offsetAdjustment;
    }

    /**
     * Insert Header
     *
     * @param  Zend_Pdf_Page          &$page    Current page object of Zend_Pdf
     * @param  Mage_Sales_Model_Order $order    Order object
     * @param  object                 $document Document object
     *
     * @return void
     */
    protected function insertHeader(&$page, $order, $document)
    {
        $page->setFillColor($this->colors['black']);

        $mode = $this->getMode();

        $this->_setFontBold($page, 15);

        if ($mode == 'invoice') {
            $title = 'Invoice';
        } elseif ($mode == 'shipment') {
            $title = 'Packingslip';
        } else {
            $title = 'Creditmemo';
        }
        $page->drawText(Mage::helper('firegento_pdf')->__($title),
            $this->margin['left'], $this->y, $this->encoding);

        $this->_setFontRegular($page);

        $this->y += 80;
        $labelRightOffset = 180 + $this->getHeaderblockOffset();

        $valueRightOffset = 10 + $this->getHeaderblockOffset();
        $font = $this->_setFontRegular($page, 10);
        $width = 80;
        $numberOfLines = 0;


        // Invoice/shipment/creditmemo Number
        if ($mode == 'invoice') {
            $numberTitle = 'Invoice number:';
        } elseif ($mode == 'shipment') {
            $numberTitle = 'Shipment number:';
        } else {
            $numberTitle = 'Creditmemo number:';
        }
        $page->drawText(
            Mage::helper('firegento_pdf')->__($numberTitle),
            ($this->margin['right'] - $labelRightOffset), $this->y,
            $this->encoding
        );

        $incrementId = $document->getIncrementId();
        $page->drawText(
            $incrementId,
            ($this->margin['right'] - $valueRightOffset
                - $this->widthForStringUsingFontSize($incrementId, $font, 10)),
            $this->y, $this->encoding
        );
        $this->Ln();
        $numberOfLines++;

        // Order Number
        $putOrderId = $this->_putOrderId($order);
        if ($putOrderId) {
            $page->drawText(
                Mage::helper('firegento_pdf')->__('Order number:'),
                ($this->margin['right'] - $labelRightOffset),
                $this->y, $this->encoding
            );
            $page->drawText(
                $putOrderId, ($this->margin['right'] - $valueRightOffset
                - $this->widthForStringUsingFontSize(
                    $putOrderId, $font, 10
                )), $this->y, $this->encoding
            );
            $this->Ln();
            $numberOfLines++;
        }

        // Customer Number
        if ($this->_showCustomerNumber($order->getStore())) {
            $guestorderCustomerNo = $this->_getGuestorderCustomerNo($order->getStore());
            if ($order->getCustomerId() != '' || $guestorderCustomerNo != '') {
                $page->drawText(
                    Mage::helper('firegento_pdf')->__('Customer number:'),
                    ($this->margin['right'] - $labelRightOffset),
                    $this->y, $this->encoding
                );
                $numberOfLines++;
            }

            if ($order->getCustomerId() != '') {

                $prefix
                    = Mage::getStoreConfig('sales_pdf/invoice/customeridprefix');

                if (!empty($prefix)) {
                    $customerid = $prefix . $order->getCustomerId();
                } else {
                    $customerid = $order->getCustomerId();
                }

                $page->drawText(
                    $customerid, ($this->margin['right'] - $valueRightOffset
                    - $this->widthForStringUsingFontSize(
                        $customerid, $font, 10
                    )), $this->y, $this->encoding
                );
                $this->Ln();
                $numberOfLines++;
            } elseif ($guestorderCustomerNo != '') {
                $page->drawText(
                    $guestorderCustomerNo,
                    ($this->margin['right'] - $valueRightOffset
                        - $this->widthForStringUsingFontSize('-', $font, 10)),
                    $this->y, $this->encoding
                );
                $this->Ln();
                $numberOfLines++;
            }
        }

        /** print VAT ID */
        if ($this->_showCustomerVATNumber($order->getStore())) {
            $page->drawText(
                Mage::helper('firegento_pdf')->__('VAT-ID:'),
                ($this->margin['right'] - $labelRightOffset),
                $this->y, $this->encoding
            );
            if ($order->getBillingAddress()->getVatId()) {
                $customerVatId = $order->getBillingAddress()->getVatId();
            } elseif ($order->getCustomerTaxvat()) {
                $customerVatId = $order->getCustomerTaxvat();
            } else {
                $customerVatId = '-';
            }
            $font = $this->_setFontRegular($page, 10);
            $page->drawText(
                $customerVatId, ($this->margin['right'] - $valueRightOffset
                - $this->widthForStringUsingFontSize(
                    $customerVatId, $font, 10
                )), $this->y, $this->encoding
            );
            $this->Ln();
            $numberOfLines++;
        }
        /** end VAT ID print*/

        // Customer IP
        if (!Mage::getStoreConfigFlag('sales/general/hide_customer_ip',
            $order->getStoreId())
        ) {
            $page->drawText(
                Mage::helper('firegento_pdf')->__('Customer IP:'),
                ($this->margin['right'] - $labelRightOffset),
                $this->y, $this->encoding
            );
            $customerIP = $order->getData('remote_ip');
            $font = $this->_setFontRegular($page, 10);
            $page->drawText(
                $customerIP, ($this->margin['right'] - $valueRightOffset
                - $this->widthForStringUsingFontSize(
                    $customerIP, $font, 10
                )), $this->y, $this->encoding
            );
            $this->Ln();
            $numberOfLines++;
        }

        $page->drawText(
            Mage::helper('firegento_pdf')->__(($mode == 'invoice')
                ? 'Invoice date:' : 'Date:'),
            ($this->margin['right'] - $labelRightOffset), $this->y,
            $this->encoding
        );
        $documentDate = Mage::helper('core')
            ->formatDate($document->getCreatedAtDate(), 'medium', false);
        $page->drawText(
            $documentDate,
            ($this->margin['right'] - $valueRightOffset
                - $this->widthForStringUsingFontSize($documentDate, $font, 10)),
            $this->y, $this->encoding
        );
        $this->Ln();
        $numberOfLines++;


        // Payment method.
        $putPaymentMethod = ($mode == 'invoice'
            && Mage::getStoreConfig('sales_pdf/invoice/payment_method_position')
            == FireGento_Pdf_Model_System_Config_Source_Payment::POSITION_HEADER);
        if ($putPaymentMethod) {
            $page->drawText(
                Mage::helper('firegento_pdf')->__('Payment method:'),
                ($this->margin['right'] - $labelRightOffset),
                $this->y, $this->encoding
            );
            $paymentMethodArray = $this->_prepareText(
                $order->getPayment()->getMethodInstance()->getTitle(), $page,
                $font, 10, $width
            );
            $paymentMethod = array_shift($paymentMethodArray);
            $page->drawText(
                $paymentMethod,
                ($this->margin['right'] - $valueRightOffset
                    - $this->widthForStringUsingFontSize($paymentMethod, $font, 10)),
                $this->y, $this->encoding
            );
            $this->Ln();
            $numberOfLines++;
            $paymentMethodArray = $this->_prepareText(implode(" ",
                $paymentMethodArray), $page, $font, 10, 2 * $width);
            foreach ($paymentMethodArray as $methodString) {
                $page->drawText($methodString,
                    $this->margin['right'] - $labelRightOffset, $this->y,
                    $this->encoding);
                $this->Ln();
                $numberOfLines++;
            }

        }

        // Shipping method.
        $putShippingMethod = ($mode == 'invoice'
            && Mage::getStoreConfig('sales_pdf/invoice/shipping_method_position')
            == FireGento_Pdf_Model_System_Config_Source_Shipping::POSITION_HEADER
            || $mode == 'shipment'
            && Mage::getStoreConfig('sales_pdf/shipment/shipping_method_position')
            == FireGento_Pdf_Model_System_Config_Source_Shipping::POSITION_HEADER);

        if ($putShippingMethod && $order->getIsNotVirtual()) {
            $page->drawText(
                Mage::helper('firegento_pdf')->__('Shipping method:'),
                ($this->margin['right'] - $labelRightOffset),
                $this->y, $this->encoding
            );
            $shippingMethodArray
                = $this->_prepareText($order->getShippingDescription(), $page,
                $font, 10, $width);
            $shippingMethod = array_shift($shippingMethodArray);
            $page->drawText(
                $shippingMethod,
                ($this->margin['right'] - $valueRightOffset
                    - $this->widthForStringUsingFontSize($shippingMethod, $font, 10)),
                $this->y, $this->encoding
            );
            $this->Ln();
            $numberOfLines++;
            $shippingMethodArray = $this->_prepareText(
                implode(" ", $shippingMethodArray), $page, $font, 10, 2 * $width
            );
            foreach ($shippingMethodArray as $methodString) {
                $page->drawText($methodString,
                    $this->margin['right'] - $labelRightOffset, $this->y,
                    $this->encoding);
                $this->Ln();
                $numberOfLines++;
            }

        }
        $this->y -= ($numberOfLines * 2);
    }

    /**
     * Return the order id or false if order id should not be displayed on document.
     *
     * @param  Mage_Sales_Model_Order $order order to get id from
     *
     * @return int|false
     */
    protected function _putOrderId($order)
    {
        return Mage::helper('firegento_pdf')->putOrderId($order, $this->mode);
    }

    /**
     * do we show the customber number on this document
     *
     * @param  mixed $store store from whom we need the config setting
     *
     * @return bool
     */
    protected function _showCustomerNumber($store)
    {
        return Mage::helper('firegento_pdf')
            ->showCustomerNumber($this->mode, $store);
    }

    /**
     * do we show the customber VAT number on this document
     *
     * @param  mixed $store store from whom we need the config setting
     *
     * @return bool
     */
    protected function _showCustomerVATNumber($store)
    {
        return Mage::helper('firegento_pdf')
            ->showCustomerVATNumber($this->mode, $store);
    }

    /**
     * which customer number should be displayed for guest orders
     *
     * @param  mixed $store store from whom we need the config setting
     *
     * @return string
     */
    protected function _getGuestorderCustomerNo($store)
    {
        return Mage::helper('firegento_pdf')
            ->getGuestorderCustomerNo($this->mode, $store);
    }

    /**
     * Generate new PDF page.
     *
     * @param  array $settings Page settings
     *
     * @return Zend_Pdf_Page
     */
    public function newPage(array $settings = array())
    {
        $pdf = $this->_getPdf();

        $page = $pdf->newPage($this->getPageSize());
        $this->pagecounter++;
        $pdf->pages[] = $page;

        $this->_addFooter($page, Mage::app()->getStore());

        // set the font because it may not be set, see https://github.com/firegento/firegento-pdf/issues/184
        $this->_setFontRegular($page, 9);

        // provide the possibility to add random stuff to the page
        Mage::dispatchEvent(
            'firegento_pdf_' . $this->getMode() . '_edit_page',
            array('page' => $page, 'order' => $this->getOrder())
        );

        $this->y = 800;
        $this->_setFontRegular($page, 9);

        return $page;
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
        $type = $item->getOrderItem()->getProductType();

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
     * Insert Totals Block
     *
     * @param  object $page   Current page object of Zend_Pdf
     * @param  object $source Fields of footer
     *
     * @return Zend_Pdf_Page
     */
    protected function insertTotals($page, $source)
    {
        $this->y -= 15;

        $order = $source->getOrder();

        $totalTax = 0;
        $shippingTaxRate = 0;
        $shippingTaxAmount = $order->getShippingTaxAmount();

        if ($shippingTaxAmount > 0) {
            $shippingTaxRate
                = $order->getShippingTaxAmount() * 100
                / ($order->getShippingInclTax()
                    - $order->getShippingTaxAmount());
        }

        $groupedTax = array();

        $items['items'] = array();
        foreach ($source->getAllItems() as $item) {
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }
            $items['items'][] = $item->getOrderItem()->toArray();
        }

        array_push(
            $items['items'], array(
                'row_invoiced'     => $order->getShippingInvoiced(),
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
            $total->setOrder($order)->setSource($source);

            if ($total->canDisplay()) {
                $total->setFontSize(10);
                // fix Magento 1.8 bug, so that taxes for shipping do not appear twice
                // see https://github.com/firegento/firegento-pdf/issues/106
                $uniqueTotalsForDisplay = array_map(
                    'unserialize', array_unique(array_map('serialize',
                        $total->getTotalsForDisplay()))
                );
                foreach ($uniqueTotalsForDisplay as $totalData) {
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
     * Insert Notes
     *
     * @param  Zend_Pdf_Page             $page   Current Page Object of Zend_PDF
     * @param  Mage_Sales_Model_Order    &$order order to get note from
     * @param  Mage_Sales_Model_Abstract &$model invoice/shipment/creditmemo
     *
     * @return \Zend_Pdf_Page
     */
    protected function _insertNote($page, &$order, &$model)
    {
        $fontSize = 10;
        $font = $this->_setFontRegular($page, $fontSize);
        $this->y = $this->y - 60;

        $notes = array();
        $result = new Varien_Object();
        $result->setNotes($notes);
        Mage::dispatchEvent(
            'firegento_pdf_' . $this->getMode() . '_insert_note',
            array(
                'order'          => $order,
                $this->getMode() => $model,
                'result'         => $result
            )
        );
        $notes = array_merge($notes, $result->getNotes());

        // Get free text notes.
        $note = Mage::getStoreConfig('sales_pdf/' . $this->getMode() . '/note');
        if (!empty($note)) {
            $tmpNotes = explode("\n", $note);
            $notes = array_merge($notes, $tmpNotes);
        }

        // Draw notes on PDF.
        foreach ($notes as $note) {
            // prepare the text so that it fits to the paper
            foreach ($this->_prepareText($note, $page, $font, 10) as $tmpNote) {
                // create a new page if necessary
                if ($this->y < 50
                    || (Mage::getStoreConfig('sales_pdf/firegento_pdf/show_footer')
                        == 1
                        && $this->y < 100)
                ) {
                    $page = $this->newPage(array());
                    $this->y = $this->y - 60;
                    $font = $this->_setFontRegular($page, $fontSize);
                }
                $page->drawText($tmpNote, $this->margin['left'], $this->y + 30,
                    $this->encoding);
                $this->Ln(15);
            }
        }

        return $page;
    }

    /**
     * draw footer on pdf
     *
     * @param Zend_Pdf_Page &$page page to draw on
     * @param mixed         $store store to get infos from
     */
    protected function _addFooter(&$page, $store = null)
    {
        // get the imprint of the store if a store is set
        if (!empty($store)) {
            $imprintObject = new Varien_Object();
            $imprintObject->setImprint(Mage::getStoreConfig('general/imprint',
                $store));
            Mage::dispatchEvent('firegento_pdf_imprint_load_after', array(
                    'transport_object' => $imprintObject
                )
            );
            $this->_imprint = $imprintObject->getImprint();
        }

        // Add footer if GermanSetup is installed.
        if ($this->_imprint
            && Mage::getStoreConfig('sales_pdf/firegento_pdf/show_footer') == 1
        ) {
            $this->y = 110;
            $this->_insertFooter($page);

            // Add page counter.
            $this->y = 110;
            $this->_insertPageCounter($page);
        }
    }

    /**
     * Insert footer
     *
     * @param  Zend_Pdf_Page &$page Current page object of Zend_Pdf
     *
     * @return void
     */
    protected function _insertFooter(&$page)
    {
        $page->setLineColor($this->colors['black']);
        $page->setLineWidth(0.5);
        $page->drawLine($this->margin['left'] - 20, $this->y - 5,
            $this->margin['right'] + 30, $this->y - 5);

        $this->Ln(15);
        $this->_insertFooterAddress($page);

        $fields = array(
            'telephone' => Mage::helper('firegento_pdf')->__('Telephone:'),
            'fax'       => Mage::helper('firegento_pdf')->__('Fax:'),
            'email'     => Mage::helper('firegento_pdf')->__('E-Mail:'),
            'web'       => Mage::helper('firegento_pdf')->__('Web:')
        );
        $this->_insertFooterBlock($page, $fields, 70, 40, 140);

        $fields = array(
            'bank_name'          => Mage::helper('firegento_pdf')
                ->__('Bank name:'),
            'bank_account'       => Mage::helper('firegento_pdf')
                ->__('Account:'),
            'bank_code_number'   => Mage::helper('firegento_pdf')
                ->__('Bank number:'),
            'bank_account_owner' => Mage::helper('firegento_pdf')
                ->__('Account owner:'),
            'swift'              => Mage::helper('firegento_pdf')->__('SWIFT:'),
            'iban'               => Mage::helper('firegento_pdf')->__('IBAN:')
        );
        $this->_insertFooterBlock($page, $fields, 215, 50, 140);

        $fields = array(
            'tax_number'      => Mage::helper('firegento_pdf')
                ->__('Tax number:'),
            'vat_id'          => Mage::helper('firegento_pdf')->__('VAT-ID:'),
            'register_number' => Mage::helper('firegento_pdf')
                ->__('Register number:'),
            'ceo'             => Mage::helper('firegento_pdf')->__('CEO:'),
            'city'            => Mage::helper('firegento_pdf')
                ->__('Registered seat:'),
            'court'           => Mage::helper('firegento_pdf')
                ->__('Register court:'),
        );
        $this->_insertFooterBlock($page, $fields, 355, 60,
            $this->margin['right'] - 365 - 10);
    }

    /**
     * Insert footer block
     *
     * @param  Zend_Pdf_Page &$page       Current page object of Zend_Pdf
     * @param  array         $fields      Fields of footer
     * @param  int           $colposition Starting colposition
     * @param  int           $valadjust   Margin between label and value
     * @param  int           $colwidth    the width of this footer block - text will be wrapped if it is broader
     *                                    than this width
     *
     * @return void
     */
    protected function _insertFooterBlock(
        &$page,
        $fields,
        $colposition = 0,
        $valadjust = 30,
        $colwidth = null
    ) {
        $fontSize = 7;
        $font = $this->_setFontRegular($page, $fontSize);
        $y = $this->y;

        $valposition = $colposition + $valadjust;

        if (is_array($fields)) {
            foreach ($fields as $field => $label) {
                if (empty($this->_imprint[$field])) {
                    continue;
                }
                // draw the label
                $page->drawText($label, $this->margin['left'] + $colposition,
                    $y, $this->encoding);
                // prepare the value: wrap it if necessary
                $val = $this->_imprint[$field];
                $width = $colwidth;
                if (!empty($colwidth)) {
                    // calculate the maximum width for the value
                    $width = $this->margin['left'] + $colposition + $colwidth
                        - ($this->margin['left'] + $valposition);
                }
                foreach (
                    $this->_prepareText($val, $page, $font, $fontSize, $width)
                    as $tmpVal
                ) {
                    $page->drawText($tmpVal,
                        $this->margin['left'] + $valposition, $y,
                        $this->encoding);
                    $y -= 12;
                }
            }
        }
    }

    /**
     * Insert address of store owner
     *
     * @param  Zend_Pdf_Page &$page Current page object of Zend_Pdf
     * @param  mixed         $store store to get info from
     *
     * @return void
     */
    protected function _insertFooterAddress(&$page, $store = null)
    {
        $fontSize = 7;
        $font = $this->_setFontRegular($page, $fontSize);
        $y = $this->y;
        $address = '';

        foreach (
            $this->_prepareText($this->_imprint['company_first'], $page, $font,
                $fontSize, 90) as $companyFirst
        ) {
            $address .= $companyFirst . "\n";
        }

        if (array_key_exists('company_second', $this->_imprint)) {
            foreach (
                $this->_prepareText($this->_imprint['company_second'], $page,
                    $font, $fontSize, 90) as $companySecond
            ) {
                $address .= $companySecond . "\n";
            }
        }

        if (array_key_exists('street', $this->_imprint)) {
            $address .= $this->_imprint['street'] . "\n";
        }
        if (array_key_exists('zip', $this->_imprint)) {
            $address .= $this->_imprint['zip'] . " ";
        }
        if (array_key_exists('city', $this->_imprint)) {
            $address .= $this->_imprint['city'] . "\n";
        }

        if (!empty($this->_imprint['country'])) {
            $countryName = Mage::getModel('directory/country')
                ->loadByCode($this->_imprint['country'])->getName();
            $address .= Mage::helper('core')->__($countryName);
        }

        foreach (explode("\n", $address) as $value) {
            if ($value !== '') {
                $page->drawText(trim(strip_tags($value)),
                    $this->margin['left'] - 20, $y, $this->encoding);
                $y -= 12;
            }
        }
    }

    /**
     * Insert page counter
     *
     * @param  Zend_Pdf_Page &$page Current page object of Zend_Pdf
     *
     * @return void
     */
    protected function _insertPageCounter(&$page)
    {
        $font = $this->_setFontRegular($page, 9);
        $page->drawText(
            Mage::helper('firegento_pdf')->__('Page') . ' '
            . $this->pagecounter,
            $this->margin['right'] - 23
            - $this->widthForStringUsingFontSize($this->pagecounter, $font, 9),
            $this->y,
            $this->encoding
        );
    }

    /**
     * get stanard font
     *
     * @return Zend_Pdf_Resource_Font the regular font
     */
    public function getFontRegular()
    {
        if ($this->getRegularFont() && $this->regularFontFileExists()) {
            return Zend_Pdf_Font::fontWithPath($this->getRegularFontFile());
        }

        return Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
    }

    /**
     * Set default font
     *
     * @param  Zend_Pdf_Page $object Current page object of Zend_Pdf
     * @param  string|int    $size   Font size
     *
     * @return Zend_Pdf_Resource_Font
     */
    protected function _setFontRegular($object, $size = 10)
    {
        $font = $this->getFontRegular();
        $object->setFont($font, $size);

        return $font;
    }

    /**
     * get default bold font
     *
     * @return Zend_Pdf_Resource_Font the bold font
     */
    public function getFontBold()
    {
        if ($this->getBoldFont() && $this->boldFontFileExists()) {
            return Zend_Pdf_Font::fontWithPath($this->getBoldFontFile());
        }

        return Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
    }

    /**
     * Set bold font
     *
     * @param  Zend_Pdf_Page $object Current page object of Zend_Pdf
     * @param  string|int    $size   Font size
     *
     * @return Zend_Pdf_Resource_Font
     */
    protected function _setFontBold($object, $size = 10)
    {
        $font = $this->getFontBold();
        $object->setFont($font, $size);

        return $font;
    }

    /**
     * get italic font
     *
     * @return Zend_Pdf_Resource_Font
     */
    public function getFontItalic()
    {
        if ($this->getItalicFont() && $this->italicFontFileExists()) {
            return Zend_Pdf_Font::fontWithPath($this->getItalicFontFile());
        }

        return Zend_Pdf_Font::fontWithName(
            Zend_Pdf_Font::FONT_HELVETICA_ITALIC
        );
    }

    /**
     * Set italic font
     *
     * @param  Zend_Pdf_Page $object Current page object of Zend_Pdf
     * @param  string|int    $size   Font size
     *
     * @return Zend_Pdf_Resource_Font
     */
    protected function _setFontItalic($object, $size = 10)
    {
        $font = $this->getFontItalic();
        $object->setFont($font, $size);

        return $font;
    }

    /**
     * Prepares the text so that it fits to the given page's width.
     *
     * @param  string                 $text     the text which should be prepared
     * @param  Zend_Pdf_Page          $page     the page on which the text will be rendered
     * @param  Zend_Pdf_Resource_Font $font     the font with which the text will be rendered
     * @param  int                    $fontSize the font size with which the text will be rendered
     * @param  int                    $width    [optional] the width for the given text, defaults to the page width
     *
     * @return array the given text in an array where each item represents a new line
     */
    public function _prepareText($text, $page, $font, $fontSize, $width = null)
    {
        if (empty($text)) {
            return array();
        }
        $lines = '';
        $currentLine = '';
        // calculate the page's width with respect to the margins
        if (empty($width)) {
            $width
                = $page->getWidth() - $this->margin['left'] - ($page->getWidth()
                    - $this->margin['right']);
        }
        // regular expression that splits on whitespaces and dashes based on http://stackoverflow.com/a/11758732/719023
        $textChunks = preg_split('/([^\s-]+[\s-]+)/', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        foreach ($textChunks as $textChunk) {
            $textChunk = trim($textChunk);
            if ($this->widthForStringUsingFontSize($currentLine . ' '
                    . $textChunk, $font, $fontSize) < $width
            ) {
                // do not add whitespace on first line
                if (!empty($currentLine)) {
                    $currentLine .= ' ';
                }
                $currentLine .= $textChunk;
            } else {
                // text is too broad, so add new line character
                $lines .= $currentLine . "\n";
                $currentLine = $textChunk;
            }
        }
        // append the last line
        $lines .= $currentLine;

        return explode("\n", $lines);
    }

    /**
     * Fix the percentage for taxes which come with four decimal places
     * from magento core.
     *
     * @param  string $label tax label which contains the badly formatted tax percentage
     *
     * @return string
     */
    protected function fixNumberFormat($label)
    {
        $pattern = "/(.*)\((\d{1,2}\.\d{4}%)\)/";
        if (preg_match($pattern, $label, $matches)) {
            $percentage = Zend_Locale_Format::toNumber(
                $matches[2],
                array(
                    'locale'    => Mage::app()->getLocale()->getLocale(),
                    'precision' => 2,
                )
            );

            return $matches[1] . '(' . $percentage . '%)';
        }

        return $label;
    }

    /**
     * get bold font file
     *
     * @return string
     */
    protected function getBoldFontFile()
    {
        return Mage::helper('firegento_pdf')->getFontPath() . DS
        . $this->getBoldFont();
    }

    /**
     * get bold font path
     *
     * @return string
     */
    protected function getBoldFont()
    {
        return Mage::getStoreConfig(
            FireGento_Pdf_Helper_Data::XML_PATH_BOLD_FONT
        );
    }

    /**
     * check whether font file exists for bold font
     *
     * @return bool
     */
    protected function boldFontFileExists()
    {
        return file_exists($this->getBoldFontFile());
    }

    /**
     * get italic font path
     *
     * @return string
     */
    protected function getItalicFont()
    {
        return Mage::getStoreConfig(
            FireGento_Pdf_Helper_Data::XML_PATH_ITALIC_FONT
        );
    }

    /**
     * check whether italic font file exists
     *
     * @return bool
     */
    protected function ItalicFontFileExists()
    {
        return file_exists($this->getItalicFontFile());
    }

    /**
     * get italic font file
     *
     * @return string
     */
    protected function getItalicFontFile()
    {
        return Mage::helper('firegento_pdf')->getFontPath() . DS
        . $this->getItalicFont();
    }


    /**
     * get the regular font path
     *
     * @return string
     */
    protected function getRegularFont()
    {
        return Mage::getStoreConfig(
            FireGento_Pdf_Helper_Data::XML_PATH_REGULAR_FONT
        );
    }

    /**
     * check whether font file exists for regular font
     *
     * @return bool
     */
    protected function regularFontFileExists()
    {
        return file_exists($this->getRegularFontFile());
    }

    /**
     * get the path to the font file for regular font
     *
     * @return string
     */
    protected function getRegularFontFile()
    {
        return Mage::helper('firegento_pdf')->getFontPath() . DS
        . $this->getRegularFont();
    }

    /**
     * @return string
     */
    private function getPageSize()
    {
        return Mage::helper('firegento_pdf')->getPageSizeConfigPath();
    }
}
