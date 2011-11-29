<?php

class QM_Transients extends QM {

	var $id   = 'transients';
	var $trans = array();

	function __construct() {

		parent::__construct();

		add_action( 'setted_site_transient',  array( $this, 'setted_site_transient' ) );
		add_action( 'setted_transient',       array( $this, 'setted_blog_transient' ) );

	}

	function setted_site_transient( $transient ) {
		$this->setted_transient( $transient, 'site' );
	}

	function setted_blog_transient( $transient ) {
		$this->setted_transient( $transient, 'blog' );
	}

	function setted_transient( $transient, $type ) {
		$this->trans[] = array(
			'transient' => $transient,
			'trace'     => $this->backtrace(),
			'type'      => $type
		);
	}

	function output() {

		echo '<table class="qm" cellspacing="0" id="' . $this->id() . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Transient Set', 'query_monitor' ) . '</th>';
		if ( is_multisite() )
			echo '<th>' . __( 'Type', 'query_monitor' ) . '</th>';
		echo '<th>' . __( 'Function', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( !empty( $this->trans ) ) {

			foreach ( $this->trans as $row ) {
				unset( $row['trace'][0], $row['trace'][1] );
				$func = $row['trace'][2];
				$transient = str_replace( array(
					'_site_transient_',
					'_transient_'
				), '', $row['transient'] );
				$funcs = esc_attr( implode( ', ', array_reverse( $row['trace'] ) ) );
				$type = ( is_multisite() ) ? "<td valign='top'>{$row['type']}</td>\n" : '';
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
			echo '<td colspan="3" style="text-align:center !important"><em>' . __( 'none', 'query_monitor' ) . '</em></td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';

	}

	function admin_menu() {

		$title = ( empty( $this->trans ) )
			? __( 'Transients Set', 'query_monitor' )
			: _n( 'Transients Set (%s)', 'Transients Set (%s)', count( $this->trans ), 'query_monitor' );

		return $this->menu( array(
			'title' => sprintf( $title, number_format_i18n( count( $this->trans ) ) )
		) );

	}


}

function register_qm_transients( $qm ) {
	$qm['transients'] = new QM_Transients;
	return $qm;
}

add_filter( 'qm', 'register_qm_transients' );

?>