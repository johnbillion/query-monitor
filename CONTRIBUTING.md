# Contributing to Query Monitor

Code contributions, bug reports, and feedback are very welcome. These should be submitted through [the GitHub repository](https://github.com/johnbillion/query-monitor). Development happens in the `develop` branch, and any pull requests should be made to that branch.

## Inclusivity and Code of Conduct

Contributions to Query Monitor are welcome from anyone. Whether you are new to Open Source or a seasoned veteran, all constructive contribution is welcome and I'll endeavour to support you when I can.

This project uses <a href="https://github.com/johnbillion/query-monitor/blob/develop/CODE_OF_CONDUCT.md">a contributor code of conduct</a> and by participating in this project you agree to abide by its terms. The code of conduct is nothing to worry about, if you are respectful then all will be good.

## AI-assisted development

AI-assisted development is welcome and encouraged, but you must:

- Always disclose your use of AI-assisted coding agents. Failure to do so may result in your contribution being refused.
- Respect the GNU GPL software license that applies to this project.
- Prefer human-written issue descriptions and PR descriptions over AI-generated ones.
- Keep written descriptions brief, there is no need to write a novel that describes every issue or change in detail. Brevity is a skill.

## Setting up Locally

You can clone this repo and activate it like a normal plugin, but you'll need to install the developer dependencies in order to build the assets.

### Prerequisites

* [Composer](https://getcomposer.org/)
* [Node](https://nodejs.org/)
* [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or compatible) to run the tests

### Setup

1. Install the PHP dependencies:

       composer install

2. Install the Node dependencies:

       npm install

## Building the Assets

To compile the React components:

	npm run build

To start the file watcher which will watch for changes and automatically compile the React components:

	npm run watch

## Data Type Generation

Query Monitor uses JSON Schema to define the structure of data passed between PHP collectors and React output panels. This ensures type safety across both languages.

### How it works

1. **JSON Schemas** (`src/schemas/data/*.json`) define the structure of data objects using JSON Schema format with custom extensions:
   - `phpClass`: The PHP class name to generate
   - `phpFile`: The file path (relative to `data/`) where the PHP class will be written
   - `phpType`: Reference an existing PHP type instead of generating one

2. **Build command** (`npm run build-schemas`) generates:
   - **PHP classes** in `data/` from the schema definitions
   - **TypeScript interfaces** in `output/data-types.ts`

3. **PHP collectors** (`collectors/*.php`) populate instances of the generated data classes

4. **React panels** (`output/html/*.tsx`) consume the data with full TypeScript type checking

## Running the Tests

The test suite consists of:

* Acceptance tests using Playwright
* Integration tests using PHPUnit
* Linting using PHPCS
* Static analysis using PHPStan

The acceptance and integration tests run in a container. Ensure Docker Desktop is running before running the tests.

To run the whole test suite:

	composer test

To run tests individually, run one of:

	composer test:phpcs
	composer test:phpstan
	composer test:integration
	composer test:acceptance

To run a single test:

	composer test:acceptance -- tests/acceptance/EnqueuedScripts.spec.ts

The individual integration and acceptance tests require the Docker containers to be running. To start and stop them, use:

	composer test:start
	composer test:stop

## Releasing a New Version

See [RELEASING.md](RELEASING.md).
