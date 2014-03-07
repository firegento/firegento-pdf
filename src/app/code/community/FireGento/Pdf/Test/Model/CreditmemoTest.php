<?php


class FireGento_Pdf_Test_Model_CreditmemoTest extends FireGento_Pdf_Test_Model_SalesObjectTestAbstract
{
    protected $class = 'FireGento_Pdf_Model_Creditmemo';
    
    public function getEngineXmlConfigPath()
    {
        return 'sales_pdf/creditmemo/engine';
    }
    
    public function getExpectedDefaultEngineClass()
    {
        return 'Mage_Sales_Model_Order_Pdf_Creditmemo';
    }

    public function getOrderObjectClassName()
    {
        return 'Mage_Sales_Model_Order_Creditmemo';
    }
} 