<?php

namespace Tito10047\TypeSafeIdBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html
 */
class TypeSafeIdBundle extends AbstractBundle
{
	protected string $extensionAlias = 'type_safe_id';
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }
    
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

		$container->parameters()
			->set('type_safe_id.entity_namespace', $config['entity_namespace'])
			->set('type_safe_id.type_id_namespace', $config['type_id_namespace'])
			->set('type_safe_id.repository_namespace', $config['repository_namespace'])
		;
    }

	public function build(ContainerBuilder $container): void {
		// Register our compiler pass with high priority to run before DoctrineBundle's IdGeneratorPass
		$container->addCompilerPass(
			new EntityIdTypeRegisterCompilerPass(
				$container->getParameter('kernel.project_dir')
			),
			\Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION,
			100
		);
	}
}