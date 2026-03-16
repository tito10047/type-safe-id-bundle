<?php

namespace Tito10047\TypeSafeIdBundle;

/**
 * Interface for ID types that can be auto-generated.
 */
interface GeneratableId
{
    /**
     * Creates a new ID instance.
     * This method is called by TypeIdGenerator when a new entity is persisted.
     */
    public static function new(): self;
}
