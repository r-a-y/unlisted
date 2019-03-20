<?php
namespace Ray\UnlistedPosts\OEmbed;

use Ray\UnlistedPosts as App;

add_filter( 'rest_dispatch_request', __NAMESPACE__ . '\\set_marker_before_dispatch', 10, 2 );
add_filter( 'get_post_status',       __NAMESPACE__ . '\\pass_post_status_checks', 10, 2 );
add_filter( 'oembed_response_data',  __NAMESPACE__ . '\\response_data' );

/**
 * Determine if we're doing a embed dispatch.
 *
 * @param null|mixed      $retval  Dispatch result, will be used if not empty.
 * @param WP_REST_Request $request Request used to generate the response.
 * @return null|mixed
 */
function set_marker_before_dispatch( $retval, $request ) {
	// Not an oEmbed REST request, so bail.
	if ( 0 !== strpos( $request->get_route(), '/oembed/' ) ) {
		return $retval;
	}

	// Not a post embed attempt, so bail.
	if ( 0 !== substr_compare( $request->get_route(), 'embed', -5 ) ) {
		return $retval;
	}

	// Set our rest dispatch property to true.
	require_once App\DIR . '/registry.php';
	App\Registry::set( 'is_rest_dispatch', true );

	return $retval;
}

/**
 * Force post status checks to 'publish' during unlisted post embed attempts.
 *
 * This passes the url_to_postid() and get_oembed_response_data() checks.
 *
 * @param  string  $retval Current post status.
 * @param  WP_Post $post   Current WP post object.
 * @return string
 */
function pass_post_status_checks( $retval, $post ) {
	// Sanity check.
	require_once App\DIR . '/registry.php';

	// We're not running a REST API request, so bail.
	if ( false === App\Registry::get( 'is_rest_dispatch' ) ) {
		return $retval;
	}

	// Don't do anything if not an unlisted post.
	if ( false === App\is_unlisted( $post->ID ) ) {
		return $retval;
	}

	// Force to 'publish'.
	return 'publish';
}

/**
 * Remove our hackery after the oEmbed response is done!
 *
 * @param  array $retval The oEmbed response data.
 * @return array
 */
function response_data( $retval ) {
	remove_filter( 'get_post_status', __NAMESPACE__ . '\\pass_post_status_checks', 10, 2 );
	return $retval;
}