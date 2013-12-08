<?php
/*
Copyright 2013 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Collector_Transients extends QM_Collector {

	var $id = 'transients';

	function name() {
		return __( 'Transients', 'query-monitor' );
	}

	function __construct() {
		parent::__construct();
		# See http://core.trac.wordpress.org/ticket/24583
		add_action( 'setted_site_transient', array( $this, 'setted_site_transient' ), 10, 3 );
		add_action( 'setted_transient',      array( $this, 'setted_blog_transient' ), 10, 3 );
	}

	function setted_site_transient( $transient, $value = null, $expiration = null ) {
		$this->setted_transient( $transient, 'site', $value, $expiration );
	}

	function setted_blog_transient( $transient, $value = null, $expiration = null ) {
		$this->setted_transient( $transient, 'blog', $value, $expiration );
	}

	function setted_transient( $transient, $type, $value = null, $expiration = null ) {
		$trace = new QM_Backtrace( array(
			'ignore_items' => 1 # Ignore the setted_(site|blog)_transient method
		) );
		$this->data['trans'][] = array(
			'transient'  => $transient,
			'trace'      => $trace,
			'type'       => $type,
			'value'      => $value,
			'expiration' => $expiration,
		);
	}

}

function register_qm_transients( array $qm ) {
	$qm['transients'] = new QM_Collector_Transients;
	return $qm;
}

add_filter( 'query_monitor_collectors', 'register_qm_transients', 90 );
