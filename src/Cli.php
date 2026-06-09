<?php

declare(strict_types=1);

namespace Vibe4Dock;

use Vibe4Dock\Exception\Vibe4DockException;

/**
 * Command line entry point: argument handling, interactive prompts and output.
 */
final class Cli
{
    public const VERSION = '1.0.3';

    private const COLOR_GREEN = "\033[38;2;136;238;255m";

    private const COLOR_RED = "\033[31m";

    private const COLOR_NONE = "\033[0m";

    public function __construct(
        private string $skeletonDir
    ) {
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        $options = OptionParser::parse($argv);

        if (isset($options['help'])) {
            $this->printUsage();

            return 0;
        }

        if (isset($options['version'])) {
            echo self::VERSION . PHP_EOL;

            return 0;
        }

        try {
            $config = $this->buildConfig($options);
            $this->generate($config);
        } catch (Vibe4DockException $vibe4DockException) {
            $this->printError($vibe4DockException->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * @param array<string, string|bool> $options
     */
    private function buildConfig(array $options): SetupConfig
    {
        if ($options === []) {
            return SetupConfig::fromOptions($this->promptForOptions());
        }

        return SetupConfig::fromOptions($options);
    }

    private function generate(SetupConfig $config): void
    {
        $this->log('Creating Vibe4Dock project: ' . $config->projectName());

        (new ProjectGenerator($this->skeletonDir))->generate($config);

        $this->log(
            'Vibe4Dock, Copyright (c) 2026+ JBS New Media GmbH, Juergen Schwind | MIT License | '
            . 'https://github.com/jbsnewmedia/vibe4dock'
        );
        $this->printSuccess(sprintf(
            'Vibe4Dock setup complete: [%s] in [%s]',
            $config->projectName(),
            rtrim($config->outputDir(), DIRECTORY_SEPARATOR)
        ));
    }

    /**
     * @return array<string, string>
     */
    private function promptForOptions(): array
    {
        $defaults = $this->detectDefaults();

        echo PHP_EOL;
        echo 'Welcome to Vibe4Dock Setup Tool ' . self::VERSION . PHP_EOL;
        echo '------------------------------' . PHP_EOL;

        $options = [];
        $options['project-name'] = $this->ask('Project Name', $defaults['project-name']);
        $options['php-version'] = $this->ask('PHP Version', $defaults['php-version']);

        echo PHP_EOL . 'Port Configuration:' . PHP_EOL;
        $options['web-port'] = (string) $this->askPort('Web Port', $defaults['web-port']);
        $options['tools-port'] = (string) $this->askPort('Tools UI Port', $defaults['tools-port']);
        $options['root-shell-port'] = (string) $this->askPort('Root Shell Port', $defaults['root-shell-port']);
        $options['app-shell-port'] = (string) $this->askPort('Application Shell Port', $defaults['app-shell-port']);

        echo PHP_EOL;
        $options['output-dir'] = $this->ask('Output Directory', $defaults['output-dir']);

        return $options;
    }

    /**
     * @return array{
     *     project-name: string,
     *     php-version: string,
     *     web-port: string,
     *     tools-port: string,
     *     root-shell-port: string,
     *     app-shell-port: string,
     *     output-dir: string
     * }
     */
    private function detectDefaults(): array
    {
        $cwd = (string) getcwd();

        $defaults = [
            'project-name' => basename($cwd),
            'php-version' => SetupConfig::DEFAULT_PHP_VERSION,
            'web-port' => (string) SetupConfig::DEFAULT_WEB_PORT,
            'tools-port' => (string) SetupConfig::DEFAULT_TOOLS_PORT,
            'root-shell-port' => (string) SetupConfig::DEFAULT_ROOT_SHELL_PORT,
            'app-shell-port' => (string) SetupConfig::DEFAULT_APP_SHELL_PORT,
            'output-dir' => $cwd . DIRECTORY_SEPARATOR,
        ];

        $environmentDir = $this->detectExistingEnvironmentDirectory($cwd);
        if ($environmentDir === null) {
            return $defaults;
        }

        $compose = $this->readFileIfExists($environmentDir . DIRECTORY_SEPARATOR . 'docker-compose.yml');
        if ($compose !== null) {
            $defaults['project-name'] = EnvironmentReader::projectName($compose) ?? $defaults['project-name'];
            $defaults['web-port'] = $this->portDefault($compose, 'web', SetupConfig::WEB_CONTAINER_PORT, $defaults['web-port']);
            $defaults['root-shell-port'] = $this->portDefault($compose, 'web', SetupConfig::ROOT_SHELL_CONTAINER_PORT, $defaults['root-shell-port']);
            $defaults['app-shell-port'] = $this->portDefault($compose, 'web', SetupConfig::APP_SHELL_CONTAINER_PORT, $defaults['app-shell-port']);
            $defaults['tools-port'] = $this->portDefault($compose, 'tools', SetupConfig::TOOLS_CONTAINER_PORT, $defaults['tools-port']);
        }

        $dockerfile = $this->readFileIfExists(
            $environmentDir . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'Dockerfile'
        );
        if ($dockerfile !== null) {
            $defaults['php-version'] = EnvironmentReader::phpVersion($dockerfile) ?? $defaults['php-version'];
        }

        return $defaults;
    }

    private function portDefault(string $compose, string $service, int $containerPort, string $fallback): string
    {
        $port = EnvironmentReader::hostPort($compose, $service, $containerPort);

        return $port === null ? $fallback : (string) $port;
    }

    private function detectExistingEnvironmentDirectory(string $candidate): ?string
    {
        $requiredFiles = [
            $candidate . DIRECTORY_SEPARATOR . 'docker-compose.yml',
            $candidate . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'Dockerfile',
            $candidate . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'Dockerfile',
        ];

        foreach ($requiredFiles as $file) {
            if (! is_file($file)) {
                return null;
            }
        }

        return $candidate;
    }

    private function readFileIfExists(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);

        return $content === false ? null : $content;
    }

    private function ask(string $question, string $default): string
    {
        $prompt = $question;
        if ($default !== '') {
            $prompt .= ' [' . self::COLOR_GREEN . $default . self::COLOR_NONE . ']';
        }

        echo $prompt . ': ';
        $input = trim((string) fgets(STDIN));

        return $input === '' ? $default : $input;
    }

    private function askPort(string $question, string $default): int
    {
        $value = $this->ask($question, $default);
        $port = PortNormalizer::normalize($value);

        if (! PortNormalizer::isValid($port)) {
            echo 'Invalid port. Use a value between 1 and 65535.' . PHP_EOL;

            return $this->askPort($question, $default);
        }

        return $port;
    }

    private function log(string $message): void
    {
        echo $message . PHP_EOL;
    }

    private function printError(string $message): void
    {
        echo self::COLOR_RED . $message . self::COLOR_NONE . PHP_EOL;
    }

    private function printSuccess(string $message): void
    {
        echo self::COLOR_GREEN . $message . self::COLOR_NONE . PHP_EOL;
    }

    private function printUsage(): void
    {
        echo PHP_EOL;
        echo 'Vibe4Dock ' . self::VERSION . PHP_EOL;
        echo PHP_EOL;
        echo 'Create a Vibe4Dock project from the bundled skeleton template.' . PHP_EOL;
        echo PHP_EOL;
        echo 'USAGE' . PHP_EOL;
        echo '    vibe4dock [OPTIONS]' . PHP_EOL;
        echo PHP_EOL;
        echo 'OPTIONS' . PHP_EOL;
        echo '    --help, -h' . PHP_EOL;
        echo '    --version, -v' . PHP_EOL;
        echo '    --project-name=<name>' . PHP_EOL;
        echo '    --php-version=<version>' . PHP_EOL;
        echo '    --web-port=<port>' . PHP_EOL;
        echo '    --tools-port=<port>' . PHP_EOL;
        echo '    --root-shell-port=<port>' . PHP_EOL;
        echo '    --app-shell-port=<port>' . PHP_EOL;
        echo '    --output-dir=<dir>' . PHP_EOL;
        echo PHP_EOL;
        echo 'EXAMPLE' . PHP_EOL;
        echo '    vibe4dock --project-name=my-vibe4dock --web-port=8080 --tools-port=8095' . PHP_EOL;
    }
}
