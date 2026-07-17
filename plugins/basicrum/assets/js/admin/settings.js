( function() {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function() {
		var enabled = document.getElementById( 'basicrum_enabled' );
		var form = enabled ? enabled.closest( 'form' ) : null;
		var fieldIds = [ 'basicrum_beacon_url', 'basicrum_brum_site_id' ];
		var requiredFields = fieldIds.map( function( fieldId ) {
			return document.getElementById( fieldId );
		} ).filter( Boolean );
		var dependentFields = form ? Array.from( form.querySelectorAll( 'input, select, textarea' ) ).filter( function( field ) {
			return field !== enabled && 'hidden' !== field.type && 0 === field.name.indexOf( 'basicrum_settings[' );
		} ) : [];
		var preservedValues = form ? Array.from( form.querySelectorAll( '.basicrum-disabled-setting-value' ) ) : [];

		if ( ! enabled || ! form || ! requiredFields.length ) {
			return;
		}

		function setInvalidState( field, isInvalid ) {
			var row = field.closest( 'tr' );
			var error = document.getElementById( field.id + '_error' );
			var errorIcon = document.getElementById( field.id + '_error_icon' );

			field.setAttribute( 'aria-invalid', isInvalid ? 'true' : 'false' );

			if ( row ) {
				row.classList.toggle( 'form-invalid', isInvalid );
			}

			if ( error ) {
				error.hidden = ! isInvalid;
			}

			if ( errorIcon ) {
				errorIcon.hidden = ! isInvalid;
			}
		}

		function syncField( field, showInvalid ) {
			var isRequired = enabled.checked;
			var isEmpty = '' === field.value.trim();
			var row = field.closest( 'tr' );

			field.required = isRequired;
			field.setAttribute( 'aria-required', isRequired ? 'true' : 'false' );
			field.setCustomValidity( isRequired && isEmpty ? field.dataset.requiredMessage : '' );

			field.classList.toggle( 'form-required', isRequired );

			setInvalidState( field, isRequired && isEmpty && showInvalid );
		}

		function syncAvailability() {
			var isEnabled = enabled.checked;

			dependentFields.forEach( function( field ) {
				field.disabled = ! isEnabled;
				field.setAttribute( 'aria-disabled', isEnabled ? 'false' : 'true' );
			} );

			preservedValues.forEach( function( field ) {
				field.disabled = isEnabled;
			} );
		}

		syncAvailability();

		requiredFields.forEach( function( field ) {
			var showInvalid = 'true' === field.getAttribute( 'aria-invalid' );

			syncField( field, showInvalid );

			field.addEventListener( 'invalid', function() {
				syncField( field, true );
			} );

			field.addEventListener( 'input', function() {
				syncField( field, 'true' === field.getAttribute( 'aria-invalid' ) );
			} );
		} );

		enabled.addEventListener( 'change', function() {
			syncAvailability();

			requiredFields.forEach( function( field ) {
				syncField( field, false );
			} );
		} );

		form.addEventListener( 'submit', function() {
			if ( enabled.checked ) {
				return;
			}

			preservedValues.forEach( function( field ) {
				field.disabled = true;
			} );

			dependentFields.forEach( function( field ) {
				field.disabled = false;
			} );
		} );
	} );
}() );
