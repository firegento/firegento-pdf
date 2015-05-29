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
 * Model to serve invoices as zip
 *
 * @category FireGento
 * @package  FireGento_Pdf
 * @author   FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Model_ZippedInvoice
{
    /**
     * Create a ZIP file with pdfed invoices based on orders
     *
     * @param string                                     $file   file the zip is written to
     * @param Mage_Sales_Model_Resource_Order_Collection $orders orders to be pdfed and saved to zip
     *
     * @throws Zend_Pdf_Exception
     */
    public function createFromOrders($file, Mage_Sales_Model_Resource_Order_Collection $orders)
    {
        $zip = $this->openZip($file);

        foreach ($orders as $order) {
            $this->addInvoicesFromOrder($zip, $order);
        }

        // Close and send to users
        $this->closeZip($zip);
    }

    /**
     * Create a ZIP file with pdfed invoices based on invoices
     *
     * @param string                                             $file     file to save the zip to
     * @param Mage_Sales_Model_Resource_Order_Invoice_Collection $invoices invoices to pdf and save
     *
     * @throws Zend_Pdf_Exception
     */
    public function create($file, Mage_Sales_Model_Resource_Order_Invoice_Collection $invoices)
    {
        $zip = $this->openZip($file);
        foreach ($invoices as $invoice) {
            $this->addInvoice($zip, $invoice);
        }

        // Close and send to users
        $zip->close();
    }

    /**
     * create new archive and open it for writing
     *
     * @param  string $file name of the archive file
     *
     * @return ZipArchive
     */
    private function openZip($file)
    {
        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::OVERWRITE);

        return $zip;
    }

    /**
     * close archive after writing
     *
     * @param ZipArchive $zip ZipArchive resource
     */
    private function closeZip($zip)
    {
        $zip->close();
    }

    /**
     * Add all the invoice from an order to the zip file
     *
     * @param ZipArchive             $zip   zip archive object to write to
     * @param Mage_Sales_Model_Order $order orders to write
     *
     * @throws Zend_Pdf_Exception
     */
    private function addInvoicesFromOrder(ZipArchive $zip, $order)
    {
        $zip->addFromString(
            $order->getIncrementId() . '.pdf',
            Mage::getModel('sales/order_pdf_invoice')->getPdf(
                $order->getInvoiceCollection()
            )->render()
        );
    }

    /**
     * Add an invoice the the zip file
     *
     * @param ZipArchive                     $zip     zip archive object to write to
     * @param Mage_Sales_Model_Order_Invoice $invoice invoice to write
     *
     * @throws Zend_Pdf_Exception
     */
    private function addInvoice(ZipArchive $zip, $invoice)
    {
        $zip->addFromString(
            $invoice->getOrder()->getIncrementId() . '.pdf',
            Mage::getModel('sales/order_pdf_invoice')->getPdf(
                array($invoice)
            )->render()
        );
    }
}
