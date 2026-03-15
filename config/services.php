<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tito10047\TypeSafeIdBundle\IdEntityGenerator;
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

	if ('dev' === $container->env()) {
		if (!class_exists(\Symfony\Bundle\MakerBundle\Generator::class)) {
			throw new \RuntimeException('MakerBundle is not installed, try running "composer require symfony/maker-bundle".');
		}
		$services->set(IdEntityGenerator::class,IdEntityGenerator::class)
			->decorate('maker.generator')
			->args([
				service('.inner'),
			])
		;
	}
};
