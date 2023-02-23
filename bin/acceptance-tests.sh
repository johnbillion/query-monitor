#!/usr/bin/env bash

# -e          Exit immediately if a pipeline returns a non-zero status
# -o pipefail Produce a failure return code if any command errors
set -eo pipefail

echo "Starting up..."

# Prep:
docker-compose --profile acceptance-tests up -d
WP_PORT="$(docker inspect --type=container --format='{{(index .NetworkSettings.Ports "80/tcp" 0).HostPort}}' qm-server)"
CHROME_PORT="$(docker inspect --type=container --format='{{(index .NetworkSettings.Ports "4444/tcp" 0).HostPort}}' qm-chrome)"
DATABASE_PORT="$(docker inspect --type=container --format='{{(index .NetworkSettings.Ports "3306/tcp" 0).HostPort}}' qm-database)"
WP_URL="http://host.docker.internal:${WP_PORT}"
wp() {
	docker-compose run --rm wpcli --url="${WP_URL}" "$@"
}

# Wait for the web server:
./node_modules/.bin/wait-port -t 10000 "${WP_PORT}"

# Wait for MariaDB:
while ! docker-compose exec -T database /bin/bash -c 'mysqladmin ping --user="${MYSQL_USER}" --password="${MYSQL_PASSWORD}" --silent' | grep --quiet 'mysqld is alive'; do
	echo 'Waiting for MariaDB ping...'
	sleep 1
done
while ! docker-compose exec -T database /bin/bash -c 'mysql --user="${MYSQL_USER}" --password="${MYSQL_PASSWORD}" --execute="SHOW DATABASES;"' | grep --quiet 'information_schema'; do
	echo 'Waiting for MariaDB query...'
	sleep 1
done

# Wait for Selenium:
while ! curl -sSL "http://localhost:${CHROME_PORT}/wd/hub/status" 2>&1 | grep --quiet '"ready": true'; do
	echo 'Waiting for Selenium...'
	sleep 1
done

# Reset or install the test database:
echo "Installing database..."
wp db reset --yes

# Install WordPress:
echo "Installing WordPress..."
wp core install \
	--title="Example" \
	--admin_user="admin" \
	--admin_password="admin" \
	--admin_email="admin@example.com" \
	--skip-email \
	--require="wp-content/plugins/query-monitor/bin/mysqli_report.php"
echo "Home URL: ${WP_URL}"
wp plugin activate query-monitor

# Run the acceptance tests:
echo "Running tests..."
TEST_SITE_WEBDRIVER_PORT="${CHROME_PORT}" \
	TEST_SITE_DATABASE_PORT="${DATABASE_PORT}" \
	TEST_SITE_WP_URL="${WP_URL}" \
	./vendor/bin/codecept run acceptance --steps "$1"

# Ciao:
docker-compose stop chrome
