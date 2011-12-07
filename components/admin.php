<?php

class QM_Admin extends QM {

	var $id = 'admin';

	function __construct() {
		parent::__construct();
		add_filter( 'current_screen',      array( $this, 'current_screen' ), 99 );
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 90 );
	}

	function current_screen( $screen ) {
		if ( empty( $this->data['admin'] ) )
			$this->data['admin'] = wp_clone( $screen );
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

			$this->data['base'] = $page_hook;

		} else {
			if ( isset( $_GET['page'] ) )
				$this->data['base'] = $current_screen->base;
			else
				$this->data['base'] = $pagenow;
		}

		if ( !isset( $this->data['admin'] ) )
			$this->data['admin'] = __( 'n/a', 'query_monitor' );

		$this->data['pagenow'] = $pagenow;
		$this->data['current_screen'] = $current_screen;

	}

	function admin_menu( $menu ) {

		$menu[] = $this->menu( array(
			'title' => sprintf( __( 'Admin Screen: %s', 'query_monitor' ), $this->data['base'] )
		) );
		return $menu;

	}

	function output( $args, $data ) {

		$post_type_warning = '';

		echo '<table class="qm" cellspacing="0" id="' . $args['id'] . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="3">' . __( 'Admin', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td rowspan="2">' . __( 'Variables', 'query_monitor' ) . '</td>';
		echo '<td class="qm-ltr">$current_screen</td>';
		echo '<td>';

		if ( is_object( $data['admin'] ) ) {
			echo '<table class="qm-inner" cellspacing="0">';
			echo '<tbody>';
			foreach ( $data['admin'] as $key => $value ) {
				echo '<tr>';
				echo "<td class='qm-var'>{$key}:</td>";
				echo '<td>';
				echo $value;
				if ( !empty( $value ) and ( $data['current_screen']->$key != $value ) )
					echo $post_type_warning = '&nbsp;(<a href="http://core.trac.wordpress.org/ticket/14886" class="qm-warn" title="' . esc_attr__( 'This value may not be as expected. Please see WordPress bug #14886.', 'query_monitor' ) . '" target="_blank">!</a>)';
				echo '</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table>';
		} else {
			echo $data['admin'];
		}

		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td class="qm-ltr">$pagenow</td>';
		echo "<td>{$data['pagenow']}</td>";
		echo '</tr>';

		if ( !empty( $data['current_screen'] ) and in_array( $data['current_screen']->base, array( 'edit', 'edit-comments', 'edit-tags', 'link-manager', 'plugins', 'plugins-network', 'sites-network', 'themes-network', 'upload', 'users', 'users-network' ) ) ) {

			# And now, WordPress' legendary inconsistency comes into play:

			if ( !empty( $data['current_screen']->taxonomy ) )
				$col = $data['current_screen']->taxonomy;
			else if ( !empty( $data['current_screen']->post_type ) )
				$col = $data['current_screen']->post_type . '_posts';
			else
				$col = $data['current_screen']->base;

			if ( !empty( $data['current_screen']->post_type ) )
				$cols = $data['current_screen']->post_type . '_posts';
			else
				$cols = $data['current_screen']->id;

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
			echo "<td colspan='2'>manage_<span class='qm-current'>{$data['current_screen']->id}</span>_sortable_columns</td>";
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

add_filter( 'query_monitor_components', 'register_qm_admin', 50 );

?>