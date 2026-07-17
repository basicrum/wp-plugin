#!/bin/sh

set -eu

WP_PATH=${WP_PATH:-/var/www/html}
WOOCOMMERCE_VERSION='10.9.4'
WOOCOMMERCE_SHA256='6e58fc3ba9b18d1c9aee6b0227d3c3c09e4fe2c1332823bd2e0ac54ffcff64a9'
WOOCOMMERCE_ARCHIVE="/tmp/woocommerce-${WOOCOMMERCE_VERSION}.zip"
WOOCOMMERCE_URL="https://downloads.wordpress.org/plugin/woocommerce.${WOOCOMMERCE_VERSION}.zip"

if ! command -v curl >/dev/null 2>&1; then
	printf '%s\n' 'curl is required to download the pinned WooCommerce test dependency.' >&2
	exit 1
fi

until [ -f "${WP_PATH}/wp-includes/version.php" ]; do
	printf '%s\n' 'Waiting for WordPress files...'
	sleep 1
done

mkdir -p "${WP_PATH}/wp-content/uploads"

if [ ! -f "${WP_PATH}/wp-config.php" ]; then
	wp config create \
		--path="${WP_PATH}" \
		--dbname="${WORDPRESS_DB_NAME}" \
		--dbuser="${WORDPRESS_DB_USER}" \
		--dbpass="${WORDPRESS_DB_PASSWORD}" \
		--dbhost="${WORDPRESS_DB_HOST}" \
		--skip-check
fi

if ! wp core is-installed --path="${WP_PATH}"; then
	wp core install \
		--path="${WP_PATH}" \
		--url="${WP_URL}" \
		--title="${WP_TITLE}" \
		--admin_user="${WP_ADMIN_USER}" \
		--admin_password="${WP_ADMIN_PASSWORD}" \
		--admin_email="${WP_ADMIN_EMAIL}" \
		--skip-email
fi

curl --fail --location --retry 3 --retry-delay 2 --silent --show-error "$WOOCOMMERCE_URL" --output "$WOOCOMMERCE_ARCHIVE"

actual_sha256=$( php -r 'echo hash_file("sha256", $argv[1]);' "$WOOCOMMERCE_ARCHIVE" )

if [ "$actual_sha256" != "$WOOCOMMERCE_SHA256" ]; then
	printf '%s\n' "WooCommerce archive checksum mismatch for version $WOOCOMMERCE_VERSION." >&2
	exit 1
fi

wp plugin install "$WOOCOMMERCE_ARCHIVE" --activate --force --path="${WP_PATH}"

if [ "$(wp plugin get woocommerce --field=version --path="${WP_PATH}")" != "$WOOCOMMERCE_VERSION" ]; then
	printf '%s\n' "Expected WooCommerce $WOOCOMMERCE_VERSION after installation." >&2
	exit 1
fi

wp wc tool run install_pages --user=1 --path="${WP_PATH}"

# Fresh WooCommerce installs enable Coming soon mode for anonymous visitors.
wp option update woocommerce_coming_soon no --path="${WP_PATH}"
wp option update woocommerce_store_pages_only no --path="${WP_PATH}"

wp plugin activate basicrum --path="${WP_PATH}"
wp rewrite structure '/%postname%/' --hard --path="${WP_PATH}"

wp option update basicrum_settings \
	'{"enabled":"1","development_mode":"0","beacon_url":"https://collector.basicrum.test/beacon","brum_site_id":"550e8400-e29b-41d4-a716-446655440000","track_admins":"0","consent_enabled":"0","wait_after_onload":"0","delay_ms":0,"script_position":"footer","use_unminified_loaders":"0"}' \
	--format=json \
	--path="${WP_PATH}"

wp eval-file /tools/seed-woocommerce-e2e.php --path="${WP_PATH}"
