<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Unit;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Tito10047\TypeSafeIdBundle\AbstractIntIdType;
use Tito10047\TypeSafeIdBundle\IntId;

class TestIntIdForType extends IntId {}

class TestIntIdType extends AbstractIntIdType
{
    protected function getIntIdClass(): string
    {
        return TestIntIdForType::class;
    }

    public function getName(): string
    {
        return 'test_int_id';
    }
}

class AbstractIntIdTypeTest extends TestCase
{
    private TestIntIdType $type;
    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        if (!Type::hasType('test_int_id')) {
            Type::addType('test_int_id', TestIntIdType::class);
        }
        $this->type = Type::getType('test_int_id');
        $this->platform = $this->createMock(AbstractPlatform::class);
    }

    public function testGetSQLDeclaration(): void
    {
        $this->platform->expects($this->once())
            ->method('getIntegerTypeDeclarationSQL')
            ->with(['foo' => 'bar'])
            ->willReturn('INT');

        $this->assertSame('INT', $this->type->getSQLDeclaration(['foo' => 'bar'], $this->platform));
    }

    public function testConvertToPHPValue(): void
    {
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue('', $this->platform));

        $id = new TestIntIdForType(123);
        $this->assertSame($id, $this->type->convertToPHPValue($id, $this->platform));

        $converted = $this->type->convertToPHPValue('456', $this->platform);
        $this->assertInstanceOf(TestIntIdForType::class, $converted);
        $this->assertSame(456, $converted->toInt());
    }

    public function testConvertToDatabaseValue(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToDatabaseValue('', $this->platform));

        $id = new TestIntIdForType(123);
        $this->assertSame(123, $this->type->convertToDatabaseValue($id, $this->platform));
    }

    public function testConvertToDatabaseValueWithInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instance of Tito10047\TypeSafeIdBundle\Tests\Unit\TestIntIdForType, got string');
        $this->type->convertToDatabaseValue('not-an-id', $this->platform);
    }

    public function testRequiresSQLCommentHint(): void
    {
        $this->assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }
}
