<?php
/**
 * Hooks within bounds collector.
 *
 * @package query-monitor
 */

/**
 * @extends QM_DataCollector<QM_Data_Hooks_Discovered>
 */
class QM_Collector_Hooks_Discovered extends QM_DataCollector {

	public $id = 'hooks_discovered';

	public function get_storage(): QM_Data {
		return new QM_Data_Hooks_Discovered();
	}

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Discovered Hooks', 'query-monitor' );
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		if ( defined( 'QM_DISABLED_HOOK_DISCOVERY' ) && constant( 'QM_DISABLED_HOOK_DISCOVERY' ) ) {
			return;
		}

		add_action( 'qm/listen/start', array( $this, 'action_listener_start' ) );
		add_action( 'qm/listen/stop', array( $this, 'action_listener_stop' ) );
		add_action( 'shutdown', array( $this, 'action_shutdown' ) );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		parent::tear_down();

		remove_action( 'qm/listen/start', array( $this, 'action_listener_start' ) );
		remove_action( 'qm/listen/stop', array( $this, 'action_listener_stop' ) );
		remove_action( 'shutdown', array( $this, 'action_shutdown' ) );
	}

	/**
	 * @param string $id
	 * @return void
	 */
	public function action_listener_start( $id ) {
		if ( $this->is_active( $id ) ) {
			return;
		}

		if ( is_array( $this->data->bounds ) && array_key_exists( $id, $this->data->bounds ) ) {
			trigger_error( sprintf(
				/* translators: %s: Hook discovery ID */
				esc_html__( 'Hook discovery ID `%s` already exists', 'query-monitor' ),
				$id,
			), E_USER_NOTICE );

			return;
		}

		$this->maybe_add_all_callback();

		if ( ! is_array( $this->data->active ) ) {
			$this->data->active = array();
		}

		if ( ! is_array( $this->data->hooks ) ) {
			$this->data->hooks = array();
		}

		if ( ! is_array( $this->data->counts ) ) {
			$this->data->counts = array();
		}

		if ( ! is_array( $this->data->bounds ) ) {
			$this->data->bounds = array();
		}

		$this->data->active[ $id ] = 1;
		$this->data->hooks[ $id ]  = array();
		$this->data->counts[ $id ] = 0;
		$this->data->bounds[ $id ] = array(
			'start' => new QM_Backtrace(),
			'stop'  => null,
		);
	}

	/**
	 * @param string $id
	 * @return void
	 */
	public function action_listener_stop( $id ) {
		if ( ! $this->is_active( $id ) && ! array_key_exists( $id, $this->data->hooks ) ) {
			trigger_error( sprintf(
				/* translators: %s: Hook discovery ID */
				esc_html__( 'Hook discovery starting bound for `%s` has not been set', 'query-monitor' ),
				$id
			), E_USER_NOTICE );

			return;
		}

		unset( $this->data->active[ $id ] );
		$this->data->bounds[ $id ]['stop'] = new QM_Backtrace();

		if ( $this->is_active() ) {
			return;
		}

		remove_filter( 'all', array( $this, 'filter_all' ) );
	}

	/**
	 * @param mixed $var
	 * @return mixed
	 */
	public function filter_all( $var ) {
		if ( ! $this->is_active() ) {
			remove_filter( 'all', array( $this, 'filter_all' ) );

			return $var;
		}

		if ( in_array( current_action(), array(
			'qm/listen/start',
			'qm/listen/stop',
		) ) ) {
			return $var;
		}

		global $wp_actions;

		foreach ( array_keys( $this->data->active ) as $id ) {
			end( $this->data->hooks[ $id ] );
			$last = current( $this->data->hooks[ $id ] );

			if ( ! empty( $last ) && current_action() === $last['name'] ) {
				$i = key( $this->data->hooks[ $id ] );
				$this->data->hooks[ $id ][ $i ]['fires']++;
			} else {
				$this->data->hooks[ $id ][] = array(
					'name'      => current_action(),
					'is_action' => array_key_exists( current_action(), $wp_actions ),
					'fires'     => 1,
				);
			}

			if ( constant( 'QM_MAX_DISCOVERED_HOOKS' ) < ++$this->data->counts[ $id ] ) {
				$this->action_listener_stop( $id );
				$this->data->bounds[ $id ]['terminated'] = true;
			}

			return $var;
		}
	}

	/**
	 * @return void
	 */
	public function action_shutdown() {
		if ( ! $this->is_active() ) {
			return;
		}

		foreach ( $this->data->active as $id ) {
			$this->action_listener_stop( $id );
		}
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	protected function is_active( $id = '' ) {
		if ( empty( $id ) ) {
			return ! empty( $this->data->active );
		}

		return is_array( $this->data->active ) && array_key_exists( $id, $this->data->active );
	}

	/**
	 * @return void
	 */
	protected function maybe_add_all_callback() {
		if ( $this->is_active() ) {
			return;
		}

		add_filter( 'all', array( $this, 'filter_all' ) );
	}

}

# Load early to catch all hooks
QM_Collectors::add( new QM_Collector_Hooks_Discovered() );
