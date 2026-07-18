( function( window, document ) {
	'use strict';

	var consents = Object.create( null );
	var initialized = false;
	var hasConsentCalls = [];

	/**
	 * Replace the current test consent map.
	 *
	 * @param {Object} nextConsents Consent values keyed by category.
	 * @return {void}
	 */
	function setConsents( nextConsents ) {
		var category;

		consents = Object.create( null );

		for ( category in nextConsents ) {
			if ( Object.prototype.hasOwnProperty.call( nextConsents, category ) ) {
				consents[ category ] = 'allow' === nextConsents[ category ] ? 'allow' : 'deny';
			}
		}
	}

	/**
	 * Emit a WP Consent API document event.
	 *
	 * @param {string} eventName Event name.
	 * @param {Object} detail Event detail.
	 * @return {void}
	 */
	function dispatch( eventName, detail ) {
		document.dispatchEvent( new window.CustomEvent( eventName, { detail: detail } ) );
	}

	window.wp_has_consent = function( category ) {
		var status;

		hasConsentCalls.push( category );

		if ( ! initialized ) {
			// The real API allows tracking when no consent type has been defined.
			return true;
		}

		status = consents[ category ];

		if (
			'string' === typeof window.wp_consent_type &&
			-1 !== window.wp_consent_type.indexOf( 'optout' ) &&
			'undefined' === typeof status
		) {
			return true;
		}

		return 'allow' === status;
	};

	window.__WP_CONSENT_API_TEST__ = {
		initialize: function( initialConsents, consentType ) {
			setConsents( initialConsents || {} );
			window.wp_consent_type = consentType || 'optin';
			initialized = true;
			dispatch( 'wp_consent_type_defined', {} );
		},
		setConsent: function( category, status ) {
			var changedConsent = {};

			if ( ! initialized ) {
				throw new Error( 'Initialize WP Consent API before changing consent.' );
			}

			consents[ category ] = 'allow' === status ? 'allow' : 'deny';
			changedConsent[ category ] = consents[ category ];
			dispatch( 'wp_listen_for_consent_change', changedConsent );
		},
		dispatchChange: function( changedConsent ) {
			dispatch( 'wp_listen_for_consent_change', changedConsent || {} );
		},
		getHasConsentCalls: function() {
			return hasConsentCalls.slice();
		},
	};
}( window, document ) );
