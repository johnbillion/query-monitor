<?php
/**
 * Request collector.
 *
 * @package query-monitor
 */

class QM_Collector_Request extends QM_Collector {

	public $id = 'request';

	public function name() {
		return __( 'Request', 'query-monitor' );
	}

	public function process() {

		global $wp, $wp_query, $current_blog, $current_site, $wp_rewrite;

		$qo   = get_queried_object();
		$user = wp_get_current_user();

		if ( $user->exists() ) {
			$user_title = sprintf(
				/* translators: %d: User ID */
				__( 'Current User: #%d', 'query-monitor' ),
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
			$this->data['multisite']['current_site'] = array(
				'title' => sprintf(
					/* translators: %d: Multisite site ID */
					__( 'Current Site: #%d', 'query-monitor' ),
					$current_blog->blog_id
				),
				'data'  => $current_blog,
			);
		}

		if ( QM_Util::is_multi_network() ) {
			$this->data['multisite']['current_network'] = array(
				'title' => sprintf(
					/* translators: %d: Multisite network ID */
					__( 'Current Network: #%d', 'query-monitor' ),
					$current_site->id
				),
				'data'  => $current_site,
			);
		}

		if ( is_admin() ) {
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$home_path = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
				$request   = wp_unslash( $_SERVER['REQUEST_URI'] ); // @codingStandardsIgnoreLine

				$this->data['request']['request'] = str_replace( "/{$home_path}/", '', $request );
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

		/** This filter is documented in wp-includes/class-wp.php */
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
				$this->data['qvars'][ $k ]        = $v;
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

			case ! is_object( $qo ):
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

		if ( $qo ) {
			$this->data['queried_object']['data'] = $qo;
		}

		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			$this->data['request_method'] = strtoupper( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ); // @codingStandardsIgnoreLine
		} else {
			$this->data['request_method'] = '';
		}

		if ( is_admin() || QM_Util::is_async() || empty( $wp_rewrite->rules ) ) {
			return;
		}

		$matching = array();

		foreach ( $wp_rewrite->rules as $match => $query ) {
			if ( preg_match( "#^{$match}#", $this->data['request']['request'] ) ) {
				$matching[ $match ] = $query;
			}
		}

		$this->data['matching_rewrites'] = $matching;
	}

}

function register_qm_collector_request( array $collectors, QueryMonitor $qm ) {
	$collectors['request'] = new QM_Collector_Request();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_request', 10, 2 );
