<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\TypeSafeIdBundle\Tests\Fixture\TestKernel;

class MakerTest extends TestCase
{
    private Filesystem $fs;

    private ?\Composer\Autoload\ClassLoader $loader = null;
	private string                          $projectDir;

	public function setUp(): void
    {
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
        if (file_exists($this->projectDir)) {
//            $this->fs->remove($this->projectDir);
        }
		parent::tearDown();

    }

	public static function commandProvider():iterable {
		yield "--with-ulid"=>['Foo1','--with-ulid'];
		yield "--with-uuid"=>['Foo2','--with-uuid'];
		yield "no option"=>['Foo3',null];
	}

	#[DataProvider('commandProvider')]
	public function testGenerateEntity(string $className, ?string $argument): void
    {
        $kernel = new TestKernel('dev', true, $this->projectDir);
        $kernel->boot();
        $application = new Application($kernel);
        $application->setAutoExit(false);

		$arguments   = [
			'command'     => 'make:entity',
			'name'        => $className
		];
		if ($argument) {
			$arguments[$argument] = true;
		}
		$input = new ArrayInput($arguments);
        $input->setInteractive(false);
        $output = new BufferedOutput();
        $application->run($input, $output);

		$this->checkClassAndRequire($this->projectDir . "/src/EntityId/{$className}Id.php");
		$this->checkClassAndRequire($this->projectDir . "/src/EntityId/{$className}IdType.php");
		$this->checkClassAndRequire($this->projectDir . "/src/Repository/{$className}Repository.php");
		$this->checkClassAndRequire($this->projectDir . "/src/Entity/{$className}.php");

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
        $input->setInteractive(false);
        $output = new BufferedOutput();
        $application->run($input, $output);

        $migrationFiles = glob($this->projectDir . '/migrations/Version*.php');
        $this->assertNotEmpty($migrationFiles);
        $migrationContent = file_get_contents($migrationFiles[0]);
        // ULID should be VARCHAR(26) or similar depending on DB, but for SQLite it might be different.
        // Let's check if it's there.
        $this->assertStringContainsString('CREATE TABLE foo', $migrationContent);
		$kernel2->shutdown();
    }

	private function checkClassAndRequire(string $string) {
		$this->assertFileExists($string);
		require_once $string;
	}

}
