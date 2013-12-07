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

class QM_Component_DB_Components extends QM_Component {

	var $id = 'db_components';

	function name() {
		return __( 'Queries by Component', 'query-monitor' );
	}

	function __construct() {
		parent::__construct();
	}

	function process() {

		if ( $dbq = QueryMonitor::get_component( 'db_queries' ) ) {
			if ( isset( $dbq->data['component_times'] ) ) {
				$this->data['times'] = $dbq->data['component_times'];
			}
			if ( isset( $dbq->data['types'] ) ) {
				$this->data['types'] = $dbq->data['types'];
			}
		}

	}

}

function register_qm_db_components( array $qm ) {
	$qm['db_components'] = new QM_Component_DB_Components;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_db_components', 35 );
