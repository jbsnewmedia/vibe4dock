<?php

declare(strict_types=1);

namespace Vibe4Dock;

use Vibe4Dock\Exception\InvalidConfigException;

/**
 * Immutable, validated configuration for a Vibe4Dock project to generate.
 */
final class SetupConfig
{
    public const DEFAULT_PHP_VERSION = '8.5';

    public const DEFAULT_WEB_PORT = 80;

    public const DEFAULT_TOOLS_PORT = 8090;

    public const DEFAULT_ROOT_SHELL_PORT = 7681;

    public const DEFAULT_APP_SHELL_PORT = 7682;

    public const WEB_CONTAINER_PORT = 80;

    public const TOOLS_CONTAINER_PORT = 8090;

    public const ROOT_SHELL_CONTAINER_PORT = 7681;

    public const APP_SHELL_CONTAINER_PORT = 7682;

    public const ROUTING_PORTS = 'ports';

    public const ROUTING_PATHS = 'paths';

    public const DEFAULT_BASE_PATH_PREFIX = 'vibe';

    private function __construct(
        private string $projectName,
        private string $phpVersion,
        private int $webPort,
        private int $toolsPort,
        private int $rootShellPort,
        private int $appShellPort,
        private string $outputDir,
        private string $routing = self::ROUTING_PORTS,
        private string $basePathPrefix = self::DEFAULT_BASE_PATH_PREFIX,
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
        $routing = self::normalizeRouting(
            self::stringOption($options, 'routing', self::ROUTING_PORTS)
        );
        $basePathPrefix = self::normalizeBasePathPrefix(
            self::stringOption($options, 'base-path-prefix', self::DEFAULT_BASE_PATH_PREFIX)
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
            $outputDir,
            $routing,
            $basePathPrefix
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

    public function routing(): string
    {
        return $this->routing;
    }

    public function basePathPrefix(): string
    {
        return $this->basePathPrefix;
    }

    public function basePathDashboard(): string
    {
        return $this->basePath('/dashboard');
    }

    public function basePathRootShell(): string
    {
        return $this->basePath('/shell-root');
    }

    public function basePathAppShell(): string
    {
        return $this->basePath('/shell-app');
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
            '{{VIBE4DOCK_ROUTING}}' => $this->routing,
            '{{VIBE4DOCK_BASE_PATH_PREFIX}}' => $this->basePathPrefix,
            '{{VIBE4DOCK_BASE_PATH_DASHBOARD}}' => $this->basePathDashboard(),
            '{{VIBE4DOCK_BASE_PATH_ROOT_SHELL}}' => $this->basePathRootShell(),
            '{{VIBE4DOCK_BASE_PATH_APP_SHELL}}' => $this->basePathAppShell(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function manifest(): array
    {
        return [
            'version' => Cli::VERSION,
            'project_name' => $this->projectName,
            'php_version' => $this->phpVersion,
            'routing' => $this->routing,
            'base_path_prefix' => $this->basePathPrefix,
        ];
    }

    private function basePath(string $suffix): string
    {
        return '/' . $this->basePathPrefix . '-' . ltrim($suffix, '/');
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

        if ($this->routing === self::ROUTING_PORTS && count($ports) !== count(array_unique($ports))) {
            throw new InvalidConfigException('All host ports must be unique.');
        }
    }

    /**
     * @throws InvalidConfigException
     */
    private static function normalizeRouting(string $value): string
    {
        $routing = strtolower(trim($value));
        if ($routing !== self::ROUTING_PORTS && $routing !== self::ROUTING_PATHS) {
            throw new InvalidConfigException('Invalid routing mode. Use "ports" or "paths".');
        }

        return $routing;
    }

    /**
     * @throws InvalidConfigException
     */
    private static function normalizeBasePathPrefix(string $value): string
    {
        $prefix = strtolower(trim($value));
        if ($prefix === '' || preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $prefix) !== 1) {
            throw new InvalidConfigException(
                'Invalid base path prefix. Allowed characters: [a-z0-9-], must start and end with a letter or digit.'
            );
        }

        return $prefix;
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
