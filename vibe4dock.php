<?php

class Vibe4DockSetup
{
    private const COLORS = ['GREEN' => "\033[32m", 'RED' => "\033[31m", 'NONE' => "\033[0m"];
    private const NL = "\n";
    private const DB_TYPES = ['mysql', 'postgres', 'mariadb', 'firebird'];
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
    private bool $addCodeQuality;
    private array $codeQualityTools = [];
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
        $this->phpVersion = '8.3';
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
        $this->addCodeQuality = false;
        $this->codeQualityTools = [];
    }

    private function interactiveSetup(): void
    {
        echo self::NL;
        echo 'Welcome to Vibe4Dock Setup Tool' . self::NL;
        echo '------------------------------' . self::NL;

        $this->projectName = $this->ask('Project Name', $this->projectName);
        $this->phpVersion = $this->ask('PHP Version', $this->phpVersion);

        echo self::NL . 'Database Configuration:' . self::NL;
        $this->dbType = $this->askChoice('Database Type', self::DB_TYPES, $this->dbType);
        $this->mariadbVersion = $this->dbType === 'mariadb' ? $this->ask('MariaDB Version', $this->mariadbVersion) : '12.2';
        $this->postgresVersion = $this->dbType === 'postgres' ? $this->ask('PostgreSQL Version', $this->postgresVersion) : '18.4';
        $this->mysqlVersion = $this->dbType === 'mysql' ? $this->ask('MySQL Version', $this->mysqlVersion) : '9.7';
        $this->firebirdVersion = $this->dbType === 'firebird' ? $this->ask('Firebird Version', $this->firebirdVersion) : '3';

        echo self::NL . 'Code Quality Tools:' . self::NL;
        $this->addCodeQuality = $this->askConfirm('Add Code Quality Tools?', 'yes');
        if ($this->addCodeQuality) {
            if ($this->askConfirm('Add ECS (Easy Coding Standard)?', 'yes')) {
                $this->codeQualityTools[] = 'ecs';
            }
            if ($this->askConfirm('Add Rector?', 'yes')) {
                $this->codeQualityTools[] = 'rector';
            }
            if ($this->askConfirm('Add PHPStan?', 'yes')) {
                $this->codeQualityTools[] = 'phpstan';
            }
            if ($this->askConfirm('Add PHPUnit?', 'yes')) {
                $this->codeQualityTools[] = 'phpunit';
            }
        }

        echo self::NL . 'Port Configuration:' . self::NL;
        $this->webPort = $this->askPort('Web Port', (string) $this->webPort);
        $this->toolsPort = $this->askPort('Tools UI Port', (string) $this->toolsPort);
        $this->rootShellPort = $this->askPort('Root Shell Port', (string) $this->rootShellPort);
        $this->appShellPort = $this->askPort('Application Shell Port', (string) $this->appShellPort);
        $this->dbPort = $this->askPort($this->getDatabaseLabel() . ' Port', (string) $this->getDefaultDbPort($this->dbType));

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
        $this->phpVersion = $options['php-version'] ?? '8.3';
        $this->symfonyVersion = '7.*';
        $this->postgresVersion = $options['postgres-version'] ?? '18.4';
        $this->mysqlVersion = $options['mysql-version'] ?? '9.7';
        $this->mariadbVersion = $options['mariadb-version'] ?? '12.2';
        $this->firebirdVersion = $options['firebird-version'] ?? '3';
        $this->dbType = $options['db-type'] ?? 'mariadb';
        $this->outputDir = $options['output-dir'] ?? (getcwd() . DIRECTORY_SEPARATOR);
        $this->webPort = $this->normalizePort($options['web-port'] ?? self::DEFAULT_WEB_PORT);
        $this->toolsPort = $this->normalizePort($options['tools-port'] ?? self::DEFAULT_TOOLS_PORT);
        $this->rootShellPort = $this->normalizePort($options['root-shell-port'] ?? self::DEFAULT_ROOT_SHELL_PORT);
        $this->appShellPort = $this->normalizePort($options['app-shell-port'] ?? self::DEFAULT_APP_SHELL_PORT);
        $this->dbPort = $this->normalizePort($options['db-port'] ?? $this->getDefaultDbPort($this->dbType));
        $this->addCodeQuality = isset($options['code-quality']) && in_array((string) $options['code-quality'], ['true', '1'], true);
        $this->codeQualityTools = isset($options['tools'])
            ? array_values(array_filter(array_map('trim', explode(',', (string) $options['tools']))))
            : [];

        if (!empty($this->codeQualityTools)) {
            $this->addCodeQuality = true;
        }
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

    private function validateInputs(): void
    {
        if (empty($this->projectName) || !preg_match('/^[a-zA-Z0-9._-]+$/', $this->projectName)) {
            $this->printError('Invalid project name. [a-zA-Z0-9-_.]');
            exit(1);
        }

        if (!in_array($this->dbType, self::DB_TYPES, true)) {
            $this->printError('Invalid database type. [mysql, postgres, mariadb, firebird]');
            exit(1);
        }

        $ports = [
            $this->webPort,
            $this->toolsPort,
            $this->rootShellPort,
            $this->appShellPort,
            $this->dbPort,
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
        if ($this->addCodeQuality && !empty($this->codeQualityTools)) {
            $this->setupCodeQuality();
        }
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
        $normalizedPath = str_replace('\\', '/', $relativePath);

        return in_array($normalizedPath, [
            'composer.json',
            'phpstan-global.neon',
            'phpunit-coverage.xml.dist',
            'phpunit-no-coverage.xml.dist',
            'rector.php',
            '.php-cs-fixer.dist.php',
        ], true) || str_starts_with($normalizedPath, 'vendor-bin/');
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
            if ($this->shouldSkipTemplatePath($outputRelativePath)) {
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
            '{{VIBE4DOCK_DB_LABEL}}' => $this->getDatabaseLabel(),
            '{{VIBE4DOCK_DB_HOST_PORT}}' => (string) $this->dbPort,
            '{{VIBE4DOCK_DB_SERVICE_DEFINITION}}' => $this->getDatabaseServiceDefinition(),
            '{{VIBE4DOCK_FIREBIRD_INSTALL}}' => $this->getFirebirdInstallSnippet(),
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
        $readme[] = '- ' . $this->getDatabaseLabel() . ': `localhost:' . $this->dbPort . '`';
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

    private function setupCodeQuality(): void
    {
        $targetDir = $this->outputDir;
        $composerFile = $targetDir . 'composer.json';
        $composerData = [];

        if (file_exists($composerFile)) {
            $composerData = json_decode((string) file_get_contents($composerFile), true) ?? [];
        }

        $composerData['require-dev']['bamarni/composer-bin-plugin'] = '^1.8';
        $composerData['config']['allow-plugins']['bamarni/composer-bin-plugin'] = true;
        $composerData['extra']['bamarni-bin']['bin-links'] = false;
        $composerData['extra']['bamarni-bin']['target-directory'] = 'vendor-bin';
        $composerData['extra']['bamarni-bin']['forward-command'] = true;
        $composerData['scripts'] = $composerData['scripts'] ?? [];

        $skeletonComposer = json_decode((string) $this->getSkeletonFile('composer.json'), true);
        foreach ($skeletonComposer['scripts'] as $key => $script) {
            $match = false;
            foreach ($this->codeQualityTools as $tool) {
                if (strpos($key, 'bin-' . $tool) !== false) {
                    $match = true;
                    break;
                }
            }

            if (in_array($key, ['test', 'test-coverage', 'test-full', 'test-watch', 'ci', 'ci-fix', 'ci-coverage'], true)) {
                $match = true;
            }

            if (!$match) {
                continue;
            }

            if (is_array($script)) {
                $filteredScript = [];
                foreach ($script as $subScript) {
                    if (strpos($subScript, '@bin-') === 0) {
                        $subTool = str_replace(['@bin-', '-install', '-update', '-v', '-fix', '-process', '-no-coverage', '-coverage'], '', $subScript);
                        if (in_array($subTool, $this->codeQualityTools, true)) {
                            $filteredScript[] = $subScript;
                        }
                    } else {
                        $filteredScript[] = $subScript;
                    }
                }

                if (!empty($filteredScript)) {
                    $composerData['scripts'][$key] = $filteredScript;
                }
                continue;
            }

            $composerData['scripts'][$key] = $script;
        }

        file_put_contents($composerFile, json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        $filesToCreate = [
            'phpstan' => ['phpstan-global.neon'],
            'phpunit' => ['phpunit-coverage.xml.dist', 'phpunit-no-coverage.xml.dist'],
            'rector' => ['rector.php'],
            'ecs' => ['.php-cs-fixer.dist.php'],
        ];

        foreach ($filesToCreate as $tool => $files) {
            if (!in_array($tool, $this->codeQualityTools, true)) {
                continue;
            }

            foreach ($files as $file) {
                $content = $this->getSkeletonFile($file);
                if ($content === null) {
                    continue;
                }

                if ($file === 'rector.php') {
                    $phpVer = str_replace('.', '', $this->phpVersion);
                    $content = preg_replace('/php\d+: true/', 'php' . $phpVer . ': true', $content);
                }

                if (strpos($file, 'phpunit') === 0) {
                    $phpUnitVer = $this->getPhpUnitVersion($this->phpVersion);
                    $phpUnitVerNumeric = ltrim($phpUnitVer, '^');
                    $content = preg_replace('/https:\/\/schema\.phpunit\.de\/\d+\.\d+\/phpunit\.xsd/', 'https://schema.phpunit.de/' . $phpUnitVerNumeric . '/phpunit.xsd', $content);

                    if (version_compare($phpUnitVerNumeric, '10.0', '<') && strpos($content, '<source') !== false) {
                        $content = preg_replace('/displayDetailsOnTestsThatTrigger\w+="true"/', '', $content);
                        $content = preg_replace('/<source.*?>(.*?)<\/source>/s', "<filter>\n        <whitelist processUncoveredFilesFromWhitelist=\"true\">\n$1        </whitelist>\n    </filter>", $content);
                    }
                }

                file_put_contents($targetDir . $file, $content);
            }
        }

        foreach ($this->codeQualityTools as $tool) {
            $toolDir = 'vendor-bin' . DIRECTORY_SEPARATOR . $tool;
            $destDir = $targetDir . $toolDir;
            $content = $this->getSkeletonFile($toolDir . DIRECTORY_SEPARATOR . 'composer.json');

            if ($content === null) {
                continue;
            }

            if (!is_dir($destDir)) {
                mkdir($destDir, 0777, true);
            }

            $sfVersionConstraint = $this->symfonyVersion;
            if (!preg_match('/[\^\~\>\<]/', $sfVersionConstraint) && strpos($sfVersionConstraint, '*') === false) {
                $sfVersionConstraint = '^' . $sfVersionConstraint;
            }

            $content = preg_replace('/"symfony\/([^"]+)": "[^"]+"/', '"symfony/$1": "' . $sfVersionConstraint . '"', $content);

            if ($tool === 'phpunit') {
                $content = preg_replace('/"phpunit\/phpunit": "\^11\.0"/', '"phpunit/phpunit": "' . $this->getPhpUnitVersion($this->phpVersion) . '"', $content);
            }

            if ($tool === 'rector') {
                $content = preg_replace('/"php": "\d+\.\d+"/', '"php": "' . $this->phpVersion . '"', $content);
            }

            file_put_contents($destDir . DIRECTORY_SEPARATOR . 'composer.json', $content);
        }
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

    private function getPhpUnitVersion(string $phpVersion): string
    {
        if (version_compare($phpVersion, '8.3', '>=')) {
            return '^12.0';
        }
        if (version_compare($phpVersion, '8.2', '>=')) {
            return '^11.0';
        }
        if (version_compare($phpVersion, '8.1', '>=')) {
            return '^10.0';
        }

        return '^9.6';
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
        echo '    --db-type=<mariadb|mysql|postgres|firebird>' . self::NL;
        echo '    --mariadb-version=<version>' . self::NL;
        echo '    --mysql-version=<version>' . self::NL;
        echo '    --postgres-version=<version>' . self::NL;
        echo '    --firebird-version=<version>' . self::NL;
        echo '    --code-quality=<true|false>' . self::NL;
        echo '    --tools=<ecs,rector,phpstan,phpunit>' . self::NL;
        echo '    --web-port=<port>' . self::NL;
        echo '    --tools-port=<port>' . self::NL;
        echo '    --root-shell-port=<port>' . self::NL;
        echo '    --app-shell-port=<port>' . self::NL;
        echo '    --db-port=<port>' . self::NL;
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
