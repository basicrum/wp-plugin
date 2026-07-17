( function( window ) {
	'use strict';

	var consents = Object.create( null );
	var initialized = false;
	var hasConsentCalls = [];

	/**
	 * Replace the current test consent map.
	 *
	 * @param {Object} nextConsents Consent values keyed by service ID.
	 * @return {void}
	 */
	function setConsents( nextConsents ) {
		var serviceId;

		consents = Object.create( null );

		for ( serviceId in nextConsents ) {
			if ( Object.prototype.hasOwnProperty.call( nextConsents, serviceId ) ) {
				consents[ serviceId ] = true === nextConsents[ serviceId ];
			}
		}
	}

	/**
	 * Emit one of Borlabs Cookie's documented lifecycle events.
	 *
	 * @param {string} eventName Event name.
	 * @return {void}
	 */
	function dispatch( eventName ) {
		window.dispatchEvent( new window.Event( eventName ) );
	}

	window.BorlabsCookie = {
		Consents: {
			hasConsent: function( serviceId, serviceGroupId ) {
				hasConsentCalls.push( {
					serviceId: serviceId,
					serviceGroupId: serviceGroupId,
				} );

				if ( ! initialized ) {
					throw new Error( 'Borlabs Cookie is not initialized.' );
				}

				return true === consents[ serviceId ];
			},
		},
	};

	window.__BORLABS_COOKIE_TEST__ = {
		initialize: function( initialConsents ) {
			setConsents( initialConsents || {} );
			initialized = true;
			dispatch( 'borlabs-cookie-after-init' );
		},
		saveConsent: function( serviceId, allowed ) {
			if ( ! initialized ) {
				throw new Error( 'Initialize Borlabs Cookie before saving consent.' );
			}

			consents[ serviceId ] = true === allowed;
			dispatch( 'borlabs-cookie-consent-saved' );
		},
		dispatchConsentSaved: function() {
			dispatch( 'borlabs-cookie-consent-saved' );
		},
		getHasConsentCalls: function() {
			return hasConsentCalls.slice();
		},
	};
}( window ) );
