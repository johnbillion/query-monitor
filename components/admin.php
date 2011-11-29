<?php

class QM_Admin extends QM {

	var $id = 'admin';
	var $admin  = array();
	var $screen = '';

	function __construct() {

		parent::__construct();

		add_filter( 'current_screen', array( $this, 'current_screen' ), 99 );

	}

	function current_screen( $screen ) {
		if ( empty( $this->admin ) )
			$this->admin = wp_clone( $screen );
		return $screen;
	}

	function process() {

		global $current_screen, $pagenow;

		if ( !isset( $current_screen ) or empty( $current_screen ) ) {

			# Pre-3.0 compat:
			if ( isset( $_GET['page'] ) ) {

				$plugin_page = plugin_basename( stripslashes( $_GET['page'] ) );

				if ( isset( $plugin_page ) ) {
					if ( !$page_hook = get_plugin_page_hook( $plugin_page, $pagenow ) )
						$page_hook = get_plugin_page_hook( $plugin_page, $plugin_page );
					if ( !$page_hook )
						$page_hook = $plugin_page;
				}

			} else {
				$page_hook = $pagenow;
			}

			$this->screen = $page_hook;

		} else {
			if ( isset( $_GET['page'] ) )
				$this->screen = $current_screen->base;
			else
				$this->screen = $pagenow;
		}

	}

	function admin_menu() {

		return $this->menu( array(
			'title' => sprintf( __( 'Admin Screen: %s', 'query_monitor' ), $this->screen )
		) );

	}

	function output() {

		global $current_screen, $typenow, $pagenow;

		$post_type_warning = '';

		echo '<table class="qm" cellspacing="0" id="' . $this->id() . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="3">' . __( 'Admin', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td rowspan="3">' . __( 'Variables', 'query_monitor' ) . '</td>';
		echo '<td class="qm-ltr">$current_screen</td>';
		echo '<td>';

		if ( is_object( $this->admin ) ) {
			echo '<table class="qm-inner" cellspacing="0">';
			echo '<tbody>';
			foreach ( $this->admin as $key => $value ) {
				echo '<tr>';
				echo "<td class='qm-var'>{$key}:</td>";
				echo '<td>';
				echo $value;
				if ( !empty( $value ) and ( $current_screen->$key != $value ) )
					echo $post_type_warning = '&nbsp;(<a href="http://core.trac.wordpress.org/ticket/14886" class="qm-warn" title="' . esc_attr__( 'This value may not be as expected. Please see WordPress bug #14886.', 'query_monitor' ) . '" target="_blank">!</a>)';
				echo '</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table>';
		} else {
			echo $this->admin;
		}

		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td class="qm-ltr">$pagenow</td>';
		echo "<td>{$pagenow}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td class="qm-ltr">$typenow</td>';
		echo "<td>{$typenow} {$post_type_warning}</td>";
		echo '</tr>';

		if ( in_array( $current_screen->base, array( 'edit', 'edit-comments', 'edit-tags', 'link-manager', 'plugins', 'plugins-network', 'sites-network', 'themes-network', 'upload', 'users', 'users-network' ) ) ) {

			# And now, WordPress' legendary inconsistency comes into play:

			if ( !empty( $current_screen->taxonomy ) )
				$col = $current_screen->taxonomy;
			else if ( !empty( $current_screen->post_type ) )
				$col = $current_screen->post_type . '_posts';
			else
				$col = $current_screen->base;

			if ( !empty( $current_screen->post_type ) )
				$cols = $current_screen->post_type . '_posts';
			else
				$cols = $current_screen->id;

			if ( 'edit-comments' == $col )
				$col = 'comments';
			else if ( 'upload' == $col )
				$col = 'media';
			else if ( 'link-manager' == $col )
				$col = 'link';

			echo '<tr>';
			echo '<td rowspan="3">' . __( 'Columns', 'query_monitor' ) . '</td>';
			echo "<td colspan='2'>manage_<span class='qm-current'>{$cols}</span>_columns</td>";
			echo '</tr>';
			echo '<tr>';
			echo "<td colspan='2'>manage_<span class='qm-current'>{$col}</span>_custom_column</td>";
			echo '</tr>';
			echo '<tr>';
			echo "<td colspan='2'>manage_<span class='qm-current'>{$current_screen->id}</span>_sortable_columns</td>";
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';

	}

}

function register_qm_admin( $qm ) {
	if ( is_admin() )
		$qm['admin'] = new QM_Admin;
	return $qm;
}

add_filter( 'qm', 'register_qm_admin' );

?>