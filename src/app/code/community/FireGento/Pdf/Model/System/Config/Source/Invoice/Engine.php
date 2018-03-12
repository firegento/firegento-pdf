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
 * Pdf creation engine source model.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Model_System_Config_Source_Invoice_Engine
{
    /**
     * Config xpath to pdf engine node
     *
     */
    const XML_PATH_PDF_ENGINE = 'global/pdf/firegento_invoice_engines';

    /**
     * Return array of possible engines.
     *
     * @return array
     */
    public function toOptionArray()
    {
        // load default engines shipped with Mage_Sales and FireGento_Pdf
        $engines = array(
            ''                                     => Mage::helper('firegento_pdf')->__('Standard Magento'),
            'firegento_pdf/engine_invoice_default' => Mage::helper('firegento_pdf')->__('Standard FireGento')
        );

        // load additional engines provided by third party extensions
        $engineNodes = Mage::app()->getConfig()->getNode(self::XML_PATH_PDF_ENGINE);
        if ($engineNodes && $engineNodes->hasChildren()) {
            foreach ($engineNodes->children() as $engineName => $engineNode) {
                $className   = (string) $engineNode->class;
                $engineLabel = Mage::helper('firegento_pdf')->__((string) $engineNode->label);
                $engines[$className] = $engineLabel;
            }
        }

        $options = array();
        foreach ($engines as $k => $v) {
            $options[] = array(
                'value' => $k,
                'label' => $v
            );
        }

        return $options;
    }
}
