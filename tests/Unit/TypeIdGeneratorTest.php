<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Tito10047\TypeSafeIdBundle\GeneratableId;
use Tito10047\TypeSafeIdBundle\IdGenerator\TypeIdGenerator;

class TestGeneratableId implements GeneratableId
{
    public static function new(): self
    {
        return new self();
    }
}

class TypeIdGeneratorTest extends TestCase
{
    public function testGenerateId(): void
    {
        $generator = new TypeIdGenerator(TestGeneratableId::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();

        $id = $generator->generateId($em, $entity);

        $this->assertInstanceOf(TestGeneratableId::class, $id);
    }

    public function testGenerateOldBCOverlay(): void
    {
        $generator = new TypeIdGenerator(TestGeneratableId::class);
        $em = $this->createMock(\Doctrine\ORM\EntityManager::class);
        $entity = new \stdClass();

        $id = $generator->generate($em, $entity);

        $this->assertInstanceOf(TestGeneratableId::class, $id);
    }
}
