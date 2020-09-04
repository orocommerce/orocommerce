<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\FrontendLineItemsGridExtension;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FrontendLineItemsGridExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListRepository;

    /** @var LineItemRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemRepository;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ParameterBag */
    private $parameters;

    /** @var FrontendLineItemsGridExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->shoppingListRepository = $this->createMock(ShoppingListRepository::class);
        $this->lineItemRepository = $this->createMock(LineItemRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [ShoppingList::class, $this->shoppingListRepository],
                    [LineItem::class, $this->lineItemRepository]
                ]
            );

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->configManager = $this->createMock(ConfigManager::class);

        $this->parameters = new ParameterBag();

        $this->extension = new FrontendLineItemsGridExtension($registry, $this->configManager);
        $this->extension->setParameters($this->parameters);
    }

    public function testIsApplicable(): void
    {
        $config = DatagridConfiguration::create(['name' => 'frontend-customer-user-shopping-list-grid']);

        $this->assertTrue($this->extension->isApplicable($config));
    }

    public function testIsNotApplicable(): void
    {
        $config = DatagridConfiguration::create(['name' => 'shopping-list-line-items-grid']);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testProcessConfigs(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.my_shopping_lists_max_line_items_per_page')
            ->willReturn(1000);

        $this->shoppingListRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn((new ShoppingListStub())->setLineItemsCount(900));

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [
                                10,
                                25,
                                50,
                                100,
                                [
                                    'label' => 'oro.shoppinglist.datagrid.toolbar.pageSize.all.label',
                                    'size' => 1000
                                ]
                            ],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
                        ],
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsWithoutId(): void
    {
        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->never())
            ->method('get');

        $this->shoppingListRepository->expects($this->never())
            ->method('find');

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
                        ],
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsCountMoreThanConfig(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.my_shopping_lists_max_line_items_per_page')
            ->willReturn(1000);

        $this->shoppingListRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn((new ShoppingListStub())->setLineItemsCount(2000));

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100, 1000],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
                        ],
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsCountLessThanConfig(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
            ]
        );

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.my_shopping_lists_max_line_items_per_page')
            ->willReturn(1000);

        $this->shoppingListRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn((new ShoppingListStub())->setLineItemsCount(999));

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [
                                10,
                                25,
                                50,
                                100,
                                [
                                    'label' => 'oro.shoppinglist.datagrid.toolbar.pageSize.all.label',
                                    'size' => 1000
                                ]
                            ],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            'lineItem.id',
                        ],
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testProcessConfigsWithGrouping(): void
    {
        $this->parameters->set('_parameters', ['group' => true]);

        $config = DatagridConfiguration::create(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
            ]
        );

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'options' => [
                    'toolbarOptions' => [
                        'pageSize' => [
                            'items' => [10, 25, 50, 100],
                        ],
                    ],
                ],
                'source' => [
                    'query' => [
                        'select' => [
                            '(SELECT GROUP_CONCAT(innerItem.id) ' .
                            'FROM Oro\Bundle\ShoppingListBundle\Entity\LineItem innerItem ' .
                            'WHERE (innerItem.parentProduct = lineItem.parentProduct ' .
                            'OR innerItem.product = lineItem.product) ' .
                            'AND innerItem.shoppingList = lineItem.shoppingList ' .
                            'AND innerItem.unit = lineItem.unit) as allLineItemsIds',
                        ],
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testVisitMetadata(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $data = MetadataObject::create([]);

        $this->lineItemRepository->expects($this->once())
            ->method('hasEmptyMatrix')
            ->with(42)
            ->willReturn(true);

        $this->lineItemRepository->expects($this->once())
            ->method('canBeGrouped')
            ->with(42)
            ->willReturn(true);

        $shoppingList = new ShoppingListStub();
        $shoppingList->setLabel('Shopping List Label');
        $this->shoppingListRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn(($shoppingList)->setLineItemsCount(900));

        $this->extension->visitMetadata(DatagridConfiguration::create([]), $data);

        $this->assertTrue($data->offsetGetByPath('hasEmptyMatrix'));
        $this->assertTrue($data->offsetGetByPath('canBeGrouped'));
        $this->assertEquals('Shopping List Label', $data->offsetGetByPath('shoppingListLabel'));
    }

    public function testVisitMetadataWithoutId(): void
    {
        $data = MetadataObject::create([]);

        $this->lineItemRepository->expects($this->never())
            ->method('hasEmptyMatrix');

        $this->lineItemRepository->expects($this->never())
            ->method('canBeGrouped');

        $this->extension->visitMetadata(DatagridConfiguration::create([]), $data);

        $this->assertNull($data->offsetGetByPath('hasEmptyMatrix'));
    }

    public function testVisitResult(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $data = ResultsObject::create([]);

        $this->lineItemRepository->expects($this->once())
            ->method('hasEmptyMatrix')
            ->with(42)
            ->willReturn(true);

        $this->lineItemRepository->expects($this->once())
            ->method('canBeGrouped')
            ->with(42)
            ->willReturn(true);

        $this->extension->visitResult(DatagridConfiguration::create([]), $data);

        $this->assertTrue($data->offsetGetByPath('[metadata][hasEmptyMatrix]'));
        $this->assertTrue($data->offsetGetByPath('[metadata][canBeGrouped]'));
    }

    public function testVisitResultWithoutId(): void
    {
        $data = ResultsObject::create([]);

        $this->lineItemRepository->expects($this->never())
            ->method('hasEmptyMatrix');

        $this->lineItemRepository->expects($this->never())
            ->method('canBeGrouped');

        $this->extension->visitResult(DatagridConfiguration::create([]), $data);

        $this->assertNull($data->offsetGetByPath('[metadata][hasEmptyMatrix]'));
    }
}
