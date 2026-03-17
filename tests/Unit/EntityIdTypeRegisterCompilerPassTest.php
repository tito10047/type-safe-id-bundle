<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tito10047\TypeSafeIdBundle\EntityIdTypeRegisterCompilerPass;
use Tito10047\TypeSafeIdBundle\IdGenerator\UniversalTypeIdGenerator;
use Tito10047\TypeSafeIdBundle\Util\PathUtil;

class EntityIdTypeRegisterCompilerPassTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/type_safe_id_bundle_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $path): void
    {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }

    public function testProcessDoesNothingIfParameterIsMissing(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('hasParameter')
            ->with('type_safe_id.entity_namespace')
            ->willReturn(false);

        $pass = new EntityIdTypeRegisterCompilerPass('/tmp');
        $pass->process($container);
    }

    public function testProcessRegistersTypesAndUniversalGenerator(): void
    {
        $projectDir = realpath(__DIR__ . '/../../..');
        $pass = new EntityIdTypeRegisterCompilerPass($this->tempDir);

        // Create some dummy ID types
        $typeIdPath = 'src/TypeId';
        mkdir($this->tempDir . '/' . $typeIdPath, 0777, true);
        
        file_put_contents($this->tempDir . '/' . $typeIdPath . '/ProductIdType.php', "<?php
namespace Tito10047\\TypeSafeIdBundle\\Tests\\Unit\\Fixture;
use Tito10047\\TypeSafeIdBundle\\AbstractIntIdType;
class ProductIdType extends AbstractIntIdType {
    protected function getIntIdClass(): string { return ProductId::class; }
    public function getName(): string { return 'product_id'; }
}");

        file_put_contents($this->tempDir . '/' . $typeIdPath . '/ProductId.php', "<?php
namespace Tito10047\\TypeSafeIdBundle\\Tests\\Unit\\Fixture;
use Tito10047\\TypeSafeIdBundle\\IntId;
class ProductId extends IntId {}");

        // We need to load these classes so Reflection works
        require_once $this->tempDir . '/' . $typeIdPath . '/ProductId.php';
        require_once $this->tempDir . '/' . $typeIdPath . '/ProductIdType.php';

        $container = new ContainerBuilder();
        $container->setParameter('type_safe_id.entity_namespace', 'Tito10047\TypeSafeIdBundle\Tests\Unit\Fixture');
        $container->setParameter('type_safe_id.type_id_namespace', 'Tito10047\TypeSafeIdBundle\Tests\Unit\Fixture');
        $container->setParameter('doctrine.dbal.connection_factory.types', []);

        // Tell PathUtil where to find this namespace
        $loader = PathUtil::getClassLoader();
        if ($loader) {
            $loader->addPsr4('Tito10047\\TypeSafeIdBundle\\Tests\\Unit\\Fixture\\', [$this->tempDir . '/' . $typeIdPath]);
        }
        $path = PathUtil::namespaceToPath('Tito10047\TypeSafeIdBundle\Tests\Unit\Fixture');
        
        $pass->process($container);

        $types = $container->getParameter('doctrine.dbal.connection_factory.types');
        $this->assertArrayHasKey('Tito10047\TypeSafeIdBundle\Tests\Unit\Fixture\ProductIdType', $types);
        
        $this->assertTrue($container->hasDefinition('doctrine.id_generator.universal'));
        $generatorDef = $container->getDefinition('doctrine.id_generator.universal');
        $this->assertSame(UniversalTypeIdGenerator::class, $generatorDef->getClass());
        
        $mapping = $generatorDef->getArgument(0);
        $this->assertArrayHasKey('Tito10047\TypeSafeIdBundle\Tests\Unit\Fixture\Product', $mapping);
        $this->assertSame('Tito10047\TypeSafeIdBundle\Tests\Unit\Fixture\ProductId', $mapping['Tito10047\TypeSafeIdBundle\Tests\Unit\Fixture\Product']);
    }
}
