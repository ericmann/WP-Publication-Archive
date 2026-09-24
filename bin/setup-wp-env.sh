#!/usr/bin/env bash
# Runs after `npx wp-env start` (see .wp-env.json lifecycleScripts). Idempotent.
set -euo pipefail

cd "$(dirname "$0")/.."
# shellcheck source=bin/wp-env.conf
source bin/wp-env.conf

for ENV in cli tests-cli; do
	npx wp-env run "$ENV" wp plugin activate "$PLUGIN_SLUG" || true
	npx wp-env run "$ENV" wp rewrite structure '/%postname%/' --hard || true

	if [ -d .cache/vip-digital-asset-manager ]; then
		npx wp-env run "$ENV" wp plugin activate vip-digital-asset-manager || true
	fi

	echo "ENV=$ENV plugin=${PLUGIN_SLUG} ready"
done
