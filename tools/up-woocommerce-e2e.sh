#!/bin/sh

set -eu

SCRIPT_DIR=$( CDPATH= cd -- "$(dirname -- "$0")" && pwd )
REPOSITORY_ROOT=$( CDPATH= cd -- "$SCRIPT_DIR/.." && pwd )
COMPOSE_FILE="$REPOSITORY_ROOT/docker/woocommerce-e2e.yml"
COMPOSE_DEV_FILE="$REPOSITORY_ROOT/docker/woocommerce-e2e.dev.yml"
PORT=${WOOCOMMERCE_E2E_PORT:-9081}

if ! command -v docker >/dev/null 2>&1; then
	printf '%s\n' 'Docker is required to run the WooCommerce development stack.' >&2
	exit 1
fi

COMPOSE_PROJECT_NAME=basicrum_woocommerce_dev
export COMPOSE_PROJECT_NAME

compose() {
	docker compose -f "$COMPOSE_FILE" -f "$COMPOSE_DEV_FILE" "$@"
}

print_access_details() {
	printf '%s\n' "WooCommerce test stack is ready at http://localhost:${PORT}/"
	printf '%s\n' "Admin: http://localhost:${PORT}/wp-admin/"
	printf '%s\n' 'Login: admin / basicrum-e2e-password'
	printf '%s\n' 'Run make woocommerce-e2e-down to stop and reset it.'
}

if [ -n "$(compose ps -aq wordpress)" ]; then
	compose up -d db wordpress

	if compose run --rm --no-deps wpcli core is-installed --path=/var/www/html >/dev/null 2>&1; then
		print_access_details
		exit 0
	fi
fi

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

compose run --rm --no-deps -e COMPOSER_HOME=/tmp/composer composer \
	install --no-dev --no-interaction --prefer-dist

compose run --rm wpcli sh /tools/setup-woocommerce-e2e.sh

print_access_details
