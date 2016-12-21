<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Acl\Resolver;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\CustomerBundle\Acl\Resolver\RoleTranslationPrefixResolver;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;

class RoleTranslationPrefixResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var RoleTranslationPrefixResolver */
    protected $resolver;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = new RoleTranslationPrefixResolver($this->securityFacade);
    }

    protected function tearDown()
    {
        unset($this->resolver);
    }

    /**
     * @dataProvider getPrefixDataProvider
     *
     * @param UserInterface|string|null $loggedUser
     * @param string|null $expectedPrefix
     */
    public function testGetPrefix($loggedUser, $expectedPrefix = null)
    {
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($loggedUser);

        if (!$expectedPrefix) {
            $this->expectException('\RuntimeException');
            $this->expectExceptionMessage('This method must be called only for logged User or AccountUser');
        }

        $this->assertEquals($expectedPrefix, $this->resolver->getPrefix());
    }

    /**
     * @return array
     */
    public function getPrefixDataProvider()
    {
        return [
            [new User, RoleTranslationPrefixResolver::BACKEND_PREFIX],
            [new AccountUser(), RoleTranslationPrefixResolver::FRONTEND_PREFIX],
            ['anon.'],
            [null]
        ];
    }
}
