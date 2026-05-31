<?php

declare(strict_types=1);

namespace Vibe4Dock;

/**
 * Parses raw CLI arguments into an associative options array.
 */
final class OptionParser
{
    /**
     * @param list<string> $args
     *
     * @return array<string, string|bool>
     */
    public static function parse(array $args): array
    {
        $options = [];

        foreach ($args as $arg) {
            if ($arg === '--help' || $arg === '-h') {
                $options['help'] = true;

                continue;
            }

            if ($arg === '--version' || $arg === '-v') {
                $options['version'] = true;

                continue;
            }

            if (! str_starts_with($arg, '-')) {
                continue;
            }

            $normalized = ltrim($arg, '-');
            if ($normalized === '') {
                continue;
            }

            $parts = explode('=', $normalized, 2);
            $key = $parts[0];
            $value = $parts[1] ?? true;
            if (is_string($value)) {
                $value = trim($value);
            }

            $options[$key] = $value;
        }

        return $options;
    }
}
