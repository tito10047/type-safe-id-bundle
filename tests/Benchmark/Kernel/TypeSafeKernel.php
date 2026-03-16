<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Benchmark\Kernel;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Tito10047\TypeSafeIdBundle\TypeSafeIdBundle;
use Tito10047\TypeSafeIdBundle\EntityIdTypeRegisterCompilerPass;

/**
 * Kernel with TypeSafeIdBundle enabled
 */
class TypeSafeKernel extends Kernel
{
    use MicroKernelTrait;

    private string $cacheHash;
    private string $projectDir;

    public function __construct(string $environment, bool $debug, string $projectDir)
    {
        parent::__construct($environment, $debug);
        $this->projectDir = $projectDir;
        $this->cacheHash = md5(serialize(['typesafe', $environment, $debug, $projectDir]));
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new EntityIdTypeRegisterCompilerPass($this->projectDir),
            \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION,
            100
        );
    }

    public function registerBundles(): iterable
    {
        yield new \Symfony\Bundle\FrameworkBundle\FrameworkBundle();
        yield new DoctrineBundle();
        yield new TypeSafeIdBundle();
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $builder->loadFromExtension('framework', [
            'secret' => 'benchmark_secret',
            'http_method_override' => false,
            'php_errors' => ['log' => true],
        ]);

        $container->extension('doctrine', [
            'dbal' => [
                'url' => 'sqlite:///:memory:',
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
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    public function getCacheDir(): string
    {
        return $this->projectDir . '/var/cache/' . $this->cacheHash;
    }

    public function getLogDir(): string
    {
        return $this->projectDir . '/var/log';
    }
}
