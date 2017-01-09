<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Configured\Decorator\OverriddenCost;

// @codingStandardsIgnoreStart
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\OverriddenCost\OverriddenCostShippingConfiguredPriceProviderDecorator;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;
// @codingStandardsIgnoreEnd

class OverriddenCostShippingConfiguredPriceProviderDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingConfiguredPriceProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $parentProviderMock;

    /**
     * @var OverriddenCostShippingConfiguredPriceProviderDecorator
     */
    private $testedProvider;

    public function setUp()
    {
        $this->parentProviderMock = $this
            ->getMockBuilder(ShippingConfiguredPriceProviderInterface::class)
            ->getMock();

        $this->testedProvider = new OverriddenCostShippingConfiguredPriceProviderDecorator($this->parentProviderMock);
    }

    public function testGetApplicableMethodsViews()
    {
        $overriddenShippingCost = Price::create(50, 'EUR');
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $parentMethodViews = new ShippingMethodViewCollection();

        $parentMethodViews
            ->addMethodView('flat_rate', [])
            ->addMethodTypeView('flat_rate', 'primary', ['price' => Price::create(12, 'USD')]);

        $this->parentProviderMock
            ->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($configurationMock, $contextMock)
            ->willReturn($parentMethodViews);

        $configurationMock
            ->expects($this->once())
            ->method('isOverriddenShippingCost')
            ->willReturn(true);

        $configurationMock
            ->expects($this->once())
            ->method('getShippingCost')
            ->willReturn($overriddenShippingCost);

        $expectedMethods = clone $parentMethodViews;
        $expectedMethods
            ->removeMethodTypeView('flat_rate', 'primary')
            ->addMethodTypeView('flat_rate', 'primary', ['price' => $overriddenShippingCost]);

        $actualMethods = $this->testedProvider->getApplicableMethodsViews($configurationMock, $contextMock);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetPrice()
    {
        $methodId = 'flat_rate';
        $methodTypeId = 'primary';
        $overriddenShippingCost = Price::create(50, 'EUR');
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $configurationMock
            ->expects($this->once())
            ->method('isOverriddenShippingCost')
            ->willReturn(true);

        $configurationMock
            ->expects($this->once())
            ->method('getShippingCost')
            ->willReturn($overriddenShippingCost);

        $actualPrice = $this->testedProvider->getPrice($methodId, $methodTypeId, $configurationMock, $contextMock);

        $this->assertEquals($overriddenShippingCost, $actualPrice);
    }

    /**
     * @return ShippingContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getShippingContextMock()
    {
        return $this
            ->getMockBuilder(ShippingContext::class)
            ->getMock();
    }

    /**
     * @return ComposedShippingMethodConfigurationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getConfigurationMock()
    {
        return $this
            ->getMockBuilder(ComposedShippingMethodConfigurationInterface::class)
            ->getMock();
    }
}
