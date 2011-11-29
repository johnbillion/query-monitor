<?php

class QM_Theme extends QM {

	var $id = 'theme';
	var $body_class = array();

	function __construct() {

		parent::__construct();

		add_filter( 'body_class', array( $this, 'body_class' ), 99 );

	}

	function body_class( $class ) {
		$this->body_class = $class;
		return $class;
	}

	function output() {

		global $template;

		if ( is_admin() )
			return;

		# @TODO display parent/child theme info

		$template_file = apply_filters( 'query_monitor_template', basename( $template ) );

		echo '<table class="qm" cellspacing="0" id="' . $this->id() . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="2">' . __( 'Theme', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';
		echo '<tr>';
		echo '<td>' . __( 'Template', 'query_monitor' ) . '</td>';
		echo "<td>{$template_file}</td>";
		echo '</tr>';

		if ( !empty( $this->body_class ) ) {

			echo '<tr>';
			echo '<td rowspan="' . count( $this->body_class ) . '">' . __( 'Body Classes', 'query_monitor' ) . '</td>';
			$first = true;

			foreach ( $this->body_class as $class ) {

				if ( !$first )
					echo '<tr>';

				echo "<td>{$class}</td>";
				echo '</tr>';

				$first = false;

			}

		}

		echo '</tbody>';
		echo '</table>';

	}

	function admin_menu() {

		global $template;

		$template_file = apply_filters( 'query_monitor_template', basename( $template ) );

		return $this->menu( array(
			'title' => sprintf( __( 'Template: %s', 'query_monitor' ), $template_file )
		) );

	}

}

function register_qm_theme( $qm ) {
	if ( !is_admin() )
		$qm['theme'] = new QM_Theme;
	return $qm;
}

add_filter( 'qm', 'register_qm_theme' );

?>