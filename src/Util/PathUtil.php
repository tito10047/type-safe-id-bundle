<?php

namespace Tito10047\TypeSafeIdBundle\Util;

use Composer\Autoload\ClassLoader;

class PathUtil
{
    public static function pathToNamespace(string $path): string
    {
        // This is now less used, but we keep it for backward compatibility if needed
        // Simple heuristic: assume src/ is at the root and skip it
        $namespace = preg_replace('/^src\//', '', $path);
        $namespace = str_replace('/', '\\', $namespace);
        return trim($namespace, '\\');
    }

    public static function namespaceToPath(string $namespace): string
    {
        $loader = self::getClassLoader();
        if (!$loader) {
            // Fallback for environments without Composer ClassLoader (e.g. simple tests)
            // Heuristic: App\ -> src/
            $path = str_replace('App\\', 'src/', $namespace);
            return str_replace('\\', '/', rtrim($path, '/'));
        }

        $namespaceWithBackslash = trim($namespace, '\\') . '\\';
        $prefixes = $loader->getPrefixesPsr4();
        
        // Sort prefixes by length descending to find the most specific match
        uksort($prefixes, function(string $a, string $b) {
            return strlen($b) <=> strlen($a);
        });

        foreach ($prefixes as $prefix => $paths) {
            if (str_starts_with($namespaceWithBackslash, $prefix)) {
                $relativePath = substr($namespaceWithBackslash, strlen($prefix));
                $path = rtrim($paths[0], '/') . '/' . str_replace('\\', '/', $relativePath);
                return rtrim($path, '/');
            }
        }

        // Fallback if not found in PSR-4 prefixes
        return str_replace('\\', '/', rtrim($namespace, '\\'));
    }

    public static function getClassLoader(): ?ClassLoader
    {
        foreach (spl_autoload_functions() as $autoloader) {
            if (!is_array($autoloader)) {
                continue;
            }

            if ($autoloader[0] instanceof ClassLoader) {
                return $autoloader[0];
            }

            if (method_exists($autoloader[0], 'getClassLoader')) {
                $loader = $autoloader[0]->getClassLoader();
                if (is_array($loader) && isset($loader[0]) && $loader[0] instanceof ClassLoader) {
                    return $loader[0];
                }
                if ($loader instanceof ClassLoader) {
                    return $loader;
                }
            }
        }

        return null;
    }
}
