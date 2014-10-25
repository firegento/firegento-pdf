<?php


class FireGento_Pdf_Test_Model_InvoiceTest extends FireGento_Pdf_Test_Model_SalesObjectTestAbstract
{
    protected $class = 'FireGento_Pdf_Model_Invoice';
    
    public function getEngineXmlConfigPath()
    {
        return 'sales_pdf/invoice/engine';
    }
    
    public function getExpectedDefaultEngineClass()
    {
        return 'Mage_Sales_Model_Order_Pdf_Invoice';
    }

    public function getOrderObjectClassName()
    {
        return 'Mage_Sales_Model_Order_Invoice';
    }
} 