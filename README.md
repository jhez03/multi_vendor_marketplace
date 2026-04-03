# Multi-Vendor Marketplace

A Symfony-based multi-vendor marketplace application running in Docker.

---

## Table of Contents

- [Quick Start](#quick-start)
- [Environment Files Explained](#environment-files-explained)
  - [Symfony Dotenv files](#symfony-dotenv-files)
  - [Docker Compose env files](#docker-compose-env-files)
  - [Recommended workflow](#recommended-workflow)
- [Running with Docker Compose](#running-with-docker-compose)
- [Useful Commands](#useful-commands)

---

## Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/jhez03/multi_vendor_marketplace.git
cd multi_vendor_marketplace

# 2. Create your Docker env file from the example template
cp .env.docker.example .env.docker
# Edit .env.docker and fill in your values (passwords, secrets, etc.)

# 3. Build and start all services
docker compose up -d --build

# 4. Install PHP dependencies
docker compose exec app composer install

# 5. Run database migrations
docker compose exec app bin/console doctrine:migrations:migrate --no-interaction
```

The application will be available at:
- **App**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081

---

## Environment Files Explained

### Symfony Dotenv files

Symfony uses its own [Dotenv component](https://symfony.com/doc/current/components/dotenv.html) to load `.env*` files **at PHP runtime** (inside the container). These files are read by Symfony itself — not by Docker Compose.

| File | Purpose | Committed? |
|---|---|---|
| `.env` | Symfony defaults / safe public values | **No\*** (ignored by git in this repo) |
| `.env.local` | Local overrides for a single developer | **No** |
| `.env.test` | Overrides for the `test` environment | **No** |
| `.env.local.php` | Compiled cache produced by `composer dump-env` | **No** |

> \* **Note on Symfony convention:** The standard Symfony convention is to commit `.env` with
> safe, non-secret default values and only git-ignore `.env.local` / `.env.*.local`.
> This repository deviates from that convention and git-ignores `.env` as well.
> When running inside Docker, Symfony env vars are typically injected by Docker Compose
> as real OS environment variables (see below). In this case, Symfony Dotenv is not invoked and
> `bin/console debug:dotenv` will report "Dotenv component is not initialized" — this is expected
> and does **not** affect application functionality. Use `bin/console about` or
> `bin/console debug:container --env-vars` to inspect runtime variables instead.

### Docker Compose env files

Docker Compose reads env files to set **OS-level environment variables** inside each container.
This happens before PHP (or any process) starts — these are not parsed by Symfony Dotenv.

| File | Purpose | Committed? |
|---|---|---|
| `.env.docker.example` | Template — safe to commit, no secrets | **Yes** |
| `.env.docker` | Your actual Docker env values | **No** (ignored by git) |
| `.env.docker.local` | Per-machine overrides on top of `.env.docker` | **No** (ignored by git) |

Docker Compose merges the two files in order: variables in `.env.docker.local` override
those in `.env.docker`. The `.env.docker.local` file is **optional** — Docker Compose will
silently skip it if it does not exist.

### Recommended workflow

```
.env.docker.example   ← committed template (no real secrets)
        │
        ▼  cp .env.docker.example .env.docker
.env.docker           ← your runtime values (git-ignored)
        │
        ▼  cp .env.docker .env.docker.local  (only if you need local tweaks)
.env.docker.local     ← personal overrides (git-ignored, optional)
```

Inside the container, Symfony picks up these OS-level variables automatically.
You do **not** need a `.env` file inside the container when using Docker Compose
`env_file` directives.

---

## Running with Docker Compose

### Start all services

```bash
docker compose up -d
```

### Rebuild after Dockerfile changes

```bash
docker compose up -d --build
```

### Stop all services

```bash
docker compose down
```

### Tear down and remove volumes (⚠ deletes database data)

```bash
docker compose down -v
```

---

## Useful Commands

```bash
# Open a shell in the app container
docker compose exec app bash

# Run Symfony console commands
docker compose exec app bin/console <command>

# Check active environment variables inside the container
docker compose exec app printenv | grep -E '^(APP_ENV|APP_DEBUG|DATABASE_URL)='

# Inspect Symfony container env vars (works even without Dotenv)
docker compose exec app bin/console debug:container --env-vars

# Run PHPUnit tests
docker compose exec app ./vendor/bin/phpunit

# Install/update Composer dependencies
docker compose exec app composer install
docker compose exec app composer update
```
