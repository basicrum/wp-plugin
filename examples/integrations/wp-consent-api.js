( function( window, document ) {
	'use strict';

	var consentCategory = 'statistics';

	/**
	 * Report the current decision to the Basicrum consent loader.
	 *
	 * @param {boolean} allowed Whether statistics collection is allowed.
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
	 * Read the authoritative current decision from WP Consent API.
	 *
	 * @return {void}
	 */
	function synchronizeConsent() {
		if (
			'undefined' === typeof window.wp_consent_type ||
			'function' !== typeof window.wp_has_consent
		) {
			reportDecision( false );
			return;
		}

		try {
			reportDecision( true === window.wp_has_consent( consentCategory ) );
		} catch ( error ) {
			// Fail closed until WP Consent API can report an authoritative decision.
			reportDecision( false );
		}
	}

	/**
	 * React to an explicit WP Consent API category change.
	 *
	 * @param {CustomEvent} event WP Consent API change event.
	 * @return {void}
	 */
	function synchronizeChangedConsent( event ) {
		var changedConsent = event && event.detail;

		if ( ! changedConsent || ! Object.prototype.hasOwnProperty.call( changedConsent, consentCategory ) ) {
			return;
		}

		synchronizeConsent();
	}

	document.addEventListener( 'wp_consent_type_defined', synchronizeConsent );
	document.addEventListener( 'wp_listen_for_consent_change', synchronizeChangedConsent );

	// Support snippets added after the consent type has already been defined.
	synchronizeConsent();
}( window, document ) );
