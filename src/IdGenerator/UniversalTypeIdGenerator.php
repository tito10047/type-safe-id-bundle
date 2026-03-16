<?php

namespace Tito10047\TypeSafeIdBundle\IdGenerator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Tito10047\TypeSafeIdBundle\GeneratableId;

final class UniversalTypeIdGenerator extends AbstractIdGenerator
{
    /** @var array<class-string, class-string<GeneratableId>> */
    private array $entityToIdClassMap;

    /**
     * @param array<class-string, class-string<GeneratableId>> $entityToIdClassMap
     */
    public function __construct(array $entityToIdClassMap)
    {
        $this->entityToIdClassMap = $entityToIdClassMap;
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
        $entityClass = $entity::class;

        if (!isset($this->entityToIdClassMap[$entityClass])) {
            throw new \LogicException(sprintf(
                'No ID class mapped for entity "%s". Available mappings: %s',
                $entityClass,
                implode(', ', array_keys($this->entityToIdClassMap))
            ));
        }

        $idClass = $this->entityToIdClassMap[$entityClass];
        return $idClass::new();
    }
}
