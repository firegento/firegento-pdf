<?php


class FireGento_Pdf_Test_Model_ShipmentTest extends FireGento_Pdf_Test_Model_SalesObjectTestAbstract
{
    protected $class = 'FireGento_Pdf_Model_Shipment';

    public function getEngineXmlConfigPath()
    {
        return 'sales_pdf/invoice/shipment';
    }

    public function getExpectedDefaultEngineClass()
    {
        return 'Mage_Sales_Model_Order_Pdf_Shipment';
    }
    
    public function getOrderObjectClassName()
    {
        return 'Mage_Sales_Model_Order_Shipment';
    }
}