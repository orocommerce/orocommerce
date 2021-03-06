<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

class ProductDataConverterEventListener
{
    public function onBackendHeader(ProductDataConverterEvent $event)
    {
        $data = $event->getData();
        $data[] = AbstractProductImportEventListener::CATEGORY_KEY;
        $event->setData($data);
    }
}
