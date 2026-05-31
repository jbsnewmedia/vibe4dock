<?php

declare(strict_types=1);

namespace Vibe4Dock;

/**
 * Normalizes and validates TCP port values coming from user input.
 */
final class PortNormalizer
{
    public const MIN = 1;

    public const MAX = 65535;

    /**
     * Returns the port as an integer or 0 when the value is not a valid port number.
     */
    public static function normalize(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        return 0;
    }

    public static function isValid(int $port): bool
    {
        return $port >= self::MIN && $port <= self::MAX;
    }
}
