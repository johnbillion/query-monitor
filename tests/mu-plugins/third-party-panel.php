<?php
/**
 * Simulates a third-party plugin that registers a top-level QM menu with child sub-menus.
 *
 * This mirrors the pattern used by plugins like Remote Data Blocks: a parent
 * collector/outputter provides the top-level menu, and child outputters add
 * sub-menu items using the parent's `qm-` prefixed menu key.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only load when the acceptance test group requests it.
if ( ! isset( $_GET['_qm_acceptance_group'] ) || 'third_party_panel' !== $_GET['_qm_acceptance_group'] ) {
	return;
}

// Defer class definitions and registration until QM's classes are available.
// The qm/collectors filter fires after QM is loaded, so classes are defined
// inside the callbacks.

add_filter( 'qm/collectors', function( array $collectors ) {
	class QM_Test_Collector_ThirdParty extends QM_Collector {
		public $id = 'test-third-party';
	}

	class QM_Test_Collector_ThirdParty_Child extends QM_Collector {
		public $id = 'test-third-party-child';
	}

	$collectors['test-third-party'] = new QM_Test_Collector_ThirdParty();
	$collectors['test-third-party-child'] = new QM_Test_Collector_ThirdParty_Child();

	return $collectors;
} );

add_filter( 'qm/outputter/html', function( array $output, QM_Collectors $collectors ) {
	// --- Parent Outputter (top-level menu) ---

	class QM_Test_Output_ThirdParty extends QM_Output_Html {
		public function __construct( QM_Collector $collector ) {
			parent::__construct( $collector );
			add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 200 );
		}

		public function name() {
			return 'Test Third Party';
		}

		public function output() {
			$this->before_non_tabular_output();
			echo '<p>Test third-party parent panel content.</p>';
			$this->after_non_tabular_output();
		}
	}

	// --- Child Outputter (sub-menu under parent) ---

	class QM_Test_Output_ThirdParty_Child extends QM_Output_Html {
		public function __construct( QM_Collector $collector ) {
			parent::__construct( $collector );
			add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 201 );
		}

		public function name() {
			return 'Test Child Panel';
		}

		/**
		 * Register as a child of the parent menu using the qm- prefixed key.
		 */
		public function admin_menu( array $menu ) {
			$menu['qm-test-third-party']['children'][ $this->collector->id() ] = $this->menu( array(
				'title' => esc_html( $this->name() ),
			) );
			return $menu;
		}

		public function output() {
			$this->before_non_tabular_output();
			echo '<p>Test third-party child panel content.</p>';
			$this->after_non_tabular_output();
		}
	}

	$collector = QM_Collectors::get( 'test-third-party' );
	if ( $collector ) {
		$output['test-third-party'] = new QM_Test_Output_ThirdParty( $collector );
	}

	$collector = QM_Collectors::get( 'test-third-party-child' );
	if ( $collector ) {
		$output['test-third-party-child'] = new QM_Test_Output_ThirdParty_Child( $collector );
	}

	return $output;
}, 200, 2 );
