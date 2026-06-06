<?php

declare(strict_types=1);

namespace Vibe4Dock;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Vibe4Dock\Exception\Vibe4DockException;

/**
 * Generates a Vibe4Dock project by copying and rendering the skeleton templates.
 */
final class ProjectGenerator
{
    public function __construct(
        private string $skeletonDir
    ) {
        if (! is_dir($this->skeletonDir)) {
            throw new Vibe4DockException('Skeleton directory not found: ' . $this->skeletonDir);
        }
    }

    /**
     * @throws Vibe4DockException
     */
    public function generate(SetupConfig $config): void
    {
        $outputDir = $config->outputDir();
        $this->ensureDirectory($outputDir);

        $replacements = $config->templateReplacements();

        /** @var SplFileInfo $item */
        foreach ($this->iterateSkeleton() as $item) {
            if (! $item->isFile()) {
                continue;
            }

            $source = $item->getPathname();
            $relativePath = substr($source, strlen($this->skeletonDir) + 1);
            $outputRelativePath = TemplatePath::normalizeOutputPath($relativePath);

            if (TemplatePath::shouldSkip($outputRelativePath)) {
                continue;
            }

            $destination = $outputDir . str_replace('/', DIRECTORY_SEPARATOR, $outputRelativePath);
            $this->writeRendered($source, $destination, $replacements);
        }
    }

    /**
     * @return RecursiveIteratorIterator<RecursiveDirectoryIterator>
     */
    private function iterateSkeleton(): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->skeletonDir, FilesystemIterator::SKIP_DOTS)
        );
    }

    /**
     * @param array<string, string> $replacements
     */
    private function writeRendered(string $source, string $destination, array $replacements): void
    {
        $content = file_get_contents($source);
        if ($content === false) {
            throw new Vibe4DockException('Unable to read skeleton file: ' . $source);
        }

        if (TemplatePath::isSkeletonFile($source)) {
            $content = strtr($content, $replacements);
        }

        $this->ensureDirectory(dirname($destination) . DIRECTORY_SEPARATOR);

        if (file_put_contents($destination, $content) === false) {
            throw new Vibe4DockException('Unable to write file: ' . $destination);
        }

        if (str_ends_with($destination, '.sh')) {
            chmod($destination, 0755);
        }
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new Vibe4DockException('Unable to create directory: ' . $directory);
        }
    }
}
