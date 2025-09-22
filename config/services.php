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
		$services->set(IdEntityGenerator::class)
			->decorate('maker.generator')
			->args([
				service('.inner'),
			])
		;
	}
};
