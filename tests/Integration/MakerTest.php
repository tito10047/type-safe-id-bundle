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

class MakerTest extends TestCase
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
        $this->projectDir = realpath(sys_get_temp_dir()) . '/type_safe_id_bundle_test_' . uniqid();
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
            'autoload-dev' => [
                'psr-4' => [
                    'App\\Tests\\' => 'tests/',
                ],
            ],
        ]));

        // Create a real ClassLoader and register it
        $this->loader = new \Composer\Autoload\ClassLoader();
        $this->loader->addPsr4('App\\', $this->projectDir . '/src/');
        $this->loader->register(true); // Prepend to be sure

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

	public static function commandProvider():iterable {
		// SQLite maps ULID (CHAR(26)) and UUID (BINARY(16)) both to BLOB
		// In real MySQL/PostgreSQL, UUID would be BINARY(16) and ULID would be CHAR(26)
		yield "--with-ulid"=>['Foo1','--with-ulid','Ulid', 'BLOB'];
		yield "--with-uuid"=>['Foo2','--with-uuid','Uuid', 'BLOB'];
		yield "no option"=>['Foo3',null,'IntId', 'INTEGER'];
	}

	#[DataProvider('commandProvider')]
	public function testGenerateEntity(string $className, ?string $argument, string $expectedIdType, string $expectedSqlType): void
    {
        $kernel = new TestKernel('dev', true, $this->projectDir);
        $kernel->boot();
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $this->assertTrue($application->has('make:entity:typesafe'), 'Command make:entity:typesafe not found');

		$arguments   = [
			'command'     => 'make:entity:typesafe',
			'name'        => $className
		];
		if ($argument) {
			$arguments[$argument] = true;
		}
		$input = new ArrayInput($arguments);
		$input->setInteractive(false);

  $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);

        $this->assertEquals(0, $exitCode, "Command failed with exit code " . $exitCode);

		$this->checkClassAndRequire($this->projectDir . "/src/EntityId/{$className}Id.php");
		$this->checkClassAndRequire($this->projectDir . "/src/EntityId/{$className}IdType.php");
		$this->checkClassAndRequire($this->projectDir . "/src/Repository/{$className}Repository.php");
		$this->checkClassAndRequire($this->projectDir . "/src/Entity/{$className}.php");

		$a1 = file_get_contents($this->projectDir . "/src/EntityId/{$className}Id.php");
		$a2 = file_get_contents($this->projectDir . "/src/EntityId/{$className}IdType.php");

		$namespace = "App\\EntityId\\{$className}Id";
		$entityId = new $namespace();
		$this->assertInstanceOf($namespace, $entityId);

		$kernel->shutdown();
		$this->fs->remove($kernel->getCacheDir());
        // We need a NEW kernel instance after files are generated so CompilerPass can find them
        $kernel2 = new TestKernel('dev', true, $this->projectDir);
        $kernel2->boot();
        $application = new Application($kernel2);
        $application->setAutoExit(false);

        $outputText = $output->fetch();

        // Test migration generation
        $input = new ArrayInput([
            'command' => 'make:migration',
        ]);

        // Create a stream with automatic "yes" responses for interactive prompts
        $inputStream = fopen('php://memory', 'r+', false);
        fwrite($inputStream, "\n"); // Auto-confirm any prompts
        rewind($inputStream);

        if ($input instanceof StreamableInputInterface) {
            $input->setStream($inputStream);
        }
        $input->setInteractive(true); // Enable interactive mode with piped input

        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);

        $this->assertEquals(0, $exitCode, "Migration command failed with exit code " . $exitCode);
        fclose($inputStream);

        $migrationFiles = glob($this->projectDir . '/migrations/Version*.php');
        $this->assertNotEmpty($migrationFiles, "Migration file was not generated");
        $migrationContent = file_get_contents($migrationFiles[0]);

        // Convert class name to table name using underscore naming (Foo1 -> foo1, FooBar -> foo_bar)
        $tableName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));

        // Check that migration creates the table
        $this->assertStringContainsString("CREATE TABLE", $migrationContent,
            "Migration should create table");

        // Check that ID column has correct SQL type
        $this->assertStringContainsString($expectedSqlType, $migrationContent,
            "Migration should use $expectedSqlType for $expectedIdType ID type. Got: " . $migrationContent);

        // Run schema update to create the database schema
        $input = new ArrayInput([
            'command' => 'doctrine:schema:update',
            '--force' => true,
        ]);
        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);
        $this->assertEquals(0, $exitCode, "Schema update failed: " . $output->fetch());

        // Verify the table was created in SQLite
        $connection = $kernel2->getContainer()->get('doctrine')->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();
        $this->assertContains($tableName, $tables, "Table $tableName was not created in database");

        // Verify the column exists
        $columns = $schemaManager->listTableColumns($tableName);
        $this->assertArrayHasKey('id', $columns, "ID column was not created");

		$kernel2->shutdown();
    }

	private function checkClassAndRequire(string $string) {
		$this->assertFileExists($string);
		require_once $string;
	}

}
