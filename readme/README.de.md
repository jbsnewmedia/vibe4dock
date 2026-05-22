# Vibe4Dock

Vibe4Dock ist eine Docker-basierte Entwicklungsumgebung mit Weboberfläche für CLI-Tools, Shell-Zugänge und projektbezogene Runtime-Erweiterungen. Der Hauptgrund für das Projekt ist, dass man über das Web direkt in AI-CLI-Tools kommt und jederzeit am Projekt arbeiten kann: am Desktop, auf dem Handy, auf dem Tablet, unterwegs oder quasi von überall.

Dieses Repository enthält nicht nur das Vibe4Dock-Skeleton, sondern auch die Setup-CLI (`./vibe4dock` bzw. `php vibe4dock.php`). Damit lässt sich aus dem eingebauten Verzeichnis `skeleton/` ein neues Vibe4Dock-Projekt mit passender Docker-Konfiguration erzeugen.

Technisch besteht Vibe4Dock aus einem PHP-basierten Web-Container für die eigentliche Arbeitsumgebung, einem separaten Tools-Service für das Management-UI und einem Datenbank-Container. Die Tools-Oberfläche generiert Provisioning- und Mount-Konfigurationen dynamisch aus JSON-Definitionen und steuert damit, welche Werkzeuge in der Webumgebung verfügbar sind.

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

Die Setup-CLI erzeugt ein neues Vibe4Dock-Projekt ausschließlich aus dem mitgelieferten `skeleton/`. Das Verzeichnis arbeitet jetzt als Baukasten mit Platzhaltern, sodass Ports, Image-Versionen, Container-Namen und Datenbankvarianten erst beim Generieren eingesetzt werden und nicht mehr im Generator hart codiert sind. Alle erzeugten Projektdateien stammen aus diesen Vorlagen. Die Vorlagen liegen dort als `*.skeleton`-Dateien und werden im Zielprojekt ohne die Endung `.skeleton` geschrieben.

### Vibe4Dock Setup CLI einrichten

Für die CLI selbst wird nur PHP CLI ab Version 8 benötigt. Weitere Abhängigkeiten oder Composer-Installationen sind für das Starten des Setup-Tools nicht notwendig.

Letzter stabiler Tag:

```bash
git clone --branch 1.0.0 --depth 1 https://github.com/jbsnewmedia/vibe4dock.git
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

Ohne Optionen läuft das Setup interaktiv und fragt nacheinander Projektname, PHP-Version, Datenbanktyp, Datenbankversion, Ports, Ausgabeverzeichnis und optionales Code-Quality-Setup ab.

### CLI-Hilfe

```bash
./vibe4dock --help
```

### Nicht-interaktiver Aufruf

```bash
./vibe4dock \
  --project-name=my-vibe4dock \
  --php-version=8.3 \
  --db-type=mariadb \
  --mariadb-version=12.2 \
  --web-port=80 \
  --tools-port=8090 \
  --root-shell-port=7681 \
  --app-shell-port=7682 \
  --db-port=3306 \
  --output-dir=./build/my-vibe4dock \
  --code-quality=true \
  --tools=ecs,rector,phpstan,phpunit
```

### Unterstützte Optionen

| Option | Bedeutung |
| --- | --- |
| `--project-name` | Name des zu erzeugenden Projekts |
| `--php-version` | PHP-Basisversion für das Web-Image |
| `--db-type` | Datenbanktyp: `mariadb`, `mysql`, `postgres`, `firebird` |
| `--mariadb-version` | MariaDB-Version |
| `--mysql-version` | MySQL-Version |
| `--postgres-version` | PostgreSQL-Version |
| `--firebird-version` | Firebird-Version |
| `--web-port` | Host-Port für die Web-Anwendung |
| `--tools-port` | Host-Port für die Tools-UI |
| `--root-shell-port` | Host-Port für die Root-Shell |
| `--app-shell-port` | Host-Port für die Application-Shell |
| `--db-port` | Host-Port für die Datenbank |
| `--output-dir` | Zielverzeichnis für das generierte Projekt |
| `--code-quality=true` | aktiviert das zusätzliche Code-Quality-Setup |
| `--tools=...` | wählt Code-Quality-Tools aus: `ecs`, `rector`, `phpstan`, `phpunit` |

### Code-Quality-Setup

Wenn `--code-quality=true` gesetzt ist oder über `--tools` Werkzeuge ausgewählt werden, ergänzt die CLI das generierte Projekt um Composer-Skripte und Basisdateien für:

- ECS / PHP-CS-Fixer
- Rector
- PHPStan
- PHPUnit

## Architektur im Überblick

Vibe4Dock startet standardmäßig drei Docker-Services:

| Service | Zweck | Port(s) |
| --- | --- | --- |
| `web` | Hauptentwicklungsumgebung mit Apache/PHP, ttyd, Shells und den installierten Tools | `81`, `7681`, `7682` |
| `tools` | Verwaltungsoberfläche für Dashboard, Kategorien, Einstellungen, Install/Uninstall-Workflows | `8090` |
| `db` | Konfigurierbare Datenbank für lokale Entwicklungsdaten | datenbankabhängig |

### Service `web`

Der Web-Service basiert auf `webdevops/php-apache-dev:8.3` und erweitert dieses Image um:

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

Der Tools-Service ist ein eigenständiger PHP-Container auf Basis von `php:8.3-cli-bookworm`. Er stellt die Verwaltungsoberfläche bereit und hat Zugriff auf:

- das Projektverzeichnis via Bind-Mount,
- den Docker-Socket,
- die Konfigurationsdateien für Tools und Settings,
- den Zielcontainer `ttyd-web-1`, in dem Befehle ausgeführt werden.

Die Oberfläche ist nicht nur Anzeige, sondern steuert aktiv:

- Installation und Deinstallation von Tools,
- Generierung von `docker/web/provision.sh`,
- Generierung von `docker-compose.override.yml`,
- Aktivierung des Rebuild-Hinweises bei neuen Mounts,
- Statusanzeige für Runtime, Speicher, Datenträger und installierte Tools.

### Service `db`

Der `db`-Service wird durch die Setup-CLI konfiguriert. Je nach Auswahl erzeugt Vibe4Dock hier eine MariaDB-, MySQL-, PostgreSQL- oder Firebird-Instanz inklusive passendem Host-Port.

## Projektstruktur

Die wichtigsten Dateien und Verzeichnisse:

| Pfad | Bedeutung |
| --- | --- |
| `docker-compose.yml` | Hauptdefinition der Services |
| `docker-compose.override.yml` | Wird bei Bedarf automatisch erzeugt, um persistente Tool-Mounts an den Web-Service zu hängen |
| `public/` | Webroot des `web`-Containers |
| `docker/web/` | Dockerfile, Entry-Logik, Provisioning und persistente Tooldaten |
| `docker/tools/` | Verwaltungsoberfläche, Dashboard, Routing, Tool- und Settings-Definitionen |
| `docker/tools/category/` | Tool-Definitionen, nach Dateinamen sortiert und zusammengeführt |
| `docker/tools/settings/` | Einstellungsdefinitionen, ebenfalls mergebar |
| `docker/web/settings/` | Persistente Nutzdaten wie installierte Tools, Caches, Logins und Hint-Dateien |
| `readme/` | Screenshot und Doku-Assets |

## Bedienkonzept

Die Tools-Oberfläche auf Port `8090` ist in mehrere Bereiche gegliedert:

- **Dashboard**
  - Application Shell
  - Root Shell
  - Systeminformationen wie PHP-, Composer-, RAM- und Disk-Status
  - Konfigurationsstatus
  - Liste installierter Tools
- **Kategorien**
  - gruppierte Werkzeuge wie AI CLI, System & Runtime oder PHP & Frameworks
- **Settings**
  - projektweite Einstellungen, aktuell z. B. Git-Benutzername und E-Mail

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

## Aktuell definierte Tool-Kategorien

Die Kategorien werden in `docker/tools/category/*.json` definiert und nach `order` sortiert:

- **AI CLI**
- **System & Runtime**
- **PHP & Frameworks**

## Aktuell hinterlegte Tools

Stand der aktuellen Basiskonfiguration:

### AI CLI

### AI CLI

- Claude Code
- Cline CLI
- Codex CLI **(ungetestet)**
- GitHub Copilot CLI
- Hermes CLI **(ungetestet)**
- Junie CLI **(ungetestet)**

### System & Runtime

- Node.js (multiple versions)
- pnpm (multiple versions)
- Yarn (multiple versions)

### PHP & Frameworks

- Laravel CLI **(ungetestet)**
- Symfony CLI
- WordPress CLI **(ungetestet)**

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

Aktuelle Basisdatei:

```text
docker/tools/category/100_vibe4dock.json
```

### Settings-Definitionen

Settings liegen unter:

```text
docker/tools/settings/
```

Auch diese Dateien werden nach Dateinamen geladen und gemerged.

### Merge-Regeln

Die Merge-Logik steckt in `docker/tools/config.php`:

- Kategorien werden über `unique_key` zusammengeführt,
- Tools werden über `id` zusammengeführt,
- Settings werden über `id` zusammengeführt,
- die Dateien werden alphabetisch bzw. numerisch nach Dateinamen verarbeitet,
- spätere Dateien können bestehende Definitionen gezielt erweitern oder überschreiben.

Beispiel für die Dateibenennung:

```text
100_vibe4dock.json
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

## Einstellungen

Aktuell ist eine Settings-Gruppe enthalten:

- **Git Config**
  - `GIT_USER_NAME`
  - `GIT_USER_EMAIL`
  - Aktivierung über `GIT_CONFIG_ENABLED`

Die Daten werden in `.env.local` geschrieben und beim Start des Web-Containers angewendet.

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
- `docker/web/settings/hermes`
- `docker/web/settings/junie`
- `docker/web/settings/npm`

Außerdem werden dort technische Statusdateien abgelegt:

- `installed_tools.json`
- `rebuild_required.hint`

## Shell-Zugänge

Vibe4Dock stellt zwei direkte Browser-Shells bereit:

| Shell | Zweck | Port |
| --- | --- | --- |
| Root Shell | Administrative Aufgaben im Container | `7681` |
| Application Shell | Normale Entwicklungsarbeit als `application` | `7682` |

Die Application Shell startet über `tmux`, damit Sessions bestehen bleiben können.

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
- Datenbank: `localhost:<db-port>` je nach `--db-type`

## Typischer Workflow

1. Vibe4Dock starten
2. Tools-UI aufrufen
3. benötigte CLIs oder Runtimes installieren
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

- Tools mit Mounts erfordern einen manuellen Rebuild.
- Installationen können je nach Tool Netzwerkzugriff und externe Paketquellen benötigen.
- Manche CLIs speichern Logins erst nach dem ersten interaktiven Start.
- Die Compose-Konfiguration des generierten Projekts hängt vom gewählten `--db-type` ab.

## Kurzfassung

Vibe4Dock ist ein modularer, Docker-basierter Entwicklungs-Workspace mit Weboberfläche für Tool-Management, Browser-Terminals, persistente CLI-Konfigurationen und dynamisch generierte Container-Provisionierung. Der größte Mehrwert ist der browserbasierte Zugriff auf AI-CLI-Tools und Projektumgebungen, sodass Arbeit am Projekt jederzeit und von praktisch überall möglich ist.
