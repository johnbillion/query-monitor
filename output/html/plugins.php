<?php
/**
 * Plugin output for HTML pages.
 *
 * @package query-monitor
 */

class QM_Output_Html_Plugins extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Theme Collector.
	 */
	protected $collector;

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 60 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 60 );
	}

	public function name() {
		return __( 'Plugins', 'query-monitor' );
	}

	public function output() {
		$hooks_data = QM_Collectors::get( 'hooks' )->get_data();
		$plugins_data = QM_Collectors::get( 'plugins' )->get_data();

		$plugins = array();
		foreach ( $plugins_data['plugins'] as $name => $plugin ) {
			$plugins[ $name ] = array();
			$plugins[ $name ]['actions'] = array();

			if ( isset( $plugin['load_time'] ) ) {
				$file_data = get_file_data( $plugin['file'],
					array(
						'PluginName' => 'Plugin Name',
						'PluginURI' => 'Plugin URI',
						'Version' => 'Version',
						'TextDomain' => 'Text Domain',
					),
					'plugin'
				);
				if ( $file_data ) {
					$plugins[ $name ]['plugin_name'] = $file_data['PluginName'];
					$plugins[ $name ]['plugin_uri'] = $file_data['PluginURI'];
					$plugins[ $name ]['version'] = $file_data['Version'];
					$plugins[ $name ]['text_domain'] = $file_data['TextDomain'];
				}
				$plugins[ $name ]['time'] = $plugin['load_time'];
			}
		}

		foreach ( $plugins as $name => $plugin ) {
			foreach ( $hooks_data['hooks'] as $hook ) {
				foreach ( $hook['actions'] as $action ) {
					if ( isset( $action['callback'] )
						&& isset( $action['callback']['component'] )
						&& $action['callback']['component']->context == $name
						&& isset( $action['time'] ) ) {
						if ( ! isset( $plugins[ $name ]['time'] ) ) {
							$plugins[ $name ]['time'] = 0;
						}
						$plugins[ $name ]['time'] += $action['time'];
					}
				}
			}
		}

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Name', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Name', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Version', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Text Domain', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Time', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $plugins as $name => $plugin ) {
			printf( // WPCS: XSS ok.
				'<tr data-qm-subject="%s">',
				esc_attr( $name )
			);
			echo '<th scope="row">' . esc_html( $name ) . '</th>';
			if ( $plugin['plugin_uri'] ) {
				echo '<th scope="row"><a href="' . esc_html( $plugin['plugin_uri'] ) . '" target="_blank">' . esc_html( $plugin['plugin_name'] ) . '</a></th>';
			} else {
				echo '<th scope="row">' . esc_html( $plugin['plugin_name'] ) . '</th>';
			}
			echo '<th scope="row">' . esc_html( $plugin['version'] ) . '</th>';
			echo '<th scope="row">' . esc_html( $plugin['text_domain'] ) . '</th>';
			echo '<td>' . esc_html( number_format_i18n( $plugin['time'], 4 ) ) . '</td>';
		}

		echo '</tbody>';

		$this->after_tabular_output();
	}

	public function admin_menu( array $menu ) {

		$data = $this->collector->get_data();

		$name = __( 'Unknown', 'query-monitor' );

		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => esc_html( sprintf(
				__( 'Plugins', 'query-monitor' ),
				$name
			) ),
		) );

		return $menu;

	}

	public function panel_menu( array $menu ) {
		$id = $this->collector->id();
		if ( isset( $menu[ $id ] ) ) {
			$menu[ $id ]['title'] = __( 'Plugins', 'query-monitor' );

			$menu[ $id ]['children'][] = array(
				'id'    => $id . '-hooks',
				'href'  => '#' . $id . '-hooks',
				'title' => esc_html__( 'Hooks in Use', 'query-monitor' ),
			);

		}

		return $menu;
	}

}

class QM_Output_Html_Plugins_Hooks extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var QM_Collector_Theme Collector.
	 */
	protected $collector;

	public function name() {
		return __( 'Plugins Hooks', 'query-monitor' );
	}

	public function output() {
		$data = $this->collector->get_data();
		$hooks_data = QM_Collectors::get( 'hooks' )->get_data();

		$plugins = array();
		foreach ( $hooks_data['components'] as $component ) {
			if ( preg_match( '/^' . __( 'Plugin', 'query-monitor' ) . ': (.*)/', $component, $matches ) ) {
				$name = $matches[1];
				$plugins[ $name ] = array();
				$plugins[ $name ]['actions'] = array();

				if ( isset( $data['plugins'][ $name ] )
					&& isset( $data['plugins'][ $name ]['load_time'] ) ) {
					$plugins[ $name ]['actions']['']['hook'] = 'Load';
					$plugins[ $name ]['actions']['']['time'] = $data['plugins'][ $name ]['load_time'];
				}
			}
		}

		foreach ( $plugins as $name => $component_name ) {
			foreach ( $hooks_data['hooks'] as $hook ) {
				foreach ( $hook['actions'] as $action ) {
					if ( isset( $action['callback'] )
						&& isset( $action['callback']['component'] )
						&& $action['callback']['component']->context == $name
						&& isset( $action['time'] ) ) {
						$plugins[ $name ]['actions'][ $action['callback']['name'] ]['hook'] = $hook['name'];
						if ( ! isset( $plugins[ $name ]['actions'][ $action['callback']['name'] ]['time'] ) ) {
							$plugins[ $name ]['actions'][ $action['callback']['name'] ]['time'] = 0;
						}
						$plugins[ $name ]['actions'][ $action['callback']['name'] ]['time'] += $action['time'];
					}
				}
			}
		}

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Name', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Hook', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Callback', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Time', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $plugins as $name => $plugin ) {
			if ( ! empty( $plugins[ $name ]['actions'] ) ) {
				$rowspan = count( $plugins[ $name ]['actions'] );
			} else {
				$rowspan = 1;
			}

			$first = true;

			foreach ( $plugins[ $name ]['actions'] as $action_name => $action ) {
				if ( $first ) {
					printf( // WPCS: XSS ok.
						'<tr data-qm-subject="%s">',
						esc_attr( $name )
					);
					echo '<th scope="row" rowspan="' . intval( $rowspan ) . '">' . esc_html( $name ) . '</th>';

					$first = false;
				} else {
					echo '<tr>';
				}

				echo '<td>' . esc_html( $action['hook'] ) . '</td>';
				echo '<td>' . esc_html( $action_name ) . '</td>';
				echo '<td>' . esc_html( number_format_i18n( $action['time'], 4 ) ) . '</td>';
				echo '</tr>';
			}
		}
		echo '</tbody>';

		$this->after_tabular_output();
	}

}

function register_qm_output_html_plugins( array $output, QM_Collectors $collectors ) {
	$collector = QM_Collectors::get( 'plugins' );
	if ( $collector ) {
		$output['plugins'] = new QM_Output_Html_Plugins( $collector );
	}

	$collector = QM_Collectors::get( 'plugins-hooks' );
	if ( $collector ) {
		$output['plugins-hooks'] = new QM_Output_Html_Plugins_Hooks( $collector );
	}

	return $output;
}

add_filter( 'qm/outputter/html', 'register_qm_output_html_plugins', 71, 2 );
