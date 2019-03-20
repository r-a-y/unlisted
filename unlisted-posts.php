<?php
/*
 * Plugin Name: Unlisted Posts
 * Description: Adds new post visibility option, 'Unlisted', which allows direct linking to private posts without authentication. Requires WordPress 4.4+.
 * Author: r-a-y
 * Author URI: http://profiles.wordpress.org/r-a-y
 * Version: 0.2-beta
 */

// loader.
add_action( 'plugins_loaded', function() {
	// Plugin only works with WordPress 4.4+.
	if ( false === function_exists( 'wp_handle_comment_submission' ) ) {
		return;
	}

	require_once __DIR__ . '/plugin.php';
} );

/**
 * This is for backwards-compatibility mostly.
 *
 * Will remove in a future release.
 */
add_action( 'plugins_loaded', function() {
	require_once __DIR__ . '/class.ray-unlisted-posts.php';
} );