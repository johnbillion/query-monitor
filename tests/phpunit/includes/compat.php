<?php
/*
 * Because we are using the WordPress tests from trunk, we may occasionally
 * find functions are called which are not included in the version of
 * WordPress which we are testing against. This file conditionally
 * defines these functions so that the tests will not fatal.
 *
 * A function can be removed from this file once the minimum version we
 * test against is greater than the `@since` of the function.
 */

if ( ! function_exists( 'is_post_type_viewable' ) ) :
/**
 * Determines whether a post type is considered "viewable".
 *
 * For built-in post types such as posts and pages, the 'public' value will be evaluated.
 * For all others, the 'publicly_queryable' value will be used.
 *
 * @since 4.4.0
 *
 * @param object $post_type_object Post type object.
 * @return bool Whether the post type should be considered viewable.
 */
function is_post_type_viewable( $post_type_object ) {
	return $post_type_object->publicly_queryable || ( $post_type_object->_builtin && $post_type_object->public );
}
endif;

if ( ! function_exists( 'wp_installing' ) ) :
/**
 * Check or set whether WordPress is in "installation" mode.
 *
 * If the `WP_INSTALLING` constant is defined during the bootstrap, `wp_installing()` will default to `true`.
 *
 * @since 4.4.0
 *
 * @staticvar bool $installing
 *
 * @param bool $is_installing Optional. True to set WP into Installing mode, false to turn Installing mode off.
 *                            Omit this parameter if you only want to fetch the current status.
 * @return bool True if WP is installing, otherwise false. When a `$is_installing` is passed, the function will
 *              report whether WP was in installing mode prior to the change to `$is_installing`.
 */
function wp_installing( $is_installing = null ) {
	static $installing = null;

	// Support for the `WP_INSTALLING` constant, defined before WP is loaded.
	if ( is_null( $installing ) ) {
		$installing = defined( 'WP_INSTALLING' ) && WP_INSTALLING;
	}

	if ( ! is_null( $is_installing ) ) {
		$old_installing = $installing;
		$installing = $is_installing;
		return (bool) $old_installing;
	}

	return (bool) $installing;
}
endif;
