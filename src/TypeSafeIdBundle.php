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

		$builder->getDefinition(IdEntityGenerator::class);
    }

	public function build(ContainerBuilder $container): void {
		$container->addCompilerPass(
			new EntityIdTypeRegisterCompilerPass(
				$container->getParameter('kernel.project_dir')
			)
		);
	}
}