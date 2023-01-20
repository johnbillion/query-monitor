#!/usr/bin/env bash

# -e          Exit immediately if a pipeline returns a non-zero status
# -o pipefail Produce a failure return code if any command errors
set -eo pipefail

# Run the integration tests:
echo "Running tests..."

# Wait for MariaDB:
while ! docker container exec -it qm-database /bin/bash -c 'mysqladmin ping -u"${MYSQL_USER}" -p"${MYSQL_PASSWORD}"' | grep 'mysqld is alive' >/dev/null; do
	echo 'Waiting for MariaDB...'
	sleep 1
done

# Why are these sent to /dev/null? See https://github.com/docker/compose/issues/8833
docker-compose exec \
	-T \
	--workdir /var/www/html/wp-content/plugins/query-monitor php \
	./vendor/bin/codecept run integration --env singlesite --skip-group ms-required --debug "$1" \
	< /dev/null

docker-compose exec \
	-T \
	--workdir /var/www/html/wp-content/plugins/query-monitor php \
	./vendor/bin/codecept run integration --env multisite --skip-group ms-excluded --debug "$1" \
	< /dev/null
