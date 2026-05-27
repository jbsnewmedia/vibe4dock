<p align="center">
  <img src="vibe4dock-logo-icon.svg" alt="Vibe4Dock Logo" width="160">
</p>

# Vibe4Dock

Diese Dokumentation beschreibt **Vibe4Dock 1.0.1**.

Vibe4Dock ist eine Docker-basierte Entwicklungsumgebung mit Weboberfläche für CLI-Tools, Shell-Zugänge und projektbezogene Runtime-Erweiterungen. Der Hauptgrund für das Projekt ist, dass man über das Web direkt in AI-CLI-Tools kommt und jederzeit am Projekt arbeiten kann: am Desktop, auf dem Handy, auf dem Tablet, unterwegs oder quasi von überall.

Dieses Repository enthält nicht nur das Vibe4Dock-Skeleton, sondern auch die Setup-CLI (`./vibe4dock` bzw. `php vibe4dock.php`). Damit lässt sich aus dem eingebauten Verzeichnis `skeleton/` ein neues Vibe4Dock-Projekt mit passender Docker-Konfiguration erzeugen.

Technisch besteht Vibe4Dock aus einem PHP-basierten Web-Container für die eigentliche Arbeitsumgebung und einem separaten Tools-Service für das Management-UI. Optionale Laufzeit-Erweiterungen wie Datenbanken werden später über die Tools-Oberfläche aktiviert, die Provisioning-, Mount- und Compose-Override-Konfigurationen dynamisch aus JSON-Definitionen erzeugt.

![Vibe4Dock Example](readme/example.webp)

## Ziel des Projekts

Vibe4Dock löst ein typisches Problem in lokalen und teamweiten Dev-Setups: Die Basisumgebung soll schnell startbar sein, während optionale Werkzeuge nur bei Bedarf aktiviert werden. Gleichzeitig soll der Zugriff auf AI- und Entwickler-Tools nicht an einen einzelnen Rechner gebunden sein. Statt für jedes Projekt ein großes, statisches Docker-Image mit allen möglichen Tools zu pflegen, können in Vibe4Dock Werkzeuge modular verwaltet und dann bequem über den Browser genutzt werden.

Das Projekt richtet sich vor allem an Setups, in denen:

- eine PHP-basierte Webumgebung benötigt wird,
- mehrere CLI-Werkzeuge optional verfügbar sein sollen,
- Konfigurationen wie Logins, Caches oder Dotfiles persistent sein müssen,
- Shell-Zugriff für Root und Application User direkt im Browser hilfreich ist,
- man auch mobil oder remote über Webzugriff produktiv weiterarbeiten möchte,
- Docker-Mounts dynamisch aktiviert werden sollen, ohne Compose-Dateien manuell zu pflegen.

## Setup-CLI

Die Setup-CLI erzeugt ein neues Vibe4Dock-Projekt ausschließlich aus dem mitgelieferten `skeleton/`. Das Verzeichnis arbeitet jetzt als Baukasten mit Platzhaltern, sodass Ports, Image-Versionen und Container-Namen erst beim Generieren eingesetzt werden und nicht mehr im Generator hart codiert sind. Alle erzeugten Projektdateien stammen aus diesen Vorlagen. Die Vorlagen liegen dort als `*.skeleton`-Dateien und werden im Zielprojekt ohne die Endung `.skeleton` geschrieben.

### Vibe4Dock Setup CLI einrichten

Für die CLI selbst wird nur PHP CLI ab Version 8 benötigt. Weitere Abhängigkeiten oder Composer-Installationen sind für das Starten des Setup-Tools nicht notwendig.

Letzter stabiler Tag:

```bash
git clone --branch 1.0.1 --depth 1 https://github.com/jbsnewmedia/vibe4dock.git
cd vibe4dock
chmod +x vibe4dock
```

Entwicklungsstand:

```bash
git clone https://github.com/jbsnewmedia/vibe4dock.git
cd vibe4dock
chmod +x vibe4dock
```

Optional kann ein Symlink erstellt werden, um die CLI global verfügbar zu machen:

```bash
sudo ln -s $(pwd)/vibe4dock /usr/local/bin/vibe4dock
```

Danach kann die CLI direkt gestartet werden:

```bash
./vibe4dock --help
```

Falls die Datei nicht ausführbar gemacht werden soll, funktioniert immer auch:

```bash
php vibe4dock.php --help
```

### Interaktiver Start

```bash
./vibe4dock
```

Alternativ:

```bash
php vibe4dock.php
```

Ohne Optionen läuft das Setup interaktiv und fragt nacheinander Projektname, PHP-Version, Ports und Ausgabeverzeichnis ab.

### CLI-Hilfe

```bash
./vibe4dock --help
```

### Nicht-interaktiver Aufruf

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

### Unterstützte Optionen

| Option | Bedeutung |
| --- | --- |
| `--project-name` | Name des zu erzeugenden Projekts |
| `--php-version` | PHP-Basisversion für das Web-Image |
| `--web-port` | Host-Port für die Web-Anwendung |
| `--tools-port` | Host-Port für die Tools-UI |
| `--root-shell-port` | Host-Port für die Root-Shell |
| `--app-shell-port` | Host-Port für die Application-Shell |
| `--output-dir` | Zielverzeichnis für das generierte Projekt |

## Architektur im Überblick

Vibe4Dock startet standardmäßig zwei Docker-Services:

| Service | Zweck | Port(s) |
| --- | --- | --- |
| `web` | Hauptentwicklungsumgebung mit Apache/PHP, ttyd, Shells und den installierten Tools | `80`, `7681`, `7682` |
| `tools` | Verwaltungsoberfläche für Dashboard, Kategorien, Einstellungen, Install/Uninstall-Workflows | `8090` |

### Service `web`

Der Web-Service basiert auf `webdevops/php-apache-dev:8.4` und erweitert dieses Image um:

- `ttyd` für browserbasierte Terminals,
- `tmux`,
- `git`,
- `sudo`,
- dynamisches Provisioning über `docker/web/provision.sh`,
- Startlogik über `docker/web/entrypoint-dev.sh`.

Beim Containerstart passiert Folgendes:

1. optionale Werte aus `.env.local` werden eingelesen,
2. Git-Config wird bei Bedarf für den Application User gesetzt,
3. bereits aktivierte Tools werden anhand von `docker/web/settings/installed_tools.json` erneut installiert bzw. verifiziert,
4. persistente Mounts werden aus Backup-Verzeichnissen wiederhergestellt,
5. Rechte unter `/home/application` werden korrigiert,
6. zwei ttyd-Sessions werden gestartet:
   - Root-Shell auf Port `7681`
   - Application-Shell auf Port `7682`

Damit entsteht eine Umgebung, die nicht nur lokal am Rechner funktioniert, sondern auch aus jedem Browser erreichbar ist. Genau das macht Vibe4Dock für das Arbeiten unterwegs interessant: Das Projekt läuft zentral im Container, während der Zugriff über Weboberfläche und Browser-Terminals erfolgt.

### Service `tools`

Der Tools-Service ist ein eigenständiger PHP-Container auf Basis von `php:8.4-cli-bookworm`. Er stellt die Verwaltungsoberfläche bereit und hat Zugriff auf:

- das Projektverzeichnis via Bind-Mount,
- den Docker-Socket,
- die Konfigurationsdateien für Tools und Settings,
- den Zielcontainer `<projektname>-web-1`, in dem Befehle ausgeführt werden.

Die Oberfläche ist nicht nur Anzeige, sondern steuert aktiv:

- Installation und Deinstallation von Tools,
- Generierung von `docker/web/provision.sh`,
- Generierung von `docker-compose.override.yml`,
- Aktivierung des Rebuild-Hinweises, wenn installierte Packs neue Mounts oder Services ergänzen,
- Statusanzeige für Runtime, Speicher, Datenträger und installierte Tools.

### Mitgelieferte Packs plus bedarfsgesteuerte Aktivierung

Das Repository liefert die Basis-Services zusammen mit einer kuratierten Auswahl an Tool- und Addon-Definitionen aus. Generierte Projekte bleiben trotzdem praktisch schlank, weil Tools, Addon-Services und ihre persistenten Verzeichnisse erst dann aktiviert werden, wenn sie über die Tools-UI installiert werden. Mount-basierte Verzeichnisse und Addon-Datenordner werden bei Bedarf angelegt und wieder entfernt, wenn sie nicht mehr gebraucht werden.

## Projektstruktur

Die wichtigsten Dateien und Verzeichnisse:

| Pfad | Bedeutung |
| --- | --- |
| `docker-compose.yml` | Hauptdefinition der Services |
| `docker-compose.override.yml` | Wird bei Bedarf automatisch erzeugt, wenn installierte Packs persistente Mounts oder optionale Services ergänzen |
| `public/` | Webroot des `web`-Containers |
| `docker/web/` | Dockerfile, Entry-Logik, Provisioning und persistente Tooldaten |
| `docker/tools/` | Verwaltungsoberfläche, Dashboard, Routing, Tool- und Settings-Definitionen |
| `docker/tools/category/` | Tool-Definitionen, nach Dateinamen sortiert und zusammengeführt |
| `docker/tools/addons/` | Addon- und optionale Service-Definitionen, ebenfalls sortiert und zusammengeführt |
| `docker/tools/settings/` | Einstellungsdefinitionen, ebenfalls mergebar |
| `docker/web/settings/` | Persistente Nutzdaten wie installierte Tools, Caches, Logins und Hint-Dateien |
| `docker/data/` | Bedarfsgesteuerte Addon-Datenverzeichnisse, z. B. für Datenbanken |
| `readme/` | Screenshot und Doku-Assets |

## Bedienkonzept

Die Tools-Oberfläche auf Port `8090` ist in mehrere Bereiche gegliedert:

- **Dashboard**
  - Application Shell
  - Root Shell
  - addonbasierte Browser-Shells, entweder als Erweiterung von `web` oder über eigene Port-Freigaben von Addon-Services
  - Systeminformationen wie PHP-, Composer-, RAM- und Disk-Status
  - Konfigurationsstatus
  - Liste installierter Tools / Addons
- **Tools**
  - eine gemeinsame Tool-Ansicht mit Suche und Kategorie-Umschalter
  - installierbare Tools können nachgelagert eine eigene `Config`-Aktion anbieten
  - Git Config liegt jetzt als normales Tool in einer eigenen Kategorie `Git`
  - Kategorien erscheinen nur dann, wenn Tool-Packs sie per JSON definieren
- **Addons**
  - Addon-Kategorien erscheinen nur dann, wenn Addon-Packs sie per JSON definieren

Installationen oder Updates werden direkt über Form-Aktionen ausgelöst. Während eine Aktion läuft, legt die UI einen globalen Loading-Layer mit Spinner über die Seite, bis die Antwortseite geladen ist.

## Warum Vibe4Dock besonders praktisch ist

Der zentrale Nutzen ist nicht nur die Installation von Tools, sondern die Ortsunabhängigkeit. Vibe4Dock macht AI-CLI-Tools und Projektzugriff über das Web verfügbar. Dadurch kann dieselbe Arbeitsumgebung genutzt werden:

- am Desktop im Büro,
- auf dem privaten Laptop,
- auf dem Handy,
- auf dem Tablet,
- unterwegs im Zug oder beim Kunden,
- überall dort, wo ein Browser verfügbar ist.

Die eigentliche Arbeit bleibt im Container und damit in einer konsistenten Umgebung. Gerät, Betriebssystem und lokales Setup werden deutlich unwichtiger.

## Tool- und Addon-Packs

Die mitgelieferten Definitionen für **Vibe4Dock 1.0.1** werden aus folgenden Pfaden geladen:

```text
docker/tools/category/
docker/tools/addons/
```

Aktuell mitgelieferte Tool-Packs:

- **AI CLI**: GitHub Copilot CLI, Codex CLI, Claude Code, Cline CLI, Hermes CLI, Junie CLI
- **Git**: Git Config, Lazygit
- **System & Runtime**: Node.js, pnpm, Yarn
- **PHP Frameworks**: Laravel CLI, Symfony CLI, WordPress CLI
- **Code Quality**: Code Quality Package

Aktuell mitgelieferte Addon-Packs:

- **Databases**: MariaDB, MySQL, PostgreSQL, Firebird
- **Browser Shells**: Lazygit Shell
- **Browser IDEs**: code-server

Eigene Packs oder Überschreibungen können weiterhin zusätzlich ergänzt werden.

## Wie Tool-Installation technisch funktioniert

Die Installation eines Tools ist in Vibe4Dock mehrstufig:

1. In der UI wird eine Aktion wie `install`, `update`, `switch` oder `uninstall` ausgelöst.
2. Der Tools-Service führt den hinterlegten Befehl im `web`-Container aus.
3. Das Tool wird in `docker/web/settings/installed_tools.json` als aktiv markiert.
4. Falls das Tool persistente Mounts benötigt, wird ein Rebuild-Hinweis gesetzt.
5. `docker/web/provision.sh` wird neu generiert.
6. `docker-compose.override.yml` wird neu generiert, wenn zusätzliche Volumes benötigt werden.

### Provisioning

`docker/web/provision.sh` wird automatisch aufgebaut und enthält:

- die Installationsbefehle aller aktivierten Tools,
- eine topologisch sortierte Reihenfolge anhand definierter Abhängigkeiten,
- Backup-Logik für gemountete Tool-Verzeichnisse,
- Entfernung alter Rebuild-Hinweise.

### Dynamische Volumes

Tools mit persistenten Daten definieren `mounts`. Diese landen automatisch in `docker-compose.override.yml`, damit z. B. folgende Daten erhalten bleiben:

- npm-Cache,
- CLI-Login-Daten,
- Tool-Konfigurationen,
- lokale Agenten-Datenverzeichnisse.

## Rebuild-Flow

Einige Tools benötigen Volume-Mounts. In diesem Fall reicht eine reine Installation im laufenden Container nicht aus. Vibe4Dock markiert den Zustand dann als:

**Manual Rebuild Required**

Der Rebuild erfolgt über:

```bash
./docker/rebuild.sh
```

oder unter Windows:

```bat
./docker/rebuild.bat
```

Dabei wird:

1. `docker compose down` ausgeführt,
2. der Web-Container neu gebaut,
3. das Compose-Setup neu gestartet,
4. der Rebuild-Hinweis im Container entfernt.

Zusätzlich fragt die UI den Rebuild-Status alle 15 Sekunden ab. Sobald das Hint-File entfernt wurde, blendet sich der Hinweis automatisch weich aus.

## Konfigurationssystem

Ein zentraler Teil des Projekts ist das mergebare JSON-Konfigurationssystem.

### Tool-Definitionen

Tool-Definitionen liegen unter:

```text
docker/tools/category/
```

Jede Datei:

- endet auf `.json`,
- wird nach Dateinamen sortiert geladen,
- kann Kategorien und Tools definieren,
- wird mit allen anderen Dateien zusammengeführt.

Die mitgelieferten Tool-Dateien sind in Version 1.0.1 bereits Teil des Repositories, und zusätzliche team- oder projektspezifische Packs können über denselben Merge-Mechanismus darübergelegt werden.

### Addon-Definitionen

Addon-Definitionen liegen unter:

```text
docker/tools/addons/
```

Diese Dateien werden mit denselben Merge-Regeln wie normale Tools geladen, definieren aber meist optionale Compose-Services, Ports, Umgebungsvariablen, persistente Datenverzeichnisse und an das Dashboard andockbare Browser-Endpunkte wie Shells oder Browser-IDEs.

### Settings-Definitionen

Settings liegen unter:

```text
docker/tools/settings/
```

Auch diese Dateien werden nach Dateinamen geladen und gemerged.

Settings koennen optional `apply_commands` definieren. Diese Befehle werden nach dem Speichern ueber die Tools-UI ausgefuehrt und koennen zentral ueber `POST /action/settings/apply` erneut angewendet werden.

### Merge-Regeln

Die Merge-Logik steckt in `docker/tools/config.php`:

- Kategorien werden über `unique_key` zusammengeführt,
- Tools werden über `id` zusammengeführt,
- Settings werden über `id` zusammengeführt,
- die Dateien werden alphabetisch bzw. numerisch nach Dateinamen verarbeitet,
- spätere Dateien können bestehende Definitionen gezielt erweitern oder überschreiben.

Beispiel für die Dateibenennung:

```text
100_base.json
200_team.json
300_project.json
```

Damit lassen sich Basiskonfiguration, teamweite Erweiterungen und projektspezifische Anpassungen sauber staffeln.

## Aufbau einer Tool-Definition

Ein Tool kann unter anderem folgende Felder besitzen:

| Feld | Bedeutung |
| --- | --- |
| `id` | Eindeutige Tool-ID |
| `category` | Zielkategorie |
| `label` | Anzeigename in der UI |
| `description` | Kurzbeschreibung |
| `check` | Befehl zur Status- oder Versionsprüfung |
| `install` | Installationsbefehl |
| `uninstall` | Deinstallationsbefehl |
| `mounts` | Persistente Volume-Mounts |
| `dependencies` | Vorbedingungen für die Installation |
| `type` | Optional, z. B. `versioned` |
| `versions` | Versionseinträge für versionierte Tools |
| `default_version` | Vorauswahl bei versionierten Tools |
| `config_schema` | Optionale Definition für einen tool-spezifischen Config-Dialog |
| `apply_commands` | Optionale Befehle, die nach Config-Änderungen oder Aktivierung laufen |
| `package_operations` | Optionale Paket-/Datei-Operationen für Scaffold-Tools |
| `compose_service` | Optionale Service-Definition, vor allem für Addons |
| `dashboard_shell` | Optionaler Dashboard-Eintrag für browserfähige Shells oder Addon-Endpunkte |

### Browser-Endpunkt-Andockung

Addons können auf zwei generische Arten ins Dashboard andocken:

1. über einen eigenen Host-Port, der per `dashboard_shell` beschrieben wird
2. über eine Erweiterung des `web`-Services mit einem deklarativen `dashboard_shell.launcher`

Typische Felder von `dashboard_shell`:

| Feld | Bedeutung |
| --- | --- |
| `label` | Titel der Dashboard-Kachel |
| `port_field` / `port` | Host-Port aus der Config oder als fixer Wert |
| `username_field` / `username` | Optionaler Basic-Auth-Benutzername |
| `password_field` / `password` | Optionales Basic-Auth-Passwort |
| `protocol` | Optionales URL-Schema wie `http` oder `https` |
| `path` | Optionaler URL-Pfad |
| `button_label` | Optionaler Text für den Button |
| `help_title` / `help_text` / `help_command` | Inhalte für den Hilfe-Dialog im Dashboard |

Damit lassen sich auch rein passwortgeschützte Endpunkte wie `code-server` abbilden, bei denen kein Benutzername nötig ist, ein gesetztes Passwort aber trotzdem als geschützter Zugriff gilt.

Für browserbasierte Shells im `web`-Container unterstützt `dashboard_shell.launcher` eine deklarative Laufzeitdefinition:

| Launcher-Feld | Bedeutung |
| --- | --- |
| `type` | Aktuell `ttyd` |
| `service` | Aktuell `web` für In-Container-Browser-Shells |
| `container_port` | Container-Port des Launchers |
| `run_as` | `application` oder `root` |
| `working_directory` | Optionales Arbeitsverzeichnis vor dem Start |
| `executable` | Zu startender Befehl |
| `arguments` | Optionale Argumentliste |
| `fallback_profile` | Optionales Fallback-Shell-Profil wie `application-shell` |
| `environment` | Optionale zusätzliche Umgebungsvariablen |

Installierte deklarative Launcher werden in `docker/web/settings/browser_shells.json` materialisiert. Der Web-Entrypoint liest diese Datei beim Start und erzeugt daraus zusätzliche Browser-Shells ohne addon-spezifische Sonderlogik im Runtime-Code.

Beispiele für mitgelieferte Browser-Endpunkte:

- **Lazygit Shell**: zusätzliche ttyd-Session im `web`-Container
- **code-server**: separater Addon-Service mit eigenem Port und passwortgeschützter Browser-IDE

## Einstellungen

Settings-Definitionen werden weiterhin unterstützt, aber der mitgelieferte Git-Identitäts-Flow läuft jetzt als normales Tool statt als alte Settings-Karte.

- **Git Config** (Tool-Kategorie `Git`)
  - Installation und Deinstallation über den normalen Tool-Workflow
  - Konfiguration über den tool-spezifischen `Config`-Dialog
  - `GIT_USER_NAME`
  - `GIT_USER_EMAIL`

Die Daten werden in `.env.local` geschrieben. Optionale `apply_commands` machen die Laufzeit-Anwendung neutral und definitionsbasiert, statt setting-spezifisches Verhalten in PHP fest zu verdrahten.

Generierte Projekte enthalten außerdem eine auskommentierte `.env.local.example` mit optionalen HTTP-Basic-Auth-Zugangsdaten für Tools-UI, Application Shell und Root Shell.

Um diese Absicherung zu aktivieren, die Datei nach `.env.local` kopieren, die benötigten Variablen auskommentieren und die Platzhalter-Passwörter vor dem Start des Stacks ersetzen:

```dotenv
TOOLS_USERNAME="tools"
TOOLS_PASSWORD="durch-ein-starkes-passwort-ersetzen"
APP_SHELL_USERNAME="application"
APP_SHELL_PASSWORD="durch-ein-starkes-passwort-ersetzen"
ROOT_SHELL_USERNAME="root"
ROOT_SHELL_PASSWORD="durch-ein-starkes-passwort-ersetzen"
```

## Persistenz

Persistente Daten landen bewusst außerhalb des Images in gemounteten Verzeichnissen unter `docker/web/settings/`.

Beispiele:

- `docker/web/settings/copilot`
- `docker/web/settings/claude`
- `docker/web/settings/cline`
- `docker/web/settings/codex`
- `docker/web/settings/code-server/config`
- `docker/web/settings/code-server/data`
- `docker/web/settings/hermes`
- `docker/web/settings/junie`
- `docker/web/settings/lazygit`
- `docker/web/settings/npm`

Addon-Services speichern ihre Laufzeitdaten ebenfalls außerhalb des Images, zum Beispiel unter:

- `docker/data/mariadb`
- `docker/data/mysql`
- `docker/data/postgresql`
- `docker/data/firebird`

Außerdem werden dort technische Statusdateien abgelegt:

- `installed_tools.json`
- `browser_shells.json`
- `rebuild_required.hint`

## Browser-Zugänge

Vibe4Dock stellt zwei eingebaute Browser-Shells sowie addonbasierte Dashboard-Endpunkte wie zusätzliche Shells oder Browser-IDEs bereit:

| Shell | Zweck | Port |
| --- | --- | --- |
| Root Shell | Administrative Aufgaben im Container | `7681` |
| Application Shell | Normale Entwicklungsarbeit als `application` | `7682` |

Die Application Shell startet über `tmux`, damit Sessions bestehen bleiben können. Zusätzliche Browser-Shells können von Addons deklarativ registriert werden, und separate Addon-Services wie `code-server` können eigene Browser-IDE-Ports bereitstellen und trotzdem im selben Dashboard erscheinen.

## Starten des Projekts

### Voraussetzungen

- Docker
- Docker Compose

### Start

```bash
cp .env.local.example .env.local
docker compose up -d --build
```

Danach sind die wichtigsten Endpunkte:

- Anwendung: `http://localhost:80`
- Tools-UI: `http://localhost:8090` (geschützt, wenn `TOOLS_USERNAME` und `TOOLS_PASSWORD` beide in `.env.local` gesetzt sind)
- Root Shell: `http://localhost:7681` (geschützt, wenn `ROOT_SHELL_USERNAME` und `ROOT_SHELL_PASSWORD` beide in `.env.local` gesetzt sind)
- Application Shell: `http://localhost:7682` (geschützt, wenn `APP_SHELL_USERNAME` und `APP_SHELL_PASSWORD` beide in `.env.local` gesetzt sind)

## Typischer Workflow

1. Vibe4Dock starten
2. Tools-UI aufrufen
3. die benötigten Tool- und Addon-Packs ergänzen oder installieren
4. falls ein Rebuild verlangt wird: `./docker/rebuild.sh` ausführen
5. über die Application Shell im Projekt arbeiten

## Erweiterung des Systems

### Neue Tool-Datei anlegen

Neue Tools oder Überschreibungen sollten als eigene Datei in `docker/tools/category/` landen, z. B.:

```text
docker/tools/category/200_custom.json
```

### Neue Kategorie anlegen

Neue Kategorien brauchen:

- `unique_key`
- `id`
- `label`
- `order`

### Neues Setting anlegen

Neue Settings werden als zusätzlicher Eintrag unter `docker/tools/settings/*.json` angelegt.

## Hinweise zur aktuellen Implementierung

- Die Tools-Oberfläche arbeitet serverseitig mit einfachem PHP ohne Framework.
- Das Routing ist direkt in `docker/tools/index.php` implementiert.
- Die Generierung von Provisioning und Override-Datei passiert aus dem aktuellen Konfigurationsstand.
- Das System ist absichtlich pragmatisch gehalten und leicht erweiterbar.
- Durch den Docker-Socket im Tools-Container hat die Verwaltungsoberfläche weitreichenden Zugriff auf die lokale Docker-Umgebung.

## Sicherheit und Betriebsaspekte

Vibe4Dock ist klar als Entwicklungswerkzeug gedacht, nicht als gehärtete Multi-Tenant-Plattform. Besonders wichtig:

- der Tools-Container besitzt Zugriff auf `/var/run/docker.sock`,
- Installationsbefehle werden im Web-Container ausgeführt,
- einige Tools installieren Software direkt als Root,
- generierte Projekte können Tools-UI sowie beide Browser-Shells optional über `.env.local` absichern,
- persistente Mounts enthalten potenziell Logins, Tokens und lokale Konfigurationen.

Wenn Vibe4Dock nicht nur auf `localhost` genutzt wird, sollten mindestens die HTTP-Basic-Auth-Zugangsdaten aus `.env.local` aktiviert, alle Platzhalter-Passwörter ersetzt und zusätzlich Netzwerkschutz wie VPN, Reverse-Proxy-Zugriffsschutz oder ein privates Subnetz eingesetzt werden.

Für den produktiven Internetbetrieb ohne zusätzliche Absicherung ist das Setup daher nicht gedacht.

## Bekannte Besonderheiten

- Tools oder Addons mit zusätzlichen Mounts oder Services erfordern einen manuellen Rebuild.
- Installationen können je nach Tool Netzwerkzugriff und externe Paketquellen benötigen.
- Manche CLIs speichern Logins erst nach dem ersten interaktiven Start.
- Das Compose-Override des generierten Projekts wird aus den aktuell gewählten Tools und optionalen Services erzeugt.

## Kurzfassung

Vibe4Dock ist ein modularer, Docker-basierter Entwicklungs-Workspace mit Weboberfläche für Tool-Management, Browser-Terminals, persistente CLI-Konfigurationen und dynamisch generierte Container-Provisionierung. Der größte Mehrwert ist der browserbasierte Zugriff auf AI-CLI-Tools und Projektumgebungen, sodass Arbeit am Projekt jederzeit und von praktisch überall möglich ist.
