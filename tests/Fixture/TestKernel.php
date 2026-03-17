<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Fixture;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Tito10047\TypeSafeIdBundle\TypeSafeIdBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    private string $cacheHash;
    private string $projectDir;

    public function __construct(string $environment, bool $debug, string $projectDir) {
        parent::__construct($environment, $debug);
        $this->projectDir = $projectDir;
        $this->cacheHash = md5(serialize([$environment, $debug, $projectDir]));
    }

    public function registerBundles(): iterable {
        yield new \Symfony\Bundle\FrameworkBundle\FrameworkBundle();
        yield new DoctrineBundle();
        yield new DoctrineMigrationsBundle();
        yield new MakerBundle();
        yield new TypeSafeIdBundle();
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void {
        $builder->loadFromExtension('framework', [
            'test' => true,
            'secret' => 'test',
            'http_method_override' => false,
            'php_errors' => ['log' => true],
        ]);

		$builder->loadFromExtension('type_safe_id', [
			'entity_path' => 'src/Entity',
			'type_id_path' => 'src/EntityId',
			'repository_path' => 'src/Repository',
		]);

        $container->extension('doctrine', [
            'dbal' => [
                'url' => 'sqlite:///%kernel.project_dir%/var/data.db',
            ],
            'orm' => [
                'validate_xml_mapping' => true,
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                'auto_mapping' => true,
                'mappings' => [
                    'App' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => '%kernel.project_dir%/src/Entity',
                        'prefix' => 'App\Entity',
                        'alias' => 'App',
                    ],
                ],
            ],
        ]);

        $container->extension('doctrine_migrations', [
            'migrations_paths' => [
                'DoctrineMigrations' => '%kernel.project_dir%/migrations',
            ],
            'enable_profiler' => false,
        ]);
    }

    public function getProjectDir(): string {
        return $this->projectDir;
    }

    public function getCacheDir(): string {
        return $this->projectDir . '/var/cache/' . $this->cacheHash;
    }

    public function getLogDir(): string {
        return $this->projectDir . '/var/log';
    }
}
