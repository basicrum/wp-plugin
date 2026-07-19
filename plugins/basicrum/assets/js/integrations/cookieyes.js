/*
 * Basicrum consent adapter for a connected CookieYes installation.
 *
 * Use this direct adapter only when WP Consent API is not active. It follows
 * CookieYes's Analytics category, not Performance or another category.
 * Basicrum can load this packaged adapter automatically, or a webmaster can use
 * it as unblocked site code after the Basicrum consent loader in manual mode.
 * Using the wrong category can silently result in no Basicrum data.
 */
( function( window, document ) {
	'use strict';

	var consentCategory = 'analytics';

	/**
	 * Report the current decision to the Basicrum consent loader.
	 *
	 * @param {boolean} allowed Whether CookieYes allows analytics.
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
	 * Determine whether CookieYes has allowed Basicrum's category.
	 *
	 * @param {Object} consent CookieYes consent data.
	 * @return {boolean} Whether analytics collection is allowed.
	 */
	function hasAnalyticsConsent( consent ) {
		var categories = consent && consent.categories;
		var accepted   = consent && consent.accepted;

		if ( categories && Object.prototype.hasOwnProperty.call( categories, consentCategory ) ) {
			return true === categories[ consentCategory ];
		}

		return Array.isArray( accepted ) && -1 !== accepted.indexOf( consentCategory );
	}

	/**
	 * Synchronize a CookieYes consent payload.
	 *
	 * @param {Object} consent CookieYes consent data.
	 * @return {void}
	 */
	function synchronizeConsent( consent ) {
		if ( ! consent || 'object' !== typeof consent ) {
			reportDecision( false );
			return;
		}

		reportDecision( hasAnalyticsConsent( consent ) );
	}

	/**
	 * Read the current decision from the CookieYes browser API.
	 *
	 * @return {void}
	 */
	function synchronizeStoredConsent() {
		if ( 'function' !== typeof window.getCkyConsent ) {
			reportDecision( false );
			return;
		}

		try {
			synchronizeConsent( window.getCkyConsent() );
		} catch ( error ) {
			// Fail closed until CookieYes can report an authoritative decision.
			reportDecision( false );
		}
	}

	document.addEventListener( 'cookieyes_banner_load', function( event ) {
		if ( event && event.detail ) {
			synchronizeConsent( event.detail );
			return;
		}

		synchronizeStoredConsent();
	} );
	document.addEventListener( 'cookieyes_consent_update', function( event ) {
		synchronizeConsent( event && event.detail );
	} );

	// Also support snippets added after CookieYes has initialized.
	synchronizeStoredConsent();
}( window, document ) );
