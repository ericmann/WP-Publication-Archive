#!/usr/bin/env bash
#
# Implements SPEC.md §7: builds dist/wp-publication-archive.zip containing
# only the runtime files a WordPress install needs.
#
# @author Eric Mann <eric@eamann.com>

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

STAGE_DIR="dist/build/wp-publication-archive"

rm -rf dist
mkdir -p "$STAGE_DIR"

rsync -a --exclude-from=.distignore ./ "$STAGE_DIR/"

# .distignore excludes composer.json/composer.lock's dev config sibling
# entries but not composer.json/composer.lock themselves; copy them in
# explicitly in case that ever changes, per the task's own instruction.
cp composer.json "$STAGE_DIR/composer.json"
cp composer.lock "$STAGE_DIR/composer.lock"

(
	cd "$STAGE_DIR"
	composer install --no-dev -o --no-interaction
	rm -f composer.json composer.lock
)

(
	cd dist/build
	zip -rq ../wp-publication-archive.zip wp-publication-archive
)

# $4 is the path column; "/vendor/$" (trailing slash, nothing after) is the
# vendor/ directory entry itself, always present and always fine.
BAD_ENTRIES=$(unzip -l dist/wp-publication-archive.zip | awk '{print $4}' | grep -E '(^|/)(tests|bin|\.cache)/' || true)
BAD_ENTRIES="$BAD_ENTRIES$(unzip -l dist/wp-publication-archive.zip | awk '{print $4}' | grep -E '/vendor/.+' | grep -v -E '/vendor/(autoload\.php$|composer/)' || true)"

if [ -n "$BAD_ENTRIES" ]; then
	echo "build-zip.sh: dist/wp-publication-archive.zip contains files it should not:" >&2
	echo "$BAD_ENTRIES" >&2
	exit 1
fi

echo "Built dist/wp-publication-archive.zip"
