<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tito10047\TypeSafeIdBundle\Util\PathUtil;

class PathUtilTest extends TestCase
{
    #[DataProvider('pathProvider')]
    public function testPathToNamespace(string $path, string $expectedNamespace): void
    {
        $this->assertSame($expectedNamespace, PathUtil::pathToNamespace($path));
    }

    #[DataProvider('namespaceProvider')]
    public function testNamespaceToPath(string $namespace, string $expectedSuffix): void
    {
        $path = PathUtil::namespaceToPath($namespace);
        $this->assertStringEndsWith($expectedSuffix, $path);
    }

    public static function pathProvider(): array
    {
        return [
            ['src/Entity', 'Entity'],
            ['src/Domain/Entity', 'Domain\Entity'],
            ['Entity', 'Entity'],
            ['src/Entity/', 'Entity'],
            ['/src/Entity', 'src\Entity'],
        ];
    }

    public static function namespaceProvider(): array
    {
        return [
            ['Tito10047\TypeSafeIdBundle\Entity', 'src/Entity'],
            ['Tito10047\TypeSafeIdBundle\Domain\Entity', 'src/Domain/Entity'],
            ['Other\Namespace', 'Other/Namespace'],
        ];
    }
}
