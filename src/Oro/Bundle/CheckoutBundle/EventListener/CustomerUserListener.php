<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Event\CustomerUserEmailSendEvent;
use Oro\Bundle\CustomerBundle\Mailer\Processor;
use Oro\Bundle\CustomerBundle\Security\LoginManager;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Executes autologin depending on CustomerUser status and adds email template params for autologin link
 */
class CustomerUserListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CheckoutManager
     */
    private $checkoutManager;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var LoginManager
     */
    private $loginManager;

    /**
     * @var string
     */
    private $firewallName;

    /**
     * @param RequestStack $requestStack
     * @param CheckoutManager $checkoutManager
     * @param ConfigManager $configManager
     * @param LoginManager $loginManager
     * @param string $firewallName
     */
    public function __construct(
        RequestStack $requestStack,
        CheckoutManager $checkoutManager,
        ConfigManager $configManager,
        LoginManager $loginManager,
        $firewallName
    ) {
        $this->requestStack = $requestStack;
        $this->checkoutManager = $checkoutManager;
        $this->configManager = $configManager;
        $this->loginManager = $loginManager;
        $this->firewallName = $firewallName;
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function afterFlush(AfterFormProcessEvent $event)
    {
        $customerUser = $event->getData();

        if ($this->getFromRequest('_checkout_registration')) {
            if ($customerUser->isConfirmed()) {
                $this->loginManager->logInUser($this->firewallName, $customerUser);

                return;
            }

            $checkoutId = $this->getFromRequest('_checkout_id');
            if ($checkoutId) {
                $this->checkoutManager->assignRegisteredCustomerUserToCheckout($customerUser, $checkoutId);
            }
        }
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function getFromRequest($name)
    {
        $request = $this->requestStack->getMasterRequest();

        return $request ? $request->request->get($name) : null;
    }

    /**
     * @param CustomerUserEmailSendEvent $event
     */
    public function onCustomerUserEmailSend(CustomerUserEmailSendEvent $event)
    {
        $checkoutId = $this->getFromRequest('_checkout_id');
        if ($this->getFromRequest('_checkout_registration') && $checkoutId &&
            !$this->configManager->get('oro_checkout.allow_checkout_without_email_confirmation') &&
            $event->getEmailTemplate() === Processor::CONFIRMATION_EMAIL_TEMPLATE_NAME
        ) {
            $event->setEmailTemplate('checkout_registration_confirmation');
            $params = $event->getEmailTemplateParams();

            $params['redirectParams'] = json_encode([
                'route' => 'oro_checkout_frontend_checkout',
                'params' => [
                    'id' => $checkoutId,
                    'transition' => 'back_to_billing_address'
                ]
            ]);
            $event->setEmailTemplateParams($params);
        }
    }
}
