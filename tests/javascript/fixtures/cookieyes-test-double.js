( function( window, document ) {
	'use strict';

	var categories = Object.create( null );
	var initialized = false;
	var getConsentCalls = 0;

	/**
	 * Replace the current test consent categories.
	 *
	 * @param {Object} nextCategories Consent values keyed by category.
	 * @return {void}
	 */
	function setCategories( nextCategories ) {
		var category;

		categories = Object.create( null );

		for ( category in nextCategories ) {
			if ( Object.prototype.hasOwnProperty.call( nextCategories, category ) ) {
				categories[ category ] = true === nextCategories[ category ];
			}
		}
	}

	/**
	 * Build the stored-state payload returned by getCkyConsent and banner load.
	 *
	 * @return {Object} CookieYes consent data.
	 */
	function getStoredConsent() {
		var currentCategories = {};
		var category;

		for ( category in categories ) {
			if ( Object.prototype.hasOwnProperty.call( categories, category ) ) {
				currentCategories[ category ] = categories[ category ];
			}
		}

		return {
			categories: currentCategories,
			isUserActionCompleted: true,
		};
	}

	/**
	 * Build CookieYes's documented consent-update event detail.
	 *
	 * @return {Object} Accepted and rejected category lists.
	 */
	function getConsentUpdate() {
		var accepted = [];
		var rejected = [];
		var category;

		for ( category in categories ) {
			if ( Object.prototype.hasOwnProperty.call( categories, category ) ) {
				if ( categories[ category ] ) {
					accepted.push( category );
				} else {
					rejected.push( category );
				}
			}
		}

		return {
			accepted: accepted,
			rejected: rejected,
		};
	}

	/**
	 * Emit a CookieYes document event.
	 *
	 * @param {string} eventName Event name.
	 * @param {Object} detail Event detail.
	 * @return {void}
	 */
	function dispatch( eventName, detail ) {
		document.dispatchEvent( new window.CustomEvent( eventName, { detail: detail } ) );
	}

	window.getCkyConsent = function() {
		getConsentCalls += 1;

		if ( ! initialized ) {
			throw new Error( 'CookieYes is not initialized.' );
		}

		return getStoredConsent();
	};

	window.__COOKIEYES_TEST__ = {
		initialize: function( initialCategories ) {
			setCategories( initialCategories || {} );
			initialized = true;
			dispatch( 'cookieyes_banner_load', getStoredConsent() );
		},
		setCategory: function( category, allowed ) {
			if ( ! initialized ) {
				throw new Error( 'Initialize CookieYes before changing consent.' );
			}

			categories[ category ] = true === allowed;
			dispatch( 'cookieyes_consent_update', getConsentUpdate() );
		},
		dispatchConsentUpdate: function() {
			dispatch( 'cookieyes_consent_update', getConsentUpdate() );
		},
		dispatchBannerLoad: function( detail ) {
			dispatch( 'cookieyes_banner_load', detail );
		},
		getConsentCalls: function() {
			return getConsentCalls;
		},
	};
}( window, document ) );
