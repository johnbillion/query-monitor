#!/usr/bin/env bash

# -e          Exit immediately if a pipeline returns a non-zero status
# -o pipefail Produce a failure return code if any command errors
set -eo pipefail

echo "Starting up..."

# Wait for MariaDB:
while ! docker-compose exec -T database /bin/bash -c 'mysqladmin ping --user="${MYSQL_USER}" --password="${MYSQL_PASSWORD}" --silent' | grep --quiet 'mysqld is alive'; do
	echo 'Waiting for MariaDB...'
	sleep 1
done

# Run the integration tests:
echo "Running tests..."

# Why is /dev/null piped into stdout? See https://github.com/docker/compose/issues/8833
docker-compose exec \
	--no-TTY \
	--workdir /var/www/html/wp-content/plugins/query-monitor php \
	./vendor/bin/codecept run integration --env singlesite --skip-group ms-required "$1" \
	< /dev/null

docker-compose exec \
	--no-TTY \
	--workdir /var/www/html/wp-content/plugins/query-monitor php \
	./vendor/bin/codecept run integration --env multisite --skip-group ms-excluded "$1" \
	< /dev/null
