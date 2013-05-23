<?php

class QM_Transients extends QM {

	var $id = 'transients';

	function __construct() {
		parent::__construct();
		add_action( 'setted_site_transient', array( $this, 'setted_site_transient' ) );
		add_action( 'setted_transient',      array( $this, 'setted_blog_transient' ) );
		add_filter( 'query_monitor_menus',   array( $this, 'admin_menu' ), 50 );
	}

	function setted_site_transient( $transient ) {
		$this->setted_transient( $transient, 'site' );
	}

	function setted_blog_transient( $transient ) {
		$this->setted_transient( $transient, 'blog' );
	}

	function setted_transient( $transient, $type ) {
		$this->data['trans'][] = array(
			'transient' => $transient,
			'trace'     => $this->backtrace(),
			'type'      => $type
		);
	}

	function output( $args, $data ) {

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Transient Set', 'query-monitor' ) . '</th>';
		if ( QM::is_multisite() )
			echo '<th>' . __( 'Type', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Function', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( !empty( $data['trans'] ) ) {

			foreach ( $data['trans'] as $row ) {
				unset( $row['trace'][0], $row['trace'][1], $row['trace'][2], $row['trace'][3] );
				$func = $row['trace'][5];
				$transient = str_replace( array(
					'_site_transient_',
					'_transient_'
				), '', $row['transient'] );
				$funcs = esc_attr( implode( ', ', array_reverse( $row['trace'] ) ) );
				$type = ( QM::is_multisite() ) ? "<td valign='top'>{$row['type']}</td>\n" : '';
				echo "
					<tr>\n
						<td valign='top'>{$transient}</td>\n
						{$type}
						<td valign='top' title='{$funcs}' class='qm-ltr'>{$func}</td>\n
					</tr>\n
				";
			}

		} else {

			echo '<tr>';
			echo '<td colspan="3" style="text-align:center !important"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	function admin_menu( $menu ) {

		$count = isset( $this->data['trans'] ) ? count( $this->data['trans'] ) : 0;

		$title = ( empty( $count ) )
			? __( 'Transients Set', 'query-monitor' )
			: __( 'Transients Set (%s)', 'query-monitor' );

		$menu[] = $this->menu( array(
			'title' => sprintf( $title, number_format_i18n( $count ) )
		) );
		return $menu;

	}


}

function register_qm_transients( $qm ) {
	$qm['transients'] = new QM_Transients;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_transients', 100 );

?>
