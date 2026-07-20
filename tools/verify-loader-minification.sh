#!/bin/sh
# Verify that the shipped minified loaders are exactly the mangle-only
# derivation of their readable sources, using the uglify-js version pinned in
# package-lock.json. Mangle-only renames local identifiers but never window
# properties, so the public OPT_IN_BASICRUM_LOADER_WRAPPER and
# OPT_OUT_BASICRUM_LOADER_WRAPPER callbacks always survive verbatim, and the
# minified twins cannot drift from the audited readable files.
set -eu

LOADERS_DIR="plugins/basicrum/assets/js/loaders"
UGLIFY="node_modules/.bin/uglifyjs"

if [ ! -x "$UGLIFY" ]; then
	printf '%s\n' 'uglify-js is not installed; run npm ci first.' >&2
	exit 1
fi

TMP_DIR=$(mktemp -d)
trap 'rm -rf "$TMP_DIR"' EXIT

"$UGLIFY" "$LOADERS_DIR/boomerang-loader-v15.js" \
	--mangle -o "$TMP_DIR/standard.min.js"
"$UGLIFY" "$LOADERS_DIR/consent-boomerang-loader-v1-15.js" \
	--mangle --comments '/^!/' -o "$TMP_DIR/consent.min.js"

for pair in \
	"$TMP_DIR/standard.min.js:$LOADERS_DIR/boomerang-loader-v15.min.js" \
	"$TMP_DIR/consent.min.js:$LOADERS_DIR/consent-boomerang-loader-v1-15.min.js"; do
	expected=${pair%%:*}
	shipped=${pair#*:}

	if ! cmp -s "$expected" "$shipped"; then
		printf '%s is not the mangle-only derivation of its readable source. Run: npm run minify:loaders\n' "$shipped" >&2
		exit 1
	fi
done

for callback in OPT_IN_BASICRUM_LOADER_WRAPPER OPT_OUT_BASICRUM_LOADER_WRAPPER; do
	if ! grep -q "$callback" "$LOADERS_DIR/consent-boomerang-loader-v1-15.min.js"; then
		printf 'Public callback %s is missing from the minified consent loader.\n' "$callback" >&2
		exit 1
	fi
done

printf '%s\n' 'Loader minification check passed.'
