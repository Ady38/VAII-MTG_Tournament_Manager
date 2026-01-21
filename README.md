# Semestralka — Installation Guide

This document explains how to install and run this MTG Tournament Manager application locally. The recommended way is to use the included Docker setup (fast, reproducible). A non-Docker (native PHP + MariaDB) alternative is also provided.

## Prerequisites
- Docker and Docker Compose (Docker Desktop on Windows recommended)
- git
- A terminal (cmd.exe or PowerShell on Windows)

## Quick start — Docker (recommended)

## 1) Edit the existing environment file

- The repository includes `docker/.env`. Edit that file to set the database credentials for your environment. If `docker/.env` is missing, create it with the example content shown below.

Example content (for local development only — change passwords):

```text
MARIADB_ROOT_PASSWORD=rootpass
MARIADB_DATABASE=semestralka
MARIADB_USER=semestralka_user
MARIADB_PASSWORD=pass123
```

## 2a) Start the services — CLI

- From the project root run (Windows example using cmd.exe):

```bat
cd docker
docker-compose up -d
```

- What this does:
  - `web` — Apache + PHP (project mounted into container, serves `public/`).
  - `db` — MariaDB, initialization runs any .sql files from `docker/sql` (creates schema and seeds dummy data on first run).
  - `adminer` — Adminer DB UI on port 8080.

## 2b) Start the services — Docker Desktop (GUI)

If you prefer a graphical workflow, deploy the included Compose file using Docker Desktop:

- Make sure Docker Desktop is installed and running.
- Open Docker Desktop and open the Compose/Apps/Stacks section (label varies by version).
- Use the deploy/import action and select `docker/docker-compose.yml` from the project.
- Docker Desktop should pick up `docker/.env` automatically; if prompted, ensure the environment values match.
- Click **Deploy** (or **Start**) to run the stack. Verify containers are running, and view logs/ports from the UI.

> Note: If your Docker Desktop version lacks a Compose deploy UI, use the CLI method above.

## 3) Verify the app

- Web app: http://localhost:2080/
- Adminer (DB UI): http://localhost:8080/
  - Server (from a container): `db`
  - Server (from host): `127.0.0.1:3306`
  - Use the credentials defined in `docker/.env`.

## Notes about database initialization
- The `docker/` folder contains SQL files (`docker/sql/ddl.tables.sql` and `docker/sql/insert_dummies.sql`). MariaDB runs these on first initialization when the container's data directory is empty.
- To re-run initialization: stop containers and remove the DB data volume, then start the stack again (see Stop & cleanup below).

## Stop & cleanup

### Stop containers

From `docker/`:

```bat
docker-compose down
```

### Remove containers and volumes (data loss — will force DB re-init)

```bat
docker-compose down -v
```

## Troubleshooting — Docker

- View DB logs:

```bat
docker-compose logs db
```

- Common issue — port collisions: default host mappings are:
  - web: `127.0.0.1:2080` -> container `:80`
  - db: `127.0.0.1:3306` -> container `:3306`
  - adminer: `127.0.0.1:8080` -> container `:8080`

  If ports conflict, stop the other service or edit `docker/docker-compose.yml` to change host ports.

## Native (non-Docker) setup — PHP + MariaDB
Use this option only if Docker is unavailable.

### 1) Requirements
- PHP 8.1+ with extensions: `pdo`, `pdo_mysql`
- Apache or Nginx configured to serve the `public/` directory
- MariaDB / MySQL server

### 2) Create database and import schema

Example (run in a MySQL/MariaDB client):

```sql
CREATE DATABASE semestralka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'semestralka_user'@'localhost' IDENTIFIED BY 'pass123';
GRANT ALL PRIVILEGES ON semestralka.* TO 'semestralka_user'@'localhost';
FLUSH PRIVILEGES;
```

Then import SQL fixtures from the project root:

```bat
mysql -u semestralka_user -p semestralka < docker/sql/ddl.tables.sql
mysql -u semestralka_user -p semestralka < docker/sql/insert_dummies.sql
```

### 3) Configure application DB credentials
- Edit `App/Configuration.php` and set DB host, username, password and database name to match your environment. The app uses PDO.

### 4) Serve the app
- Configure your webserver to use the `public/` directory as the document root and ensure PHP is enabled.

## Permissions & uploads
- Uploaded files are stored in `public/uploads/`. Ensure the webserver user (or container) has write access to this directory if you plan to upload decklists.

## Security notes
- Example credentials in this guide are for local development only. Use strong, unique passwords in production and avoid exposing Adminer or DB ports publicly.

## Developer tips
- Xdebug is enabled in the provided web image; configure your IDE to listen on port `9003` for debugging.
- If you add more SQL fixtures, drop the DB volume and restart the DB container so initialization re-runs.
