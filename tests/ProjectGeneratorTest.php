<?php

declare(strict_types=1);

namespace Vibe4Dock\Tests;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Vibe4Dock\ProjectGenerator;
use Vibe4Dock\SetupConfig;

#[CoversClass(ProjectGenerator::class)]
final class ProjectGeneratorTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vibe4dock-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->outputDir);
    }

    public function testGeneratesProjectFromSkeleton(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
            'php-version' => '8.3',
            'web-port' => '8080',
            'tools-port' => '8095',
            'root-shell-port' => '7001',
            'app-shell-port' => '7002',
            'output-dir' => $this->outputDir,
        ]);

        (new ProjectGenerator($this->skeletonDir()))->generate($config);

        $compose = $this->outputDir . DIRECTORY_SEPARATOR . 'docker-compose.yml';
        self::assertFileExists($compose);

        $contents = (string) file_get_contents($compose);
        self::assertStringContainsString('demo-web-1', $contents);
        self::assertStringContainsString('"8080:80"', $contents);
        self::assertStringContainsString('"8095:8090"', $contents);
    }

    public function testRendersEveryPlaceholder(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
            'output-dir' => $this->outputDir,
        ]);

        (new ProjectGenerator($this->skeletonDir()))->generate($config);

        foreach ($this->files($this->outputDir) as $file) {
            $contents = (string) file_get_contents($file->getPathname());
            self::assertStringNotContainsString(
                '{{VIBE4DOCK_',
                $contents,
                'Unresolved placeholder in ' . $file->getPathname()
            );
        }
    }

    public function testDoesNotCopySkeletonFilesOrSkippedPaths(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
            'output-dir' => $this->outputDir,
        ]);

        (new ProjectGenerator($this->skeletonDir()))->generate($config);

        foreach ($this->files($this->outputDir) as $file) {
            self::assertStringEndsNotWith('.skeleton', $file->getPathname());
        }

        self::assertFileDoesNotExist($this->outputDir . DIRECTORY_SEPARATOR . 'README.de.md');
        self::assertDirectoryDoesNotExist($this->outputDir . DIRECTORY_SEPARATOR . 'readme');
    }

    public function testGeneratedShellScriptsAreExecutable(): void
    {
        $config = SetupConfig::fromOptions([
            'project-name' => 'demo',
            'output-dir' => $this->outputDir,
        ]);

        (new ProjectGenerator($this->skeletonDir()))->generate($config);

        $script = $this->outputDir . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . 'bash.sh';
        self::assertFileExists($script);
        self::assertTrue(is_executable($script), 'Generated shell scripts must be executable.');
    }

    private function skeletonDir(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'skeleton';
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function files(string $directory): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item instanceof SplFileInfo && $item->isFile()) {
                yield $item;
            }
        }
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if (! $item instanceof SplFileInfo) {
                continue;
            }

            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}
