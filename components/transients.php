<?php

class QM_Component_Transients extends QM_Component {

	var $id = 'transients';

	function __construct() {
		parent::__construct();
		# See http://core.trac.wordpress.org/ticket/24583
		add_action( 'setted_site_transient', array( $this, 'setted_site_transient' ), 10, 3 );
		add_action( 'setted_transient',      array( $this, 'setted_blog_transient' ), 10, 3 );
		add_filter( 'query_monitor_menus',   array( $this, 'admin_menu' ), 50 );
	}

	function setted_site_transient( $transient, $value = null, $expiration = null ) {
		$this->setted_transient( $transient, 'site', $value, $expiration );
	}

	function setted_blog_transient( $transient, $value = null, $expiration = null ) {
		$this->setted_transient( $transient, 'blog', $value, $expiration );
	}

	function setted_transient( $transient, $type, $value = null, $expiration = null ) {
		$this->data['trans'][] = array(
			'transient'  => $transient,
			'trace'      => QM_Util::backtrace(),
			'type'       => $type,
			'value'      => $value,
			'expiration' => $expiration,
		);
	}

	function output( array $args, array $data ) {

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Transient Set', 'query-monitor' ) . '</th>';
		if ( is_multisite() )
			echo '<th>' . __( 'Type', 'query-monitor' ) . '</th>';
		if ( !empty( $data['trans'] ) and !is_null( $data['trans'][0]['expiration'] ) )
			echo '<th>' . __( 'Expiration', 'query-monitor' ) . '</th>';
		echo '<th>' . __( 'Call Stack', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( !empty( $data['trans'] ) ) {

			foreach ( $data['trans'] as $row ) {
				unset( $row['trace'][0], $row['trace'][1], $row['trace'][2] ); # QM funcs
				unset( $row['trace'][3] ); # transient funcs
				$transient = str_replace( array(
					'_site_transient_',
					'_transient_'
				), '', $row['transient'] );
				$type = ( is_multisite() ) ? "<td valign='top'>{$row['type']}</td>\n" : '';
				if ( 0 === $row['expiration'] )
					$row['expiration'] = '<em>' . __( 'none', 'query-monitor' ) . '</em>';
				$expiration = ( !is_null( $row['expiration'] ) ) ? "<td valign='top'>{$row['expiration']}</td>\n" : '';

				foreach ( $row['trace'] as & $trace ) {
					foreach ( array( 'set_transient', 'set_site_transient' ) as $skip ) {
						if ( 0 === strpos( $trace, $skip ) ) {
							$trace = sprintf( '<span class="qm-na">%s</span>', $trace );
							break;
						}
					}
				}

				$stack = implode( '<br />', $row['trace'] );
				echo "
					<tr>\n
						<td valign='top'>{$transient}</td>\n
						{$type}
						{$expiration}
						<td valign='top' class='qm-ltr'>{$stack}</td>\n
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

	function admin_menu( array $menu ) {

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

function register_qm_transients( array $qm ) {
	$qm['transients'] = new QM_Component_Transients;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_transients', 100 );

?>
