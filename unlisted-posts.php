<?php
/*
 * Plugin Name: Unlisted Posts
 * Description: Adds new post visibility option, 'Unlisted', which allows direct linking to private posts without authentication. Requires WordPress 4.4+.
 * Author: r-a-y
 * Author URI: http://profiles.wordpress.org/r-a-y
 * Version: 0.1-beta
 */

add_action( 'plugins_loaded', array( 'Ray_Unlisted_Posts', 'init' ) );

/**
 * Unlisted Posts class.
 *
 * Requires WordPress 4.4+.
 *
 * Originally based on nacin's work:
 * https://gist.github.com/nacin/4f4bc2d18a66c1eff93a
 *
 * Changes:
 * - Add ability to set "Visibility" to "Unlisted" in the admin post UI.
 * - Switch unlisted check from category to post meta instead.
 * - Unlisted posts are private by default.
 * - Fixed commenting on unlisted posts.
 * - Removed post content injection. Using dashicon in post title instead to
 *   denote unlisted status.
 * - Removed $comment_on_private property.
 */
class Ray_Unlisted_Posts {
	/**
	 * @var bool|null Internal marker to see if we should restore post back to 'private'.
	 */
	private $restore = null;

	/**
	 * Static initializer.
	 */
	static public function init() {
		return new self();
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Plugin only tested with WordPress 4.4+.
		if ( false === function_exists( 'wp_handle_comment_submission' ) ) {
			return;
		}

		// Post hook.
		add_filter( 'posts_results', array( $this, 'post_results' ), 10, 2 );

		// Comment hooks.
		add_filter( 'get_post_status',      array( $this, 'filter_post_status' ), 10, 2 );
		add_filter( 'pre_comment_approved', array( $this, 'pre_comment_approved' ), 10, 2 );

		// Admin hooks.
		add_action( 'post_submitbox_misc_actions', array( $this, 'add_visibility_option' ) );
		add_action( 'wp_insert_post',              array( $this, 'save_unlisted_option' ) );
		add_filter( 'display_post_states',         array( $this, 'filter_post_states' ), 10, 2 );

		// Privacy hook.
		add_filter( 'redirect_canonical', array( $this, 'check_for_shortlink' ), 10, 2 );
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
	public function post_results( $posts, $wp_query ) {
		// Make sure this is the main query.
		if ( $wp_query !== $GLOBALS['wp_the_query'] ) {
			return $posts;
		}

		// Add unlock dashicon to unlisted post title.
		add_filter( 'private_title_format', array( $this, 'remove_private_from_posttitle' ) );
		add_filter( 'private_title_format', array( $this, 'add_icon_to_posttitle' ) );
		add_action( 'wp_enqueue_scripts',   array( $this, 'enqueue_dashicons' ) );
		add_action( 'wp_head',              array( $this, 'inline_css' ) );

		// Need to be a single post, and not the comments feed.
		if ( ! $wp_query->is_single || $wp_query->is_feed ) {
			return $posts;
		}

		// Not unlisted? Bail!
		if ( false === self::is_unlisted( $posts[0]->ID ) ) {
			return $posts;
		}

		// Switch status temporarily to 'publish' to allow public viewing.
		$posts[0]->post_status = 'publish';

		// Add noindex, nofollow robots.
		add_filter( 'pre_option_blog_public', '__return_zero' );

		/**
		 * Hook for devs to do stuff on an unlisted post.
		 *
		 * @param Ray_Unlisted_Posts $this
		 */
		do_action( 'ray_unlisted_post_ready', $this );

		// Restore.
		add_filter( 'the_posts', array( $this, 'the_posts' ) );
		$this->restore = $posts[0];

		return $posts;
	}

	/**
	 * Filter array of retrieved posts after they've been fetched and processed.
	 *
	 * This is where we restore the unlisted post status from 'public' back to
	 * 'private'.
	 *
	 * @param  array $posts Array of retrieved posts.
	 * @return array
	 */
	public function the_posts( $posts ) {
		if ( $posts[0] === $this->restore ) {
			$posts[0]->post_status = 'private';
			$this->restore = null;
		}
		return $posts;
	}

	/**
	 * Filter the post status to allow unlisted post comment submission to work.
	 *
	 * Passes 'private' check in {@link wp_handle_comment_submission()}.
	 *
	 * @param string  $retval The post status.
	 * @param WP_Post $post   The post object.
	 */
	public function filter_post_status( $retval, $post ) {
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
		if ( false === self::is_unlisted( $post->ID ) ) {
			return $retval;
		}

		return 'publish';
	}

	/**
	 * Removes 'Private: ' from private post titles.
	 *
	 * @param  string $retval Current title format.
	 * @return string
	 */
	public function remove_private_from_posttitle( $retval ) {
		return '%s';
	}

	/**
	 * Adds unlock dashicon to the post title.
	 *
	 * @param  string $retval Current title format.
	 * @return string
	 */
	public function add_icon_to_posttitle( $retval ) {
		// Only do this once!
		if ( did_action( 'the_post' ) && is_singular() ) {
			remove_filter( 'private_title_format', array( $this, 'add_icon_to_posttitle' ) );
		}

		$title = esc_attr__( 'This post is unlisted. Only those with the link can view it.', 'unlisted-posts' );
		return '<span class="dashicons dashicons-unlock unlisted-icon" title="' . $title . '"></span>%s';
	}

	/**
	 * Enqueue Dashicons style for frontend use.
	 */
	public function enqueue_dashicons() {
		wp_enqueue_style( 'dashicons' );
	}

	/**
	 * Inline CSS for the unlock dashicon.
	 */
	public function inline_css() {
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
	function pre_comment_approved( $approved, $commentdata ) {
		if ( true === self::is_unlisted( $commentdata['comment_post_ID'] ) ) {
			$approved = 0;
		}

		return $approved;
	}

	/**
	 * Check if a post is unlisted or not.
	 *
	 * @param  int $post_id The post ID.
	 * @return bool
	 */
	public static function is_unlisted( $post_id = 0 ) {
		$unlisted = get_post_meta( $post_id, 'ray_unlisted', true );

		if ( empty( $unlisted ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Adds our 'Unlisted' option to the 'Visibility' toggle menu.
	 *
	 * Requires JS to reposition our 'Unlisted' option so it is displayed in the
	 * 'Visibility' dropdown menu. This is done due to lack of admin hooks...
	 *
	 * @param WP_Post $post
	 */
	public function add_visibility_option( $post ) {
		/**
		 * Filter if we should show the "Unlisted" visibility option or not.
		 *
		 * Return boolean true to bail.  Do checks against the $post variable.
		 *
		 * @param bool    $retval Default to false.
		 * @param WP_Post $post The post to check.
		 */
		if ( true === apply_filters( 'unlisted_posts_bail', false, $post ) ) {
			return;
		}

		$unlisted = (int) self::is_unlisted( $post->ID );
	?>

		<div id="post-visibility-unlisted" style="display:none;">
			<input type="hidden" id="ray-unlisted" name="ray_unlisted" value="<?php esc_attr_e( $unlisted ); ?>" />
			<input type="radio" name="visibility" id="visibility-radio-unlisted" value="private" <?php checked( $unlisted, 1 ); ?> /> <label for="visibility-radio-unlisted" class="selectit"><?php _e( 'Unlisted', 'unlisted-posts' ); ?></label><br />
		</div>

		<script>
		jQuery(function($){
			var unlistedElem = $('#ray-unlisted');

			<?php if ( self::is_unlisted( $post->ID ) ) : ?>
				changeVisibilityLabel();
			<?php endif; ?>

			$('#post-visibility-unlisted').insertBefore('#post-visibility-select p').show();
			$('input[name=visibility]').change(function(){
				if ( 'visibility-radio-unlisted' === $(this).prop('id') ) {
					unlistedElem.prop( 'value', 1 );
				} else {
					unlistedElem.prop( 'value', 0 );
				}
			});

			$('a.save-post-visibility').on( 'click', function( e ) {
				if ( 1 == unlistedElem.prop( 'value' ) ) {
					// Fires after WP's slideUp() visiblity JS. Pssh!
					setTimeout( function(){
						changeVisibilityLabel();
					}, 50);
				}
			});

			function changeVisibilityLabel() {
				$( '#post-visibility-display').text("<?php esc_attr_e( 'Private, Unlisted', 'unlisted-posts' ); ?>");
			}
		});
		</script>

	<?php
	}

	/**
	 * Saves our unlisted option if selected in the admin post UI.
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_unlisted_option( $post_id ) {
		if ( empty( $_POST['ray_unlisted'] ) ) {
			delete_post_meta( $post_id, 'ray_unlisted' );
		} else {
			update_post_meta( $post_id, 'ray_unlisted', 1 );
		}
	}

	/**
	 * Change private label in the admin post list table to 'Private, Unlisted'.
	 *
	 * @param  array   $retval An array of post display states.
	 * @param  WP_Post $post   The current post object.
	 * @return array
	 */
	public function filter_post_states( $retval, $post ) {
		if ( false === isset( $retval['private'] ) ) {
			return $retval;
		}

		if ( false === self::is_unlisted( $post->ID ) ) {
			return $retval;
		}

		$retval['private'] = esc_attr__( 'Private, Unlisted', 'unlisted-posts' );
		return $retval;
	}

	/**
	 * Protect against those sniffing the shortlink for unlisted posts.
	 *
	 * @param  string $retval        The redirect URL.
	 * @param  string $requested_url The requested URL.
	 * @return string
	 */
	public function check_for_shortlink( $retval, $requested_url) {
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
		if ( self::is_unlisted( $p ) ) {
			add_filter( 'wp_redirect_status', array( $this, 'set_redirect_status_to_temporary' ) );
			return trailingslashit( get_home_url() );
		}

		return $retval;
	}

	/**
	 * Set redirect status to 307, which means a temporary redirect.
	 *
	 * This overrides the default 301, which means permanent redirect.
	 *
	 * @return int
	 */
	public function set_redirect_status_to_temporary( $retval ) {
		return 307;
	}
}
