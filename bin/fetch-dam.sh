#!/usr/bin/env bash
# Clones or updates the VIP DAM at DAM_REF into .cache/vip-digital-asset-manager
# and maps it into .wp-env.json via .wp-env.override.json. Needs SSH access to
# github.a8c.com. Idempotent.
set -euo pipefail

cd "$(dirname "$0")/.."
# shellcheck source=bin/wp-env.conf
source bin/wp-env.conf

DAM_DIR=".cache/vip-digital-asset-manager"

mkdir -p .cache

if [ -d "$DAM_DIR/.git" ]; then
	git -C "$DAM_DIR" fetch --quiet origin
else
	rm -rf "$DAM_DIR"
	git clone --quiet "$DAM_REPO" "$DAM_DIR"
fi

git -C "$DAM_DIR" checkout --quiet --detach "$DAM_REF"

php -r '
	$override_path = ".wp-env.override.json";
	$override = file_exists( $override_path ) ? json_decode( file_get_contents( $override_path ), true ) : array();
	if ( ! is_array( $override ) ) {
		$override = array();
	}
	if ( ! isset( $override["mappings"] ) || ! is_array( $override["mappings"] ) ) {
		$override["mappings"] = array();
	}
	$override["mappings"]["wp-content/plugins/vip-digital-asset-manager"] = "./.cache/vip-digital-asset-manager";
	file_put_contents( $override_path, json_encode( $override, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
'

VERSION=$(grep -m1 '^ \* Version:' "$DAM_DIR/index.php" | sed 's/.*Version:[[:space:]]*//')

echo "DAM pinned at ${DAM_REF} (Version: ${VERSION}) in ${DAM_DIR}"
