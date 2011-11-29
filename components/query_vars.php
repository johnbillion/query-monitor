<?php

class QM_Query_Vars extends QM {

	var $id = 'query_vars';
	var $qvars        = array();
	var $plugin_qvars = array();

	function __construct() {

		parent::__construct();

	}

	function process() {

		$query_vars   = array_filter( $GLOBALS['wp_query']->query_vars );
		$plugin_qvars = apply_filters( 'query_vars', array() );

		ksort( $query_vars );

		# Array query vars in < 3.0 get smushed to the string 'Array'
		foreach ( $query_vars as $key => $var ) {
			if ( 'Array' === $var ) {
				$query_vars[$key] = 'Array (<span class="qm-warn">!</span>)';
			}
		}

		# First add plugin vars to $this->qvars:
		foreach ( $query_vars as $k => $v ) {
			if ( in_array( $k, $plugin_qvars ) ) {
				$this->qvars[$k] = $v;
				$this->plugin_qvars[] = $k;
			}
		}

		# Now add all other vars to $this->qvars:
		foreach ( $query_vars as $k => $v ) {
			if ( !in_array( $k, $plugin_qvars ) )
				$this->qvars[$k] = $v;
		}

	}

	function output() {

		echo '<table class="qm" cellspacing="0" id="' . $this->id() . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'Query Vars', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( empty( $this->qvars ) ) {
			echo '<tr>';
			echo '<td colspan="2" style="text-align:center !important"><em>' . __( 'none', 'query_monitor' ) . '</em></td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';
			return;
		}

		foreach( $this->qvars as $var => $value ) {
			if ( in_array( $var, $this->plugin_qvars ) )
				$var = '<span class="qm-current">' . $var . '</span>';
			echo '<tr>';
			echo "<td valign='top'>{$var}</td>";
			if ( is_array( $value ) ) {
				echo '<td valign="top"><ul>';
				foreach ( $value as $k => $v ) {
					$k = esc_html( $k );
					$v = esc_html( $v );
					echo "<li>{$k} => {$v}</li>";
				}
				echo '</ul></td>';
			} else {
				$value = esc_html( $value );
				echo "<td valign='top'>{$value}</td>";
			}
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';

	}

	function admin_menu() {

		$title = ( empty( $this->plugin_qvars ) )
			? __( 'Query Vars', 'query_monitor' )
			: _n( 'Query Vars (+%s)', 'Query Vars (+%s)', count( $this->plugin_qvars ), 'query_monitor' );

		return $this->menu( array(
			'title' => sprintf( $title, number_format_i18n( count( $this->plugin_qvars ) ) )
		) );

	}

}

function register_qm_query_vars( $qm ) {
	$qm['query_vars'] = new QM_Query_Vars;
	return $qm;
}

add_filter( 'qm', 'register_qm_query_vars' );

?>