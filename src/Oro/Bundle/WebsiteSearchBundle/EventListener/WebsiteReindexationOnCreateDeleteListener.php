<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This listener listens for creation and deletion of Website entity
 * and triggers event telling that indexes with this website should be
 * created or deleted.
 */
class WebsiteReindexationOnCreateDeleteListener
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Website $website
     */
    public function postPersist(Website $website)
    {
        $this->dispatchReindexationRequestEvent($website);
    }

    /**
     * @param Website $website
     */
    public function preRemove(Website $website)
    {
        $this->dispatchReindexationRequestEvent($website);
    }

    /**
     * @param Website $website
     */
    protected function dispatchReindexationRequestEvent(Website $website)
    {
        $event = new ReindexationRequestEvent([], [$website->getId()]);

        $this->dispatcher->dispatch($event, ReindexationRequestEvent::EVENT_NAME);
    }
}
