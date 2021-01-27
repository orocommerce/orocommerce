<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\OrganizationBundle\Tests\Functional\OrganizationTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryListenerTest extends WebTestCase
{
    use OrganizationTrait, CatalogTrait;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var EntityManager */
    private $entityManager;

    /** @var CategoryRepository */
    private $categoryRepo;

    /** @var Category  */
    private $rootCategory;

    protected function setUp(): void
    {
        $this->initClient();

        /** @var CategoryRepository $categoryRepo */
        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->categoryRepo = $this->doctrine->getRepository(Category::class);
        $this->rootCategory = $this->getRootCategory();
        $this->entityManager = $this->doctrine->getManagerForClass(Category::class);
    }

    /**
     * @return Category
     */
    public function testPostPersist(): Category
    {
        $category1 = $this->createCategory('Sample Category 1', $this->rootCategory);
        $category2 = $this->createCategory('Sample Category 2', $category1);
        // Persists categories in reverse order to ensure the listener can handle it as well.
        $this->entityManager->persist($category2);
        $this->entityManager->persist($category1);
        $this->entityManager->flush();

        $this->assertEquals($this->rootCategory->getId().'_'.$category1->getId(), $category1->getMaterializedPath());
        $this->assertEquals(
            $this->rootCategory->getId().'_'.$category1->getId().'_'.$category2->getId(),
            $category2->getMaterializedPath()
        );

        return $category2;
    }

    /**
     * @depends testPostPersist
     *
     * @param Category $category2
     *
     * @return Category
     */
    public function testOnFlushWhenParentDoesNotHaveId(Category $category2): Category
    {
        $category1 = $category2->getParentCategory();

        $category3 = $this->createCategory('Sample Category 3', $this->rootCategory);
        $this->entityManager->persist($category3);

        $category1->setParentCategory($category3);
        $this->entityManager->persist($category1);

        $this->entityManager->flush();

        $this->assertEquals(
            $this->rootCategory->getId().'_'.$category3->getId().'_'.$category1->getId(),
            $category1->getMaterializedPath()
        );
        $this->assertEquals(
            $this->rootCategory->getId().'_'.$category3->getId().'_'.$category1->getId().'_'.$category2->getId(),
            $category2->getMaterializedPath()
        );

        return $category2;
    }

    /**
     * @depends testOnFlushWhenParentDoesNotHaveId
     *
     * @param Category $category2
     */
    public function testOnFlush(Category $category2): void
    {
        $category2->setParentCategory($this->rootCategory);

        $this->entityManager->persist($category2);
        $this->entityManager->flush();

        $this->assertEquals($this->rootCategory->getId().'_'.$category2->getId(), $category2->getMaterializedPath());
    }

    /**
     * @param string $title
     * @param Category $parentCategory
     *
     * @return Category
     */
    private function createCategory(string $title, Category $parentCategory): Category
    {
        $category = new Category();
        $category->setParentCategory($parentCategory);
        $category->addTitle((new CategoryTitle())->setString($title));

        return $category;
    }
}
