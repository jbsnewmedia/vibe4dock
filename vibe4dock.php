<?php

class Vibe4DockSetup
{
    private const COLORS = ['GREEN' => "\033[32m", 'RED' => "\033[31m", 'NONE' => "\033[0m"];
    private const NL = "\n";
    private const DEFAULT_WEB_PORT = 80;
    private const DEFAULT_TOOLS_PORT = 8090;
    private const DEFAULT_ROOT_SHELL_PORT = 7681;
    private const DEFAULT_APP_SHELL_PORT = 7682;
    private const WEB_CONTAINER_PORT = 80;
    private const TOOLS_CONTAINER_PORT = 8090;
    private const ROOT_SHELL_CONTAINER_PORT = 7681;
    private const APP_SHELL_CONTAINER_PORT = 7682;
    private ?string $projectName;
    private string $phpVersion;
    private string $symfonyVersion;
    private string $mysqlVersion;
    private string $postgresVersion;
    private string $mariadbVersion;
    private string $firebirdVersion;
    private string $dbType;
    private string $outputDir;
    private int $webPort;
    private int $toolsPort;
    private int $rootShellPort;
    private int $appShellPort;
    private int $dbPort;
    private ?string $templateSourceDir = null;
    private bool $templateSourceResolved = false;

    public function __construct(array $options)
    {
        if (empty($options)) {
            $this->setDefaults();
            $this->interactiveSetup();
        } else {
            $this->setOptions($options);
        }

        $this->validateInputs();
        $this->setOutputDir();
    }

    private function setDefaults(): void
    {
        $this->projectName = basename(getcwd());
        $this->phpVersion = '8.4';
        $this->symfonyVersion = '7.*';
        $this->postgresVersion = '18.4';
        $this->mysqlVersion = '9.7';
        $this->mariadbVersion = '12.2';
        $this->firebirdVersion = '3';
        $this->dbType = 'mariadb';
        $this->outputDir = getcwd() . DIRECTORY_SEPARATOR;
        $this->webPort = self::DEFAULT_WEB_PORT;
        $this->toolsPort = self::DEFAULT_TOOLS_PORT;
        $this->rootShellPort = self::DEFAULT_ROOT_SHELL_PORT;
        $this->appShellPort = self::DEFAULT_APP_SHELL_PORT;
        $this->dbPort = $this->getDefaultDbPort($this->dbType);
        $this->loadExistingEnvironmentDefaults();
    }

    private function interactiveSetup(): void
    {
        echo self::NL;
        echo 'Welcome to Vibe4Dock Setup Tool' . self::NL;
        echo '------------------------------' . self::NL;

        $this->projectName = $this->ask('Project Name', $this->projectName);
        $this->phpVersion = $this->ask('PHP Version', $this->phpVersion);

        echo self::NL . 'Port Configuration:' . self::NL;
        $this->webPort = $this->askPort('Web Port', (string) $this->webPort);
        $this->toolsPort = $this->askPort('Tools UI Port', (string) $this->toolsPort);
        $this->rootShellPort = $this->askPort('Root Shell Port', (string) $this->rootShellPort);
        $this->appShellPort = $this->askPort('Application Shell Port', (string) $this->appShellPort);
        echo self::NL;
        $this->outputDir = $this->ask('Output Directory', $this->outputDir);
        if (substr($this->outputDir, -1) !== DIRECTORY_SEPARATOR) {
            $this->outputDir .= DIRECTORY_SEPARATOR;
        }
    }

    private function ask(string $question, ?string $default = null): string
    {
        $prompt = $question;
        if ($default !== null && $default !== '') {
            $prompt .= " [$default]";
        }

        echo $prompt . ': ';
        $input = trim((string) fgets(STDIN));

        return $input === '' ? (string) $default : $input;
    }

    private function askConfirm(string $question, string $default = 'yes'): bool
    {
        $prompt = $question . ' (yes/no) [' . $default . ']';
        echo $prompt . ': ';
        $input = strtolower(trim((string) fgets(STDIN)));
        $input = $input === '' ? $default : $input;

        return in_array($input, ['y', 'yes', 'true', '1'], true);
    }

    private function askChoice(string $question, array $choices, ?string $default = null): string
    {
        $prompt = $question . ' (' . implode(', ', $choices) . ')';
        if ($default !== null) {
            $prompt .= " [$default]";
        }

        echo $prompt . ': ';
        $input = trim((string) fgets(STDIN));
        $input = $input === '' ? (string) $default : $input;

        if (!in_array($input, $choices, true)) {
            echo 'Invalid choice. Please choose from: ' . implode(', ', $choices) . self::NL;
            return $this->askChoice($question, $choices, $default);
        }

        return $input;
    }

    private function askPort(string $question, string $default): int
    {
        $value = $this->ask($question, $default);
        if (!preg_match('/^\d+$/', $value)) {
            echo 'Invalid port. Please enter a number.' . self::NL;
            return $this->askPort($question, $default);
        }

        $port = (int) $value;
        if ($port < 1 || $port > 65535) {
            echo 'Invalid port. Use a value between 1 and 65535.' . self::NL;
            return $this->askPort($question, $default);
        }

        return $port;
    }

    private function setOptions(array $options): void
    {
        $this->projectName = $options['project-name'] ?? basename(getcwd());
        $this->phpVersion = $options['php-version'] ?? '8.4';
        $this->symfonyVersion = '7.*';
        $this->outputDir = $options['output-dir'] ?? (getcwd() . DIRECTORY_SEPARATOR);
        $this->webPort = $this->normalizePort($options['web-port'] ?? self::DEFAULT_WEB_PORT);
        $this->toolsPort = $this->normalizePort($options['tools-port'] ?? self::DEFAULT_TOOLS_PORT);
        $this->rootShellPort = $this->normalizePort($options['root-shell-port'] ?? self::DEFAULT_ROOT_SHELL_PORT);
        $this->appShellPort = $this->normalizePort($options['app-shell-port'] ?? self::DEFAULT_APP_SHELL_PORT);
    }

    private function normalizePort($value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^\d+$/', $value)) {
            return (int) $value;
        }

        return 0;
    }

    private function loadExistingEnvironmentDefaults(): void
    {
        $environmentDir = $this->detectExistingEnvironmentDirectory();
        if ($environmentDir === null) {
            return;
        }

        $dockerCompose = $this->readFileIfExists($environmentDir . DIRECTORY_SEPARATOR . 'docker-compose.yml');
        if ($dockerCompose !== null) {
            $this->applyComposeDefaults($dockerCompose);
        }

        $webDockerfile = $this->readFileIfExists(
            $environmentDir . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'Dockerfile'
        );
        if ($webDockerfile !== null) {
            $this->applyPhpVersionDefaults($webDockerfile);
        }

    }

    private function detectExistingEnvironmentDirectory(): ?string
    {
        $candidate = getcwd();

        $requiredFiles = [
            $candidate . DIRECTORY_SEPARATOR . 'docker-compose.yml',
            $candidate . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'Dockerfile',
            $candidate . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'Dockerfile',
        ];

        foreach ($requiredFiles as $file) {
            if (!is_file($file)) {
                return null;
            }
        }

        return $candidate;
    }

    private function readFileIfExists(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        return $content;
    }

    private function applyComposeDefaults(string $dockerCompose): void
    {
        $projectName = $this->extractProjectNameFromCompose($dockerCompose);
        if ($projectName !== null) {
            $this->projectName = $projectName;
        }

        $webService = $this->extractComposeServiceBlock($dockerCompose, 'web');
        $toolsService = $this->extractComposeServiceBlock($dockerCompose, 'tools');
        $this->webPort = $this->extractHostPortForContainerPort($webService, self::WEB_CONTAINER_PORT) ?? $this->webPort;
        $this->rootShellPort = $this->extractHostPortForContainerPort($webService, self::ROOT_SHELL_CONTAINER_PORT) ?? $this->rootShellPort;
        $this->appShellPort = $this->extractHostPortForContainerPort($webService, self::APP_SHELL_CONTAINER_PORT) ?? $this->appShellPort;
        $this->toolsPort = $this->extractHostPortForContainerPort($toolsService, self::TOOLS_CONTAINER_PORT) ?? $this->toolsPort;
    }

    private function extractProjectNameFromCompose(string $dockerCompose): ?string
    {
        if (!preg_match('/TARGET_CONTAINER=([A-Za-z0-9._-]+)-web-1/', $dockerCompose, $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function extractComposeServiceBlock(string $dockerCompose, string $serviceName): ?string
    {
        $pattern = '/^  ' . preg_quote($serviceName, '/') . ":\n((?:    .*?(?:\n|$))*)/m";
        if (!preg_match($pattern, $dockerCompose, $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function extractHostPortForContainerPort(?string $serviceBlock, int $containerPort): ?int
    {
        if ($serviceBlock === null) {
            return null;
        }

        if (!preg_match_all('/-\s*"?(?<host>\d+):(?<container>\d+)"?/', $serviceBlock, $matches, PREG_SET_ORDER)) {
            return null;
        }

        foreach ($matches as $match) {
            if ((int) $match['container'] === $containerPort) {
                return (int) $match['host'];
            }
        }

        return null;
    }

    private function applyPhpVersionDefaults(string $webDockerfile): void
    {
        if (!preg_match('/^FROM\s+\S+:(\d+(?:\.\d+)?)/m', $webDockerfile, $matches)) {
            return;
        }

        $this->phpVersion = $matches[1];
    }

    private function validateInputs(): void
    {
        if (empty($this->projectName) || !preg_match('/^[a-zA-Z0-9._-]+$/', $this->projectName)) {
            $this->printError('Invalid project name. [a-zA-Z0-9-_.]');
            exit(1);
        }

        $ports = [
            $this->webPort,
            $this->toolsPort,
            $this->rootShellPort,
            $this->appShellPort,
        ];

        foreach ($ports as $port) {
            if ($port < 1 || $port > 65535) {
                $this->printError('All host ports must be between 1 and 65535.');
                exit(1);
            }
        }

        if (count($ports) !== count(array_unique($ports))) {
            $this->printError('All host ports must be unique.');
            exit(1);
        }
    }

    private function setOutputDir(): void
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }

        if (substr($this->outputDir, -1) !== DIRECTORY_SEPARATOR) {
            $this->outputDir .= DIRECTORY_SEPARATOR;
        }
    }

    public function createProject(): void
    {
        $this->log('Creating Vibe4Dock project: ' . $this->projectName);
        $this->copySkeletonTemplate();
        $this->renderSkeletonTemplates();
        $this->log('Vibe4Dock, Copyright (c) 2026+ JBS New Media GmbH, Juergen Schwind | MIT License | https://github.com/jbsnewmedia/vibe4dock');
        $this->printSuccess('Vibe4Dock setup complete in: ' . $this->outputDir);
    }

    private function copySkeletonTemplate(): void
    {
        $this->copyTemplateDirectory($this->resolveSkeletonDirectory());
    }

    private function resolveSkeletonDirectory(): string
    {
        if ($this->templateSourceResolved && $this->templateSourceDir !== null) {
            return $this->templateSourceDir;
        }

        $path = __DIR__ . DIRECTORY_SEPARATOR . 'skeleton';
        if (!is_dir($path)) {
            $this->printError('Skeleton directory not found: ' . $path);
            exit(1);
        }

        $this->templateSourceDir = $path;
        $this->templateSourceResolved = true;

        return $this->templateSourceDir;
    }

    private function copyTemplateDirectory(string $source): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($source) + 1);
            $outputRelativePath = $this->normalizeTemplateOutputPath($relativePath);
            $destination = $this->outputDir . str_replace('/', DIRECTORY_SEPARATOR, $outputRelativePath);

            if ($this->shouldSkipTemplatePath($outputRelativePath) || $this->shouldDeferTemplatePath($outputRelativePath)) {
                continue;
            }

            if ($item->isDir()) {
                if (!is_dir($destination)) {
                    mkdir($destination, 0777, true);
                }
                continue;
            }

            $parentDir = dirname($destination);
            if (!is_dir($parentDir)) {
                mkdir($parentDir, 0777, true);
            }

            copy($item->getPathname(), $destination);
            chmod($destination, 0777);
        }
    }

    private function normalizeTemplateOutputPath(string $relativePath): string
    {
        $normalizedPath = str_replace('\\', '/', $relativePath);

        if (str_ends_with($normalizedPath, '.skeleton')) {
            return substr($normalizedPath, 0, -strlen('.skeleton'));
        }

        return $normalizedPath;
    }

    private function shouldSkipTemplatePath(string $relativePath): bool
    {
        $normalizedPath = str_replace('\\', '/', $relativePath);

        return $normalizedPath === 'README.de.md'
            || str_starts_with($normalizedPath, 'readme/');
    }

    private function shouldDeferTemplatePath(string $relativePath): bool
    {
        return false;
    }

    private function getTemplateFileContent(string $relativePath): string
    {
        $sourceDir = $this->resolveSkeletonDirectory();
        $candidatePaths = [
            $sourceDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath . '.skeleton'),
            $sourceDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath),
        ];

        foreach ($candidatePaths as $path) {
            if (!is_file($path)) {
                continue;
            }

            $content = file_get_contents($path);
            if ($content === false) {
                throw new UnexpectedValueException('Unable to read template file: ' . $relativePath);
            }

            return $content;
        }

        throw new UnexpectedValueException('Skeleton template file not found: ' . $relativePath);
    }

    private function renderSkeletonTemplates(): void
    {
        $sourceDir = $this->resolveSkeletonDirectory();

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $relativePath = substr($item->getPathname(), strlen($sourceDir) + 1);
            if (!str_ends_with(str_replace('\\', '/', $relativePath), '.skeleton')) {
                continue;
            }

            $outputRelativePath = $this->normalizeTemplateOutputPath($relativePath);
            if ($this->shouldSkipTemplatePath($outputRelativePath) || $this->shouldDeferTemplatePath($outputRelativePath)) {
                continue;
            }

            $path = $this->outputDir . str_replace('/', DIRECTORY_SEPARATOR, $outputRelativePath);
            if (!file_exists($path)) {
                continue;
            }

            $content = file_get_contents($path);
            if ($content === false) {
                throw new UnexpectedValueException('Unable to read generated skeleton file: ' . $outputRelativePath);
            }

            file_put_contents($path, $this->renderTemplateContent($content));
            chmod($path, 0777);
        }
    }

    private function renderTemplateContent(string $content): string
    {
        return strtr($content, $this->getTemplateReplacements());
    }

    private function getTemplateReplacements(): array
    {
        return [
            '{{VIBE4DOCK_PHP_VERSION}}' => $this->phpVersion,
            '{{VIBE4DOCK_PROJECT_NAME}}' => (string) $this->projectName,
            '{{VIBE4DOCK_WEB_HOST_PORT}}' => (string) $this->webPort,
            '{{VIBE4DOCK_WEB_CONTAINER_PORT}}' => (string) $this->getWebContainerPort(),
            '{{VIBE4DOCK_TOOLS_HOST_PORT}}' => (string) $this->toolsPort,
            '{{VIBE4DOCK_TOOLS_CONTAINER_PORT}}' => (string) $this->getToolsContainerPort(),
            '{{VIBE4DOCK_ROOT_SHELL_HOST_PORT}}' => (string) $this->rootShellPort,
            '{{VIBE4DOCK_ROOT_SHELL_CONTAINER_PORT}}' => (string) $this->getRootShellContainerPort(),
            '{{VIBE4DOCK_APP_SHELL_HOST_PORT}}' => (string) $this->appShellPort,
            '{{VIBE4DOCK_APP_SHELL_CONTAINER_PORT}}' => (string) $this->getAppShellContainerPort(),
            '{{VIBE4DOCK_TARGET_CONTAINER}}' => $this->getTargetContainer(),
        ];
    }

    private function getDatabaseServiceDefinition(): string
    {
        $content = [];

        if ($this->dbType === 'mysql') {
            $content[] = '    image: mysql:' . $this->mysqlVersion;
            $content[] = '    environment:';
            $content[] = '      MYSQL_ROOT_PASSWORD: root';
            $content[] = '      MYSQL_DATABASE: my_database';
            $content[] = '      MYSQL_USER: my_user';
            $content[] = '      MYSQL_PASSWORD: my_password';
            $content[] = '    volumes:';
            $content[] = '      - ./docker/mysql:/docker-entrypoint-initdb.d';
            $content[] = '      - ./docker/mysql/data:/var/lib/mysql';
        } elseif ($this->dbType === 'postgres') {
            $content[] = '    image: postgres:' . $this->postgresVersion;
            $content[] = '    environment:';
            $content[] = '      POSTGRES_DB: my_database';
            $content[] = '      POSTGRES_USER: my_user';
            $content[] = '      POSTGRES_PASSWORD: my_password';
            $content[] = '    volumes:';
            $content[] = '      - ./docker/postgres:/docker-entrypoint-initdb.d';
            $content[] = '      - ./docker/postgres/data:/var/lib/postgresql/data';
        } elseif ($this->dbType === 'firebird') {
            $content[] = '    image: jacobalberty/firebird:' . $this->firebirdVersion;
            $content[] = '    environment:';
            $content[] = '      ISC_PASSWORD: masterkey';
            $content[] = '      FIREBIRD_DATABASE: my_database.fdb';
            $content[] = '      TZ: Europe/Berlin';
            $content[] = '    volumes:';
            $content[] = '      - ./docker/firebird/data:/firebird/data';
        } else {
            $content[] = '    image: mariadb:' . $this->mariadbVersion;
            $content[] = '    environment:';
            $content[] = '      MYSQL_ROOT_PASSWORD: root';
            $content[] = '      MYSQL_DATABASE: my_database';
            $content[] = '      MYSQL_USER: my_user';
            $content[] = '      MYSQL_PASSWORD: my_password';
            $content[] = '    volumes:';
            $content[] = '      - ./docker/mariadb:/docker-entrypoint-initdb.d';
            $content[] = '      - ./docker/mariadb/data:/var/lib/mysql';
        }

        $content[] = '    ports:';
        $content[] = sprintf('      - "%d:%d"', $this->dbPort, $this->getDatabaseContainerPort());

        return implode("\n", $content);
    }

    private function getFirebirdInstallSnippet(): string
    {
        if ($this->dbType !== 'firebird') {
            return '';
        }

        return "RUN apt-get update && apt-get install -y --no-install-recommends firebird-dev firebird3.0-utils \\\n"
            . "    && docker-php-source extract \\\n"
            . "    && git clone --branch v3.0.1 --depth 1 https://github.com/FirebirdSQL/php-firebird.git /usr/src/php/ext/interbase \\\n"
            . "    && docker-php-ext-install interbase \\\n"
            . "    && rm -rf /var/lib/apt/lists/*\n\n";
    }

    private function createGitattributesFile(): void
    {
        $content = [
            '*.css text eol=lf',
            '*.htaccess text eol=lf',
            '*.htm text eol=lf',
            '*.html text eol=lf',
            '*.js text eol=lf',
            '*.json text eol=lf',
            '*.map text eol=lf',
            '*.md text eol=lf',
            '*.php text eol=lf',
            '*.profile text eol=lf',
            '*.script text eol=lf',
            '*.sh text eol=lf',
            '*.svg text eol=lf',
            '*.txt text eol=lf',
            '*.xml text eol=lf',
            '*.yml text eol=lf',
        ];

        file_put_contents($this->outputDir . '.gitattributes', implode(PHP_EOL, $content));
        chmod($this->outputDir . '.gitattributes', 0777);
    }

    private function writeDockerCompose(): void
    {
        $content = [];
        $content[] = 'services:';
        $content[] = '  web:';
        $content[] = '    container_name: ' . $this->getTargetContainer();
        $content[] = '    build: ./docker/web';
        $content[] = '    working_dir: /app';
        $content[] = '    user: application';
        $content[] = '    ports:';
        $content[] = sprintf('      - "%d:80"', $this->webPort);
        $content[] = sprintf('      - "%d:7681"', $this->rootShellPort);
        $content[] = sprintf('      - "%d:7682"', $this->appShellPort);
        $content[] = '    volumes:';
        $content[] = '      - ./:/app';
        $content[] = '    tmpfs:';
        $content[] = '      - /tmp:exec,mode=1777';
        $content[] = '    environment:';
        $content[] = '      - WEB_DOCUMENT_ROOT=/app/public';
        $content[] = '      - PHP_DISPLAY_ERRORS=1';
        $content[] = '      - PHP_MEMORY_LIMIT=512M';
        $content[] = '      - PHP_MAX_EXECUTION_TIME=300';
        $content[] = '      - PHP_POST_MAX_SIZE=200M';
        $content[] = '      - PHP_UPLOAD_MAX_FILESIZE=100M';
        $content[] = '      - PHP_DISMOD=ioncube';
        $content[] = '      - TERM=xterm-256color';
        $content[] = '  tools:';
        $content[] = '    build: ./docker/tools';
        $content[] = '    ports:';
        $content[] = sprintf('      - "%d:8090"', $this->toolsPort);
        $content[] = '    volumes:';
        $content[] = '      - /var/run/docker.sock:/var/run/docker.sock';
        $content[] = '      - ./:/app';
        $content[] = '    environment:';
        $content[] = '      - TARGET_CONTAINER=' . $this->getTargetContainer();
        $content[] = '      - ROOT_SHELL_HOST_PORT=' . $this->rootShellPort;
        $content[] = '      - APP_SHELL_HOST_PORT=' . $this->appShellPort;
        $content[] = '    restart: unless-stopped';
        $content[] = '  db:';

        if ($this->dbType === 'mysql') {
            $content[] = '    image: mysql:' . $this->mysqlVersion;
            $content[] = '    environment:';
            $content[] = '      MYSQL_ROOT_PASSWORD: root';
            $content[] = '      MYSQL_DATABASE: my_database';
            $content[] = '      MYSQL_USER: my_user';
            $content[] = '      MYSQL_PASSWORD: my_password';
            $content[] = '    volumes:';
            $content[] = '      - ./docker/mysql:/docker-entrypoint-initdb.d';
            $content[] = '      - ./docker/mysql/data:/var/lib/mysql';
        } elseif ($this->dbType === 'postgres') {
            $content[] = '    image: postgres:' . $this->postgresVersion;
            $content[] = '    environment:';
            $content[] = '      POSTGRES_DB: my_database';
            $content[] = '      POSTGRES_USER: my_user';
            $content[] = '      POSTGRES_PASSWORD: my_password';
            $content[] = '    volumes:';
            $content[] = '      - ./docker/postgres:/docker-entrypoint-initdb.d';
            $content[] = '      - ./docker/postgres/data:/var/lib/postgresql/data';
        } elseif ($this->dbType === 'firebird') {
            $content[] = '    image: jacobalberty/firebird:' . $this->firebirdVersion;
            $content[] = '    environment:';
            $content[] = '      ISC_PASSWORD: masterkey';
            $content[] = '      FIREBIRD_DATABASE: my_database.fdb';
            $content[] = '      TZ: Europe/Berlin';
            $content[] = '    volumes:';
            $content[] = '      - ./docker/firebird/data:/firebird/data';
        } else {
            $content[] = '    image: mariadb:' . $this->mariadbVersion;
            $content[] = '    environment:';
            $content[] = '      MYSQL_ROOT_PASSWORD: root';
            $content[] = '      MYSQL_DATABASE: my_database';
            $content[] = '      MYSQL_USER: my_user';
            $content[] = '      MYSQL_PASSWORD: my_password';
            $content[] = '    volumes:';
            $content[] = '      - ./docker/mariadb:/docker-entrypoint-initdb.d';
            $content[] = '      - ./docker/mariadb/data:/var/lib/mysql';
        }

        $content[] = '    ports:';
        $content[] = sprintf('      - "%d:%d"', $this->dbPort, $this->getDatabaseContainerPort());

        file_put_contents($this->outputDir . 'docker-compose.yml', implode(PHP_EOL, $content) . PHP_EOL);
        chmod($this->outputDir . 'docker-compose.yml', 0777);
    }

    private function writeDockerFile(): void
    {
        $dockerfilePath = $this->outputDir . 'docker' . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'Dockerfile';
        $content = $this->getTemplateFileContent('docker/web/Dockerfile');
        $content = $this->renderTemplateContent($content);

        file_put_contents($dockerfilePath, $content);
        chmod($dockerfilePath, 0777);
    }

    private function writeHelperScripts(): void
    {
        $dockerDir = $this->outputDir . 'docker' . DIRECTORY_SEPARATOR;
        $scripts = [
            'bash.sh' => "#!/usr/bin/env bash\n" . 'docker compose exec -u application web bash' . PHP_EOL,
            'root.sh' => "#!/usr/bin/env bash\n" . 'docker compose exec -u root web bash' . PHP_EOL,
            'bash.bat' => "docker compose exec -u application web bash\r\n",
            'root.bat' => "docker compose exec -u root web bash\r\n",
        ];

        foreach ($scripts as $filename => $content) {
            file_put_contents($dockerDir . $filename, $content);
            chmod($dockerDir . $filename, 0777);
        }
    }

    private function writeGeneratedReadme(): void
    {
        $readme = [];
        $readme[] = '# ' . $this->projectName;
        $readme[] = '';
        $readme[] = 'Minimal Vibe4Dock project template.';
        $readme[] = '';
        $readme[] = '## Start';
        $readme[] = '';
        $readme[] = '```bash';
        $readme[] = 'docker compose up -d --build';
        $readme[] = '```';
        $readme[] = '';
        $readme[] = '## Endpoints';
        $readme[] = '';
        $readme[] = '- App: `http://localhost:' . $this->webPort . '`';
        $readme[] = '- Tools: `http://localhost:' . $this->toolsPort . '`';
        $readme[] = '- Root shell: `http://localhost:' . $this->rootShellPort . '`';
        $readme[] = '- App shell: `http://localhost:' . $this->appShellPort . '`';
        $readme[] = '';
        $readme[] = 'Use `docker/bash.sh` or `docker/root.sh` to enter the container.';

        file_put_contents($this->outputDir . 'README.md', implode(PHP_EOL, $readme) . PHP_EOL);
        @unlink($this->outputDir . 'README.de.md');
        $this->removeDirectory($this->outputDir . 'readme');
    }

    private function customizeToolsFiles(): void
    {
        $this->replaceInFile(
            $this->outputDir . 'docker/tools/index.php',
            [
                '{{VIBE4DOCK_TARGET_CONTAINER}}' => $this->getTargetContainer(),
                'ttyd-web-1' => $this->getTargetContainer(),
            ]
        );
        $this->replaceInFile(
            $this->outputDir . 'docker/tools/dashboard.php',
            [
                '{{VIBE4DOCK_ROOT_SHELL_HOST_PORT}}' => (string) $this->rootShellPort,
                '{{VIBE4DOCK_APP_SHELL_HOST_PORT}}' => (string) $this->appShellPort,
            ]
        );
    }

    private function getSkeletonFile(string $filename): string
    {
        $sourceDir = $this->resolveSkeletonDirectory();
        $candidatePaths = [
            $sourceDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filename . '.skeleton'),
            $sourceDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filename),
        ];

        foreach ($candidatePaths as $path) {
            if (!is_file($path)) {
                continue;
            }

            $content = file_get_contents($path);
            if ($content === false) {
                throw new UnexpectedValueException('Unable to read skeleton file: ' . $filename);
            }

            return $content;
        }

        throw new UnexpectedValueException('Skeleton file not found: ' . $filename);
    }

    private function replaceInFile(string $path, array $replacements): void
    {
        $content = file_get_contents($path);
        file_put_contents($path, str_replace(array_keys($replacements), array_values($replacements), $content));
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }

    private function getDefaultDbPort(string $dbType): int
    {
        return match ($dbType) {
            'postgres' => 5432,
            'firebird' => 3050,
            default => 3306,
        };
    }

    private function getDatabaseContainerPort(): int
    {
        return match ($this->dbType) {
            'postgres' => 5432,
            'firebird' => 3050,
            default => 3306,
        };
    }

    private function getWebContainerPort(): int
    {
        return self::WEB_CONTAINER_PORT;
    }

    private function getToolsContainerPort(): int
    {
        return self::TOOLS_CONTAINER_PORT;
    }

    private function getRootShellContainerPort(): int
    {
        return self::ROOT_SHELL_CONTAINER_PORT;
    }

    private function getAppShellContainerPort(): int
    {
        return self::APP_SHELL_CONTAINER_PORT;
    }

    private function getDatabaseLabel(): string
    {
        return match ($this->dbType) {
            'postgres' => 'PostgreSQL',
            'mysql' => 'MySQL',
            'firebird' => 'Firebird',
            default => 'MariaDB',
        };
    }

    private function getTargetContainer(): string
    {
        return $this->projectName . '-web-1';
    }

    private function log(string $message): void
    {
        echo $message . self::NL;
    }

    private function printError(string $message): void
    {
        echo self::COLORS['RED'] . $message . self::COLORS['NONE'] . self::NL;
    }

    private function printSuccess(string $message): void
    {
        echo self::COLORS['GREEN'] . $message . self::COLORS['NONE'] . self::NL;
    }

    public static function printUsage(): void
    {
        echo self::NL;
        echo 'Create a Vibe4Dock project from the bundled skeleton template.' . self::NL;
        echo self::NL;
        echo 'USAGE' . self::NL;
        echo '    vibe4dock [OPTIONS]' . self::NL;
        echo self::NL;
        echo 'OPTIONS' . self::NL;
        echo '    --project-name=<name>' . self::NL;
        echo '    --php-version=<version>' . self::NL;
        echo '    --web-port=<port>' . self::NL;
        echo '    --tools-port=<port>' . self::NL;
        echo '    --root-shell-port=<port>' . self::NL;
        echo '    --app-shell-port=<port>' . self::NL;
        echo '    --output-dir=<dir>' . self::NL;
        echo self::NL;
        echo 'EXAMPLE' . self::NL;
        echo '    vibe4dock --project-name=my-vibe4dock --web-port=8080 --tools-port=8095' . self::NL;
    }
}

function vibe4dock_parse_options(array $args): array
{
    $options = [];

    foreach ($args as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
            continue;
        }

        if (strncmp($arg, '--', 2) === 0 || strncmp($arg, '-', 1) === 0) {
            $normalized = ltrim($arg, '-');
            $parts = explode('=', $normalized, 2);
            $key = $parts[0];
            $value = $parts[1] ?? true;
            if (is_string($value)) {
                $value = trim($value);
            }
            $options[$key] = $value;
        }
    }

    return $options;
}

$args = $_SERVER['argv'];
array_shift($args);
$options = vibe4dock_parse_options($args);

if (isset($options['help'])) {
    Vibe4DockSetup::printUsage();
    exit(0);
}

$setup = new Vibe4DockSetup($options);
$setup->createProject();
