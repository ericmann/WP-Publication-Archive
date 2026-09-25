#!/usr/bin/env bash
# Runs the full PHPUnit suite (unit + integration) inside wp-env's tests-cli
# container. Fails, rather than skips, when wp-env is not running.
set -euo pipefail

cd "$(dirname "$0")/.."
# shellcheck source=bin/wp-env.conf
source bin/wp-env.conf

npx wp-env run cli wp core is-installed >/dev/null 2>&1 || {
	echo "wp-env is not running. Start it with: npx wp-env start" >&2
	exit 1
}

DAM="${WPPA_DAM:-$DAM}"

if [ "$DAM" = "1" ]; then
	if [ ! -f .cache/vip-digital-asset-manager/index.php ]; then
		echo "DAM not fetched: run bash bin/fetch-dam.sh (needs SSH access to github.a8c.com)" >&2
		exit 1
	fi

	exec npx wp-env run tests-cli --env-cwd="wp-content/plugins/${PLUGIN_SLUG}" bash -c "WPPA_TEST_DAM=1 vendor/bin/phpunit --exclude-group nodam $*"
else
	exec npx wp-env run tests-cli --env-cwd="wp-content/plugins/${PLUGIN_SLUG}" bash -c "vendor/bin/phpunit --exclude-group dam $*"
fi
