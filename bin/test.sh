#!/usr/bin/env bash

set -e

# Lint all the PHP files for syntax errors:
if find . -not \( -path ./vendor -prune \) -not \( -path ./features -prune \) -name "*.php" -exec php -l {} \; | grep "^[Parse error|Fatal error]"; then
	exit 1;
fi;

# Specify the directory where the WordPress test library lives:
TMPDIR=${TMPDIR-/tmp}
if [ -z "$WP_TESTS_DIR" ]; then
	WP_TESTS_DIR="${TMPDIR}/wordpress-tests-lib"
fi

# Nicer error message if the setup script hasn't been run:
if [ ! -d "$WP_TESTS_DIR" ]; then
	echo "Please install the test suite with the following command:"
	echo "./bin/install-wp-tests.sh wordpress_test <db-user> <db-pass> [<db-host>]"
	exit 1
fi

# Nicer error message if the Composer dependencies haven't been installed:
if [ ! -d "vendor" ]; then
	echo "Please install the Composer dependencies with the following command:"
	echo "composer install"
	exit 1
fi

# Run single-site unit tests:
export WP_MULTISITE=0
./vendor/bin/phpunit -v --colors=always --exclude-group=ms-required

# Run Multisite unit tests:
export WP_MULTISITE=1
./vendor/bin/phpunit -v --colors=always --exclude-group=ms-excluded

# Run the code sniffer:
./vendor/bin/phpcs -psn --colors .
