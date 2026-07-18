/*
 * Basicrum consent adapter for Borlabs Cookie v3.0.6 or newer.
 *
 * Create an enabled Borlabs service with the ID "basicrum", normally in the
 * Statistics service group. Paste this adapter as unblocked site code that runs
 * on every page after the Basicrum consent loader. A different service ID will
 * silently prevent Basicrum from receiving the intended consent decision.
 */
( function( window ) {
	'use strict';

	var serviceId = 'basicrum';

	/**
	 * Report the current decision to the Basicrum consent loader.
	 *
	 * @param {boolean} allowed Whether Borlabs allows the Basicrum service.
	 * @return {void}
	 */
	function reportDecision( allowed ) {
		var callbackName = allowed
			? 'OPT_IN_BASICRUM_LOADER_WRAPPER'
			: 'OPT_OUT_BASICRUM_LOADER_WRAPPER';

		if ( 'function' === typeof window[ callbackName ] ) {
			window[ callbackName ]();
		}
	}

	/**
	 * Read the authoritative decision from the Borlabs Cookie v3 API.
	 *
	 * @return {void}
	 */
	function synchronizeConsent() {
		var consents = window.BorlabsCookie && window.BorlabsCookie.Consents;

		if ( ! consents || 'function' !== typeof consents.hasConsent ) {
			reportDecision( false );
			return;
		}

		try {
			reportDecision( true === consents.hasConsent( serviceId ) );
		} catch ( error ) {
			// Fail closed until Borlabs can report an authoritative decision.
			reportDecision( false );
		}
	}

	window.addEventListener( 'borlabs-cookie-after-init', synchronizeConsent );
	window.addEventListener( 'borlabs-cookie-consent-saved', synchronizeConsent );

	// Also support snippets added after Borlabs has already initialized.
	synchronizeConsent();
}( window ) );
