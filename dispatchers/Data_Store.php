<?php declare(strict_types = 1);
/**
 * Persistent data store dispatcher.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @phpstan-import-type FatalError from QM_Dispatcher
 */
class QM_Dispatcher_Data_Store extends QM_Dispatcher {

	/**
	 * Outputter instances.
	 *
	 * @var array<string, QM_Output_Html> Array of outputters.
	 */
	protected $outputters = array();

	/**
	 * @var string
	 */
	public $id = 'data_store';

	public function __construct( QM_Plugin $qm ) {
		add_action( 'shutdown', array( $this, 'dispatch' ), 9999 );

		parent::__construct( $qm );
	}

	/**
	 * @return void
	 */
	public function dispatch() {
		if ( ! $this->should_dispatch() ) {
			return;
		}

		foreach ( (array) glob( $this->qm->plugin_path( 'output/html/*.php' ) ) as $file ) {
			require_once $file;
		}

		$collectors = QM_Collectors::init();
		$collectors->process();

		/** @var array<string, QM_Output_Html> $outputters */
		$outputters = apply_filters( 'qm/outputter/html', array(), $collectors );

		$store = QM_Data_Store::init();

		foreach ( $outputters as $output ) {
			$collector = $output->get_collector();

			if ( $output::$client_side_rendered ) {
				$collector_data = $collector->get_data();

				if ( ! empty( $collector->concerned_filters ) ) {
					$collector_data->concerned_filters = $collector->concerned_filters;
				}

				if ( ! empty( $collector->concerned_actions ) ) {
					$collector_data->concerned_actions = $collector->concerned_actions;
				}

				$store->write_meta( $collector->id, array(
					'enabled' => $collector::enabled(),
					'data' => $collector_data,
				) );
			}
		}

		$store->write_meta( 'overview', array(
			'enabled' => true,
			'data' => QM_Collectors::get( 'overview' )->get_data(),
		) );
		$store->write_meta( 'timeline', array(
			'enabled' => true,
			'data' => null,
		) );
	}

	/**
	 * @return bool
	 */
	public function is_active() {

		if ( ! self::user_can_view() ) {
			return false;
		}

		// Don't dispatch if the minimum required actions haven't fired:
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			if ( ! did_action( 'rest_api_init' ) ) {
				return false;
			}
		} elseif ( is_admin() ) {
			if ( ! did_action( 'admin_init' ) ) {
				return false;
			}
		} else {
			if ( ! ( did_action( 'wp' ) || did_action( 'login_init' ) || did_action( 'gp_head' ) ) ) {
				return false;
			}
		}

		return true;

	}
}

/**
 * @param array<string, QM_Dispatcher> $dispatchers
 * @param QM_Plugin $qm
 * @return array<string, QM_Dispatcher>
 */
function register_qm_dispatcher_data_store( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['data_store'] = new QM_Dispatcher_Data_Store( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_data_store', 10, 2 );
