( function() {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function() {
		var consentTabs = document.querySelectorAll( '.basicrum-consent-tabs' );
		var consentModeContainer = document.querySelector( '.basicrum-consent-mode-panels' );
		var consentModeOptions = Array.from( document.querySelectorAll( 'input[name="basicrum_settings[consent_integration]"]' ) );
		var consentRequirementOptions = Array.from( document.querySelectorAll( 'input[name="basicrum_settings[consent_enabled]"]' ) );
		var consentConnectionRows = Array.from( document.querySelectorAll( '.basicrum-consent-connection-row' ) );
		var consentModePanels = consentModeContainer ? Array.from( consentModeContainer.querySelectorAll( '[data-basicrum-consent-integration-panel]' ) ) : [];
		var consentModeAnnouncement = consentModeContainer ? consentModeContainer.querySelector( '.basicrum-consent-mode-announcement' ) : null;
		var consentRequirementAnnouncement = document.querySelector( '.basicrum-consent-requirement-announcement' );
		var openManualConsentButtons = document.querySelectorAll( '.basicrum-open-manual-consent' );
		var copyTextButtons = document.querySelectorAll( '.basicrum-copy-text' );
		var enabled = document.getElementById( 'basicrum_enabled' );
		var waitAfterOnload = document.getElementById( 'basicrum_wait_after_onload' );
		var delay = document.getElementById( 'basicrum_delay_ms' );
		var form = enabled ? enabled.closest( 'form' ) : null;
		var fieldIds = [ 'basicrum_beacon_url', 'basicrum_brum_site_id' ];
		var requiredFields = fieldIds.map( function( fieldId ) {
			return document.getElementById( fieldId );
		} ).filter( Boolean );
		var dependentFields = form ? Array.from( form.querySelectorAll( 'input, select, textarea' ) ).filter( function( field ) {
			return field !== enabled && 'hidden' !== field.type && 0 === field.name.indexOf( 'basicrum_settings[' );
		} ) : [];
		var preservedValues = form ? Array.from( form.querySelectorAll( '.basicrum-disabled-setting-value' ) ) : [];

		/**
		 * Show only the details associated with the selected integration mode.
		 *
		 * @param {boolean} announce Whether to announce a user-triggered change.
		 * @return {void}
		 */
		function syncConsentIntegrationMode( announce ) {
			var selectedOption = consentModeOptions.find( function( option ) {
				return option.checked;
			} );

			if ( ! selectedOption ) {
				return;
			}

			consentModePanels.forEach( function( panel ) {
				panel.hidden = panel.dataset.basicrumConsentIntegrationPanel !== selectedOption.value;
			} );

			if ( announce && consentModeAnnouncement ) {
				consentModeAnnouncement.textContent = 'automatic' === selectedOption.value
					? consentModeContainer.dataset.automaticAnnouncement
					: consentModeContainer.dataset.manualAnnouncement;
			}
		}

		/**
		 * Show consent-tool connection settings only when visitor consent is required.
		 *
		 * @param {boolean} announce Whether to announce a user-triggered change.
		 * @return {boolean} Whether the connection settings are relevant.
		 */
		function syncConsentConnectionVisibility( announce ) {
			var consentRequirement = consentRequirementOptions.find( function( option ) {
				return option.checked;
			} );
			var isConnectionRelevant = consentRequirement && '1' === consentRequirement.value;

			consentConnectionRows.forEach( function( row ) {
				row.hidden = ! isConnectionRelevant;
				row.classList.toggle( 'basicrum-consent-connection-hidden', ! isConnectionRelevant );
			} );

			if ( announce && consentRequirementAnnouncement ) {
				consentRequirementAnnouncement.textContent = consentRequirementAnnouncement.getAttribute(
					'data-basicrum-announcement-' + consentRequirement.value
				);
			}

			return Boolean( isConnectionRelevant );
		}

		/**
		 * Activate one consent integration tab.
		 *
		 * @param {HTMLElement} tabContainer Tab container.
		 * @param {HTMLButtonElement} activeTab Tab to activate.
		 * @return {void}
		 */
		function activateConsentTab( tabContainer, activeTab ) {
			var tabs = Array.from( tabContainer.querySelectorAll( '[role="tab"]' ) );
			var panels = Array.from( tabContainer.querySelectorAll( '[role="tabpanel"]' ) );

			tabs.forEach( function( tab ) {
				var isActive = tab === activeTab;

				tab.classList.toggle( 'nav-tab-active', isActive );
				tab.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
				tab.tabIndex = isActive ? 0 : -1;
			} );

			panels.forEach( function( panel ) {
				var isActive = panel.id === activeTab.getAttribute( 'aria-controls' );

				panel.hidden = ! isActive;
				panel.classList.toggle( 'is-active', isActive );
			} );
		}

		/**
		 * Initialize accessible tabs and copy buttons for consent snippets.
		 *
		 * @param {HTMLElement} tabContainer Tab container.
		 * @return {void}
		 */
		function initializeConsentTabs( tabContainer ) {
			var tabs = Array.from( tabContainer.querySelectorAll( '[role="tab"]' ) );

			if ( ! tabs.length ) {
				return;
			}

			tabs.forEach( function( tab, tabIndex ) {
				tab.addEventListener( 'click', function() {
					activateConsentTab( tabContainer, tab );
				} );

				tab.addEventListener( 'keydown', function( event ) {
					var targetIndex = tabIndex;

					if ( 'ArrowLeft' === event.key ) {
						targetIndex = 0 === tabIndex ? tabs.length - 1 : tabIndex - 1;
					} else if ( 'ArrowRight' === event.key ) {
						targetIndex = tabIndex === tabs.length - 1 ? 0 : tabIndex + 1;
					} else if ( 'Home' === event.key ) {
						targetIndex = 0;
					} else if ( 'End' === event.key ) {
						targetIndex = tabs.length - 1;
					} else {
						return;
					}

					event.preventDefault();
					activateConsentTab( tabContainer, tabs[ targetIndex ] );
					tabs[ targetIndex ].focus();
				} );
			} );

			tabContainer.classList.add( 'is-initialized' );
			activateConsentTab(
				tabContainer,
				tabContainer.querySelector( '[role="tab"][aria-selected="true"]' ) || tabs[0]
			);
		}

		/**
		 * Add copy behavior to a read-only text field.
		 *
		 * @param {HTMLButtonElement} button Copy button.
		 * @return {void}
		 */
		function initializeCopyButton( button ) {
			button.addEventListener( 'click', function() {
				var target = document.getElementById( button.dataset.copyTarget );
				var status = button.parentElement.querySelector( '.basicrum-copy-status' );

				if ( ! target ) {
					return;
				}

				function reportCopied() {
					if ( status ) {
						status.textContent = button.dataset.copiedLabel;
					}
				}

				function reportCopyFallback() {
					if ( status ) {
						status.textContent = button.dataset.copyFallbackLabel;
					}
				}

				function copyWithSelection() {
					target.focus();
					target.select();

					if ( 'function' === typeof document.execCommand && document.execCommand( 'copy' ) ) {
						reportCopied();
						return;
					}

					reportCopyFallback();
				}

				if ( navigator.clipboard && 'function' === typeof navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( target.value ).then( reportCopied, copyWithSelection );
					return;
				}

				copyWithSelection();
			} );
		}

		/**
		 * Reveal manual setup without saving or submitting the settings form.
		 *
		 * @param {HTMLButtonElement} button Manual setup button.
		 * @return {void}
		 */
		function openManualConsentSetup( button ) {
			var manualOption = consentModeOptions.find( function( option ) {
				return 'manual' === option.value;
			} );
			var manualTab = button.dataset.basicrumManualTab;
			var targetTab = manualTab ? document.getElementById( 'basicrum-consent-tab-' + manualTab ) : null;

			if ( ! manualOption || manualOption.disabled ) {
				return;
			}

			manualOption.checked = true;
			manualOption.dispatchEvent( new Event( 'change', { bubbles: true } ) );

			if ( targetTab ) {
				activateConsentTab( targetTab.closest( '.basicrum-consent-tabs' ), targetTab );
			}

			( targetTab || document.querySelector( '#basicrum-consent-manual-panel [role="tab"][aria-selected="true"]' ) || manualOption ).focus();
		}

		consentTabs.forEach( initializeConsentTabs );
		copyTextButtons.forEach( initializeCopyButton );
		consentModeOptions.forEach( function( option ) {
			option.addEventListener( 'change', function() {
				syncConsentIntegrationMode( true );
			} );
		} );
		openManualConsentButtons.forEach( function( button ) {
			button.addEventListener( 'click', function() {
				openManualConsentSetup( button );
			} );
		} );
		syncConsentIntegrationMode( false );
		syncConsentConnectionVisibility( false );

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

			syncDelayAvailability();
			syncConsentConnectionAvailability();
		}

		/**
		 * Allow consent-tool connection choices only when visitor consent is required.
		 *
		 * @return {void}
		 */
		function syncConsentConnectionAvailability( announce ) {
			var isConnectionEnabled = enabled.checked && syncConsentConnectionVisibility( announce );

			consentModeOptions.forEach( function( option ) {
				option.disabled = ! isConnectionEnabled;
				option.setAttribute( 'aria-disabled', isConnectionEnabled ? 'false' : 'true' );
			} );
		}

		function syncDelayAvailability() {
			if ( ! waitAfterOnload || ! delay ) {
				return;
			}

			var isDelayEnabled = enabled.checked && waitAfterOnload.checked;

			delay.disabled = ! isDelayEnabled;
			delay.setAttribute( 'aria-disabled', isDelayEnabled ? 'false' : 'true' );
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

		if ( waitAfterOnload ) {
			waitAfterOnload.addEventListener( 'change', syncDelayAvailability );
		}

		consentRequirementOptions.forEach( function( option ) {
			option.addEventListener( 'change', function() {
				syncConsentConnectionAvailability( true );
			} );
		} );

		form.addEventListener( 'submit', function() {
			if ( enabled.checked ) {
				if ( waitAfterOnload && delay && ! waitAfterOnload.checked ) {
					delay.disabled = false;
				}

				consentModeOptions.forEach( function( option ) {
					option.disabled = false;
				} );

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
