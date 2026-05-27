# Vibe4Dock

For the German version, see [README.de.md](readme/README.de.md).

Vibe4Dock is a Docker-based development environment with a web interface for CLI tools, browser shells, and project-specific runtime extensions. The main reason this project exists is simple: it gives you direct web access to AI CLI tools so you can keep working on your project anytime - on your desktop, phone, tablet, while traveling, or basically from anywhere.

This repository contains both the Vibe4Dock skeleton template and the setup CLI (`./vibe4dock` or `php vibe4dock.php`). The setup CLI generates a new Vibe4Dock project exclusively from the bundled `skeleton/` directory.

Technically, Vibe4Dock consists of a PHP-based web container for the actual workspace and a separate tools service for the management UI. Optional runtime extensions such as databases are activated later through the tools interface, which generates provisioning, mount, and Compose override configuration dynamically from JSON definitions.

![Vibe4Dock Example](readme/example.webp)

## Project goal

Vibe4Dock solves a common problem in local and team development setups: the base environment should start quickly, while optional tools should only be enabled when needed. At the same time, access to AI and developer tools should not be tied to a single machine. Instead of maintaining one huge static Docker image per project, Vibe4Dock lets you manage tools modularly and then use them comfortably through the browser.

This project is especially useful in setups where:

- a PHP-based web environment is needed,
- multiple CLI tools should be optionally available,
- configuration such as logins, caches, or dotfiles must persist,
- direct browser access to root and application shells is useful,
- people want to continue working productively through web access while remote or mobile,
- Docker mounts should be activated dynamically without manually editing Compose files.

## Setup CLI

The setup CLI creates a new Vibe4Dock project from the bundled `skeleton/` template. All generated project files come from that placeholder-based kit, so ports, image versions, and container names are rendered into copied templates instead of being hardcoded in the generator. Files in that directory are stored as `*.skeleton` templates and are written to the target project without the `.skeleton` suffix.

### Setting up the Vibe4Dock setup CLI

The CLI itself only requires PHP CLI version 8 or newer. No additional dependencies or Composer installation are required to run the setup tool.

Latest stable tag:

```bash
git clone --branch 1.0.1 --depth 1 https://github.com/jbsnewmedia/vibe4dock.git
cd vibe4dock
chmod +x vibe4dock
```

Development branch:

```bash
git clone https://github.com/jbsnewmedia/vibe4dock.git
cd vibe4dock
chmod +x vibe4dock
```

Optionally, you can create a symlink to make the CLI available globally:

```bash
sudo ln -s $(pwd)/vibe4dock /usr/local/bin/vibe4dock
```

After that, you can run the CLI directly:

```bash
./vibe4dock --help
```

If you do not want to make the file executable, this always works as well:

```bash
php vibe4dock.php --help
```

### Interactive start

```bash
./vibe4dock
```

Alternative:

```bash
php vibe4dock.php
```

Without options, the setup runs interactively and asks for the project name, PHP version, ports, and output directory. If you run it inside an existing Vibe4Dock project, the detected settings are prefilled automatically.

### CLI help

```bash
./vibe4dock --help
```

### Non-interactive usage

```bash
./vibe4dock \
  --project-name=my-vibe4dock \
  --php-version=8.4 \
  --web-port=80 \
  --tools-port=8090 \
  --root-shell-port=7681 \
  --app-shell-port=7682 \
  --output-dir=./build/my-vibe4dock
```

### Supported options

| Option | Meaning |
| --- | --- |
| `--project-name` | Name of the generated project |
| `--php-version` | PHP base version for the web image |
| `--web-port` | Host port for the web application |
| `--tools-port` | Host port for the tools UI |
| `--root-shell-port` | Host port for the root shell |
| `--app-shell-port` | Host port for the application shell |
| `--output-dir` | Target directory for the generated project |

## Architecture overview

Vibe4Dock starts two Docker services by default:

| Service | Purpose | Port(s) |
| --- | --- | --- |
| `web` | Main development environment with Apache/PHP, ttyd, shells, and installed tools | `81`, `7681`, `7682` |
| `tools` | Management UI for dashboard, categories, settings, and install/uninstall workflows | `8090` |

### `web` service

The web service is based on `webdevops/php-apache-dev:8.4` and extends that image with:

- `ttyd` for browser-based terminals,
- `tmux`,
- `git`,
- `sudo`,
- dynamic provisioning via `docker/web/provision.sh`,
- startup logic via `docker/web/entrypoint-dev.sh`.

On container startup, the following happens:

1. optional values from `.env.local` are loaded,
2. Git config is applied for the application user when enabled,
3. already enabled tools are reinstalled or verified using `docker/web/settings/installed_tools.json`,
4. persistent mounts are restored from backup directories,
5. permissions under `/home/application` are fixed,
6. two ttyd sessions are started:
   - root shell on port `7681`
   - application shell on port `7682`

This creates an environment that is not only usable locally on one machine, but reachable from any browser. That is exactly what makes Vibe4Dock attractive for working on the go: the project runs centrally in the container while access happens through the web UI and browser terminals.

### `tools` service

The tools service is a standalone PHP container based on `php:8.4-cli-bookworm`. It provides the management interface and has access to:

- the project directory via bind mount,
- the Docker socket,
- the configuration files for tools and settings,
- the target container `<project-name>-web-1`, where commands are executed.

The interface is not just informational; it actively manages:

- tool installation and removal,
- generation of `docker/web/provision.sh`,
- generation of `docker-compose.override.yml`,
- activation of the rebuild hint when installed packs add new mounts or services,
- status display for runtime, memory, disk, and installed tools.

### Neutral base plus packs

The bundled `skeleton/` now stays intentionally neutral. It only provides the base services, the tools/settings engine, and the mergeable JSON loader. Actual tools and addons are meant to come from separate JSON-based packs. The repository can still contain examples such as `project-example/`, but the generated base project should not ship opinionated tool content by default.

## Project structure

The most important files and directories:

| Path | Meaning |
| --- | --- |
| `docker-compose.yml` | Main service definition |
| `docker-compose.override.yml` | Generated automatically when installed packs add persistent mounts or optional services |
| `public/` | Web root of the `web` container |
| `docker/web/` | Dockerfile, entry logic, provisioning, and persistent tool data |
| `docker/tools/` | Management UI, dashboard, routing, and tool/settings definitions |
| `docker/tools/category/` | Tool definitions, sorted and merged by filename |
| `docker/tools/settings/` | Settings definitions, also mergeable |
| `docker/web/settings/` | Persistent user data such as installed tools, caches, logins, and hint files |
| `readme/` | Screenshot and documentation assets |

## Usage model

The tools UI on port `8090` is split into several areas:

- **Dashboard**
  - application shell
  - root shell
  - system information such as PHP, Composer, RAM, and disk status
  - configuration status
  - list of installed tools / addons
- **Tools**
  - one combined tools view with search and a category switcher
  - project-wide configuration cards such as Git Config live here as well
  - categories appear only when tool packs provide them via JSON
- **Addons**
  - addon categories appear only when addon packs provide them via JSON

Installations and updates are triggered directly through form actions. While an action is running, the UI places a global loading layer with a spinner over the page until the response page loads.

## Why Vibe4Dock is especially useful

The core value is not just installing tools, but location independence. Vibe4Dock makes AI CLI tools and project access available over the web. That means the same development environment can be used:

- on an office desktop,
- on a personal laptop,
- on a phone,
- on a tablet,
- while traveling on a train or at a client site,
- anywhere a browser is available.

The real work stays inside the container and therefore inside one consistent environment. Device choice, operating system, and local machine setup become much less important.

## Tool and addon packs

The neutral base does not define any bundled tools or addons anymore. Categories and entries are loaded only from JSON files that are added to:

```text
docker/tools/category/
```

That means Vibe4Dock itself remains content-neutral, while concrete stacks can be shipped as separate packs or as example projects.

## How tool installation works

Tool installation in Vibe4Dock is a multi-step process:

1. the UI triggers an action such as `install`, `update`, `switch`, or `uninstall`,
2. the tools service executes the configured command inside the `web` container,
3. the tool is marked as active in `docker/web/settings/installed_tools.json`,
4. if the tool requires persistent mounts, a rebuild hint is set,
5. `docker/web/provision.sh` is regenerated,
6. `docker-compose.override.yml` is regenerated when additional volumes are required.

### Provisioning

`docker/web/provision.sh` is generated automatically and contains:

- installation commands for all enabled tools,
- a topologically sorted order based on configured dependencies,
- backup logic for mounted tool directories,
- cleanup of old rebuild hint files.

### Dynamic volumes

Tools with persistent data define `mounts`. These are written automatically into `docker-compose.override.yml` so that data such as the following can survive rebuilds:

- npm cache,
- CLI login data,
- tool configuration,
- local agent data directories.

## Rebuild flow

Some tools require volume mounts. In that case, installing them in the running container alone is not enough. Vibe4Dock marks that state as:

**Manual Rebuild Required**

The rebuild is done with:

```bash
./docker/rebuild.sh
```

or on Windows:

```bat
./docker/rebuild.bat
```

This will:

1. run `docker compose down`,
2. rebuild the web container,
3. start the Compose setup again,
4. remove the rebuild hint from inside the container.

In addition, the UI polls rebuild status every 15 seconds. As soon as the hint file disappears, the banner fades out automatically.

## Configuration system

A central part of the project is the mergeable JSON configuration system.

### Tool definitions

Tool definitions live in:

```text
docker/tools/category/
```

Each file:

- ends in `.json`,
- is loaded in filename order,
- can define categories and tools,
- is merged with all other files.

There is no required built-in tool file in the neutral base. If you want bundled examples, keep them in a separate pack or in a project template such as `project-example/`.

### Settings definitions

Settings live in:

```text
docker/tools/settings/
```

These files are also loaded by filename and merged.

Settings can optionally define `apply_commands`. Those commands are executed through the tools UI after saving a setting and can also be triggered centrally through `POST /action/settings/apply`.

### Merge rules

The merge logic lives in `docker/tools/config.php`:

- categories are merged by `unique_key`,
- tools are merged by `id`,
- settings are merged by `id`,
- files are processed alphabetically or numerically by filename,
- later files can selectively extend or override existing definitions.

Example file naming:

```text
100_base.json
200_team.json
300_project.json
```

This allows you to layer a base configuration, team-wide additions, and project-specific overrides cleanly.

## Structure of a tool definition

A tool can contain fields such as:

| Field | Meaning |
| --- | --- |
| `id` | Unique tool ID |
| `category` | Target category |
| `label` | Display name in the UI |
| `description` | Short description |
| `check` | Command used for status or version detection |
| `install` | Install command |
| `uninstall` | Uninstall command |
| `mounts` | Persistent volume mounts |
| `dependencies` | Installation prerequisites |
| `type` | Optional, e.g. `versioned` |
| `versions` | Version entries for versioned tools |
| `default_version` | Preselected version for versioned tools |

## Settings

Currently one settings group is included:

- **Git Config**
  - `GIT_USER_NAME`
  - `GIT_USER_EMAIL`
  - activation through `GIT_CONFIG_ENABLED`

The values are written into `.env.local`. Optional `apply_commands` make the runtime application neutral and definition-driven instead of hardcoding setting-specific behavior in PHP.

Generated projects also include a commented `.env.local.example` with optional HTTP Basic Auth credentials for the Tools UI, the application shell, and the root shell.

To enable that protection, copy the file to `.env.local`, uncomment the variables you need, and replace the placeholder passwords before starting the stack:

```dotenv
TOOLS_USERNAME="tools"
TOOLS_PASSWORD="replace-with-a-strong-password"
APP_SHELL_USERNAME="application"
APP_SHELL_PASSWORD="replace-with-a-strong-password"
ROOT_SHELL_USERNAME="root"
ROOT_SHELL_PASSWORD="replace-with-a-strong-password"
```

## Persistence

Persistent data is intentionally stored outside the image in mounted directories under `docker/web/settings/`.

Examples:

- `docker/web/settings/copilot`
- `docker/web/settings/claude`
- `docker/web/settings/cline`
- `docker/web/settings/codex`
- `docker/web/settings/hermes`
- `docker/web/settings/junie`
- `docker/web/settings/npm`

Technical state files are also stored there:

- `installed_tools.json`
- `rebuild_required.hint`

## Shell access

Vibe4Dock provides two direct browser shells:

| Shell | Purpose | Port |
| --- | --- | --- |
| Root Shell | Administrative tasks inside the container | `7681` |
| Application Shell | Normal development work as `application` | `7682` |

The application shell starts through `tmux` so sessions can persist.

## Starting the project

### Requirements

- Docker
- Docker Compose

### Start

```bash
cp .env.local.example .env.local
docker compose up -d --build
```

After that, the most important endpoints are:

- application: `http://localhost:80`
- tools UI: `http://localhost:8090` (protected if both `TOOLS_USERNAME` and `TOOLS_PASSWORD` are set in `.env.local`)
- root shell: `http://localhost:7681` (protected if both `ROOT_SHELL_USERNAME` and `ROOT_SHELL_PASSWORD` are set in `.env.local`)
- application shell: `http://localhost:7682` (protected if both `APP_SHELL_USERNAME` and `APP_SHELL_PASSWORD` are set in `.env.local`)

## Typical workflow

1. start Vibe4Dock,
2. open the tools UI,
3. add or install the tool and addon packs you need,
4. if a rebuild is required, run `./docker/rebuild.sh`,
5. continue working inside the application shell.

## Extending the system

### Add a new tool file

New tools or overrides should go into their own file under `docker/tools/category/`, for example:

```text
docker/tools/category/200_custom.json
```

### Add a new category

New categories need:

- `unique_key`
- `id`
- `label`
- `order`

### Add a new setting

New settings are added as additional entries under `docker/tools/settings/*.json`.

## Notes on the current implementation

- The tools UI is server-rendered PHP without a framework.
- Routing is implemented directly in `docker/tools/index.php`.
- Provisioning and override generation happen from the current configuration state.
- The system is intentionally pragmatic and easy to extend.
- Because the tools container has access to the Docker socket, the management UI has wide-reaching access to the local Docker environment.

## Security and operational considerations

Vibe4Dock is clearly intended as a development tool, not as a hardened multi-tenant platform. In particular:

- the tools container has access to `/var/run/docker.sock`,
- installation commands are executed inside the web container,
- some tools install software directly as root,
- generated projects can optionally protect the Tools UI and both browser shells via `.env.local`,
- persistent mounts may contain logins, tokens, and local configuration.

If you want to use Vibe4Dock beyond localhost, at minimum enable the HTTP Basic Auth credentials from `.env.local`, replace all placeholder passwords, and put the setup behind additional network-level protection such as a VPN, reverse proxy access control, or a private subnet.

Because of that, this setup is not intended for direct public internet exposure without additional hardening.

## Known characteristics

- Tools or addons that add mounts or services require a manual rebuild.
- Depending on the tool, installations may require network access and external package sources.
- Some CLIs only store login data after their first interactive start.
- The Compose override is generated from the currently selected tools and optional services.

## Summary

Vibe4Dock is a modular, Docker-based development workspace with a web interface for tool management, browser terminals, persistent CLI configuration, and dynamically generated container provisioning. Its biggest advantage is browser-based access to AI CLI tools and project environments, so work on a project remains possible anytime and from practically anywhere.

## Release notes

### 1.0.1

- Git Config settings (`GIT_USER_NAME`, `GIT_USER_EMAIL`) are now applied to the `application` user inside the web container, not to `root`. The values are now visible to the in-browser application shell.
- The Laravel CLI tool pack now installs `laravel/installer` for the `application` user, so the binary stays readable when invoked through the application shell.
- The web entrypoint now fixes ownership of `/home/application` before restoring installed tools, so bind-mounted tool directories like Hermes no longer fail with permission errors on startup.
- The Junie tool pack now uses the official `install.sh` installer and links the generated shim into `/usr/local/bin`, so `junie` works from the application shell.
- The Symfony CLI uninstall command is now idempotent (`rm -f` instead of `rm`), so removing it twice or after a rebuild no longer reports an error.
- The Code Quality Package now writes a real empty JSON object (`{}`) back to `composer.json` after a full uninstall, instead of an empty JSON array (`[]`).
- Tool and addon versions are now normalized through dedicated regex extraction, so CLI banners do not leak into the UI version labels.
- README clone instructions updated to point to the `1.0.1` tag.
