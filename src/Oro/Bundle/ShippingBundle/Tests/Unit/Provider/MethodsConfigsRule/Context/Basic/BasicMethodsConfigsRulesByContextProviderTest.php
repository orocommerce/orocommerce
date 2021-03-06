<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\MethodsConfigsRule\Context\Basic;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\Basic\BasicMethodsConfigsRulesByContextProvider;
use Oro\Bundle\ShippingBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\ShippingContextMockTrait;
use Oro\Bundle\ShippingBundle\Tests\Unit\Entity\ShippingMethodsConfigsRuleMockTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class BasicMethodsConfigsRulesByContextProviderTest extends \PHPUnit\Framework\TestCase
{
    use ShippingContextMockTrait;
    use ShippingMethodsConfigsRuleMockTrait;

    /**
     * @var ShippingMethodsConfigsRuleRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    /**
     * @var MethodsConfigsRulesFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filtrationService;

    /**
     * @var BasicMethodsConfigsRulesByContextProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ShippingMethodsConfigsRuleRepository::class);

        $this->filtrationService = $this->createMock(MethodsConfigsRulesFiltrationServiceInterface::class);

        $this->provider = new BasicMethodsConfigsRulesByContextProvider(
            $this->filtrationService,
            $this->repository
        );
    }

    public function testGetAllFilteredShippingMethodsConfigsWithShippingAddress()
    {
        $currency = 'USD';
        $address = $this->createAddressMock();
        $website = $this->createWebsiteMock();
        $rulesFromDb = [
            $this->createShippingMethodsConfigsRuleMock(),
            $this->createShippingMethodsConfigsRuleMock(),
        ];

        $this->repository->expects(static::once())
            ->method('getByDestinationAndCurrencyAndWebsite')
            ->with($address, $currency, $website)
            ->willReturn($rulesFromDb);

        $this->repository->expects(static::never())
            ->method('getByCurrencyAndWebsiteWithoutDestination');

        $context = $this->createShippingContextMock();
        $context->method('getCurrency')
            ->willReturn($currency);
        $context->method('getShippingAddress')
            ->willReturn($address);
        $context->method('getWebsite')
            ->willReturn($website);

        $expectedRules = [$this->createShippingMethodsConfigsRuleMock()];

        $this->filtrationService->expects(static::once())
            ->method('getFilteredShippingMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        static::assertSame(
            $expectedRules,
            $this->provider->getShippingMethodsConfigsRules($context)
        );
    }

    public function testGetAllFilteredShippingMethodsConfigsWithoutShippingAddress()
    {
        $currency = 'USD';
        $website = $this->createWebsiteMock();
        $rulesFromDb = [$this->createShippingMethodsConfigsRuleMock()];

        $this->repository->expects(static::once())
            ->method('getByCurrencyAndWebsiteWithoutDestination')
            ->with($currency, $website)
            ->willReturn($rulesFromDb);

        $context = $this->createShippingContextMock();
        $context->method('getCurrency')
            ->willReturn($currency);
        $context->method('getWebsite')
            ->willReturn($website);

        $expectedRules = [$this->createShippingMethodsConfigsRuleMock()];

        $this->filtrationService->expects(static::once())
            ->method('getFilteredShippingMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        static::assertSame(
            $expectedRules,
            $this->provider->getShippingMethodsConfigsRules($context)
        );
    }

    /**
     * @return Website|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createWebsiteMock()
    {
        return $this->createMock(Website::class);
    }

    /**
     * @return AddressInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createAddressMock()
    {
        return $this->createMock(AddressInterface::class);
    }
}
