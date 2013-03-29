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
 * Invoice model rewrite.
 *
 * The invoice model serves as a proxy to the actual PDF engine as set via
 * backend configuration.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_Pdf_Model_Invoice
{
    /**
     * The actual PDF engine responsible for rendering the file.
     * @var Mage_Sales_Model_Order_Pdf_Abstract
     */
    private $engine;

    protected function getEngine()
    {
        if (!$this->engine) {
            $modelClass = Mage::getStoreConfig('sales_pdf/firegento_pdf/engine');
            $engine = Mage::getModel($modelClass);

            if (!$engine || ($engine instanceof FireGento_Pdf_Model_Abstract && !$engine->test())) {
                // Fallback to Magento standard invoice layout.
                $engine = new Mage_Sales_Model_Order_Pdf_Invoice();
            }

            $this->engine = $engine;
        }

        return $this->engine;
    }

    public function getPdf($invoices = array())
    {
        return $this->getEngine()->getPdf($invoices);
    }


    public function widthForStringUsingFontSize($string, $font, $fontSize)
    {
        return $this->getEngine()->widthForStringUsingFontSize($string, $font, $fontSize);
    }

    public function getAlignRight($string, $x, $columnWidth, Zend_Pdf_Resource_Font $font, $fontSize, $padding = 5)
    {
        return $this->getEngine()->getAlignRight($string, $x, $columnWidth, $font, $fontSize);
    }

    public function getAlignCenter($string, $x, $columnWidth, Zend_Pdf_Resource_Font $font, $fontSize)
    {
        return $this->getEngine()->getAlignCenter($string, $x, $columnWidth, $font, $fontSize);
    }

    public function insertDocumentNumber(Zend_Pdf_Page $page, $text)
    {
        return $this->getEngine()->insertDocumentNumber($page, $text);
    }

    public function getRenderer($type)
    {
        return $this->getEngine()->getRenderer($type);
    }

    public function newPage(array $settings = array())
    {
        return $this->getEngine()->newPage($settings);
    }

    public function drawLineBlocks(Zend_Pdf_Page $page, array $draw, array $pageSettings = array())
    {
        return $this->getEngine()->drawLineBlocks($page, $draw, $pageSettings);
    }
}
