<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository as BatchJobRepository;

use Doctrine\ORM\EntityManager;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *
 * @covers \Oro\Bundle\ProductBundle\ImportExport\TemplateFixture\ProductFixture
 */
class ImportExportTest extends WebTestCase
{
    /**
     * @var string
     */
    protected $file;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    /**
     * Delete data required because there is commit to job repository in import/export controller action
     * Please use
     *   $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->beginTransaction();
     *   $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->rollback();
     *   $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->getConnection()->clear();
     * if you don't use controller
     */
    protected function tearDown()
    {
        // clear DB from separate connection
        $batchJobManager = $this->getBatchJobManager();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:JobInstance')->execute();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:JobExecution')->execute();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:StepExecution')->execute();

        parent::tearDown();
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getBatchJobManager()
    {
        /** @var BatchJobRepository $batchJobRepository */
        $batchJobRepository = $this->getContainer()->get('akeneo_batch.job_repository');

        return $batchJobRepository->getJobManager();
    }

    /**
     * @param string $strategy
     * @dataProvider strategyDataProvider
     */
    public function testImportExport($strategy)
    {
        // @todo - must be fixed in BAP-12713
        $importTemplateFile = $this->getImportTemplate();
        $this->validateImportFile($strategy, $importTemplateFile);
        $data = $this->doImport($strategy);
        $this->assertImportResponse($data, 1, 0);

        $this->doExport();
        $this->validateExportResultWithImportTemplate($importTemplateFile);
    }

    /**
     * @return array
     */
    public function strategyDataProvider()
    {
        return [
            'add or replace' => ['oro_product_product.add_or_replace'],
        ];
    }

    /**
     * @param string $strategy
     * @param string $file
     */
    protected function validateImportFile($strategy, $file)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_form',
                [
                    'entity' => Product::class,
                    '_widgetContainer' => 'dialog',
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertFileExists($file);

        /** @var Form $form */
        $form = $crawler->selectButton('Submit')->form();

        /** TODO Change after BAP-1813 */
        $form->getFormNode()->setAttribute(
            'action',
            $form->getFormNode()->getAttribute('action') . '&_widgetContainer=dialog'
        );

        $form['oro_importexport_import[file]']->upload($file);
        $form['oro_importexport_import[processorAlias]'] = $strategy;

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $crawler = $this->client->getCrawler();
        $this->assertEquals(0, $crawler->filter('.import-errors')->count());
    }

    protected function doExport()
    {
        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_export_instant',
                [
                    'processorAlias' => 'oro_product_product',
                    '_format' => 'json',
                ]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertTrue($data['success']);

        // @todo - tests must be implemented after BAP-12589
//        $this->assertEquals(1, $data['readsCount']);
//        $this->assertEquals(0, $data['errorsCount']);

//        $this->client->request(
//            'GET',
//            $data['url'],
//            [],
//            [],
//            $this->generateNoHashNavigationHeader()
//        );

//        $result = $this->client->getResponse();
//        $this->assertResponseStatusCodeEquals($result, 200);
//        $this->assertResponseContentTypeEquals($result, 'text/csv');
    }

    /**
     * @param string $importTemplateFile
     */
    protected function validateExportResultWithImportTemplate($importTemplateFile)
    {
        $importTemplate = $this->getFileContents($importTemplateFile);
        $exportedData = $this->getFileContents($this->getExportFile());

        $commonFields = array_intersect($importTemplate[0], $exportedData[0]);

        $importTemplateValues = $this->extractFieldValues($commonFields, $importTemplate);
        $exportedDataValues = $this->extractFieldValues($commonFields, $exportedData);

        $this->assertEquals($importTemplateValues, $exportedDataValues);
    }

    /**
     * @param array $fields
     * @param array $data
     * @return array
     */
    protected function extractFieldValues(array $fields, array $data)
    {
        $values = [];
        foreach ($fields as $field) {
            $key = array_search($field, $data[0], true);
            if (false !== $key) {
                $values[$field] = $data[1][$key];
            }
        }

        return $values;
    }

    /**
     * @param string $strategy
     * @return array
     */
    protected function doImport($strategy)
    {
        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_process',
                [
                    'processorAlias' => $strategy,
                    '_format' => 'json',
                ]
            )
        );

        return $this->getJsonResponseContent($this->client->getResponse(), 200);
    }

    /**
     * @return string
     */
    protected function getImportTemplate()
    {
        $result = $this
            ->getContainer()
            ->get('oro_importexport.handler.export')
            ->getExportResult(
                JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV,
                'oro_product_product_export_template',
                ProcessorRegistry::TYPE_EXPORT_TEMPLATE
            );

        $chains = explode('/', $result['url']);

        return $this
            ->getContainer()
            ->get('oro_importexport.file.file_system_operator')
            ->getTemporaryFile(end($chains))
            ->getRealPath();
    }

    /**
     * @return string
     */
    protected function getExportFile()
    {
        $result = $this
            ->getContainer()
            ->get('oro_importexport.handler.export')
            ->handleExport(
                JobExecutor::JOB_EXPORT_TO_CSV,
                'oro_product_product',
                ProcessorRegistry::TYPE_EXPORT
            );

        $this->assertResponseStatusCodeEquals($result, 200);
        $this->assertResponseContentTypeEquals($result, 'application/json');

        $result = json_decode($result->getContent(), true);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['errorsCount']);

        $chains = explode('/', $result['url']);

        return $this
            ->getContainer()
            ->get('oro_importexport.file.file_system_operator')
            ->getTemporaryFile(end($chains))
            ->getRealPath();
    }

    /**
     * @param string $fileName
     * @return array
     */
    protected function getFileContents($fileName)
    {
        $content = file_get_contents($fileName);
        $content = explode("\n", $content);
        $content = array_filter($content, 'strlen');

        return array_map('str_getcsv', $content);
    }

    /**
     * @param string $exportFile
     * @param int $expectedItemsCount
     */
    protected function validateExportResult($exportFile, $expectedItemsCount)
    {
        $exportedData = $this->getFileContents($exportFile);
        unset($exportedData[0]);

        $this->assertCount($expectedItemsCount, $exportedData);
    }

    /**
     * @param string $fileName
     * @param array $contextErrors
     *
     * @dataProvider validationDataProvider
     */
    public function testValidation($fileName, array $contextErrors = [])
    {
        $this->setSecurityToken();
        $this->cleanUpReader();

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $fileName;

        $configuration = [
            'import_validation' => [
                'processorAlias' => 'oro_product_product.add_or_replace',
                'entityName' => $this->getContainer()->getParameter('oro_product.entity.product.class'),
                'filePath' => $filePath,
            ],
        ];

        $jobResult = $this->getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT_VALIDATION,
            JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV,
            $configuration
        );

        $exceptions = $jobResult->getFailureExceptions();
        $this->assertEmpty($exceptions, implode(PHP_EOL, $exceptions));

        // owner is not available in cli context, managed using ConsoleContextListener
        $errors = array_filter(
            $jobResult->getContext()->getErrors(),
            function ($error) {
                return strpos($error, 'owner: This value should not be blank.') === false
                && strpos($error, 'Unit of Quantity Unit Code: This value should not be blank.') === false;
            }
        );
        $this->assertEquals($contextErrors, array_values($errors), implode(PHP_EOL, $errors));
        $this->getContainer()->get('security.token_storage')->setToken(null);
    }

    protected function cleanUpReader()
    {
        $reader = $this->getContainer()->get('oro_importexport.reader.csv');
        $reflection = new \ReflectionProperty(get_class($reader), 'file');
        $reflection->setAccessible(true);
        $reflection->setValue($reader, null);
        $reflection = new \ReflectionProperty(get_class($reader), 'header');
        $reflection->setAccessible(true);
        $reflection->setValue($reader, null);
    }

    /**
     * @return array
     */
    public function validationDataProvider()
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'import_validation.yml';

        return Yaml::parse(file_get_contents($filePath));
    }

    public function testImportRelations()
    {
        $this->setSecurityToken();
        $this->cleanUpReader();

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'import.csv';

        $productClass = $this->getContainer()->getParameter('oro_product.entity.product.class');
        $configuration = [
            'import' => [
                'processorAlias' => 'oro_product_product.add_or_replace',
                'entityName' => $productClass,
                'filePath' => $filePath,
            ],
        ];

        $jobResult = $this->getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $configuration
        );

        $exceptions = $jobResult->getFailureExceptions();
        $this->assertEmpty($exceptions, implode(PHP_EOL, $exceptions));
        $this->assertEmpty(
            $jobResult->getContext()->getErrors(),
            implode(PHP_EOL, $jobResult->getContext()->getErrors())
        );

        $em = $this->getContainer()->get('doctrine')->getManagerForClass($productClass);

        /** @var Product $product */
        $product = $em->getRepository($productClass)->findOneBy(['sku' => 'SKU099']);

        $this->assertNotEmpty($product);
        $this->assertEquals('enabled', $product->getStatus());
        $this->assertEquals('in_stock', $product->getInventoryStatus()->getId());

        $this->assertCount(1, $product->getUnitPrecisions());
        $this->assertEquals('each', $product->getUnitPrecisions()->first()->getUnit()->getCode());
        $this->assertEquals(3, $product->getUnitPrecisions()->first()->getPrecision());

        $this->assertCount(2, $product->getNames());
        $this->assertEquals('parent_localization', $product->getNames()->first()->getFallback());
        $this->assertEquals('Name', $product->getNames()->first()->getString());
        $this->assertEquals('system', $product->getNames()->last()->getFallback());
        $this->assertEquals('En Name', $product->getNames()->last()->getString());

        $this->getContainer()->get('security.token_storage')->setToken(null);
    }

    public function testSkippedTypeForExistingProduct()
    {
        $token = new OrganizationToken(
            $this->getContainer()->get('doctrine')->getRepository('OroOrganizationBundle:Organization')->findOneBy([])
        );
        $token->setUser(
            $this->getContainer()->get('doctrine')->getRepository('OroUserBundle:User')->findOneBy([])
        );
        $this->getContainer()->get('security.token_storage')->setToken($token);

        $this->cleanUpReader();

        $dataPath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;

        $productClass = $this->getContainer()->getParameter('oro_product.entity.product.class');
        $configuration = [
            'import' => [
                'processorAlias' => 'oro_product_product.add_or_replace',
                'entityName' => $productClass,
                'filePath' => $dataPath . 'import.csv',
            ],
        ];

        $this->getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $configuration
        );

        $this->cleanUpReader();

        $configuration = [
            'import' => [
                'processorAlias' => 'oro_product_product.add_or_replace',
                'entityName' => $productClass,
                'filePath' => $dataPath . 'import_with_type.csv',
            ],
        ];

        $this->getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $configuration
        );

        $em = $this->getContainer()->get('doctrine')->getManagerForClass($productClass);

        /** @var Product $product */
        $product = $em->getRepository($productClass)->findOneBy(['sku' => 'SKU099']);

        $this->assertNotEmpty($product);
        $this->assertNotEquals(Product::TYPE_CONFIGURABLE, $product->getType());
        $this->assertEquals(Product::STATUS_DISABLED, $product->getStatus());

        $this->getContainer()->get('security.token_storage')->setToken(null);
    }

    /**
     * @dataProvider strategyDataProvider
     * @param string $strategy
     */
    public function testAddNewProducts($strategy)
    {
        $this->loadFixtures([LoadProductData::class]);
        $productClass = $this->getContainer()->getParameter('oro_product.entity.product.class');

        $file = $this->getExportFile();
        $this->validateExportResult($file, 8);

        $doctrine = $this->getContainer()->get('doctrine');

        /** @var EntityManager $productManager */
        $productManager = $doctrine->getManagerForClass($productClass);
        $productManager->createQuery('DELETE FROM OroProductBundle:Product')->execute();

        $this->validateImportFile($strategy, $file);
        $data = $this->doImport($strategy);
        $this->assertImportResponse($data, 8, 0);

        $products = $productManager->getRepository($productClass)->findAll();
        $this->assertCount(8, $products);
    }

    /**
     * @dataProvider strategyDataProvider
     * @param string $strategy
     */
    public function testUpdateProducts($strategy)
    {
        $this->loadFixtures([LoadProductData::class]);

        $file = $this->getExportFile();
        $this->validateExportResult($file, 8);

        $this->validateImportFile($strategy, $file);
        $data = $this->doImport($strategy);
        $this->assertImportResponse($data, 0, 8);
    }

    /**
     * @param array $data
     * @param int $added
     * @param int $updated
     */
    protected function assertImportResponse(array $data, $added, $updated)
    {
        $this->assertEquals(
            [
                'success'    => true,
                'message'    => 'File was successfully imported.',
                'errorsUrl'  => null,
                'importInfo' => $added . ' products were added, ' . $updated . ' products were updated',
            ],
            $data
        );
    }

    protected function setSecurityToken()
    {
        $token = new OrganizationToken(
            $this->getContainer()->get('doctrine')->getRepository('OroOrganizationBundle:Organization')->findOneBy([])
        );
        $token->setUser(
            $this->getContainer()->get('doctrine')->getRepository('OroUserBundle:User')->findOneBy([])
        );
        $this->getContainer()->get('security.token_storage')->setToken($token);
    }
}
