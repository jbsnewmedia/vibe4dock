<?php

declare(strict_types=1);

namespace Vibe4Dock\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DatabaseAddonDriverTest extends TestCase
{
    private const ADDON_DIR = __DIR__ . '/../skeleton/docker/tools/addons';

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function databaseAddonProvider(): array
    {
        return [
            'mariadb' => ['510_mariadb.json.skeleton', 'pdo_mysql'],
            'mysql' => ['520_mysql.json.skeleton', 'pdo_mysql'],
            'postgresql' => ['530_postgresql.json.skeleton', 'pdo_pgsql'],
            'firebird' => ['540_firebird.json.skeleton', 'pdo_firebird'],
        ];
    }

    #[DataProvider('databaseAddonProvider')]
    public function testEveryVersionInstallsItsOwnPhpDriver(string $fileName, string $extension): void
    {
        $path = self::ADDON_DIR . DIRECTORY_SEPARATOR . $fileName;
        self::assertFileExists($path);

        $contents = file_get_contents($path);
        self::assertIsString($contents);

        $data = json_decode($contents, true);
        self::assertIsArray($data, sprintf('%s must be valid JSON', $fileName));
        self::assertArrayHasKey('tools', $data);
        self::assertIsArray($data['tools']);

        $versionCount = 0;
        foreach ($data['tools'] as $tool) {
            if (! is_array($tool)) {
                continue;
            }

            if (! isset($tool['versions'])) {
                continue;
            }

            if (! is_array($tool['versions'])) {
                continue;
            }

            foreach ($tool['versions'] as $version) {
                self::assertIsArray($version);
                self::assertArrayHasKey('install', $version);
                self::assertIsString($version['install']);

                $install = $version['install'];
                $versionId = is_string($version['id'] ?? null) ? $version['id'] : '?';

                self::assertStringContainsString(
                    $extension,
                    $install,
                    sprintf('Version "%s" must install the %s driver', $versionId, $extension),
                );
                self::assertStringContainsString(
                    'docker-php-ext-install',
                    $install,
                    sprintf('Version "%s" must use docker-php-ext-install', $versionId),
                );
                ++$versionCount;
            }
        }

        self::assertGreaterThan(0, $versionCount, sprintf('%s must define at least one version', $fileName));
    }
}
