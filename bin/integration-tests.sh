#!/usr/bin/env bash

# -e          Exit immediately if a pipeline returns a non-zero status
# -o pipefail Produce a failure return code if any command errors
set -eo pipefail

# Prep:
DATABASE_PORT=`docker port query-monitor-database | grep "[0-9]+$" -ohE | head -1`

# Run the integration tests:
echo "Running tests..."
TEST_SITE_DATABASE_PORT=$DATABASE_PORT \
	./vendor/bin/codecept run integration --env singlesite --skip-group ms-required "$1"

TEST_SITE_DATABASE_PORT=$DATABASE_PORT \
	./vendor/bin/codecept run integration --env multisite --skip-group ms-excluded "$1"
