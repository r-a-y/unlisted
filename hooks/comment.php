<?php
namespace Ray\UnlistedPosts\Comment;

use Ray\UnlistedPosts as App;

// Hooks.
add_filter( 'get_post_status',      __NAMESPACE__ . '\\filter_post_status',   10, 2 );
add_filter( 'pre_comment_approved', __NAMESPACE__ . '\\pre_approved', 10, 2 );

/**
 * Filter the post status to allow unlisted post comment submission to work.
 *
 * Passes 'private' check in {@link wp_handle_comment_submission()}.
 *
 * @param string  $retval The post status.
 * @param WP_Post $post   The post object.
 */
function filter_post_status( $retval, $post ) {
	// Rudimentary comment post submission checks.
	if ( empty( $_POST['comment_post_ID'] ) ) {
		return $retval;
	}
	// Whee! Check if current URI is the wp-comments-post.php script.
	// @link http://stackoverflow.com/a/2137556
	if ( 0 !== strpos( strrev( $_SERVER['REQUEST_URI'] ), 'php.tsop-stnemmoc-pw' ) ) {
		return $retval;
	}

	// Check if post is unlisted.
	if ( false === App\is_unlisted( $post->ID ) ) {
		return $retval;
	}

	return 'publish';
}

/**
 * Force comment approval to 0 for unlisted posts.
 *
 * Comments on unlisted posts requires moderation by the post author.
 *
 * @todo This is left over from nacin's code. Subject to removal after UX audit.
 *
 * @param  int|string $approved    Either '1', '0' or 'spam'.
 * @param  array      $commentdata Comment data.
 * @return int|string
 */
function pre_approved( $approved, $commentdata ) {
	if ( true === App\is_unlisted( $commentdata['comment_post_ID'] ) ) {
		$approved = 0;
	}

	return $approved;
}