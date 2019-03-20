<?php

/**
 * Legacy Unlisted Posts class.
 *
 * Kept for backwards compatibility for those using the is_unlisted() method.
 * Will remove in a future release.
 *
 * @deprecated
 */
class Ray_Unlisted_Posts {
	/**
	 * Static initializer.
	 */
	public static function init() {
		return new self();
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

	/**
	 * Check if a post is unlisted or not.
	 *
	 * @param  int $post_id The post ID.
	 * @return bool
	 */
	public static function is_unlisted( $post_id = 0 ) {
		return \Ray\UnlistedPosts\is_unlisted( $post_id );
	}
}