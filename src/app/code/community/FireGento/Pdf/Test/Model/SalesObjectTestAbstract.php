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
 * Abstract test class for the different engines.
 *
 * @category FireGento
 * @package  FireGento_Pdf
 * @author   FireGento Team <team@firegento.com>
 */
abstract class FireGento_Pdf_Test_Model_SalesObjectTestAbstract
    extends EcomDev_PHPUnit_Test_Case
{
    protected $_class = '';

    /**
     * @test
     */
    public function itShouldExist()
    {
        $this->assertTrue(class_exists($this->_class));
    }

    /**
     * @test
     * @depends itShouldExist
     */
    public function itShouldHaveAMethodGetEngine()
    {
        try {
            new ReflectionMethod($this->_class, 'getEngine');
        } catch (ReflectionException $e) {
            $this->fail($e->getMessage());
        }
    }
    
    abstract public function getEngineXmlConfigPath();
    
    abstract public function getExpectedDefaultEngineClass();

    abstract public function getOrderObjectClassName();

    /**
     * @test
     * @depends itShouldHaveAMethodGetEngine
     */
    public function itShouldReturnADefaultEngineModel()
    {
        static::app()->getStore()->setConfig($this->getEngineXmlConfigPath(), 'invalid');
        $instance = new $this->_class;
        $result = $this->callMethod($instance, 'getEngine');
        $this->assertInstanceOf($this->getExpectedDefaultEngineClass(), $result);
    }

    /**
     * @test
     * @depends itShouldExist
     */
    public function itShouldhaveAMethodGetPdf()
    {
        $instance = new $this->_class;
        $this->assertTrue(is_callable(array($instance, 'getPdf')));
    }

    /**
     * @test
     * @depends itShouldhaveAMethodGetPdf
     */
    public function itShouldReturnAZendPdf()
    {
        $instance = new $this->_class;
        
        $mockAddress = $this->getMock('Mage_Sales_Model_Order_Address');

        $mockPaymentMethod = $this->getMockForAbstractClass('Mage_Payment_Model_Method_Abstract');
        
        $mockPaymentInfo = $this->getMock('Mage_Sales_Model_Order_Payment');
        $mockPaymentInfo->expects($this->any())
            ->method('getMethodInstance')
            ->will($this->returnValue($mockPaymentMethod));

        $mockOrder = $this->getMock('Mage_Sales_Model_Order');
        $mockOrder->expects($this->any())
            ->method('getBillingAddress')
            ->will($this->returnValue($mockAddress));
        $mockOrder->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($mockAddress));
        
        $mockOrder->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($mockPaymentInfo));
        
        $mockObj = $this->getMockBuilder($this->getOrderObjectClassName())
            ->disableOriginalConstructor()
            ->getMock();
        $mockObj->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($mockOrder));
        $mockObj->expects($this->any())
            ->method('getAllItems')
            ->will($this->returnValue(array()));
        
        $result = $instance->getPdf(array($mockObj));
        $this->assertInstanceOf('Zend_Pdf', $result);
    }


    protected function callMethod($object, $method, array $args = null)
    {
        $method = new ReflectionMethod($object, $method);
        $method->setAccessible(true);
        if (isset($args)) {
            return $method->invokeArgs($object, $args);
        } else {
            return $method->invoke($object);
        }
    }
}
