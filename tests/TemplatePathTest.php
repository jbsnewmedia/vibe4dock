<?php

declare(strict_types=1);

namespace Vibe4Dock\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vibe4Dock\TemplatePath;

#[CoversClass(TemplatePath::class)]
final class TemplatePathTest extends TestCase
{
    public function testStripsSkeletonSuffix(): void
    {
        self::assertSame('docker-compose.yml', TemplatePath::normalizeOutputPath('docker-compose.yml.skeleton'));
    }

    public function testNormalizesBackslashes(): void
    {
        self::assertSame('docker/web/Dockerfile', TemplatePath::normalizeOutputPath('docker\\web\\Dockerfile.skeleton'));
    }

    public function testKeepsNonSkeletonPath(): void
    {
        self::assertSame('public/index.php', TemplatePath::normalizeOutputPath('public/index.php'));
    }

    public function testSkipsKnownPrefixesAndFiles(): void
    {
        self::assertTrue(TemplatePath::shouldSkip('readme/logo.svg'));
        self::assertTrue(TemplatePath::shouldSkip('vendor/autoload.php'));
        self::assertTrue(TemplatePath::shouldSkip('README.de.md'));
        self::assertTrue(TemplatePath::shouldSkip('.idea'));
    }

    public function testDoesNotSkipRegularFiles(): void
    {
        self::assertFalse(TemplatePath::shouldSkip('docker-compose.yml'));
        self::assertFalse(TemplatePath::shouldSkip('docker/web/Dockerfile'));
    }

    public function testDetectsSkeletonFiles(): void
    {
        self::assertTrue(TemplatePath::isSkeletonFile('/abs/docker-compose.yml.skeleton'));
        self::assertFalse(TemplatePath::isSkeletonFile('/abs/docker-compose.yml'));
    }
}
