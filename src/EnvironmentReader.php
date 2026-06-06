<?php

declare(strict_types=1);

namespace Vibe4Dock;

/**
 * Extracts defaults from an existing Vibe4Dock-generated environment.
 *
 * All methods are pure and intentionally scoped to the compose/Dockerfile format
 * produced by Vibe4Dock itself. Parsing failures return null so callers can fall
 * back to their own defaults.
 */
final class EnvironmentReader
{
    public static function projectName(string $dockerCompose): ?string
    {
        if (preg_match('/TARGET_CONTAINER=([A-Za-z0-9._-]+)-web-1/', $dockerCompose, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    public static function hostPort(string $dockerCompose, string $serviceName, int $containerPort): ?int
    {
        $serviceBlock = self::serviceBlock($dockerCompose, $serviceName);
        if ($serviceBlock === null) {
            return null;
        }

        if (preg_match_all('/-\s*"?(?<host>\d+):(?<container>\d+)"?/', $serviceBlock, $matches, PREG_SET_ORDER) === false) {
            return null;
        }

        foreach ($matches as $match) {
            if ((int) $match['container'] === $containerPort) {
                return (int) $match['host'];
            }
        }

        return null;
    }

    public static function phpVersion(string $webDockerfile): ?string
    {
        if (preg_match('/^FROM\s+\S+:(\d+(?:\.\d+)?)/m', $webDockerfile, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private static function serviceBlock(string $dockerCompose, string $serviceName): ?string
    {
        $pattern = '/^  ' . preg_quote($serviceName, '/') . ":\n((?:    .*?(?:\n|$))*)/m";
        if (preg_match($pattern, $dockerCompose, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
