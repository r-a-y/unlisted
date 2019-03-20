<?php
namespace Ray\UnlistedPosts\Admin;

// Hooks.
add_filter( 'display_post_states', __NAMESPACE__ . '\\filter_post_states', 10, 2 );

/**
 * Change private label in the admin post list table to 'Private, Unlisted'.
 *
 * @param  array   $retval An array of post display states.
 * @param  WP_Post $post   The current post object.
 * @return array
 */
function filter_post_states( $retval, $post ) {
	if ( false === isset( $retval['private'] ) ) {
		return $retval;
	}

	// Not unlisted? Bail.
	if ( false === \Ray\UnlistedPosts\is_unlisted( $post->ID ) ) {
		return $retval;
	}

	$retval['private'] = esc_attr__( 'Private, Unlisted', 'unlisted-posts' );
	return $retval;
}
