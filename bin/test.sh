#!/usr/bin/env bash

# -e          Exit immediately if a pipeline returns a non-zero status
# -o pipefail Produce a failure return code if any command errors
set -eo pipefail

# Shorthand:
WP_PORT=`docker port query-monitor-wordpress | grep "[0-9]+$" -ohE | head -1`
CHROME_PORT=`docker port query-monitor-chrome | grep "[0-9]+$" -ohE | head -1`
DATABASE_PORT=`docker port query-monitor-database | grep "[0-9]+$" -ohE | head -1`
WP_URL="http://host.docker.internal:${WP_PORT}"
WP="docker-compose run --rm wpcli wp --url=${WP_URL}"

# Reset or install the test database:
echo "Installing database..."
$WP db reset --yes

# Install WordPress:
echo "Installing WordPress..."
$WP core install \
	--title="Example" \
	--admin_user="admin" \
	--admin_password="admin" \
	--admin_email="admin@example.com" \
	--skip-email
echo "Home URL: $WP_URL"
$WP plugin activate query-monitor

# Run the acceptance tests:
echo "Running tests..."
TEST_SITE_WEBDRIVER_PORT=$CHROME_PORT \
	TEST_SITE_DATABASE_PORT=$DATABASE_PORT \
	TEST_SITE_WP_URL=$WP_URL \
	./vendor/bin/codecept run acceptance --steps "$1"
