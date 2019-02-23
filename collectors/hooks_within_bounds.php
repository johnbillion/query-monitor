<?php
/**
 * Hooks within bounds collector.
 *
 * @package query-monitor
 */

class QM_Collector_Hooks_Within_Bounds extends QM_Collector {

	public $id = 'hooks_within_bounds';
	private $active = array();

	public function name() {
		return __( 'Discovered Hooks', 'query-monitor' );
	}

	public function __construct() {
		parent::__construct();

		if ( defined( 'QM_DISABLE_HOOK_DISCOVERY' ) && QM_DISABLE_HOOK_DISCOVERY ) {
			return;
		}

		add_action( 'qm/listen/start', array( $this, 'action_function_listener_start' ) );
		add_action( 'qm/listen/stop',  array( $this, 'action_function_listener_stop'  ) );
		add_action( 'shutdown',        array( $this, 'action_function_shutdown'       ) );

		$this->data = array(
			'hooks'  => array(),
			'bounds' => array(),
			'counts' => array(),
		);
	}

	function action_function_listener_start( $id ) {
		if ( $this->is_active( $id ) ) {
			return;
		}

		if ( array_key_exists( $id, $this->data['bounds'] ) ) {
			trigger_error( sprintf(
				/* translators: %s: Hook discovery ID */
				esc_html__( 'Hook discovery ID %s already exists', 'query-monitor' ),
				'<code>' . $id . '</code>'
			), E_USER_NOTICE );
			return;
		}

		$this->maybe_add_all_callback();

		$this->active[ $id ] = 1;
		$this->data['hooks'][ $id ] = array();
		$this->data['bounds'][ $id ] = array(
			'start' => new QM_Backtrace(),
			'stop'  => null,
		);
		$this->data['counts'][ $id ] = 0;
	}

	function action_function_all( $var = null ) {
		if ( ! $this->is_active() ) {
			remove_action( 'all', array( $this, 'action_function_all' ) );
			return $var;
		}

		if ( in_array( current_action(), array(
			'qm/listen/start',
			'qm/listen/stop',
		) ) ) {
			return $var;
		}

		global $wp_actions;

		foreach ( array_keys( $this->active ) as $id ) {
			end( $this->data['hooks'][ $id ] );
			$last = current( $this->data['hooks'][ $id ] );

			if ( current_action() === $last['hook'] ) {
				$i = key( $this->data['hooks'][ $id ] );
				$this->data['hooks'][ $id ][ $i ]['fires']++;
			} else {
				$this->data['hooks'][ $id ][] = array(
					'hook'      => current_action(),
					'is_action' => array_key_exists( current_action(), $wp_actions ),
					'fires'     => 1,
				);
			}

			if ( QM_MAX_HOOKS_DISCOVERED < ++$this->data['counts'][ $id ] ) {
				$this->action_function_listener_stop( $id );
				$this->data['bounds'][ $id ]['was_terminated'] = true;
			}

			return $var;
		}
	}

	function action_function_listener_stop( $id ) {
		if ( ! $this->is_active( $id ) && ! array_key_exists( $id, $this->data['hooks'] ) ) {
			trigger_error( sprintf(
				/* translators: %s: Hook discovery ID */
				esc_html__( 'Hook discovery starting bound for %s has not been set', 'query-monitor' ),
				'<code>' . $id . '</code>'
			), E_USER_NOTICE );
			return;
		}

		unset( $this->active[ $id ] );
		$this->data['bounds'][ $id ]['stop'] = new QM_Backtrace();

		if ( ! $this->is_active() ) {
			remove_action( 'all', array( $this, 'action_function_all' ) );
		}
	}

	function action_function_shutdown() {
		if ( $this->is_active() ) {
			foreach ( array_keys( $this->active ) as $id ) {
				$this->action_function_listener_stop( $id );
			}
		}
	}

	function is_active( $id = false ) {
		return false !== $id ? array_key_exists( $id, $this->active ) : ! empty( $this->active );
	}

	function maybe_add_all_callback() {
		if ( ! $this->is_active() ) {
			add_action( 'all', array( $this, 'action_function_all' ), 0 );
		}
	}

}

# Load early so early hooks can be discovered
QM_Collectors::add( new QM_Collector_Hooks_Within_Bounds() );
