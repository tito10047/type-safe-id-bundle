<?php

use Symfony\Bridge\Doctrine\Types\AbstractUidType;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Ulid;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html#configuration
 */
return static function (DefinitionConfigurator $definition): void {
    $definition
        ->rootNode()
            ->children()
                ->scalarNode('entity_path')->defaultValue('src/Entity')->end()
                ->scalarNode('type_id_path')->defaultValue('src/EntityId')->end()
                ->scalarNode('repository_path')->defaultValue('src/Repository')->end()
            ->end()
        ->end()
    ;
};
