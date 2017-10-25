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

class QM_Collector_Rewrites extends QM_Collector {

	public $id = 'rewrites';

	public function name() {
		return __( 'Rewrite Rules', 'query-monitor' );
	}

	public function process() {

		global $wp_rewrite;

		if ( is_admin() or QM_Util::is_async() ) {
			return;
		}

		if ( ! $request = QM_Collectors::get( 'request' ) ) {
			return;
		}

		if ( empty( $wp_rewrite->rules ) ) {
			return;
		}

		$req = $request->data['request']['request'];
		$matching = array();

		foreach ( $wp_rewrite->rules as $match => $query ) {
			if ( preg_match( "#^{$match}#", $req ) ) {
				$matching[ $match ] = $query;
			}
		}

		$this->data['matching'] = $matching;

	}

}

function register_qm_collector_rewrites( array $collectors, QueryMonitor $qm ) {
	$collectors['rewrites'] = new QM_Collector_Rewrites;
	return $collectors;
}

if ( ! is_admin() ) {
	add_filter( 'qm/collectors', 'register_qm_collector_rewrites', 11, 2 );
}
