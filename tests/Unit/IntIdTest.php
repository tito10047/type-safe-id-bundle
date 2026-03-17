<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tito10047\TypeSafeIdBundle\IntId;

class TestIntId extends IntId
{
}

class IntIdTest extends TestCase
{
    public function testConstructorAndToInt(): void
    {
        $id = new TestIntId(123);
        $this->assertSame(123, $id->toInt());
        $this->assertTrue($id->isValid());

        $nullId = new TestIntId(null);
        $this->assertNull($nullId->toInt());
        $this->assertFalse($nullId->isValid());
    }

    public function testFromString(): void
    {
        $id = TestIntId::fromString('456');
        $this->assertInstanceOf(TestIntId::class, $id);
        $this->assertSame(456, $id->toInt());
    }

    public function testFromInt(): void
    {
        $id = TestIntId::fromInt(789);
        $this->assertInstanceOf(TestIntId::class, $id);
        $this->assertSame(789, $id->toInt());
    }

    public function testToString(): void
    {
        $id = new TestIntId(123);
        $this->assertSame('123', $id->toString());
        $this->assertSame('123', (string) $id);
    }

    public function testJsonSerialize(): void
    {
        $id = new TestIntId(123);
        $this->assertSame(123, $id->jsonSerialize());
        $this->assertSame('123', json_encode($id));
    }

    public function testEquals(): void
    {
        $id1 = new TestIntId(123);
        $id2 = new TestIntId(123);
        $id3 = new TestIntId(456);
        $nullId = new TestIntId(null);

        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
        $this->assertFalse($id1->equals(null));
        $this->assertFalse($id1->equals($nullId));
        $this->assertTrue($nullId->equals(new TestIntId(null)));
    }
}
