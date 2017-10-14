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
class FireGento_Pdf_Model_Creditmemo
{

    /**
     * The actual PDF engine responsible for rendering the file.
     * @var Mage_Sales_Model_Order_Pdf_Abstract
     */
    protected $_engine;

    /**
     * get pdf renderer engine
     *
     * @return Mage_Sales_Model_Order_Pdf_Abstract|Mage_Sales_Model_Order_Pdf_Creditmemo
     */
    protected function getEngine()
    {
        if (!$this->_engine) {
            $modelClass = Mage::getStoreConfig('sales_pdf/creditmemo/engine');
            $engine = Mage::getModel($modelClass);

            if (!$engine || $engine instanceof FireGento_Pdf_Model_Creditmemo) {
                // Fallback to Magento standard creditmemo layout.
                // use new here to circumvent our own rewrite
                $engine = new Mage_Sales_Model_Order_Pdf_Creditmemo();
            }

            $this->_engine = $engine;
        }

        return $this->_engine;
    }

    /**
     * get pdf object
     *
     * @param  array|Varien_Data_Collection $creditmemos creditmemos to render
     *
     * @return Zend_Pdf
     */
    public function getPdf($creditmemos = array())
    {
        return $this->getEngine()->getPdf($creditmemos);
    }

}
