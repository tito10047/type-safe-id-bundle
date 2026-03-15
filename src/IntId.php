<?php

namespace Tito10047\TypeSafeIdBundle;

use Stringable;

/**
 * Integer ID wrapper class for type-safe entity identifiers.
 * Similar to Symfony's Uuid and Ulid, but wraps an integer value.
 */
abstract class IntId implements \JsonSerializable, Stringable
{
    protected ?int $id = null;

    /**
     * Creates a new IntId instance.
     * For auto-increment IDs, the ID is null until persisted.
     */
    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    /**
     * Creates an IntId from a string representation.
     */
    public static function fromString(string $id): static
    {
        return new static((int) $id);
    }

    /**
     * Creates an IntId from an integer.
     */
    public static function fromInt(int $id): static
    {
        return new static($id);
    }

    /**
     * Returns the integer value.
     */
    public function toInt(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the string representation of the ID.
     */
    public function toString(): string
    {
        return (string) $this->id;
    }

    /**
     * Returns the string representation of the ID.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Returns the ID value for JSON serialization.
     */
    public function jsonSerialize(): ?int
    {
        return $this->id;
    }

    /**
     * Checks if the ID is valid (not null).
     */
    public function isValid(): bool
    {
        return $this->id !== null;
    }

    /**
     * Checks if two IDs are equal.
     */
    public function equals(?self $other): bool
    {
        if ($other === null) {
            return false;
        }

        return $this->id === $other->id;
    }
}
