<?php

declare(strict_types=1);

namespace Vibe4Dock\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vibe4Dock\Exception\InvalidConfigException;
use Vibe4Dock\SetupConfig;

#[CoversClass(SetupConfig::class)]
final class SetupConfigTest extends TestCase
{
    public function testUsesDefaultsForMissingOptions(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
        ]);

        self::assertSame('demo', $config->projectName());
        self::assertSame(SetupConfig::DEFAULT_PHP_VERSION, $config->phpVersion());
        self::assertSame(SetupConfig::DEFAULT_WEB_PORT, $config->webPort());
        self::assertSame(SetupConfig::DEFAULT_TOOLS_PORT, $config->toolsPort());
    }

    public function testBuildsTargetContainer(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
        ]);

        self::assertSame('demo-web-1', $config->targetContainer());
    }

    public function testNormalizesOutputDirectoryTrailingSeparator(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
            'output-dir' => '/tmp/demo',
        ]);

        self::assertSame('/tmp/demo' . DIRECTORY_SEPARATOR, $config->outputDir());
    }

    public function testProvidesTemplateReplacements(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
            'php-version' => '8.3',
            'web-port' => '8080',
        ]);

        $replacements = $config->templateReplacements();

        self::assertSame('8.3', $replacements['{{VIBE4DOCK_PHP_VERSION}}']);
        self::assertSame('demo', $replacements['{{VIBE4DOCK_PROJECT_NAME}}']);
        self::assertSame('8080', $replacements['{{VIBE4DOCK_WEB_HOST_PORT}}']);
        self::assertSame('80', $replacements['{{VIBE4DOCK_WEB_CONTAINER_PORT}}']);
        self::assertSame('demo-web-1', $replacements['{{VIBE4DOCK_TARGET_CONTAINER}}']);
    }

    public function testRejectsInvalidProjectName(): void
    {
        $this->expectException(InvalidConfigException::class);

        SetupConfig::fromOptions([
            'project-name' => 'invalid name',
        ]);
    }

    public function testRejectsOutOfRangePort(): void
    {
        $this->expectException(InvalidConfigException::class);

        SetupConfig::fromOptions([
            'project-name' => 'demo',
            'web-port' => '70000',
        ]);
    }

    public function testRejectsDuplicatePorts(): void
    {
        $this->expectException(InvalidConfigException::class);

        SetupConfig::fromOptions([
            'project-name' => 'demo',
            'web-port' => '9000',
            'tools-port' => '9000',
        ]);
    }
}
