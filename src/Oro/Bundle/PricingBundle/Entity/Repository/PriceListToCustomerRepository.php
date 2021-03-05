<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Model\DTO\CustomerWebsiteDTO;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Composite primary key fields order:
 *  - customer
 *  - priceList
 *  - website
 */
class PriceListToCustomerRepository extends EntityRepository implements PriceListRepositoryInterface
{
    /**
     * @param BasePriceList $priceList
     * @param Customer $customer
     * @param Website $website
     * @return PriceListToCustomer
     */
    public function findByPrimaryKey(BasePriceList $priceList, Customer $customer, Website $website)
    {
        return $this->findOneBy(['customer' => $customer, 'priceList' => $priceList, 'website' => $website]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceLists($customer, Website $website, $sortOrder = Criteria::ASC)
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->innerJoin('relation.priceList', 'priceList')
            ->where($qb->expr()->eq('relation.customer', ':customer'))
            ->andWhere($qb->expr()->eq('relation.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.active', ':active'))
            ->orderBy('relation.sortOrder', QueryBuilderUtil::getSortOrder($sortOrder))
            ->setParameters(['customer' => $customer, 'website' => $website, 'active' => true]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param Website $website
     * @return \Iterator|Customer[]
     */
    public function getCustomerIteratorWithDefaultFallback(
        CustomerGroup $customerGroup,
        Website $website
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('distinct customer.id')
            ->from(Customer::class, 'customer')
            ->leftJoin(
                PriceListToCustomer::class,
                'plToCustomer',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('plToCustomer.website', ':website'),
                    $qb->expr()->eq('plToCustomer.customer', 'customer')
                )
            )
            ->leftJoin(
                PriceListCustomerFallback::class,
                'priceListFallBack',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('priceListFallBack.website', ':website'),
                    $qb->expr()->eq('priceListFallBack.customer', 'customer')
                )
            )
            ->where($qb->expr()->eq('customer.group', ':customerGroup'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('priceListFallBack.fallback', ':fallbackToGroup'),
                    $qb->expr()->isNull('priceListFallBack.fallback')
                )
            )
            ->orderBy('customer.id');

        $qb->setParameters([
            'website' => $website,
            'customerGroup' => $customerGroup,
            'fallbackToGroup' => PriceListCustomerFallback::ACCOUNT_GROUP
        ]);

        $em = $this->getEntityManager();
        $iterator = new BufferedQueryResultIterator($qb->getQuery());
        $iterator->setPageLoadedCallback(
            static function ($rows) use ($em) {
                $entities = [];
                foreach ($rows as $row) {
                    $entities[] = $em->getReference(Customer::class, $row);
                }

                return $entities;
            }
        );

        return $iterator;
    }

    /**
     * @param CustomerGroup $customerGroup
     * @return BufferedQueryResultIteratorInterface
     */
    public function getCustomerWebsitePairsByCustomerGroupIterator(CustomerGroup $customerGroup)
    {
        $qb = $this->createQueryBuilder('PriceListToCustomer');

        $qb->select(
            sprintf('IDENTITY(PriceListToCustomer.customer) as %s', PriceListRelationTrigger::ACCOUNT),
            sprintf('IDENTITY(PriceListToCustomer.website) as %s', PriceListRelationTrigger::WEBSITE)
        )
            ->innerJoin('PriceListToCustomer.customer', 'acc')
            ->innerJoin(
                PriceListToCustomerGroup::class,
                'PriceListToCustomerGroup',
                Join::WITH,
                $qb->expr()->andX(
                    'PriceListToCustomerGroup.customerGroup = acc.group',
                    'PriceListToCustomerGroup.website = PriceListToCustomer.website'
                )
            )
            ->where($qb->expr()->eq('acc.group', ':customerGroup'))
            ->groupBy('PriceListToCustomer.customer', 'PriceListToCustomer.website')
            ->setParameter('customerGroup', $customerGroup)
            // order required for BufferedIdentityQueryResultIterator on PostgreSql
            ->orderBy('PriceListToCustomer.customer, PriceListToCustomer.website');

        return new BufferedQueryResultIterator($qb);
    }

    /**
     * @param PriceList $priceList
     * @return BufferedQueryResultIteratorInterface
     */
    public function getIteratorByPriceList(PriceList $priceList)
    {
        return $this->getIteratorByPriceLists([$priceList]);
    }

    /**
     * @param PriceList[] $priceLists
     * @return BufferedQueryResultIteratorInterface
     */
    public function getIteratorByPriceLists($priceLists)
    {
        $qb = $this->createQueryBuilder('priceListToCustomer');

        $qb->select(
            sprintf('IDENTITY(priceListToCustomer.customer) as %s', PriceListRelationTrigger::ACCOUNT),
            sprintf('IDENTITY(acc.group) as %s', PriceListRelationTrigger::ACCOUNT_GROUP),
            sprintf('IDENTITY(priceListToCustomer.website) as %s', PriceListRelationTrigger::WEBSITE)
        )
            ->innerJoin('priceListToCustomer.customer', 'acc')
            ->where($qb->expr()->in('priceListToCustomer.priceList', ':priceLists'))
            ->groupBy('priceListToCustomer.customer', 'acc.group', 'priceListToCustomer.website')
            ->setParameter('priceLists', $priceLists)
            // order required for BufferedIdentityQueryResultIterator on PostgreSql
            ->orderBy('priceListToCustomer.customer');


        return new BufferedQueryResultIterator($qb);
    }

    /**
     * @return CustomerWebsiteDTO[]|\Iterator
     */
    public function getAllCustomerWebsitePairsWithSelfFallback()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select(
                'IDENTITY(priceListFallBack.customer) as customer_id',
                'IDENTITY(priceListFallBack.website) as website_id'
            )
            ->from(PriceListCustomerFallback::class, 'priceListFallBack')
            ->where($qb->expr()->eq('priceListFallBack.fallback', ':fallback'))
            ->setParameter('fallback', PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY);

        $iterator = new BufferedQueryResultIterator($qb);
        $em = $this->getEntityManager();
        $iterator->setPageLoadedCallback(
            static function ($rows) use ($em) {
                $entities = [];
                foreach ($rows as $pair) {
                    /** @var Customer $customer */
                    $customer = $em->getReference(Customer::class, $pair['customer_id']);
                    /** @var Website $website */
                    $website = $em->getReference(Website::class, $pair['website_id']);
                    $entities[] = new CustomerWebsiteDTO($customer, $website);
                }

                return $entities;
            }
        );

        return $iterator;
    }

    /**
     * @param Website $website
     * @return CustomerWebsiteDTO[]|\Iterator
     */
    public function getAllCustomersWithEmptyGroupAndDefaultFallback(Website $website)
    {
        $qb = $this->createQueryBuilder('PriceListToCustomer');
        $qb
            ->select(
                'IDENTITY(PriceListToCustomer.customer) as customer_id'
            )
            ->innerJoin('PriceListToCustomer.customer', 'customer')
            ->leftJoin(
                PriceListCustomerFallback::class,
                'priceListFallBack',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('priceListFallBack.customer', 'PriceListToCustomer.customer'),
                    $qb->expr()->eq('priceListFallBack.website', 'PriceListToCustomer.website'),
                    $qb->expr()->eq('priceListFallBack.fallback', ':fallback')
                )
            )
            ->where($qb->expr()->isNull('customer.group'))
            ->andWhere($qb->expr()->isNull('priceListFallBack.id'))
            ->andWhere($qb->expr()->eq('PriceListToCustomer.website', ':website'))
            ->groupBy('PriceListToCustomer.customer');

        $qb->setParameters([
            'website' => $website,
            'fallback' => PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY
        ]);

        $iterator = new BufferedQueryResultIterator($qb);
        $em = $this->getEntityManager();
        $iterator->setPageLoadedCallback(
            static function ($rows) use ($em) {
                $entities = [];
                foreach ($rows as $row) {
                    $entities[] = $em->getReference(Customer::class, $row['customer_id']);
                }

                return $entities;
            }
        );

        return $iterator;
    }

    /**
     * @param Customer $customer
     * @param Website $website
     * @return mixed
     */
    public function delete(Customer $customer, Website $website)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'PriceListToCustomer')
            ->where('PriceListToCustomer.customer = :customer')
            ->andWhere('PriceListToCustomer.website = :website')
            ->setParameter('customer', $customer)
            ->setParameter('website', $website)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array|Customer[]|int[] $holdersIds
     * @return PriceListToCustomer[]
     */
    public function getRelationsByHolders(array $holdersIds)
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->addSelect('partial website.{id, name}')
            ->addSelect('partial priceList.{id, name}')
            ->leftJoin('relation.website', 'website')
            ->leftJoin('relation.priceList', 'priceList')
            ->where($qb->expr()->in('relation.customer', ':customers'))
            ->orderBy('relation.customer')
            ->addOrderBy('relation.website')
            ->addOrderBy('relation.sortOrder')
            ->setParameter('customers', $holdersIds);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param BasePriceList $priceList
     * @param string $parameterName
     */
    public function restrictByPriceList(
        QueryBuilder $queryBuilder,
        BasePriceList $priceList,
        $parameterName
    ) {
        QueryBuilderUtil::checkIdentifier($parameterName);
        $parentAlias = $queryBuilder->getRootAliases()[0];

        $subQueryBuilder = $this->createQueryBuilder('relation');
        $subQueryBuilder->where(
            $subQueryBuilder->expr()->andX(
                $subQueryBuilder->expr()->eq('relation.customer', $parentAlias),
                $subQueryBuilder->expr()->eq('relation.priceList', ':' . $parameterName)
            )
        );

        $queryBuilder->andWhere($subQueryBuilder->expr()->exists($subQueryBuilder->getQuery()->getDQL()));
        $queryBuilder->setParameter($parameterName, $priceList);
    }

    /**
     * @param Website $website
     * @param CustomerGroup|null $customerGroup
     * @return array
     */
    public function getCustomersWithAssignedPriceLists(Website $website, CustomerGroup $customerGroup = null)
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select('DISTINCT customer.id')
            ->join('p.customer', 'customer')
            ->where($qb->expr()->eq('p.website', ':website'))
            ->setParameter('website', $website);

        if ($customerGroup) {
            $qb->andWhere($qb->expr()->eq('customer.group', ':group'))
                ->setParameter('group', $customerGroup);
        } else {
            $qb->andWhere($qb->expr()->isNull('customer.group'));
        }

        $data = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $data[$row['id']] = true;
        }

        return $data;
    }

    /**
     * @param Website $website
     * @param Customer $customer
     * @return bool
     */
    public function hasAssignedPriceLists(Website $website, Customer $customer): bool
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select('p.id')
            ->where($qb->expr()->eq('p.website', ':website'))
            ->andWhere($qb->expr()->eq('p.customer', ':customer'))
            ->setParameter('website', $website)
            ->setParameter('customer', $customer)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult() !== null;
    }
}
