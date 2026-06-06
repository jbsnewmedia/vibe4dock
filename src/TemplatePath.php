<?php

declare(strict_types=1);

namespace Vibe4Dock;

/**
 * Pure helpers for mapping skeleton template paths to their output paths.
 */
final class TemplatePath
{
    private const SKELETON_SUFFIX = '.skeleton';

    /**
     * @var list<string>
     */
    private const SKIP_PREFIXES = [
        'readme/',
        'vendor-bin/',
        'vendor/',
        'node_modules/',
        '.git/',
        '.idea/',
    ];

    /**
     * @var list<string>
     */
    private const SKIP_EXACT = [
        'README.de.md',
        'vendor-bin',
        'vendor',
        'node_modules',
        '.git',
        '.idea',
    ];

    /**
     * Converts a skeleton-relative path into its rendered output path
     * (normalizes separators and strips the ".skeleton" suffix).
     */
    public static function normalizeOutputPath(string $relativePath): string
    {
        $normalized = str_replace('\\', '/', $relativePath);

        if (str_ends_with($normalized, self::SKELETON_SUFFIX)) {
            return substr($normalized, 0, -strlen(self::SKELETON_SUFFIX));
        }

        return $normalized;
    }

    /**
     * Determines whether the given output path must be excluded from generation.
     */
    public static function shouldSkip(string $relativePath): bool
    {
        $normalized = str_replace('\\', '/', $relativePath);

        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return in_array($normalized, self::SKIP_EXACT, true);
    }

    public static function isSkeletonFile(string $path): bool
    {
        return str_ends_with(str_replace('\\', '/', $path), self::SKELETON_SUFFIX);
    }
}
