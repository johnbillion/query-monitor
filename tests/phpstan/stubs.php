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

function wp_script_modules(): WP_Script_Modules {
	return new WP_Script_Modules();
}

class WP_Script_Modules {
	/**
	 * @phpstan-return array<string, array{
	 *   src: string,
	 *   version: string|false|null,
	 *   enqueue: bool,
	 *   dependencies: list<array{
	 *     id: string,
	 *     import: 'static'|'dynamic',
	 *   }>,
	 * }>
	 */
	private function get_marked_for_enqueue(): array {
		return [];
	}

	/**
	 * @phpstan-param list<string> $ids
	 * @phpstan-param list<'static'|'dynamic'> $import_types
	 * @phpstan-return array<string, array{
	 *   src: string,
	 *   version: string|false|null,
	 *   enqueue: bool,
	 *   dependencies: list<array{
	 *     id: string,
	 *     import: 'static'|'dynamic',
	 *   }>,
	 * }>
	 */
	private function get_dependencies( array $ids, array $import_types = array( 'static', 'dynamic' ) ): array {
		return [];
	}

	private function get_src( string $id ): string {
		return '';
	}
}
