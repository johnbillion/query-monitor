#!/usr/bin/env bash

# -e          Exit immediately if a pipeline returns a non-zero status
# -o pipefail Produce a failure return code if any command errors
set -eo pipefail

# Run the integration tests:
echo "Running tests..."

# Wait for the database:
DATABASE_PORT=`docker port qm-database | grep "[0-9]+$" -ohE | head -1`
./node_modules/.bin/wait-port -t 10000 $DATABASE_PORT

# Why are these sent to /dev/null? See https://github.com/docker/compose/issues/8833
docker-compose exec \
	-T \
	--workdir /var/www/html/wp-content/plugins/query-monitor php \
	./vendor/bin/codecept run integration --env singlesite --skip-group ms-required "$1" \
	< /dev/null

docker-compose exec \
	-T \
	--workdir /var/www/html/wp-content/plugins/query-monitor php \
	./vendor/bin/codecept run integration --env multisite --skip-group ms-excluded "$1" \
	< /dev/null
