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

    public function testRoutingDefaultsToPorts(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
        ]);

        self::assertSame(SetupConfig::ROUTING_PORTS, $config->routing());
        self::assertSame(SetupConfig::DEFAULT_BASE_PATH_PREFIX, $config->basePathPrefix());
    }

    public function testPathsRoutingDerivesBasePaths(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
            'routing' => 'paths',
        ]);

        self::assertSame(SetupConfig::ROUTING_PATHS, $config->routing());
        self::assertSame('/vibe-dashboard', $config->basePathDashboard());
        self::assertSame('/vibe-shell-root', $config->basePathRootShell());
        self::assertSame('/vibe-shell-app', $config->basePathAppShell());
    }

    public function testPathsRoutingWithCustomPrefix(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
            'routing' => 'paths',
            'base-path-prefix' => 'Vibe4Dock',
        ]);

        self::assertSame('vibe4dock', $config->basePathPrefix());
        self::assertSame('/vibe4dock-dashboard', $config->basePathDashboard());
    }

    public function testRejectsInvalidRouting(): void
    {
        $this->expectException(InvalidConfigException::class);

        SetupConfig::fromOptions([
            'project-name' => 'demo',
            'routing' => 'subdomains',
        ]);
    }

    public function testRejectsInvalidBasePathPrefix(): void
    {
        $this->expectException(InvalidConfigException::class);

        SetupConfig::fromOptions([
            'project-name' => 'demo',
            'routing' => 'paths',
            'base-path-prefix' => '-invalid-',
        ]);
    }

    public function testAllowsDuplicatePortsInPathsMode(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
            'routing' => 'paths',
            'web-port' => '8080',
            'tools-port' => '8080',
        ]);

        self::assertSame(8080, $config->webPort());
    }

    public function testTemplateReplacementsIncludeRoutingValues(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
            'routing' => 'paths',
            'base-path-prefix' => 'dev',
        ]);

        $replacements = $config->templateReplacements();

        self::assertSame('paths', $replacements['{{VIBE4DOCK_ROUTING}}']);
        self::assertSame('dev', $replacements['{{VIBE4DOCK_BASE_PATH_PREFIX}}']);
        self::assertSame('/dev-dashboard', $replacements['{{VIBE4DOCK_BASE_PATH_DASHBOARD}}']);
        self::assertSame('/dev-shell-root', $replacements['{{VIBE4DOCK_BASE_PATH_ROOT_SHELL}}']);
        self::assertSame('/dev-shell-app', $replacements['{{VIBE4DOCK_BASE_PATH_APP_SHELL}}']);
    }

    public function testProvidesManifest(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
            'php-version' => '8.3',
            'routing' => 'paths',
            'base-path-prefix' => 'vibe4dock',
        ]);

        $manifest = $config->manifest();

        self::assertSame('demo', $manifest['project_name']);
        self::assertSame('8.3', $manifest['php_version']);
        self::assertSame('paths', $manifest['routing']);
        self::assertSame('vibe4dock', $manifest['base_path_prefix']);
        self::assertArrayHasKey('version', $manifest);
    }
}
