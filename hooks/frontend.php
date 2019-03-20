<?php
namespace Ray\UnlistedPosts\Frontend;

use Ray\UnlistedPosts as App;


// Privacy hook.
add_filter( 'redirect_canonical', __NAMESPACE__ . '\\check_for_shortlink', 10, 2 );

// Post hook.
add_filter( 'posts_results', __NAMESPACE__ . '\\post_results', 10, 2 );

/**
 * Protect against those sniffing the shortlink for unlisted posts.
 *
 * @param  string $retval        The redirect URL.
 * @param  string $requested_url The requested URL.
 * @return string
 */
function check_for_shortlink( $retval, $requested_url) {
	// Check for shortlink.
	// @todo Might need some more work for custom shortlinks...
	$p = ! empty( $_GET['p'] ) ? (int) $_GET['p'] : false;
	if ( empty( $p ) ) {
		return $retval;
	}

	// Permalinks are off, so let through.
	$permalinks = get_option( 'permalink_structure' );
	if ( empty( $retval ) ) {
		return $retval;
	}

	// Permalinks are on, shortlink is being used and this post is unlisted.
	// Set redirect to homepage to prevent unlisted post sniffing.
	if ( App\is_unlisted( $p ) ) {
		add_filter( 'wp_redirect_status', function() {
			return 307;
		} );
		return trailingslashit( get_home_url() );
	}

	return $retval;
}


/**
 * Filter the raw post results array, prior to status checks.
 *
 * This allows us to manipulate unlisted posts so they can be publicly-viewed.
 *
 * @param  array    $posts    Array of queried posts.
 * @param  WP_Query $wp_query Passed by reference.
 * @return array
 */
function post_results( $posts, $wp_query ) {
	// Sanity check.
	if ( false === $wp_query->is_main_query() ) {
		return $posts;
	}

	// Add unlock dashicon to unlisted post title.
	add_filter( 'private_title_format', __NAMESPACE__ . '\\remove_private_from_posttitle' );
	add_filter( 'private_title_format', __NAMESPACE__ . '\\add_icon_to_posttitle', 10, 2 );
	add_action( 'wp_enqueue_scripts',   __NAMESPACE__ . '\\enqueue_dashicons' );
	add_action( 'wp_head',              __NAMESPACE__ . '\\inline_css' );

	// Need to be a single post, and not the comments feed.
	if ( ! $wp_query->is_singular || $wp_query->is_feed ) {
		return $posts;
	}

	// Not unlisted? Bail!
	if ( empty( $posts[0] ) || false === App\is_unlisted( $posts[0]->ID ) ) {
		return $posts;
	}

	// Switch status temporarily to 'publish' to allow public viewing.
	$posts[0]->post_status = 'publish';

	// Add noindex, nofollow robots.
	add_filter( 'pre_option_blog_public', '__return_zero' );

	/**
	 * Hook for devs to do stuff on an unlisted post.
	 *
	 * @param string $namespace String of the namespace.
	 */
	do_action( 'ray_unlisted_post_ready', __NAMESPACE__ );

	// Restore.
	add_filter( 'the_posts', __NAMESPACE__ . '\\the_posts', 10, 2 );

	require_once App\DIR . '/registry.php';
	App\Registry::set( 'restore', $posts[0] );

	return $posts;
}

/**
 * Filter array of retrieved posts after they've been fetched and processed.
 *
 * This is where we restore the unlisted post status from 'public' back to
 * 'private'.
 *
 * @param  array    $posts    Array of retrieved posts.
 * @param  WP_Query $wp_query WP_Query object.
 * @return array
 */
function the_posts( $posts, $wp_query ) {
	// Make sure this is the main query.
	if ( false === $wp_query->is_main_query() ) {
		return $posts;
	}

	// Sanity check.
	require_once App\DIR . '/registry.php';

	if ( $posts[0] === App\Registry::get( 'restore' ) ) {
		$posts[0]->post_status = 'private';
		App\Registry::set( 'restore', null );
	}
	return $posts;
}

/**
 * Removes 'Private: ' from private post titles.
 *
 * @param  string $retval Current title format.
 * @return string
 */
function remove_private_from_posttitle( $retval ) {
	return '%s';
}

/**
 * Adds unlock dashicon to the post title.
 *
 * @param  string  $retval Current title format.
 * @param  WP_Post $post   Current WP post object.
 * @return string
 */
function add_icon_to_posttitle( $retval, $post ) {
	// Only do this once!
	if ( did_action( 'the_post' ) && is_singular() ) {
		remove_filter( 'private_title_format', __NAMESPACE__ . '\\add_icon_to_posttitle' );
	}

	if ( App\is_unlisted( $post->ID ) ) {
		$class = 'unlock';
		$title = esc_attr__( 'This post is unlisted. Only those with the link can view it.', 'unlisted-posts' );
	} else {
		$class = 'lock';
		$title = esc_attr__( 'This post is private. Only you can view this post.', 'unlisted-posts' );
	}

	return '<span class="dashicons unlisted-icon dashicons-' . $class . '" title="' . $title . '"></span>%s';
}

/**
 * Enqueue Dashicons style for frontend use.
 */
function enqueue_dashicons() {
	wp_enqueue_style( 'dashicons' );
}

/**
 * Inline CSS for the unlock dashicon.
 */
function inline_css() {
?>

	<style type="text/css">
	.unlisted-icon {
		font-size: 80%;
		width: auto;
		height: auto;
		padding-right: .2em;
		vertical-align: baseline;
	}
	</style>

<?php
}