<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_12_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

class AddProductPriceAcl implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(ProductPrice::class, 'security', 'type', 'ACL')
        );
    }
}
