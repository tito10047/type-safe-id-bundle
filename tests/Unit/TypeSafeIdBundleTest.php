<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tito10047\TypeSafeIdBundle\EntityIdTypeRegisterCompilerPass;
use Tito10047\TypeSafeIdBundle\TypeSafeIdBundle;

class TypeSafeIdBundleTest extends TestCase
{
    public function testBuildAddsCompilerPass(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', '/tmp');

        $bundle = new TypeSafeIdBundle();
        $bundle->build($container);

        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $found = false;
        foreach ($passes as $pass) {
            if ($pass instanceof EntityIdTypeRegisterCompilerPass) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'EntityIdTypeRegisterCompilerPass was not added');
    }
}
