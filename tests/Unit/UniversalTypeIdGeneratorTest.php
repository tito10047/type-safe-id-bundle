<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Tito10047\TypeSafeIdBundle\GeneratableId;
use Tito10047\TypeSafeIdBundle\IdGenerator\UniversalTypeIdGenerator;

class TestEntity {}
class TestEntityWithoutIdMapping {}

class TestGeneratableIdForUniversal implements GeneratableId
{
    public static function new(): self
    {
        return new self();
    }
}

class UniversalTypeIdGeneratorTest extends TestCase
{
    public function testGenerateId(): void
    {
        $map = [
            TestEntity::class => TestGeneratableIdForUniversal::class,
        ];
        $generator = new UniversalTypeIdGenerator($map);
        $em = $this->createMock(EntityManagerInterface::class);
        $entity = new TestEntity();

        $id = $generator->generateId($em, $entity);

        $this->assertInstanceOf(TestGeneratableIdForUniversal::class, $id);
    }

    public function testGenerateIdThrowsExceptionWhenNoMappingExists(): void
    {
        $generator = new UniversalTypeIdGenerator([]);
        $em = $this->createMock(EntityManagerInterface::class);
        $entity = new TestEntityWithoutIdMapping();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No ID class mapped for entity "Tito10047\TypeSafeIdBundle\Tests\Unit\TestEntityWithoutIdMapping". Available mappings: ');

        $generator->generateId($em, $entity);
    }
}
