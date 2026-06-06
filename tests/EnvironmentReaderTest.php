<?php

declare(strict_types=1);

namespace Vibe4Dock\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vibe4Dock\EnvironmentReader;

#[CoversClass(EnvironmentReader::class)]
final class EnvironmentReaderTest extends TestCase
{
    private const COMPOSE = <<<'YAML'
        services:
          web:
            container_name: demo-web-1
            ports:
              - "8080:80"
              - "7001:7681"
              - "7002:7682"
            environment:
              - TARGET_CONTAINER=demo-web-1
          tools:
            ports:
              - "8095:8090"
            environment:
              - TARGET_CONTAINER=demo-web-1
        YAML;

    public function testExtractsProjectName(): void
    {
        self::assertSame('demo', EnvironmentReader::projectName(self::COMPOSE));
    }

    public function testReturnsNullWhenProjectNameMissing(): void
    {
        self::assertNull(EnvironmentReader::projectName('services: {}'));
    }

    public function testExtractsHostPorts(): void
    {
        self::assertSame(8080, EnvironmentReader::hostPort(self::COMPOSE, 'web', 80));
        self::assertSame(7001, EnvironmentReader::hostPort(self::COMPOSE, 'web', 7681));
        self::assertSame(8095, EnvironmentReader::hostPort(self::COMPOSE, 'tools', 8090));
    }

    public function testReturnsNullForUnknownPort(): void
    {
        self::assertNull(EnvironmentReader::hostPort(self::COMPOSE, 'web', 9999));
        self::assertNull(EnvironmentReader::hostPort(self::COMPOSE, 'missing', 80));
    }

    public function testExtractsPhpVersion(): void
    {
        $dockerfile = "FROM webdevops/php-nginx:8.3\nRUN echo hi\n";

        self::assertSame('8.3', EnvironmentReader::phpVersion($dockerfile));
    }

    public function testReturnsNullWhenPhpVersionMissing(): void
    {
        self::assertNull(EnvironmentReader::phpVersion("RUN echo hi\n"));
    }
}
