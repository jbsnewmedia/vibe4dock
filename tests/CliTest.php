<?php

declare(strict_types=1);

namespace Vibe4Dock\Tests;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Vibe4Dock\Cli;

/**
 * End-to-end tests that run the CLI as a separate PHP process, exercising the
 * runtime autoloader in vibe4dock.php exactly as an end user would.
 */
#[CoversClass(Cli::class)]
final class CliTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vibe4dock-cli-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->outputDir);
    }

    public function testPrintsVersion(): void
    {
        $result = $this->runCli(['--version']);

        self::assertSame(0, $result['code']);
        self::assertSame(Cli::VERSION, trim($result['stdout']));
    }

    public function testPrintsUsage(): void
    {
        $result = $this->runCli(['--help']);

        self::assertSame(0, $result['code']);
        self::assertStringContainsString('USAGE', $result['stdout']);
    }

    public function testGeneratesProject(): void
    {
        $result = $this->runCli([
            '--project-name=demo',
            '--output-dir=' . $this->outputDir,
            '--web-port=8080',
            '--tools-port=8095',
        ]);

        self::assertSame(0, $result['code'], $result['stdout'] . $result['stderr']);
        self::assertFileExists($this->outputDir . DIRECTORY_SEPARATOR . 'docker-compose.yml');
    }

    public function testFailsOnInvalidPort(): void
    {
        $result = $this->runCli([
            '--project-name=demo',
            '--output-dir=' . $this->outputDir,
            '--web-port=70000',
        ]);

        self::assertSame(1, $result['code']);
    }

    /**
     * @param list<string> $args
     *
     * @return array{code: int, stdout: string, stderr: string}
     */
    private function runCli(array $args): array
    {
        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vibe4dock.php');

        foreach ($args as $arg) {
            $command .= ' ' . escapeshellarg($arg);
        }

        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);
        self::assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        return [
            'code' => $code,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
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
