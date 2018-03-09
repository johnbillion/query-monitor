[![Build Status](https://travis-ci.org/johnbillion/query-monitor.svg?branch=master)](https://travis-ci.org/johnbillion/query-monitor)

# Contributing to Query Monitor

Code contributions and bug reports are very welcome. These should be submitted through [the GitHub repository](https://github.com/johnbillion/query-monitor). Development happens in the `develop` branch, and any pull requests should be made against that branch please.

## Setting up Locally

Query Monitor has no build process, so you can clone this repo and activate it like a normal WordPress plugin. If you want to contribute to Query Monitor, you should install the Composer dependencies in order to run the tests.

1. Install [Composer](https://getcomposer.org/) if you don't already have it.
2. Clone [the git repository](https://github.com/johnbillion/query-monitor) on your local machine.
3. Run `composer install` to fetch all the dependencies.
4. Install the test environment by executing:

		./bin/install-wp-tests.sh wordpress_tests <db-user> <db-pass>

## Running all the Tests

To run the whole test suite (which includes PHPUnit, code sniffer, and linting), execute the following:

	./bin/test.sh

## Running the Unit Tests

To run just the PHPUnit tests, execute the following:

	./vendor/bin/phpunit
