<?php

declare(strict_types=1);

namespace Vibe4Dock;

use Vibe4Dock\Exception\InvalidConfigException;

/**
 * Immutable, validated configuration for a Vibe4Dock project to generate.
 */
final class SetupConfig
{
    public const DEFAULT_PHP_VERSION = '8.4';

    public const DEFAULT_WEB_PORT = 80;

    public const DEFAULT_TOOLS_PORT = 8090;

    public const DEFAULT_ROOT_SHELL_PORT = 7681;

    public const DEFAULT_APP_SHELL_PORT = 7682;

    public const WEB_CONTAINER_PORT = 80;

    public const TOOLS_CONTAINER_PORT = 8090;

    public const ROOT_SHELL_CONTAINER_PORT = 7681;

    public const APP_SHELL_CONTAINER_PORT = 7682;

    private function __construct(
        private string $projectName,
        private string $phpVersion,
        private int $webPort,
        private int $toolsPort,
        private int $rootShellPort,
        private int $appShellPort,
        private string $outputDir,
    ) {
    }

    /**
     * @param array<string, string|bool> $options
     *
     * @throws InvalidConfigException
     */
    public static function fromOptions(array $options): self
    {
        $projectName = self::stringOption($options, 'project-name', basename((string) getcwd()));
        $phpVersion = self::stringOption($options, 'php-version', self::DEFAULT_PHP_VERSION);
        $outputDir = self::normalizeDirectory(
            self::stringOption($options, 'output-dir', (string) getcwd())
        );

        $webPort = PortNormalizer::normalize($options['web-port'] ?? self::DEFAULT_WEB_PORT);
        $toolsPort = PortNormalizer::normalize($options['tools-port'] ?? self::DEFAULT_TOOLS_PORT);
        $rootShellPort = PortNormalizer::normalize($options['root-shell-port'] ?? self::DEFAULT_ROOT_SHELL_PORT);
        $appShellPort = PortNormalizer::normalize($options['app-shell-port'] ?? self::DEFAULT_APP_SHELL_PORT);

        $config = new self(
            $projectName,
            $phpVersion,
            $webPort,
            $toolsPort,
            $rootShellPort,
            $appShellPort,
            $outputDir
        );
        $config->validate();

        return $config;
    }

    public function projectName(): string
    {
        return $this->projectName;
    }

    public function phpVersion(): string
    {
        return $this->phpVersion;
    }

    public function webPort(): int
    {
        return $this->webPort;
    }

    public function toolsPort(): int
    {
        return $this->toolsPort;
    }

    public function rootShellPort(): int
    {
        return $this->rootShellPort;
    }

    public function appShellPort(): int
    {
        return $this->appShellPort;
    }

    public function outputDir(): string
    {
        return $this->outputDir;
    }

    public function targetContainer(): string
    {
        return $this->projectName . '-web-1';
    }

    /**
     * @return array<string, string>
     */
    public function templateReplacements(): array
    {
        return [
            '{{VIBE4DOCK_PHP_VERSION}}' => $this->phpVersion,
            '{{VIBE4DOCK_PROJECT_NAME}}' => $this->projectName,
            '{{VIBE4DOCK_WEB_HOST_PORT}}' => (string) $this->webPort,
            '{{VIBE4DOCK_WEB_CONTAINER_PORT}}' => (string) self::WEB_CONTAINER_PORT,
            '{{VIBE4DOCK_TOOLS_HOST_PORT}}' => (string) $this->toolsPort,
            '{{VIBE4DOCK_TOOLS_CONTAINER_PORT}}' => (string) self::TOOLS_CONTAINER_PORT,
            '{{VIBE4DOCK_ROOT_SHELL_HOST_PORT}}' => (string) $this->rootShellPort,
            '{{VIBE4DOCK_ROOT_SHELL_CONTAINER_PORT}}' => (string) self::ROOT_SHELL_CONTAINER_PORT,
            '{{VIBE4DOCK_APP_SHELL_HOST_PORT}}' => (string) $this->appShellPort,
            '{{VIBE4DOCK_APP_SHELL_CONTAINER_PORT}}' => (string) self::APP_SHELL_CONTAINER_PORT,
            '{{VIBE4DOCK_TARGET_CONTAINER}}' => $this->targetContainer(),
        ];
    }

    /**
     * @throws InvalidConfigException
     */
    private function validate(): void
    {
        if ($this->projectName === '' || preg_match('/^[a-zA-Z0-9._-]+$/', $this->projectName) !== 1) {
            throw new InvalidConfigException('Invalid project name. Allowed characters: [a-zA-Z0-9-_.]');
        }

        $ports = [$this->webPort, $this->toolsPort, $this->rootShellPort, $this->appShellPort];

        foreach ($ports as $port) {
            if (! PortNormalizer::isValid($port)) {
                throw new InvalidConfigException('All host ports must be between 1 and 65535.');
            }
        }

        if (count($ports) !== count(array_unique($ports))) {
            throw new InvalidConfigException('All host ports must be unique.');
        }
    }

    /**
     * @param array<string, string|bool> $options
     */
    private static function stringOption(array $options, string $key, string $default): string
    {
        $value = $options[$key] ?? null;
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $default;
    }

    private static function normalizeDirectory(string $directory): string
    {
        if (! str_ends_with($directory, DIRECTORY_SEPARATOR)) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        return $directory;
    }
}
