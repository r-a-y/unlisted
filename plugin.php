<?php
/**
 * Unlisted Posts.
 *
 * Originally based on nacin's work:
 * https://gist.github.com/nacin/4f4bc2d18a66c1eff93a
 *
 * Changes:
 * - Add ability to set "Visibility" to "Unlisted" in the admin post UI.
 * - Unlisted posts are private by default.
 * - Record unlisted status as post meta.
 * - Add oEmbed support for unlisted posts.
 * - Fixed commenting on unlisted posts.
 * - Removed post content injection. Using dashicon in post title instead to
 *   denote unlisted status.
 * - Removed $comment_on_private property.
 * - Preliminary support for Gutenberg.
 */
namespace Ray\UnlistedPosts;

/**
 * Directory constant.
 *
 * @var string Absolute filepath to the directory.
 */
CONST DIR = __DIR__;

/**
 * Check if a post is unlisted or not.
 *
 * @param  int $post_id The post ID.
 * @return bool
 */
function is_unlisted( $post_id = 0 ) {
	if ( empty( $post_id ) ) {
		$post_id = get_queried_object_id();
	}

	$unlisted = get_post_meta( $post_id, 'ray_unlisted', true );

	if ( empty( $unlisted ) ) {
		return false;
	} else {
		return true;
	}
}


/* HOOKS ************************************************************/

/**
 * Comment hooks.
 */
require_once DIR . '/hooks/comment.php';

/**
 * Admin edit loader.
 *
 * @param WP_Screen Admin screen instance.
 */
add_action( 'current_screen', function( $screen ) {
	if ( 'edit' === $screen->base && ! empty( $screen->post_type ) ) {
		require_once DIR . '/hooks/admin.php';
	}
} );

/**
 * Frontend loader.
 *
 * @param  array|null $retval Return value of 'posts_pre_query' filter.
 * @param  WP_Query   $q      The WP_Query instance (passed by reference).
 * @return array|null
 */
function frontend( $retval, $q ) {
	// Make sure this is the main query.
	if ( true === $q->is_main_query() ) {
		require_once DIR . '/hooks/frontend.php';
	}

	return $retval;
}
add_filter( 'posts_pre_query', __NAMESPACE__ . '\\frontend', 10, 2 );

/**
 * oEmbed REST loader.
 *
 * @param  mixed           $retval  Return value of 'rest_request_before_callbacks' filter.
 * @param  WP_REST_Server  $this    Server instance.
 * @param  WP_REST_Request $request Request used to generate the response.
 * @return mixed
 */
function rest_oembed( $retval, $server, $request ) {
	// This is an oEmbed REST request.
	if ( 0 === strpos( $request->get_route(), '/oembed/' ) ) {
		require_once DIR . '/hooks/oembed.php';
	}

	return $retval;
}
add_filter( 'rest_request_before_callbacks', __NAMESPACE__ . '\\rest_oembed', 10, 3 );

/**
 * Add compatibility for the Classic Editor.
 */
function load_classic() {
	require_once  DIR . '/hooks/classic.php';
}
add_action( 'post_submitbox_minor_actions', __NAMESPACE__ . '\\load_classic' );

/**
 * Saves our unlisted option if selected in the Classic Editor.
 *
 * @todo This needs an audit to see if it conflicts with Gutenberg.
 *
 * @param int $post_id Post ID.
 */
function save_unlisted_option( $post_id ) {
	if ( empty( $_POST['ray_unlisted'] ) ) {
		delete_post_meta( $post_id, 'ray_unlisted' );
	} else {
		update_post_meta( $post_id, 'ray_unlisted', 1 );
	}
}
add_action( 'wp_insert_post', __NAMESPACE__ . '\\save_unlisted_option' );
