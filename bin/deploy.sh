#!/usr/bin/env bash
# bin/deploy.sh
# Runs on Hostinger shared hosting after CI uploads the release

set -euo pipefail

# ── Configuration ────────────────────────────────────────────────────────────
APP_DIR="${HOME}/domains/deeppink-goose-466556.hostingersite.com/app"
PUBLIC_HTML="${HOME}/domains/deeppink-goose-466556.hostingersite.com/public_html"
UPLOADS_DIR="${PUBLIC_HTML}/uploads"

echo "==> Starting shared hosting deployment"
echo "==> App dir: ${APP_DIR}"
echo "==> Public HTML: ${PUBLIC_HTML}"

# ── Step 1: Backup current app for rollback ──────────────────────────────────
echo "==> Backing up current release for rollback"
if [ -d "${APP_DIR}/app_previous" ]; then
    rm -rf "${APP_DIR}/app_previous"
fi
if [ -d "${APP_DIR}" ]; then
    cp -a "${APP_DIR}" "${APP_DIR}/app_previous"
fi

# ── Step 2: Move uploaded release into app directory ─────────────────────────
echo "==> Installing new release"
# The CI uploads a tarball to ~/app_incoming
# We extract it over the current app directory
if [ -f "${APP_DIR}/app_incoming.tar.gz" ]; then
    # Preserve uploads and .env during extraction
    tar -xzf "${APP_DIR}/app_incoming.tar.gz" -C "${APP_DIR}" \
        --exclude='./public/uploads' \
        --exclude='./.env'
    rm "${APP_DIR}/app_incoming.tar.gz"
else
    echo "ERROR: No release archive found at ~/app_incoming.tar.gz"
    exit 1
fi

# ── Step 3: Ensure .env is in place ──────────────────────────────────────────
# The .env file lives permanently at ~/app/.env
# It was NOT overwritten by the tar extraction above
echo "==> Verifying .env exists"
if [ ! -f "${APP_DIR}/.env" ]; then
    echo "ERROR: .env file missing from ${APP_DIR}"
    echo "Please create it manually via SSH"
    exit 1
fi

# ── Step 4: Install Composer dependencies ────────────────────────────────────
echo "==> Installing Composer dependencies"
cd "${APP_DIR}"

# Hostinger provides PHP and Composer
# Check the path — it varies by Hostinger plan
COMPOSER_BIN="composer"
if ! command -v composer &>/dev/null; then
    # Hostinger sometimes requires explicit path
    COMPOSER_BIN="${HOME}/bin/composer"
    if [ ! -f "${COMPOSER_BIN}" ]; then
        echo "==> Downloading Composer"
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        php composer-setup.php --install-dir="${HOME}/bin" --filename=composer
        rm composer-setup.php
    fi
fi

${COMPOSER_BIN} install \
    --no-dev \
    --optimize-autoloader \
    --classmap-authoritative \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --quiet

# ── Step 5: Symfony cache warmup ─────────────────────────────────────────────
echo "==> Clearing and warming Symfony cache"
export APP_ENV=prod
export APP_DEBUG=0

php bin/console cache:clear --env=prod --no-debug --no-warmup --quiet
php bin/console cache:warmup --env=prod --no-debug --quiet

# ── Step 6: Run database migrations ──────────────────────────────────────────
echo "==> Running database migrations"
php bin/console doctrine:migrations:migrate \
    --env=prod \
    --no-debug \
    --no-interaction \
    --allow-no-migration \
    --quiet

# ── Step 7: Sync public/ to public_html/ ─────────────────────────────────────
echo "==> Syncing public assets to public_html"

# rsync syncs app/public/ contents into public_html/
# --delete removes files from public_html that no longer exist in public/
# --exclude preserves uploads directory across deploys
rsync -a \
    --delete \
    --exclude='/uploads/' \
    "${APP_DIR}/public/" \
    "${PUBLIC_HTML}/"

# ── Step 8: Verify .htaccess landed correctly ─────────────────────────────────
echo "==> Verifying .htaccess"
if [ ! -f "${PUBLIC_HTML}/.htaccess" ]; then
    echo "ERROR: .htaccess missing from public_html after rsync"
    exit 1
fi

echo "==> Deployment complete"
echo "==> Site: https://deeppink-goose-466556.hostingersite.com"
