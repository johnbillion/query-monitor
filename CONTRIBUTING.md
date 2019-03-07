[![Build Status](https://img.shields.io/travis/johnbillion/query-monitor/develop.svg?style=flat-square&label=develop%20build)](https://travis-ci.org/johnbillion/query-monitor)

# Contributing to Query Monitor

Code contributions and bug reports are very welcome. These should be submitted through [the GitHub repository](https://github.com/johnbillion/query-monitor). Development happens in the `develop` branch, and any pull requests should be made against that branch please.

* [Reporting Security Issues](#reporting-security-issues)
* [Setting up Locally](#setting-up-locally)
* [Building the Assets](#building-the-assets)
* [Running the Tests](#running-the-tests)

## Reporting Security Issues

If you discover a security issue in Query Monitor, please report it to [the security program on HackerOne](https://hackerone.com/johnblackbourn). Do not report security issues on GitHub or the WordPress.org support forums. Thank you.

## Setting up Locally

You can clone this repo and activate it like a normal WordPress plugin. If you want to contribute to Query Monitor, you should install the developer dependencies in order to build the assets and run the tests.

### Prerequisites:

* [Composer](https://getcomposer.org/)
* [Node](https://nodejs.org/)

### Setup:

1. Install the PHP dependencies:

       composer install

2. Install the Node dependencies:

       npm install

3. Install Grunt CLI globally (if you don't already have it):

       npm install -g grunt-cli

4. Install the test environment:

       ./bin/install-wp-tests.sh wordpress_tests <db-user> <db-pass>

## Building the Assets

To compile the Sass files into CSS:

	grunt sass

To start the file watcher which will watch for changes and automatically compile the Sass:

	grunt watch

Note that the built CSS files are also committed to the Git repo. This allows the plugin to be installed via a Git clone or Composer without the need to perform a build step.

## Running the Tests

To run the whole test suite which includes PHPUnit, code sniffer, and linting:

	composer test

To run just the PHPUnit tests:

	./vendor/bin/phpunit

To run just the code sniffer:

	./vendor/bin/phpcs -psn .
