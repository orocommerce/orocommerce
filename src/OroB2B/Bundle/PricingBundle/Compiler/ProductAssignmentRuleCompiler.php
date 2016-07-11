<?php

namespace OroB2B\Bundle\PricingBundle\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class ProductAssignmentRuleCompiler extends AbstractRuleCompiler
{
    /**
     * @var array
     */
    protected $fieldsOrder = [
        'product',
        'priceList'
    ];

    /**
     * @param PriceList $priceList
     * @return QueryBuilder
     */
    public function compile(PriceList $priceList)
    {
        $qb = $this->createQueryBuilder($priceList);
        $aliases = $qb->getRootAliases();
        $rootAlias = reset($aliases);

        $this->modifySelectPart($qb, $priceList, $rootAlias);
        $this->applyRuleConditions($qb, $priceList);
        $this->restrictByManualPrices($qb, $priceList, $rootAlias);

        return $qb;
    }

    /**
     * @param PriceList $priceList
     * @return QueryBuilder
     */
    protected function createQueryBuilder(PriceList $priceList)
    {
        $node = $this->expressionParser->parse($priceList->getProductAssignmentRule());
        $source = $this->nodeConverter->convert($node);

        return $this->queryConverter->convert($source);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsOrder()
    {
        return $this->fieldsOrder;
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceList $priceList
     * @param string $rootAlias
     */
    protected function modifySelectPart(QueryBuilder $qb, PriceList $priceList, $rootAlias)
    {
        $this->addSelectInOrder(
            $qb,
            [
                'product' => $rootAlias . '.id',
                'priceList' => (string)$qb->expr()->literal($priceList->getId())
            ]
        );
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceList $priceList
     */
    protected function applyRuleConditions(QueryBuilder $qb, PriceList $priceList)
    {
        $params = [];
        $qb->andWhere(
            $this->expressionBuilder->convert(
                $this->expressionParser->parse($priceList->getProductAssignmentRule()),
                $qb->expr(),
                $params,
                $this->queryConverter->getTableAliasByColumn()
            )
        );
        $this->applyParameters($qb, $params);
    }

    /**
     * Manually entered prices should not be rewritten by generator.
     *
     * @param QueryBuilder $qb
     * @param PriceList $priceList
     * @param string $rootAlias
     */
    protected function restrictByManualPrices(QueryBuilder $qb, PriceList $priceList, $rootAlias)
    {
        /** @var EntityManagerInterface $em */
        $em = $qb->getEntityManager();
        $subQb = $em->createQueryBuilder();
        $subQb->from('OroB2BPricingBundle:PriceListToProduct', 'PriceListToProductOld')
            ->select('PriceListToProductOld')
            ->where(
                $subQb->expr()->andX(
                    $subQb->expr()->eq('PriceListToProductOld.product', $rootAlias),
                    $subQb->expr()->eq('PriceListToProductOld.priceList', ':priceList'),
                    $subQb->expr()->eq('PriceListToProductOld.manual', ':isManual')
                )
            );

        $qb->setParameter('isManual', true)
            ->setParameter('priceList', $priceList)
            ->andWhere(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $subQb->getQuery()->getDQL()
                    )
                )
            );
    }
}
