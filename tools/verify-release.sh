#!/usr/bin/env sh

set -eu

if [ "$#" -ne 1 ]; then
	printf '%s\n' "Usage: $0 /path/to/basicrum.zip" >&2
	exit 1
fi

ARCHIVE_INPUT=$1

if [ ! -f "$ARCHIVE_INPUT" ]; then
	printf '%s\n' "Release archive does not exist: $ARCHIVE_INPUT" >&2
	exit 1
fi

if ! command -v unzip >/dev/null 2>&1; then
	printf '%s\n' 'Required command is not installed: unzip' >&2
	exit 1
fi

ARCHIVE_DIR=$(CDPATH= cd -- "$(dirname -- "$ARCHIVE_INPUT")" && pwd)
ARCHIVE_NAME=$(basename -- "$ARCHIVE_INPUT")
ARCHIVE_PATH="$ARCHIVE_DIR/$ARCHIVE_NAME"
ARCHIVE_ENTRIES=$(unzip -Z1 "$ARCHIVE_PATH")

unzip -tq "$ARCHIVE_PATH" >/dev/null

require_entry() {
	if ! printf '%s\n' "$ARCHIVE_ENTRIES" | grep -Fqx "$1"; then
		printf '%s\n' "Required release file is missing: $1" >&2
		exit 1
	fi
}

require_entry 'basicrum/basicrum.php'
require_entry 'basicrum/uninstall.php'
require_entry 'basicrum/readme.txt'
require_entry 'basicrum/LICENSE.md'
require_entry 'basicrum/THIRD-PARTY-NOTICES.txt'
require_entry 'basicrum/composer.json'
require_entry 'basicrum/src/Plugin.php'
require_entry 'basicrum/src/Assets.php'
require_entry 'basicrum/src/ConsentIntegration.php'
require_entry 'basicrum/vendor/autoload.php'
require_entry 'basicrum/vendor/composer/autoload_classmap.php'
require_entry 'basicrum/vendor/composer/installed.php'
require_entry 'basicrum/assets/js/boomr/boomerang-1.815.60.cutting-edge.min.js'
require_entry 'basicrum/assets/js/boomr/LICENSE.txt'
require_entry 'basicrum/assets/js/loaders/boomerang-loader-v15.min.js'
require_entry 'basicrum/assets/js/loaders/consent-boomerang-loader-v1-15.min.js'
require_entry 'basicrum/assets/js/integrations/borlabs-cookie-v3.js'
require_entry 'basicrum/assets/js/integrations/wp-consent-api.js'
require_entry 'basicrum/assets/js/integrations/cookieyes.js'
require_entry 'basicrum/assets/js/integrations/generic-opt-in.js'
require_entry 'basicrum/assets/js/integrations/generic-opt-out.js'
require_entry 'basicrum/assets/images/basicrum-logo.png'
require_entry 'basicrum/assets/images/basicrum-menu-icon.svg'
require_entry 'basicrum/languages/basicrum.pot'

if printf '%s\n' "$ARCHIVE_ENTRIES" | grep -Fqx 'basicrum/assets/js/loaders/consent-api.js'; then
	printf '%s\n' 'Release archive contains the retired consent API bridge.' >&2
	exit 1
fi

if printf '%s\n' "$ARCHIVE_ENTRIES" | grep -Ev '^basicrum(/|$)' | grep -q .; then
	printf '%s\n' 'Release archive contains files outside the basicrum directory.' >&2
	exit 1
fi

if printf '%s\n' "$ARCHIVE_ENTRIES" | grep -Eq '^basicrum/(tests|\.git|\.github|release)(/|$)'; then
	printf '%s\n' 'Release archive contains a development directory.' >&2
	exit 1
fi

if printf '%s\n' "$ARCHIVE_ENTRIES" | grep -Eq '^basicrum/languages/.*\.(po|mo)$'; then
	printf '%s\n' 'Release archive contains a bundled locale-specific translation.' >&2
	exit 1
fi

if printf '%s\n' "$ARCHIVE_ENTRIES" | grep -Eq '^basicrum/(AGENTS\.md|CLAUDE\.md|CONTRIBUTING\.md|SECURITY\.md|checklist\.md|docs(/|$))'; then
	printf '%s\n' 'Release archive contains repository-only documentation.' >&2
	exit 1
fi

if printf '%s\n' "$ARCHIVE_ENTRIES" | grep -Eq '^basicrum/vendor/(bin|antecedent|brain|dealerdirect|doctrine|hamcrest|mockery|myclabs|nikic|phar-io|php-parallel-lint|phpcompatibility|phpcsstandards|phpunit|sebastian|squizlabs|theseer|wp-coding-standards|yoast)(/|$)'; then
	printf '%s\n' 'Release archive contains a development dependency.' >&2
	exit 1
fi

if printf '%s\n' "$ARCHIVE_ENTRIES" | grep -Eq '^basicrum/vendor/.*/\.github(/|$)'; then
	printf '%s\n' 'Release archive contains dependency repository metadata.' >&2
	exit 1
fi

if ! unzip -p "$ARCHIVE_PATH" basicrum/vendor/composer/autoload_classmap.php | grep -Fq "'Basicrum\\\\WP\\\\Plugin'"; then
	printf '%s\n' 'Composer class map does not contain the Basicrum plugin classes.' >&2
	exit 1
fi

if ! unzip -p "$ARCHIVE_PATH" basicrum/vendor/composer/autoload_classmap.php | grep -Fq "'Basicrum\\\\WP\\\\ConsentIntegration'"; then
	printf '%s\n' 'Composer class map does not contain the consent integration service.' >&2
	exit 1
fi

if ! unzip -p "$ARCHIVE_PATH" basicrum/assets/js/boomr/LICENSE.txt | grep -Fq 'Copyright (c) 2017-2023, Akamai Technologies, Inc.'; then
	printf '%s\n' 'Bundled Boomerang license text is incomplete.' >&2
	exit 1
fi

if ! unzip -p "$ARCHIVE_PATH" basicrum/THIRD-PARTY-NOTICES.txt | grep -Fq 'Boomerang 1.815.60'; then
	printf '%s\n' 'Bundled software notice is incomplete.' >&2
	exit 1
fi

if printf '%s\n' "$ARCHIVE_ENTRIES" | grep -Eq '^basicrum/(\.distignore|composer\.lock|package(-lock)?\.json|playwright[^/]*\.config\.(js|cjs|mjs|ts)|patchwork\.json|phpcs\.ruleset\.xml|phpunit[^/]*\.xml|phpstan\.neon\.dist|README\.md|coverage\.xml|\.phpunit\.result\.cache)$'; then
	printf '%s\n' 'Release archive contains a development file.' >&2
	exit 1
fi

CHECKSUM_PATH="$ARCHIVE_PATH.sha256"

if [ -f "$CHECKSUM_PATH" ]; then
	if command -v sha256sum >/dev/null 2>&1; then
		(
			cd "$ARCHIVE_DIR"
			sha256sum -c "$(basename -- "$CHECKSUM_PATH")" >/dev/null
		)
	elif command -v shasum >/dev/null 2>&1; then
		EXPECTED_CHECKSUM=$(awk 'NR == 1 { print $1 }' "$CHECKSUM_PATH")
		ACTUAL_CHECKSUM=$(shasum -a 256 "$ARCHIVE_PATH" | awk '{ print $1 }')

		if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
			printf '%s\n' 'Release checksum verification failed.' >&2
			exit 1
		fi
	else
		printf '%s\n' 'Neither sha256sum nor shasum is installed.' >&2
		exit 1
	fi
fi

printf '%s\n' "Verified release archive: $ARCHIVE_PATH"
