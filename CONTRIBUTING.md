[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg?style=flat-square)](https://github.com/johnbillion/query-monitor/actions)

# Contributing to Query Monitor

Code contributions, bug reports, and feedback are very welcome. These should be submitted through [the GitHub repository](https://github.com/johnbillion/query-monitor). Development happens in the `develop` branch, and any pull requests should be made against that branch please.

## Reviews on WordPress.org

If you enjoy using Query Monitor I would greatly appreciate it <a href="https://wordpress.org/support/plugin/query-monitor/reviews/">if you left a positive review on the WordPress.org Plugin Directory</a>. This is the fastest and easiest way to contribute to Query Monitor 😄.

## Reporting Security Issues

[You can report security bugs through the official Query Monitor Vulnerability Disclosure Program on Patchstack](https://patchstack.com/database/vdp/query-monitor). The Patchstack team helps validate, triage, and handle any security vulnerabilities.

Do not report security issues on GitHub or the WordPress.org support forums. Thank you.

## Inclusivity and Code of Conduct

Contributions to Query Monitor are welcome from anyone. Whether you are new to Open Source or a seasoned veteran, all constructive contribution is welcome and I'll endeavour to support you when I can.

This project is released with <a href="https://github.com/johnbillion/query-monitor/blob/develop/CODE_OF_CONDUCT.md">a contributor code of conduct</a> and by participating in this project you agree to abide by its terms. The code of conduct is nothing to worry about, if you are a respectful human being then all will be good.

## Setting up Locally

You can clone this repo and activate it like a normal WordPress plugin, but you'll need to install the developer dependencies in order to build the assets and you'll need to have Docker Desktop installed to run the tests.

### Prerequisites

* [Composer](https://getcomposer.org/)
* [Node](https://nodejs.org/)

To run the tests, you'll also need:

* [Docker Desktop](https://www.docker.com/desktop) running Docker Compose version 2.20 or higher

### Setup

1. Install the PHP dependencies:

       composer install

2. Install the Node dependencies:

       npm install

## Building the Assets

To compile the Sass files into CSS:

	npm run build

To start the file watcher which will watch for changes and automatically compile the Sass:

	npm run watch

## Running the Tests

The test suite includes acceptance tests which run in a Docker container. Ensure Docker Desktop is running before running the tests.

To run the whole test suite which includes integration tests, acceptance tests, linting, and static analysis:

	composer test

To run tests individually, run one of:

	composer test:phpcs
	composer test:phpstan
	composer test:integration
	composer test:acceptance

The individual integration and acceptance tests require the Docker containers to be running. To start and stop them, use:

	composer exec tests-start
	composer exec tests-stop

## Releasing a New Version

These are the steps to take to release a new version of Query Monitor (for contributors who have push access to the GitHub repo).

### Prior to Release

1. Check [the milestone on GitHub](https://github.com/johnbillion/query-monitor/milestones) for open issues or PRs. Fix or reassign as necessary.
1. If this is a non-patch release, reassign issues and PRs assigned to the patch or minor milestones that will get skipped.
1. Ensure you're on the `develop` branch and all the changes for this release have been merged in.
1. Ensure `README.md` and `readme.txt` contain up to date "Tested up to" versions, descriptions, FAQs, and screenshots.
1. Ensure `.gitattributes` is up to date with all files that shouldn't be part of the build.
   - To do this, run `git archive --output=qm.zip HEAD` then check the contents for files that shouldn't be part of the package.
1. Run `composer test` and ensure everything passes.
1. Run `git push origin develop` and ensure CI is passing.
1. Prepare a changelog for the release.

### For Release

1. Bump the plugin version number:
   - `npm run bump:patch` for a patch release (1.2.3 => 1.2.4)
   - `npm run bump:minor` for a minor release (1.2.3 => 1.3.0)
   - `npm run bump:major` for a major release (1.2.3 => 2.0.0)
1. `git push origin develop`
1. `git push origin develop:release`
1. Wait for [the Build action](https://github.com/johnbillion/query-monitor/actions/workflows/build.yml) to complete
1. Enter the changelog into [the release on GitHub](https://github.com/johnbillion/query-monitor/releases) and publish it
1. Approve the release on [the WordPress.org release management dashboard](https://wordpress.org/plugins/developers/releases/)
1. `git push origin develop:trunk`

### Post Release

Publishing a release on GitHub triggers an action which deploys the release to the WordPress.org Plugin Directory. No need to touch Subversion.

New milestones are automatically created for the next major, minor, and patch releases where appropriate.

1. If this is a non-patch release, manually delete any [unused patch and minor milestones on GitHub](https://github.com/johnbillion/query-monitor/milestones).
1. Check the new version has appeared [on the WordPress.org plugin page](https://wordpress.org/plugins/query-monitor/).
1. Resolve relevant threads on [the plugin's support forums](https://wordpress.org/support/plugin/query-monitor/).
1. Consume tea and cake as necessary.

### Asset Updates

Assets such as screenshots and banners are stored in the `.wordpress-org` directory. These get deployed as part of the automated release process too.

In order to deploy only changes to assets and the readme file, push the change to the `deploy` branch. This allows for the "Tested up to" value to be bumped as well as assets to be updated in between releases. Changes to files other than assets and the readme file will be ignored.
