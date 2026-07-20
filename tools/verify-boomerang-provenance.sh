#!/bin/sh
# Verify that the source commit stamped inside the bundled Boomerang build
# matches the commit documented for reviewers in THIRD-PARTY-NOTICES.txt and
# readme.txt, and that the version in the bundle header matches the bundled
# file name. Keeps the guideline-4 source-access statement from drifting when
# the bundle is upgraded.
set -eu

PLUGIN_DIR="plugins/basicrum"
BUNDLE=$(find "$PLUGIN_DIR/assets/js/boomr" -name 'boomerang-*.min.js' | head -n 1)

if [ -z "$BUNDLE" ]; then
	printf '%s\n' 'No bundled Boomerang build found.' >&2
	exit 1
fi

HEADER_LINE=$(grep -m 1 'Boomerang Version:' "$BUNDLE" || true)
HEADER_VERSION=$(printf '%s\n' "$HEADER_LINE" | sed -n 's/.*Boomerang Version: \([0-9.]*\) .*/\1/p')
HEADER_COMMIT=$(printf '%s\n' "$HEADER_LINE" | grep -oE '[0-9a-f]{40}' | head -n 1)

if [ -z "$HEADER_VERSION" ] || [ -z "$HEADER_COMMIT" ]; then
	printf '%s\n' 'Bundled Boomerang header does not carry a version and source commit.' >&2
	exit 1
fi

FILE_VERSION=$(basename "$BUNDLE" | sed -n 's/^boomerang-\([0-9.]*\)\..*/\1/p')

if [ "$HEADER_VERSION" != "$FILE_VERSION" ]; then
	printf 'Bundled Boomerang version mismatch: header %s, file name %s.\n' "$HEADER_VERSION" "$FILE_VERSION" >&2
	exit 1
fi

for DOC in "$PLUGIN_DIR/THIRD-PARTY-NOTICES.txt" "$PLUGIN_DIR/readme.txt"; do
	if ! grep -q "$HEADER_COMMIT" "$DOC"; then
		printf 'Documented Boomerang source commit is stale: %s does not mention %s.\n' "$DOC" "$HEADER_COMMIT" >&2
		exit 1
	fi
done

if [ ! -f "$PLUGIN_DIR/assets/js/boomr/LICENSE.txt" ]; then
	printf '%s\n' 'Boomerang LICENSE.txt is missing next to the bundled build.' >&2
	exit 1
fi

printf 'Boomerang provenance check passed: %s %s.\n' "$HEADER_VERSION" "$HEADER_COMMIT"
