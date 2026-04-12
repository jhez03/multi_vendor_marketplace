#!/usr/bin/env bash
# bin/rollback.sh — Emergency rollback to previous release
# Run manually: ssh server "bash ~/rollback.sh"

set -euo pipefail

DEPLOY_BASE="${HOME}"
RELEASES_DIR="${DEPLOY_BASE}/releases"
CURRENT_LINK="${DEPLOY_BASE}/domains/deeppink-goose-466556.hostingersite.com/public_html"

# Get the previous release
CURRENT=$(readlink -f "${CURRENT_LINK}")
PREVIOUS=$(ls -1dt "${RELEASES_DIR}"/* | grep -v "^${CURRENT}$" | head -1)

if [ -z "${PREVIOUS}" ]; then
    echo "ERROR: No previous release found to roll back to"
    exit 1
fi

echo "==> Rolling back from ${CURRENT} to ${PREVIOUS}"
ln -sfn "${PREVIOUS}" "${CURRENT_LINK}"
echo "==> Rollback complete. Current release: $(readlink "${CURRENT_LINK}")"
