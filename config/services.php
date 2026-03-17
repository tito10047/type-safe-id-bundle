<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tito10047\TypeSafeIdBundle\IdGenerator\TypeIdGenerator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html#services
 */
return static function (ContainerConfigurator $container): void {
    $container
        ->parameters()
            // ->set('tito10047_type.param_name', 'param_value');
    ;
	$services = $container->services();

	// Register TypeIdGenerator as a service
	$services->set('doctrine.type_id_generator', TypeIdGenerator::class)
		->args([abstract_arg('Type ID class')])
		->abstract();

	if ('dev' === $container->env()) {
		if (!class_exists(\Symfony\Bundle\MakerBundle\Generator::class)) {
			throw new \RuntimeException('MakerBundle is not installed, try running "composer require symfony/maker-bundle".');
		}

		$services->set(Tito10047\TypeSafeIdBundle\Maker\MakeTypeSafeEntity::class)
			->args([
				'%type_safe_id.entity_path%',
				'%type_safe_id.type_id_path%',
				'%type_safe_id.repository_path%',
			])
			->tag('maker.command')
		;
	}
};
