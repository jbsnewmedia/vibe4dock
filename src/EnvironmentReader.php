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

    /**
     * Decodes the content of a vibe4dock.project.json manifest file.
     *
     * @return array<string, string>|null
     */
    public static function projectManifest(string $manifestContent): ?array
    {
        $decoded = json_decode($manifestContent, true);
        if (! is_array($decoded)) {
            return null;
        }

        $manifest = [];
        foreach (['version', 'project_name', 'php_version', 'routing', 'base_path_prefix'] as $key) {
            if (isset($decoded[$key]) && is_string($decoded[$key]) && $decoded[$key] !== '') {
                $manifest[$key] = $decoded[$key];
            }
        }

        if ($manifest === []) {
            return null;
        }

        return $manifest;
    }

    public static function manifestValue(string $manifestContent, string $key): ?string
    {
        $manifest = self::projectManifest($manifestContent);

        return $manifest === null ? null : ($manifest[$key] ?? null);
    }

    public static function manifestRouting(string $manifestContent): ?string
    {
        $routing = self::manifestValue($manifestContent, 'routing');
        if ($routing !== SetupConfig::ROUTING_PORTS && $routing !== SetupConfig::ROUTING_PATHS) {
            return null;
        }

        return $routing;
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
