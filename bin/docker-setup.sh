#!/bin/bash

# Exit if any command fails.
set -e

# StoreCrafted Docker setup — seed profile: core.

WP_CONTAINER=${1:-site-visibility-manager-wordpress}
SITE_URL=${WP_URL:-"localhost:8888"}

redirect_output() {
    if [[ -z "$DEBUG" ]]; then
        "$@" > /dev/null 2>&1
    else
        "$@"
    fi
}

# --user 33:33 matches the wordpress image www-data UID/GID.
# See: https://hub.docker.com/_/wordpress#running-as-an-arbitrary-user
cli() {
    redirect_output docker run -i --rm \
        --volumes-from "$WP_CONTAINER" \
        --network site-visibility-manager_default \
        --user 33:33 \
        -e WORDPRESS_DB_HOST=mysql \
        -e WORDPRESS_DB_USER=root \
        -e WORDPRESS_DB_PASSWORD=password \
        -e WORDPRESS_DB_NAME=wordpress \
        wordpress:cli "$@"
}

echo "Waiting for database to be ready..."
until docker exec site-visibility-manager-mysql mysqladmin ping -h localhost -u root -ppassword --silent 2>/dev/null; do
    echo "  Not ready yet, retrying in 3s..."
    sleep 3
done
echo "Database is ready."

echo "Waiting for WordPress to be ready..."
until docker exec "$WP_CONTAINER" test -f /var/www/html/wp-includes/version.php 2>/dev/null; do
    echo "  Not ready yet, retrying in 3s..."
    sleep 3
done
echo "WordPress is ready."

# Avoid set -e abort when the site is not installed yet.
if cli wp core is-installed --path=/var/www/html && cli wp plugin is-active site-visibility-manager --path=/var/www/html; then
    echo
    echo "Environment is already set up."
    echo "WordPress:   http://${SITE_URL}/wp-admin/  (admin / password)"
    echo "Mailpit:     http://localhost:${MAILPIT_UI_PORT:-8025}/"
    echo "phpMyAdmin:  http://localhost:${PHPMYADMIN_PORT:-8080}/  (root / password)"
    exit 0
fi

echo
echo "Setting up WordPress..."
echo

cli wp core install \
    --path=/var/www/html \
    --url="$SITE_URL" \
    --title="Site Visibility Manager Dev" \
    --admin_name=admin \
    --admin_password=password \
    --admin_email=admin@example.com \
    --skip-email

echo "Installing WooCommerce..."
cli wp plugin install woocommerce --activate --path=/var/www/html

echo "Enabling HPOS..."
cli wp option update woocommerce_feature_custom_order_tables_enabled yes --path=/var/www/html
cli wp option update woocommerce_custom_orders_table_enabled yes --path=/var/www/html



echo "Activating site-visibility-manager..."
cli wp plugin activate site-visibility-manager --path=/var/www/html

echo "Configuring WordPress location settings..."
cli wp option update timezone_string "America/New_York" --path=/var/www/html
cli wp option update woocommerce_default_country "US" --path=/var/www/html
cli wp option update woocommerce_currency "USD" --path=/var/www/html



echo
echo "Done!"
echo "WordPress:   http://${SITE_URL}/wp-admin/  (admin / password)"
echo "Mailpit:     http://localhost:${MAILPIT_UI_PORT:-8025}/  (catch all wp_mail)"
echo "phpMyAdmin:  http://localhost:${PHPMYADMIN_PORT:-8080}/  (root / password)"
