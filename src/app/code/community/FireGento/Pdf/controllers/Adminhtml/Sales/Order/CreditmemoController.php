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

require('Mage/Adminhtml/controllers/Sales/Order/CreditmemoController.php');

/**
 * Class FireGento_Pdf_Adminhtml_Sales_Order_CreditmemoController
 *
 * @category FireGento
 * @package  FireGento_Pdf
 * @author   FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Adminhtml_Sales_Order_CreditmemoController
    extends Mage_Adminhtml_Sales_Order_CreditmemoController
{

    /**
     * Create pdf for current creditmemo
     */
    public function printAction()
    {
        $this->_initCreditmemo();
        /** @see Mage_Adminhtml_Sales_Order_InvoiceController */
        if ($creditmemoId = $this->getRequest()->getParam('creditmemo_id')) {
            if ($creditmemo = Mage::getModel('sales/order_creditmemo')
                ->load($creditmemoId)
            ) {
                $pdf = Mage::getModel('sales/order_pdf_creditmemo')
                    ->getPdf(array($creditmemo));
                $this->_prepareDownloadResponse(
                    Mage::helper('firegento_pdf')
                        ->getExportFilename('creditmemo', $creditmemo),
                    $pdf->render(), 'application/pdf'
                );
            }
        } else {
            $this->_forward('noRoute');
        }
    }

}
