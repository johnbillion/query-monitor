<?php
/*
Copyright 2009-2017 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Collector_DB_Callers extends QM_Collector {

	public $id = 'db_callers';

	public function name() {
		return __( 'Queries by Caller', 'query-monitor' );
	}

	public function process() {

		if ( $dbq = QM_Collectors::get( 'db_queries' ) ) {
			if ( isset( $dbq->data['times'] ) ) {
				$this->data['times'] = $dbq->data['times'];
				QM_Util::rsort( $this->data['times'], 'ltime' );
			}
			if ( isset( $dbq->data['types'] ) ) {
				$this->data['types'] = $dbq->data['types'];
			}
		}

	}

}

function register_qm_collector_db_callers( array $collectors, QueryMonitor $qm ) {
	$collectors['db_callers'] = new QM_Collector_DB_Callers;
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_db_callers', 20, 2 );
