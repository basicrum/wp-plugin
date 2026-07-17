#!/usr/bin/env sh

set -eu

if [ "$#" -ne 1 ]; then
	printf '%s\n' "Usage: $0 /path/to/basicrum.zip" >&2
	exit 1
fi

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
REPOSITORY_ROOT=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
COMPOSE_FILE="$REPOSITORY_ROOT/docker/release-smoke.yml"
ARCHIVE_INPUT=$1

if [ ! -f "$ARCHIVE_INPUT" ]; then
	printf '%s\n' "Release archive does not exist: $ARCHIVE_INPUT" >&2
	exit 1
fi

for required_command in curl docker; do
	if ! command -v "$required_command" >/dev/null 2>&1; then
		printf '%s\n' "Required command is not installed: $required_command" >&2
		exit 1
	fi
done

ARCHIVE_DIR=$(CDPATH= cd -- "$(dirname -- "$ARCHIVE_INPUT")" && pwd)
export BASICRUM_RELEASE_ZIP="$ARCHIVE_DIR/$(basename -- "$ARCHIVE_INPUT")"
COMPOSE_PROJECT_NAME="basicrum_release_smoke_$$"
export COMPOSE_PROJECT_NAME
TEMP_DIR=$(mktemp -d "${TMPDIR:-/tmp}/basicrum-smoke.XXXXXX")
FRONTEND_RESPONSE="$TEMP_DIR/frontend.html"
ADMIN_RESPONSE="$TEMP_DIR/admin.html"
COOKIE_JAR="$TEMP_DIR/cookies.txt"

compose() {
	docker compose -f "$COMPOSE_FILE" "$@"
}

cleanup() {
	compose down --volumes --remove-orphans >/dev/null 2>&1 || true
	rm -rf "$TEMP_DIR"
}

trap cleanup EXIT HUP INT TERM

assert_clean_response() {
	if grep -Eiq '(Fatal error|Uncaught [A-Za-z]+Error|Warning: .+ on line)' "$1"; then
		printf '%s\n' "PHP warning or fatal error found in $2 response." >&2
		exit 1
	fi
}

sh "$SCRIPT_DIR/verify-release.sh" "$BASICRUM_RELEASE_ZIP"
compose up -d db wordpress

ATTEMPT=0
while ! compose exec -T wordpress test -f /var/www/html/wp-includes/version.php; do
	ATTEMPT=$((ATTEMPT + 1))
	if [ "$ATTEMPT" -ge 30 ]; then
		printf '%s\n' 'WordPress files were not initialized in time.' >&2
		exit 1
	fi
	sleep 2
done

PUBLISHED_ADDRESS=$(compose port wordpress 80)
PUBLISHED_PORT=${PUBLISHED_ADDRESS##*:}
SITE_URL="http://127.0.0.1:$PUBLISHED_PORT"

compose run --rm wpcli wp core install \
	--url="$SITE_URL" \
	--title='Basicrum Release Smoke Test' \
	--admin_user=admin \
	--admin_password=basicrum-smoke-password \
	--admin_email=admin@example.test \
	--skip-email

compose run --rm wpcli wp plugin install /artifacts/basicrum.zip --activate
compose run --rm wpcli wp option update basicrum_settings '{"enabled":"1","development_mode":"0","beacon_url":"https://example.test/beacon","brum_site_id":"550e8400-e29b-41d4-a716-446655440000","track_admins":"0","consent_enabled":"0","consent_mode":"explicit","wait_after_onload":"0","delay_ms":0,"script_position":"footer","use_unminified_loaders":"0"}' --format=json

curl -fsS "$SITE_URL/" -o "$FRONTEND_RESPONSE"
assert_clean_response "$FRONTEND_RESPONSE" frontend

if ! grep -Fq 'basicrum-loader-js' "$FRONTEND_RESPONSE"; then
	printf '%s\n' 'The enabled plugin did not render the Basicrum loader on the frontend.' >&2
	exit 1
fi

curl -fsS -c "$COOKIE_JAR" "$SITE_URL/wp-login.php" >/dev/null
curl -fsS -L \
	-b "$COOKIE_JAR" \
	-c "$COOKIE_JAR" \
	--data-urlencode 'log=admin' \
	--data-urlencode 'pwd=basicrum-smoke-password' \
	--data-urlencode "redirect_to=$SITE_URL/wp-admin/admin.php?page=basicrum" \
	--data-urlencode 'testcookie=1' \
	"$SITE_URL/wp-login.php" >/dev/null
curl -fsS -b "$COOKIE_JAR" "$SITE_URL/wp-admin/admin.php?page=basicrum" -o "$ADMIN_RESPONSE"
assert_clean_response "$ADMIN_RESPONSE" admin

if ! grep -Fq 'Basicrum Settings' "$ADMIN_RESPONSE"; then
	printf '%s\n' 'The Basicrum administration page did not render after activation.' >&2
	exit 1
fi

if compose logs wordpress | grep -Eiq '(PHP Fatal error|Uncaught [A-Za-z]+Error|PHP Warning:)'; then
	printf '%s\n' 'The WordPress container logged a PHP warning or fatal error.' >&2
	exit 1
fi

printf '%s\n' "Release smoke test passed: $BASICRUM_RELEASE_ZIP"
