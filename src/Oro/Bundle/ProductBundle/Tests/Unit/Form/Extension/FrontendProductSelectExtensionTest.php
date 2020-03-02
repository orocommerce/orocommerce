<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Extension\FrontendProductSelectExtension;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontendProductSelectExtensionTest extends AbstractCustomerUserAwareExtensionTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->extension = new FrontendProductSelectExtension($this->tokenStorage);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(ProductSelectType::class, $this->extension->getExtendedType());
    }

    public function testConfigureOptionsNonCustomerUser()
    {
        $this->assertOptionsNotChangedForNonCustomerUser();
    }

    public function testConfigureOptionsCustomerUser()
    {
        $this->assertCustomerUserTokenCall();
        /** @var \PHPUnit\Framework\MockObject\MockObject|OptionsResolver $resolver */
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefault')
            ->with('grid_name', 'products-select-grid-frontend');

        $this->extension->configureOptions($resolver);
    }
}
