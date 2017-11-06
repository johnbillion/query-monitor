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

class QM_Collector_Request extends QM_Collector {

	public $id = 'request';

	public function name() {
		return __( 'Request', 'query-monitor' );
	}

	public function process() {

		global $wp, $wp_query, $current_blog, $current_site;

		$qo = get_queried_object();
		$user = wp_get_current_user();

		if ( $user->exists() ) {
			$user_title = sprintf(
				/* translators: %d: User ID */
				__( 'Current user: #%d', 'query-monitor' ),
				$user->ID
			);
		} else {
			/* translators: No user */
			$user_title = _x( 'None', 'user', 'query-monitor' );
		}

		$this->data['user'] = array(
			'title' => $user_title,
			'data'  => ( $user->exists() ? $user : false ),
		);

		if ( is_multisite() ) {
			$this->data['multisite']['current_blog'] = array(
				'title' => sprintf(
					/* translators: %d: Blog ID */
					__( 'Current blog: #%d', 'query-monitor' ),
					$current_blog->blog_id
				),
				'data'  => $current_blog,
			);
		}

		if ( QM_Util::is_multi_network() ) {
			$this->data['multisite']['current_site'] = array(
				'title' => sprintf(
					/* translators: %d: Site ID */
					__( 'Current site: #%d', 'query-monitor' ),
					$current_site->id
				),
				'data'  => $current_site,
			);
		}

		if ( is_admin() ) {
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$this->data['request']['request'] = wp_unslash( $_SERVER['REQUEST_URI'] ); // @codingStandardsIgnoreLine
			} else {
				$this->data['request']['request'] = '';
			}
			foreach ( array( 'query_string' ) as $item ) {
				$this->data['request'][ $item ] = $wp->$item;
			}
		} else {
			foreach ( array( 'request', 'matched_rule', 'matched_query', 'query_string' ) as $item ) {
				$this->data['request'][ $item ] = $wp->$item;
			}
		}

		$plugin_qvars = array_flip( apply_filters( 'query_vars', array() ) );
		$qvars        = $wp_query->query_vars;
		$query_vars   = array();

		foreach ( $qvars as $k => $v ) {
			if ( isset( $plugin_qvars[ $k ] ) ) {
				if ( '' !== $v ) {
					$query_vars[ $k ] = $v;
				}
			} else {
				if ( ! empty( $v ) ) {
					$query_vars[ $k ] = $v;
				}
			}
		}

		ksort( $query_vars );

		# First add plugin vars to $this->data['qvars']:
		foreach ( $query_vars as $k => $v ) {
			if ( isset( $plugin_qvars[ $k ] ) ) {
				$this->data['qvars'][ $k ] = $v;
				$this->data['plugin_qvars'][ $k ] = $v;
			}
		}

		# Now add all other vars to $this->data['qvars']:
		foreach ( $query_vars as $k => $v ) {
			if ( ! isset( $plugin_qvars[ $k ] ) ) {
				$this->data['qvars'][ $k ] = $v;
			}
		}

		switch ( true ) {

			case is_null( $qo ):
				// Nada
				break;

			case is_a( $qo, 'WP_Post' ):
				// Single post
				$this->data['queried_object']['title'] = sprintf(
					/* translators: 1: Post type name, 2: Post ID */
					__( 'Single %1$s: #%2$d', 'query-monitor' ),
					get_post_type_object( $qo->post_type )->labels->singular_name,
					$qo->ID
				);
				break;

			case is_a( $qo, 'WP_User' ):
				// Author archive
				$this->data['queried_object']['title'] = sprintf(
					/* translators: %s: Author name */
					__( 'Author archive: %s', 'query-monitor' ),
					$qo->user_nicename
				);
				break;

			case is_a( $qo, 'WP_Term' ):
			case property_exists( $qo, 'term_id' ):
				// Term archive
				$this->data['queried_object']['title'] = sprintf(
					/* translators: %s: Taxonomy term name */
					__( 'Term archive: %s', 'query-monitor' ),
					$qo->slug
				);
				break;

			case is_a( $qo, 'WP_Post_Type' ):
			case property_exists( $qo, 'has_archive' ):
				// Post type archive
				$this->data['queried_object']['title'] = sprintf(
					/* translators: %s: Post type name */
					__( 'Post type archive: %s', 'query-monitor' ),
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

		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			$this->data['request_method'] = strtoupper( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ); // @codingStandardsIgnoreLine
		} else {
			$this->data['request_method'] = '';
		}

	}

}

function register_qm_collector_request( array $collectors, QueryMonitor $qm ) {
	$collectors['request'] = new QM_Collector_Request;
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_request', 10, 2 );
