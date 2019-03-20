<?php

namespace Ray\UnlistedPosts;

/**
 * Registry class.
 *
 * Holds our markers used in the plugin.
 */
class Registry {
	/**
	 * @var bool|null Internal marker to see if we should restore post back to 'private'.
	 */
	private static $restore = null;

	/**
	 * @var bool Internal marker to see if a REST dispatch request is about to be made.
	 */
	private static $is_rest_dispatch = false;

	/**
	 * Getter method.
	 *
	 * @param  string $variable Name of variable to get.
	 * @return mixed  Returns null if variable doesn't exist.
	 */
	public static function get( $variable ) {
		if ( property_exists( __CLASS__, $variable ) ) {
			return self::$$variable;
		} else {
			return null;
		}
	}

	/**
	 * Setter method.
	 *
	 * @param string $variable Name of variable to set.
	 * @param mixed  $val      Value to set.
	 */
	public static function set( $variable, $val ) {
		if ( property_exists( __CLASS__, $variable ) ) {
			self::$$variable = $val;
		}
	}
}