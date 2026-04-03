# Multi-Vendor Marketplace

A Symfony-based multi-vendor marketplace application running in Docker.

---

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) ≥ 24
- [Docker Compose](https://docs.docker.com/compose/install/) V2 (`docker compose`)

---

## Quick Start

### 1. Create your Docker environment file

```bash
cp .env.docker.dist .env.docker
```

Open `.env.docker` and replace every `change_me_*` placeholder with a real value before you start the containers.

> **Tip:** You can put machine-specific overrides in `.env.docker.local` (also git-ignored).  
> Docker Compose merges both files; values in `.env.docker.local` take precedence.

### 2. Start the containers

```bash
docker compose up -d
```

The following services will start:

| Service      | URL / port          | Description             |
|-------------|---------------------|-------------------------|
| `app`        | <http://localhost:8080> | Symfony application  |
| `db`         | port `3306`         | MySQL 8.0               |
| `phpmyadmin` | <http://localhost:8081> | phpMyAdmin UI        |

### 3. Install PHP dependencies (first run)

```bash
docker compose exec app composer install
```

### 4. Run database migrations

```bash
docker compose exec app bin/console doctrine:migrations:migrate --no-interaction
```

---

## Environment files explained

### Docker Compose vs Symfony Dotenv

There are **two separate env-file systems** in this project:

| File | Who reads it | Purpose |
|------|-------------|---------|
| `.env.docker` | **Docker Compose** (`env_file:`) | Injects variables into containers at runtime |
| `.env.docker.local` | **Docker Compose** (`env_file:`, optional) | Per-machine overrides (merged on top of `.env.docker`) |
| `.env.docker.dist` | — (template only) | Committed to git; copy to `.env.docker` to get started |
| `.env` | **Symfony Dotenv** | Symfony default values (not used inside Docker containers) |
| `.env.local` | **Symfony Dotenv** | Local Symfony overrides (git-ignored) |

Inside Docker containers, `APP_ENV`, `DATABASE_URL`, etc. come from **real process environment variables** injected by Compose — Symfony's Dotenv component is not involved.  That is why `bin/console debug:dotenv` may report "Dotenv component is not initialized" inside the container; this is expected and harmless.  Use the following commands to inspect the active configuration instead:

```bash
docker compose exec app bin/console about
docker compose exec app bin/console debug:container --env-vars
```

### Variables in `.env.docker.dist`

| Variable | Used by | Description |
|----------|---------|-------------|
| `MYSQL_ROOT_PASSWORD` | `db` | MySQL root password |
| `MYSQL_DATABASE` | `db` | Database name created on first start |
| `MYSQL_USER` | `db` | Application database user |
| `MYSQL_PASSWORD` | `db` | Application database user password |
| `PMA_HOST` | `phpmyadmin` | MySQL host for phpMyAdmin (should be `db`) |
| `PMA_PORT` | `phpmyadmin` | MySQL port (default `3306`) |
| `APP_ENV` | `app` | Symfony environment (`prod` / `dev`) |
| `APP_DEBUG` | `app` | Symfony debug mode (`0` or `1`) |
| `APP_SECRET` | `app` | Symfony secret (≥ 32 random characters) |
| `DATABASE_URL` | `app` | Doctrine connection string |

---

## Useful commands

```bash
# View logs
docker compose logs -f app

# Open a shell in the app container
docker compose exec app bash

# Clear Symfony cache
docker compose exec app bin/console cache:clear

# Stop all containers
docker compose down

# Stop and remove volumes (⚠ destroys DB data)
docker compose down -v
```
