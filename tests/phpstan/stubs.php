<?php declare(strict_types = 1);

define( 'WP_CONTENT_DIR', 'wp-content' );
define( 'WP_PLUGIN_DIR', 'plugins' );

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
