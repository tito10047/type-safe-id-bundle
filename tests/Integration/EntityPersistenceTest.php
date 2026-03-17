<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\TypeSafeIdBundle\Tests\Fixture\TestKernel;

class EntityPersistenceTest extends TestCase
{
    private Filesystem $fs;
    private ?\Composer\Autoload\ClassLoader $loader = null;
    private string $projectDir;
    private mixed $originalErrorHandler = null;
    private mixed $originalExceptionHandler = null;

    public function setUp(): void
    {
        $this->originalErrorHandler = set_error_handler(static fn() => false);
        restore_error_handler();
        $this->originalExceptionHandler = set_exception_handler(static fn() => false);
        restore_exception_handler();
        $this->projectDir = realpath(sys_get_temp_dir()) . '/type_safe_id_persist_test_' . uniqid();
        $this->fs = new Filesystem();
        $this->fs->mkdir($this->projectDir . '/src/Entity');
        $this->fs->mkdir($this->projectDir . '/src/EntityId');
        $this->fs->mkdir($this->projectDir . '/src/Repository');
        $this->fs->mkdir($this->projectDir . '/migrations');
        $this->fs->mkdir($this->projectDir . '/var');

        file_put_contents($this->projectDir . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/',
                ],
            ],
        ]));

        // Create a real ClassLoader and register it
        $this->loader = new \Composer\Autoload\ClassLoader();
        $this->loader->addPsr4('App\\', $this->projectDir . '/src/');
        $this->loader->register(true);
    }

    public function tearDown(): void
    {
        if ($this->loader) {
            $this->loader->unregister();
        }

        while (set_error_handler(static fn() => false) !== $this->originalErrorHandler) {
            restore_error_handler();
            restore_error_handler();
        }
        restore_error_handler();

        while (set_exception_handler(static fn() => false) !== $this->originalExceptionHandler) {
            restore_exception_handler();
            restore_exception_handler();
        }
        restore_exception_handler();

        if (file_exists($this->projectDir)) {
            $this->fs->remove($this->projectDir);
        }
        parent::tearDown();
    }

    public static function idTypeProvider(): iterable
    {
        yield "UUID" => ['TestUser', '--with-uuid', 'Uuid'];
        yield "ULID" => ['TestProduct', '--with-ulid', 'Ulid'];
        yield "IntId" => ['TestOrder', null, 'IntId'];
    }

    #[DataProvider('idTypeProvider')]
    public function testEntityPersistence(string $className, ?string $argument, string $expectedIdType): void
    {
        // Step 1: Generate entity using make:entity
        $kernel = new TestKernel('dev', true, $this->projectDir);
        $kernel->boot();
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $arguments = [
            'command' => 'make:entity:type',
            'name' => $className
        ];
        if ($argument) {
            $arguments[$argument] = true;
        }
        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        $output = new BufferedOutput();
        $application->run($input, $output);

        // Verify entity files were created
        $this->assertFileExists($this->projectDir . "/src/Entity/{$className}.php");
        $this->assertFileExists($this->projectDir . "/src/EntityId/{$className}Id.php");
        $this->assertFileExists($this->projectDir . "/src/EntityId/{$className}IdType.php");

        require_once $this->projectDir . "/src/EntityId/{$className}Id.php";
        require_once $this->projectDir . "/src/EntityId/{$className}IdType.php";
        require_once $this->projectDir . "/src/Repository/{$className}Repository.php";
        require_once $this->projectDir . "/src/Entity/{$className}.php";

        $kernel->shutdown();
        $this->fs->remove($kernel->getCacheDir());

        // Step 2: Create a fresh kernel and generate migration
        $kernel2 = new TestKernel('dev', true, $this->projectDir);
        $kernel2->boot();
        $application = new Application($kernel2);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'make:migration',
        ]);

        $inputStream = fopen('php://memory', 'r+', false);
        fwrite($inputStream, "\n");
        rewind($inputStream);

        if ($input instanceof StreamableInputInterface) {
            $input->setStream($inputStream);
        }
        $input->setInteractive(true);

        $output = new BufferedOutput();
        $application->run($input, $output);
        fclose($inputStream);

        $migrationFiles = glob($this->projectDir . '/migrations/Version*.php');
        $this->assertNotEmpty($migrationFiles, "Migration file was not generated");

        // Load the migration file so it's available in the second kernel
        foreach ($migrationFiles as $migrationFile) {
            require_once $migrationFile;
        }

        // Step 3: Update database schema (using schema:update instead of migrations due to test isolation issues)
        $input = new ArrayInput([
            'command' => 'doctrine:schema:update',
            '--force' => true,
        ]);
        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);
        $this->assertEquals(0, $exitCode, "Schema update failed: " . $output->fetch());

        // Step 4: Verify table was created
        $connection = $kernel2->getContainer()->get('doctrine')->getConnection();
        $schemaManager = $connection->createSchemaManager();
        // Convert class name to table name using underscore naming (TestUser -> test_user)
        $tableName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));
        $tables = $schemaManager->listTableNames();
        $this->assertContains($tableName, $tables, "Table $tableName was not created in database");

        // Step 5: Test entity persistence
        $entityManager = $kernel2->getContainer()->get('doctrine')->getManager();

        $entityClass = "App\\Entity\\{$className}";
        $entity = new $entityClass();

        // Verify ID is generated on persist
        $this->assertNull($entity->getId(), "ID should be null before persist");

        $entityManager->persist($entity);
        $entityManager->flush();

        $this->assertNotNull($entity->getId(), "ID should be generated after flush");

        $idClass = "App\\EntityId\\{$className}Id";
        $this->assertInstanceOf($idClass, $entity->getId(), "ID should be instance of {$idClass}");

        // Step 6: Test retrieval
        $entityManager->clear();

        $retrievedEntity = $entityManager->find($entityClass, $entity->getId());

        $this->assertNotNull($retrievedEntity, "Entity should be retrievable from database");
        $this->assertEquals($entity->getId(), $retrievedEntity->getId(), "Retrieved entity should have same ID");

        $kernel2->shutdown();
    }
}
