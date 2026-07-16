#!/usr/bin/env sh

set -eu

WP_PATH=${WP_PATH:-/var/www/html}

until [ -f "${WP_PATH}/wp-includes/version.php" ]; do
	echo "Waiting for WordPress files..."
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

if ! wp plugin is-active basicrum --path="${WP_PATH}"; then
	wp plugin activate basicrum --path="${WP_PATH}"
fi
