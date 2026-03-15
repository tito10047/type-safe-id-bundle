<?php

namespace Tito10047\TypeSafeIdBundle;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Abstract Doctrine type for IntId-based identifiers.
 * Similar to AbstractUidType but for integer-based IDs.
 */
abstract class AbstractIntIdType extends Type
{
    /**
     * Returns the IntId class that this type handles.
     */
    abstract protected function getIntIdClass(): string;

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?IntId
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof IntId) {
            return $value;
        }

        $class = $this->getIntIdClass();

        try {
            return $class::fromInt((int) $value);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Could not convert database value "%s" to IntId: %s', $value, $e->getMessage()), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof IntId) {
            return $value->toInt();
        }

        throw new \InvalidArgumentException(sprintf('Expected instance of %s, got %s', $this->getIntIdClass(), get_debug_type($value)));
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
