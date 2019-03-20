<?php
namespace Ray\UnlistedPosts\Classic;

add_action( 'post_submitbox_misc_actions', __NAMESPACE__ . '\\add_visibility_option' );

/**
 * Adds our 'Unlisted' option to the 'Visibility' toggle menu.
 *
 * Requires JS to reposition our 'Unlisted' option so it is displayed in the
 * 'Visibility' dropdown menu. This is done due to lack of admin hooks...
 *
 * @param WP_Post $post
 */
function add_visibility_option( $post ) {
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

	$unlisted = (int) \Ray\UnlistedPosts\is_unlisted( $post->ID );
?>

	<div id="post-visibility-unlisted" style="display:none;">
		<input type="hidden" id="ray-unlisted" name="ray_unlisted" value="<?php echo $unlisted; ?>" />
		<input type="radio" name="visibility" id="visibility-radio-unlisted" value="private" <?php checked( $unlisted, 1 ); ?> /> <label for="visibility-radio-unlisted" class="selectit"><?php _e( 'Unlisted', 'unlisted-posts' ); ?></label><br />
	</div>

	<script>
	jQuery(function($){
		var unlistedElem = $('#ray-unlisted');

		<?php if ( $unlisted ) : ?>
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