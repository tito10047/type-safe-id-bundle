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

    public static function pathProvider(): array
    {
        return [
            ['src/Entity', 'Entity'],
            ['src/Domain/Entity', 'Domain\Entity'],
            ['Entity', 'Entity'],
            ['src/Entity/', 'Entity'],
            ['/src/Entity', 'src\Entity'], // Based on current implementation preg_replace('/^src\//', '', $path)
        ];
    }
}
