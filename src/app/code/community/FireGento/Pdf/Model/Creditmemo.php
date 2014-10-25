<?php
/**
 * This file is part of the FIREGENTO project.
 *
 * FireGento_Pdf is free software; you can redistribute it and/or
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
 * @copyright 2013 FireGento Team (http://www.firegento.com)
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
/**
 * Creditmemo model rewrite.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.com)
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_Pdf_Model_Creditmemo
{

    /**
     * The actual PDF engine responsible for rendering the file.
     * @var Mage_Sales_Model_Order_Pdf_Abstract
     */
    private $_engine;

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

            if (!$engine) {
                // Fallback to Magento standard creditmemo layout.
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
