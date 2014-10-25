<?php


abstract class FireGento_Pdf_Test_Model_SalesObjectTestAbstract
    extends EcomDev_PHPUnit_Test_Case
{
    protected $class = '';

    /**
     * @test
     */
    public function itShouldExist()
    {
        $this->assertTrue(class_exists($this->class));
    }

    /**
     * @test
     * @depends itShouldExist
     */
    public function itShouldHaveAMethodGetEngine()
    {
        try {
            new ReflectionMethod($this->class, 'getEngine');
        } catch (ReflectionException $e) {
            $this->fail($e->getMessage());
        }
    }
    
    abstract function getEngineXmlConfigPath();
    
    abstract function getExpectedDefaultEngineClass();

    abstract public function getOrderObjectClassName();

    /**
     * @test
     * @depends itShouldHaveAMethodGetEngine
     */
    public function itShouldReturnADefaultEngineModel()
    {
        $this->app()->getStore()->setConfig($this->getEngineXmlConfigPath(), 'invalid');
        $instance = new $this->class;
        $result = $this->callMethod($instance, 'getEngine');
        $this->assertInstanceOf($this->getExpectedDefaultEngineClass(), $result);
    }

    /**
     * @test
     * @depends itShouldExist
     */
    public function itShouldhaveAMethodGetPdf()
    {
        $instance = new $this->class;
        $this->assertTrue(is_callable(array($instance, 'getPdf')));
    }

    /**
     * @test
     * @depends itShouldhaveAMethodGetPdf
     */
    public function itShouldReturnAZendPdf()
    {
        $instance = new $this->class;
        
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