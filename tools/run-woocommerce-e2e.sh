#!/bin/sh

set -eu

SCRIPT_DIR=$( CDPATH= cd -- "$(dirname -- "$0")" && pwd )
REPOSITORY_ROOT=$( CDPATH= cd -- "$SCRIPT_DIR/.." && pwd )
COMPOSE_FILE="$REPOSITORY_ROOT/docker/woocommerce-e2e.yml"
RESULTS_DIR="$REPOSITORY_ROOT/test-results/woocommerce-e2e"

if ! command -v docker >/dev/null 2>&1; then
	printf '%s\n' 'Docker is required to run the WooCommerce E2E suite.' >&2
	exit 1
fi

COMPOSE_PROJECT_NAME="basicrum_woocommerce_e2e_$$"
export COMPOSE_PROJECT_NAME

compose() {
	docker compose -f "$COMPOSE_FILE" "$@"
}

cleanup() {
	status=$?
	trap - EXIT HUP INT TERM

	if [ "$status" -ne 0 ]; then
		mkdir -p "$RESULTS_DIR"
		compose logs --no-color > "$RESULTS_DIR/docker-compose.log" 2>&1 || true
	fi

	compose down --volumes --remove-orphans >/dev/null 2>&1 || true
	exit "$status"
}

trap cleanup EXIT HUP INT TERM

mkdir -p "$RESULTS_DIR"

compose run --rm --no-deps plugin
compose up -d db wordpress

attempt=0
while ! compose exec -T wordpress test -f /var/www/html/wp-includes/version.php; do
	attempt=$((attempt + 1))

	if [ "$attempt" -ge 30 ]; then
		printf '%s\n' 'WordPress files were not initialized in time.' >&2
		exit 1
	fi

	sleep 2
done

compose run --rm --no-deps --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer composer \
	install --no-dev --no-interaction --prefer-dist

compose run --rm wpcli sh /tools/setup-woocommerce-e2e.sh

compose run --rm --no-deps --user "$(id -u):$(id -g)" -e HOME=/tmp javascript \
	npm ci --no-audit --no-fund

compose run --rm --no-deps --user "$(id -u):$(id -g)" -e HOME=/tmp javascript \
	npm run test:e2e:woocommerce

printf '%s\n' 'WooCommerce E2E test passed.'
