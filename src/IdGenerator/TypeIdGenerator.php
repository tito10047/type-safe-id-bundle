<?php

namespace Tito10047\TypeSafeIdBundle\IdGenerator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Tito10047\TypeSafeIdBundle\GeneratableId;

final class TypeIdGenerator extends AbstractIdGenerator
{
    private string $typeClass;

    public function __construct(string $typeClass)
    {
        $this->typeClass = $typeClass;
    }

    /**
     * doctrine/orm < 2.11 BC layer.
     */
    public function generate(EntityManager $em, $entity): mixed
    {
        return $this->generateId($em, $entity);
    }

    public function generateId(EntityManagerInterface $em, $entity): GeneratableId
    {
        return $this->typeClass::new();
    }
}
