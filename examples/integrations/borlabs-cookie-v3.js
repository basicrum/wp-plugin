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
