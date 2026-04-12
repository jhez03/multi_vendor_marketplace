#!/usr/bin/env bash
# bin/deploy.sh — Runs on the server during deployment
# Called by GitHub Actions after uploading a release

set -euo pipefail

# ── Configuration ────────────────────────────────────────────────────────────
DEPLOY_BASE="${HOME}"
RELEASES_DIR="${DEPLOY_BASE}/releases"
SHARED_DIR="${DEPLOY_BASE}/shared"
CURRENT_LINK="${DEPLOY_BASE}/domains/deeppink-goose-466556.hostingersite.com/public_html"
KEEP_RELEASES=5

# The release directory is passed as an argument from CI
RELEASE_DIR="${RELEASES_DIR}/${1}"

echo "==> Starting deployment to ${RELEASE_DIR}"

# ── Step 1: Link shared directories ─────────────────────────────────────────
echo "==> Linking shared directories"

# Remove the placeholder directories created during rsync
rm -rf "${RELEASE_DIR}/var"
rm -rf "${RELEASE_DIR}/public/uploads"

# Create symlinks to shared persistent data
ln -sfn "${SHARED_DIR}/var" "${RELEASE_DIR}/var"
ln -sfn "${SHARED_DIR}/public/uploads" "${RELEASE_DIR}/public/uploads"
ln -sfn "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"
ln -sfn "${SHARED_DIR}/public/.htaccess" "${RELEASE_DIR}/public/.htaccess"

# ── Step 2: Install Composer dependencies ────────────────────────────────────
echo "==> Installing Composer dependencies (prod, no-dev)"
cd "${RELEASE_DIR}"

# Hostinger provides Composer — check where it is
COMPOSER_BIN=$(which composer || echo "${HOME}/composer.phar")

${COMPOSER_BIN} install \
    --no-dev \
    --optimize-autoloader \
    --classmap-authoritative \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --quiet

# ── Step 3: Symfony warmup ────────────────────────────────────────────────────
echo "==> Warming up Symfony cache"

# Ensure APP_ENV=prod is exported before running console commands
export APP_ENV=prod
export APP_DEBUG=0

php bin/console cache:clear --env=prod --no-debug --no-warmup
php bin/console cache:warmup --env=prod --no-debug

# ── Step 4: Run database migrations ──────────────────────────────────────────
echo "==> Running database migrations"
php bin/console doctrine:migrations:migrate --env=prod --no-debug --no-interaction --allow-no-migration

# ── Step 5: Build Tailwind CSS ────────────────────────────────────────────────
# We build assets in CI and upload them, so this is just a verification step
echo "==> Verifying asset manifest"
if [ ! -f "${RELEASE_DIR}/public/assets/manifest.json" ]; then
    echo "ERROR: Asset manifest not found. Did the build step run?"
    exit 1
fi

# ── Step 6: Set correct file permissions ─────────────────────────────────────
echo "==> Setting file permissions"
find "${RELEASE_DIR}" -type f -exec chmod 644 {} \;
find "${RELEASE_DIR}" -type d -exec chmod 755 {} \;
chmod 755 "${RELEASE_DIR}/bin/console"

# ── Step 7: Atomic switch ─────────────────────────────────────────────────────
echo "==> Switching to new release (atomic)"
# ln -sfn is atomic on Linux — this is the zero-downtime moment
ln -sfn "${RELEASE_DIR}/public" "${CURRENT_LINK}"

echo "==> Release ${1} is now live"

# ── Step 8: Clean up old releases ────────────────────────────────────────────
echo "==> Cleaning up old releases (keeping ${KEEP_RELEASES})"
ls -1dt "${RELEASES_DIR}"/* | tail -n +$((KEEP_RELEASES + 1)) | xargs rm -rf

echo "==> Deployment complete"
