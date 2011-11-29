<?php

class QM_Conditionals extends QM {

	var $id = 'conditionals';
	var $conds = array();

	function __construct() {

		parent::__construct();

	}

	function admin_menus() {

		$menus = array();

		foreach ( $this->conds['true'] as $cond ) {
			$menus[] = $this->menu( array(
				'title' => $cond . '()',
				'id'    => 'query_monitor_' . $cond,
				'meta'  => array( 'class' => 'qm-true' )
			) );
		}

		return $menus;

	}

	function output() {

		echo '<table class="qm" cellspacing="0" id="' . $this->id() . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Conditionals', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $this->conds['true'] as $cond ) {
			echo '<tr class="qm-true">';
			echo '<td class="qm-ltr">' . $cond . '()</td>';
			echo '</tr>';
		}

		foreach ( $this->conds['false'] as $cond ) {
			echo '<tr class="qm-false">';
			echo '<td class="qm-ltr">' . $cond . '()</td>';
			echo '</tr>';
		}

		#foreach ( $this->conds['na'] as $cond )
		#	echo '<tr class="qm-na">';
		#	echo '<td class="qm-ltr">' . $cond . '()</td>';
		#	echo '</tr>';
		#}

		echo '</tbody>';
		echo '</table>';

	}

	function process() {

		$conds = array(
			'is_404', 'is_archive', 'is_admin', 'is_attachment', 'is_author', 'is_blog_admin', 'is_category', 'is_comments_popup',
			'is_date', 'is_day', 'is_feed', 'is_front_page', 'is_home', 'is_main_site', 'is_month', 'is_multitax', /*'is_multi_author',*/
			'is_network_admin', 'is_page', 'is_page_template', 'is_paged', 'is_post_type_archive', 'is_preview', 'is_robots', 'is_rtl',
			'is_search', 'is_single', 'is_singular', 'is_ssl', 'is_sticky', 'is_tag', 'is_tax', 'is_time', 'is_trackback', 'is_year'
		);	

		$true = $false = $na = array();

		foreach ( $conds as $cond ) {
			if ( function_exists( $cond ) ) {

				if ( ( 'is_sticky' == $cond ) and !get_post( $id = null ) ) {
					# Special case for is_sticky to prevent PHP notices
					$false[] = $cond;
				} else if ( ( 'is_main_site' == $cond ) and !is_multisite() ) {
					# Special case for is_main_site to prevent it from being annoying on single site installs
					$na[] = $cond;
				} else {
					if ( call_user_func( $cond ) )
						$true[] = $cond;
					else
						$false[] = $cond;
				}

			} else {
				$na[] = $cond;
			}
		}

		return $this->conds = compact( 'true', 'false', 'na' );

	}

}

function register_qm_conditionals( $qm ) {
	$qm['conditionals'] = new QM_Conditionals;
	return $qm;
}

add_filter( 'qm', 'register_qm_conditionals' );

?>