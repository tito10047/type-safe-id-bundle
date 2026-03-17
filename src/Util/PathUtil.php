<?php

namespace Tito10047\TypeSafeIdBundle\Util;

class PathUtil
{
    public static function pathToNamespace(string $path): string
    {
        // Simple heuristic: assume src/ is at the root and skip it
        $namespace = preg_replace('/^src\//', '', $path);
        $namespace = str_replace('/', '\\', $namespace);
        return trim($namespace, '\\');
    }
}
