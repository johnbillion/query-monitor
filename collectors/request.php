<?php
/*
Copyright 2009-2015 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Collector_Request extends QM_Collector {

	public $id = 'request';

	public function name() {
		return __( 'Request', 'query-monitor' );
	}

	public function process() {

		global $wp, $wp_query, $current_blog, $current_site;

		$qo = get_queried_object();

		if ( is_multisite() ) {
			$this->data['multisite']['current_blog'] = array(
				'title' => sprintf( 'Current blog: #%d', $current_blog->blog_id ),
				'data'  => $current_blog,
			);
		}

		if ( QM_Util::is_multi_network() ) {
			$this->data['multisite']['current_site'] = array(
				'title' => sprintf( 'Current site: #%d', $current_site->id ),
				'data'  => $current_site,
			);
		}

		if ( is_admin() ) {
			$this->data['request']['request'] = $_SERVER['REQUEST_URI'];
			foreach ( array( 'query_string' ) as $item ) {
				$this->data['request'][$item] = $wp->$item;
			}
		} else {
			foreach ( array( 'request', 'matched_rule', 'matched_query', 'query_string' ) as $item ) {
				$this->data['request'][$item] = $wp->$item;
			}
		}

		$plugin_qvars = array_flip( apply_filters( 'query_vars', array() ) );
		$qvars        = $wp_query->query_vars;
		$query_vars   = array();

		foreach ( $qvars as $k => $v ) {
			if ( isset( $plugin_qvars[$k] ) ) {
				if ( '' !== $v ) {
					$query_vars[$k] = $v;
				}
			} else {
				if ( !empty( $v ) ) {
					$query_vars[$k] = $v;
				}
			}
		}

		ksort( $query_vars );

		# First add plugin vars to $this->data['qvars']:
		foreach ( $query_vars as $k => $v ) {
			if ( isset( $plugin_qvars[$k] ) ) {
				$this->data['qvars'][$k] = $v;
				$this->data['plugin_qvars'][$k] = $v;
			}
		}

		# Now add all other vars to $this->data['qvars']:
		foreach ( $query_vars as $k => $v ) {
			if ( !isset( $plugin_qvars[$k] ) ) {
				$this->data['qvars'][$k] = $v;
			}
		}

		switch ( true ) {

			case is_null( $qo ):
				// Nada
				break;

			case is_a( $qo, 'WP_Post' ):
				// Single post
				$this->data['queried_object']['title'] = sprintf( __( 'Single %s: #%d', 'query-monitor' ),
					get_post_type_object( $qo->post_type )->labels->singular_name,
					$qo->ID
				);
				break;

			case is_a( $qo, 'WP_User' ):
				// Author archive
				$this->data['queried_object']['title'] = sprintf( __( 'Author archive: %s', 'query-monitor' ),
					$qo->user_nicename
				);
				break;

			case is_a( $qo, 'WP_Term' ):
			case property_exists( $qo, 'term_id' ):
				// Term archive
				$this->data['queried_object']['title'] = sprintf( __( 'Term archive: %s', 'query-monitor' ),
					$qo->slug
				);
				break;

			case property_exists( $qo, 'has_archive' ):
				// Post type archive
				$this->data['queried_object']['title'] = sprintf( __( 'Post type archive: %s', 'query-monitor' ),
					$qo->name
				);
				break;

			default:
				// Unknown, but we have a queried object
				$this->data['queried_object']['title'] = __( 'Unknown queried object', 'query-monitor' );
				break;

		}

		if ( ! is_null( $qo ) ) {
			$this->data['queried_object']['data'] = $qo;
		}

	}

}

function register_qm_collector_request( array $collectors, QueryMonitor $qm ) {
	$collectors['request'] = new QM_Collector_Request;
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_request', 10, 2 );
