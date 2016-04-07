<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Functional\Controller\Frontend;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData;

/**
 * @dbIsolation
 */
class CallbackControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testWithoutTransactionNoErrors()
    {
        foreach (['POST', 'GET'] as $method) {
            foreach (['orob2b_payment_callback_return', 'orob2b_payment_callback_error'] as $route) {
                $this->client->request($method, $this->getUrl($route, ['transactionId' => 0]));
            }
        }
    }

    public function testCallbacks()
    {
        foreach (['POST', 'GET'] as $method) {
            foreach (['orob2b_payment_callback_return', 'orob2b_payment_callback_error'] as $route) {
                $this->assertCallback($method, $route);
            }
        }
    }

    /**
     * @param string $method
     * @param string $route
     */
    protected function assertCallback($method, $route)
    {
        $parameters = [
            'PNREF' => 'Transaction Reference',
            'RESULT' => '0',
            'SECURETOKEN' => 'SECURETOKEN',
            'SECURETOKENID' => 'SECURETOKENID',
        ];

        $this->loadFixtures(
            ['OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData'],
            true
        );

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(LoadPaymentTransactionData::PAYFLOW_TRANSACTION);

        $expectedData = $parameters + $paymentTransaction->getRequest();
        $this->client->request(
            $method,
            $this->getUrl($route, ['transactionId' => $paymentTransaction->getId()]),
            $expectedData
        );

        $paymentTransaction->setEntityClass('stdClass');

        $manager = $this->getContainer()->get('doctrine')->getManager();
        $manager->flush();
        $manager->clear();

        $paymentTransaction = $manager->find(
            'OroB2BPaymentBundle:PaymentTransaction',
            $paymentTransaction->getId()
        );

        $this->assertNotEquals('stdClass', $paymentTransaction->getEntityClass());

        $this->assertEquals(true, $paymentTransaction->isActive());
        $this->assertEquals('Transaction Reference', $paymentTransaction->getReference());
        $this->assertEquals($expectedData, $paymentTransaction->getResponse());
    }
}
