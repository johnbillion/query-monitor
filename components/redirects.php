<?php

class QM_Component_Redirects extends QM_Component {

	var $id = 'redirects';

	function __construct() {
		parent::__construct();
		add_filter( 'wp_redirect', array( $this, 'filter_wp_redirect' ), 999, 2 );
	}

	public function filter_wp_redirect( $location, $status ) {

		global $querymonitor;

		if ( !$location )
			return $location;
		if ( !$querymonitor->show_query_monitor() )
			return $location;
		if ( headers_sent() )
			return $location;

		$trace = QM_Backtrace::backtrace();
		unset( $trace[0] ); # wp_redirect filter

		header( sprintf( 'X-QM-Redirect-Trace: %s',
			implode( ', ', $trace )
		) );

		return $location;

	}

}

function register_qm_redirects( array $qm ) {
	$qm['redirects'] = new QM_Component_Redirects;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_redirects', 140 );
