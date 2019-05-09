( function( wp ) {
	var registerPlugin = wp.plugins.registerPlugin;
	var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
	var el = wp.element.createElement;
	var __ = wp.i18n.__;

	var checkBox = wp.components.CheckboxControl;
	var withSelect = wp.data.withSelect;
	var withDispatch = wp.data.withDispatch;
	var subscribe = wp.data.subscribe;
	var compose = wp.compose.compose;

	// If visibility is not 'private', remove our unlisted marker.
	// TODO: subscribe runs far too often, this is a Gutenberg thing though...
	subscribe( function() {
		var newVisibility = wp.data.select( 'core/editor' ).getEditedPostVisibility();
		var unlisted = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'meta' );

		// data.subscribe runs too early for meta to be set early, so set defaults.
		if ( typeof unlisted === 'undefined' ) {
			unlisted = false;
		}
		if ( ! unlisted.hasOwnProperty( 'ray_unlisted' ) ) {
			unlisted = false;
		} else {
			unlisted = unlisted.ray_unlisted;
		}

		/*
		 * Only remove our unlisted postmeta if visibility is not private and our
		 * unlisted postmeta was previously true.
		 */
		if ( typeof newVisibility !== 'undefined' && 'private' !== newVisibility && true === unlisted ) {
			wp.data.dispatch( 'core/editor' ).editPost( {
				meta: { ['ray_unlisted']: false }
			} );
		}

	} );

	var metaCheckbox = compose(
		withDispatch( function( dispatch, props ) {
			return {
				setMetaFieldValue: function( value ) {
					dispatch( 'core/editor' ).editPost(
						{ meta: { [ props.fieldName ]: value } }
					);
				}
			}
		} ),
		withSelect( function( select, props ) {
			return {
				metaFieldValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.fieldName ],
			}
		} )

	)( function( props ) {
		// https://github.com/WordPress/gutenberg/tree/master/packages/components/src/checkbox-control
		return el( checkBox, {
			label: props.label,
			checked: props.metaFieldValue,
			onChange: function( retVal ) {
				props.updater( retVal );
				props.setMetaFieldValue( retVal );
			},
		} );
	} );

	var unlistedUpdater = function( retVal ) {
		// If unlisted, switch post status to 'private'.
		if ( retVal ) {
			wp.data.dispatch( 'core/editor' ).editPost( { status: 'private' } );
		}
	};

	registerPlugin( 'ray-unlisted-posts', {
		render: function() {
			return el( PluginPostStatusInfo, {
				className: 'ray-unlisted'
			},
				el( metaCheckbox, {
					label: __( 'Unlisted?' ),
					fieldName: 'ray_unlisted',
					updater: unlistedUpdater
				} )
			);
		},
	} );
} )( window.wp );
