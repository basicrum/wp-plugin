#!/bin/sh

set -eu

fail() {
	printf '%s\n' "$1" >&2
	exit 1
}

require_file() {
	if [ ! -f "$1" ]; then
		fail "Required version metadata file is missing: $1"
	fi
}

require_single_value() {
	label=$1
	value=$2

	if [ -z "$value" ]; then
		fail "Could not find $label."
	fi

	line_count=$( printf '%s\n' "$value" | awk 'END { print NR }' )

	if [ "$line_count" -ne 1 ]; then
		fail "Expected exactly one $label, found $line_count."
	fi

	printf '%s\n' "$value"
}

repository_root=$( git rev-parse --show-toplevel 2>/dev/null ) || {
	fail 'Version consistency check must run inside a Git worktree.'
}

cd "$repository_root"

plugin_file='plugins/basicrum/basicrum.php'
readme_file='plugins/basicrum/readme.txt'

require_file "$plugin_file"
require_file "$readme_file"

plugin_header_version=$( require_single_value 'plugin header Version value' "$(
	sed -n 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*\([^[:space:]]*\)[[:space:]]*$/\1/p' "$plugin_file"
)" )

constant_version=$( require_single_value 'BASICRUM_VERSION value' "$(
	sed -n "s/^[[:space:]]*define( 'BASICRUM_VERSION', '\([^']*\)' );[[:space:]]*$/\1/p" "$plugin_file"
)" )

stable_tag=$( require_single_value 'readme Stable tag value' "$(
	sed -n 's/^Stable tag:[[:space:]]*\([^[:space:]]*\)[[:space:]]*$/\1/p' "$readme_file"
)" )

changelog_version=$( require_single_value 'top changelog version' "$(
	awk '
		$0 == "== Changelog ==" {
			in_changelog = 1
			next
		}

		in_changelog && /^= .+ =$/ {
			version = $0
			sub(/^= /, "", version)
			sub(/ =$/, "", version)
			print version
			exit
		}
	' "$readme_file"
)" )

if [ "$constant_version" != "$plugin_header_version" ]; then
	fail "BASICRUM_VERSION ($constant_version) does not match plugin header Version ($plugin_header_version)."
fi

if [ "$stable_tag" != "$plugin_header_version" ]; then
	fail "Stable tag ($stable_tag) does not match plugin header Version ($plugin_header_version)."
fi

if [ "$changelog_version" != "$plugin_header_version" ]; then
	fail "Top changelog version ($changelog_version) does not match plugin header Version ($plugin_header_version)."
fi

if ! printf '%s\n' "$plugin_header_version" | grep -Eq '^[0-9]+(\.[0-9]+){2}([.-][0-9A-Za-z]+)*$'; then
	fail "Plugin header Version ($plugin_header_version) must use an X.Y.Z version format."
fi

release_tag=${BASICRUM_RELEASE_TAG:-}

if [ "$#" -gt 1 ]; then
	fail 'Usage: tools/verify-version-consistency.sh [release-tag]'
fi

if [ "$#" -eq 1 ]; then
	release_tag=$1
fi

if [ -n "$release_tag" ]; then
	if [ "$release_tag" != "v$plugin_header_version" ]; then
		fail "Release tag ($release_tag) does not match plugin header Version ($plugin_header_version). Use v$plugin_header_version."
	fi
fi

printf '%s\n' "Version consistency check passed: $plugin_header_version"
