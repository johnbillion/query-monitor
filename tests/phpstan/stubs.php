<?php declare(strict_types = 1);

// QM constants:

define( 'QM_COOKIE', '' );
define( 'QM_DB_EXPENSIVE', 1 );
define( 'QM_EDITOR_COOKIE', '' );
define( 'QM_ERROR_FATALS', 1 );

// Third party constants:

define( 'Altis\ROOT_DIR', '' );

// Third party functions:

function members_register_cap_group( string $name, array $args = [] ): void {}

function members_register_cap( string $name, array $args = [] ): void {}

// WPBrowser compatibility:

class_alias(
	'\\Codeception\\Test\\Unit',
	'\\tad\\WPBrowser\\Compat\\Codeception\\Unit'
);
