const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { test, expect } = require( '@playwright/test' );

const SETTINGS_SCRIPT = fs.readFileSync(
	path.join(
		__dirname,
		'../../plugins/basicrum/assets/js/admin/settings.js'
	),
	'utf8'
);

/**
 * Load the settings behavior into a representative settings form.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<void>} Completion promise.
 */
async function loadSettingsForm( page ) {
	await page.setContent( `
		<form id="basicrum-settings-form">
			<label><input id="basicrum_enabled" name="basicrum_settings[enabled]" type="checkbox" value="1" checked> Enable Basicrum</label>
			<input id="basicrum_beacon_url" name="basicrum_settings[beacon_url]" value="https://collector.example.test/beacon">
			<input id="basicrum_brum_site_id" name="basicrum_settings[brum_site_id]" value="550e8400-e29b-41d4-a716-446655440000">
			<label><input id="basicrum_wait_after_onload" name="basicrum_settings[wait_after_onload]" type="checkbox" value="1"> Wait After Onload</label>
			<input id="basicrum_delay_ms" name="basicrum_settings[delay_ms]" type="number" value="750" aria-disabled="true" disabled>
			<input class="basicrum-disabled-setting-value" name="basicrum_settings[delay_ms]" type="hidden" value="750">
			<label><input id="basicrum_consent_enabled_0" name="basicrum_settings[consent_enabled]" type="radio" value="0"> Monitor without consent</label>
			<label><input id="basicrum_consent_enabled_1" name="basicrum_settings[consent_enabled]" type="radio" value="1" checked> Require consent</label>
			<span class="basicrum-consent-requirement-announcement" aria-live="polite" data-basicrum-announcement-0="Consent Tool Connection hidden." data-basicrum-announcement-1="Consent Tool Connection shown."></span>
			<table>
				<tbody>
					<tr id="basicrum-consent-connection-row" class="basicrum-consent-connection-row">
						<th></th>
						<td>
							<div class="basicrum-consent-connection-card">
								<fieldset>
									<legend>Consent Tool Connection</legend>
									<label><input name="basicrum_settings[consent_integration]" type="radio" value="automatic" checked> Automatic connection</label>
									<label><input name="basicrum_settings[consent_integration]" type="radio" value="manual"> Manual callbacks</label>
								</fieldset>
								<div class="basicrum-consent-mode-panels"></div>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
	` );

	await page.addScriptTag( { content: SETTINGS_SCRIPT } );
	await page.evaluate( () => document.dispatchEvent( new Event( 'DOMContentLoaded' ) ) );
}

test( 'enables Delay only when Wait After Onload is active', async ( { page } ) => {
	await loadSettingsForm( page );

	const waitAfterOnload = page.locator( '#basicrum_wait_after_onload' );
	const delay = page.locator( '#basicrum_delay_ms' );

	await expect( delay ).toBeDisabled();
	await expect( delay ).toHaveAttribute( 'aria-disabled', 'true' );

	await waitAfterOnload.check();
	await expect( delay ).toBeEnabled();
	await expect( delay ).toHaveAttribute( 'aria-disabled', 'false' );

	await waitAfterOnload.uncheck();
	await expect( delay ).toBeDisabled();
	await expect( delay ).toHaveAttribute( 'aria-disabled', 'true' );
} );

test( 'preserves Delay when Wait After Onload is inactive and the form is submitted', async ( { page } ) => {
	await loadSettingsForm( page );

	await page.locator( '#basicrum-settings-form' ).evaluate( ( form ) => {
		form.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			window.basicrumSubmittedDelay = new FormData( form ).get( 'basicrum_settings[delay_ms]' );
		}, { once: true } );
		form.requestSubmit();
	} );

	expect( await page.evaluate( () => window.basicrumSubmittedDelay ) ).toBe( '750' );
} );

test( 'hides and preserves Consent Tool Connection when consent is not required', async ( { page } ) => {
	await loadSettingsForm( page );

	const requireConsent = page.locator( '#basicrum_consent_enabled_1' );
	const monitorWithoutConsent = page.locator( '#basicrum_consent_enabled_0' );
	const automaticConnection = page.locator( 'input[name="basicrum_settings[consent_integration]"][value="automatic"]' );
	const manualConnection = page.locator( 'input[name="basicrum_settings[consent_integration]"][value="manual"]' );
	const connectionRow = page.locator( '#basicrum-consent-connection-row' );
	const announcement = page.locator( '.basicrum-consent-requirement-announcement' );

	await expect( automaticConnection ).toBeEnabled();
	await expect( manualConnection ).toBeEnabled();
	await expect( connectionRow ).toBeVisible();
	await expect( announcement ).toBeEmpty();

	await monitorWithoutConsent.check();
	await expect( connectionRow ).toBeHidden();
	await expect( connectionRow ).toHaveAttribute( 'hidden', '' );
	await expect( automaticConnection ).toBeDisabled();
	await expect( manualConnection ).toBeDisabled();
	await expect( automaticConnection ).toHaveAttribute( 'aria-disabled', 'true' );
	await expect( manualConnection ).toHaveAttribute( 'aria-disabled', 'true' );
	await expect( announcement ).toHaveText( 'Consent Tool Connection hidden.' );

	await page.locator( '#basicrum-settings-form' ).evaluate( ( form ) => {
		form.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			window.basicrumSubmittedConnection = new FormData( form ).get( 'basicrum_settings[consent_integration]' );
		}, { once: true } );
		form.requestSubmit();
	} );

	expect( await page.evaluate( () => window.basicrumSubmittedConnection ) ).toBe( 'automatic' );

	await requireConsent.check();
	await expect( connectionRow ).toBeVisible();
	await expect( connectionRow ).not.toHaveAttribute( 'hidden', '' );
	await expect( automaticConnection ).toBeEnabled();
	await expect( manualConnection ).toBeEnabled();
	await expect( automaticConnection ).toHaveAttribute( 'aria-disabled', 'false' );
	await expect( manualConnection ).toHaveAttribute( 'aria-disabled', 'false' );
	await expect( announcement ).toHaveText( 'Consent Tool Connection shown.' );
} );

test( 'shows only the details for the selected consent tool connection mode', async ( { page } ) => {
	await page.setContent( `
		<form>
			<label><input type="radio" name="basicrum_settings[consent_integration]" value="automatic" aria-controls="automatic-panel" checked> Automatic</label>
			<label><input type="radio" name="basicrum_settings[consent_integration]" value="manual" aria-controls="manual-panel"> Manual</label>
			<div class="basicrum-consent-mode-panels" data-automatic-announcement="Automatic connection details shown." data-manual-announcement="Manual connection setup shown. Save Changes to apply this mode.">
				<span class="basicrum-consent-mode-announcement" aria-live="polite"></span>
				<section id="automatic-panel" data-basicrum-consent-integration-panel="automatic">Automatic status</section>
				<section id="manual-panel" data-basicrum-consent-integration-panel="manual" hidden>Manual setup</section>
			</div>
		</form>
	` );

	await page.addScriptTag( { content: SETTINGS_SCRIPT } );
	await page.evaluate( () => document.dispatchEvent( new Event( 'DOMContentLoaded' ) ) );

	const automatic = page.locator( 'input[value="automatic"]' );
	const manual = page.locator( 'input[value="manual"]' );

	await expect( page.locator( '#automatic-panel' ) ).toBeVisible();
	await expect( page.locator( '#manual-panel' ) ).toBeHidden();
	await expect( automatic ).toHaveAttribute( 'aria-controls', 'automatic-panel' );
	await expect( manual ).toHaveAttribute( 'aria-controls', 'manual-panel' );

	await manual.check();

	await expect( page.locator( '#automatic-panel' ) ).toBeHidden();
	await expect( page.locator( '#manual-panel' ) ).toBeVisible();
	await expect( page.locator( '.basicrum-consent-mode-announcement' ) ).toHaveText( 'Manual connection setup shown. Save Changes to apply this mode.' );
} );

test( 'routes a blocked automatic integration to the Generic manual setup', async ( { page } ) => {
	await page.setContent( `
		<form>
			<label><input type="radio" name="basicrum_settings[consent_integration]" value="automatic" aria-controls="automatic-panel" checked> Automatic</label>
			<label><input type="radio" name="basicrum_settings[consent_integration]" value="manual" aria-controls="manual-panel"> Manual</label>
			<div class="basicrum-consent-mode-panels" data-automatic-announcement="Automatic connection details shown." data-manual-announcement="Manual connection setup shown. Save Changes to apply this mode.">
				<span class="basicrum-consent-mode-announcement" aria-live="polite"></span>
				<section id="automatic-panel" data-basicrum-consent-integration-panel="automatic">
					<button type="button" class="basicrum-open-manual-consent" data-basicrum-manual-tab="generic">Open manual setup</button>
				</section>
				<section id="manual-panel" data-basicrum-consent-integration-panel="manual" hidden>
					<div class="basicrum-consent-tabs">
						<div role="tablist">
							<button type="button" role="tab" id="basicrum-consent-tab-borlabs-cookie-v3" aria-controls="basicrum-consent-panel-borlabs-cookie-v3" aria-selected="true">Borlabs</button>
							<button type="button" role="tab" id="basicrum-consent-tab-generic" aria-controls="basicrum-consent-panel-generic" aria-selected="false" tabindex="-1">Generic</button>
						</div>
						<section id="basicrum-consent-panel-borlabs-cookie-v3" role="tabpanel">Borlabs setup</section>
						<section id="basicrum-consent-panel-generic" role="tabpanel">Generic setup</section>
					</div>
				</section>
			</div>
		</form>
	` );

	await page.addScriptTag( { content: SETTINGS_SCRIPT } );
	await page.evaluate( () => document.dispatchEvent( new Event( 'DOMContentLoaded' ) ) );
	await page.locator( '.basicrum-open-manual-consent' ).click();

	await expect( page.locator( 'input[value="manual"]' ) ).toBeChecked();
	await expect( page.locator( '#automatic-panel' ) ).toBeHidden();
	await expect( page.locator( '#manual-panel' ) ).toBeVisible();
	await expect( page.locator( '#basicrum-consent-tab-generic' ) ).toHaveAttribute( 'aria-selected', 'true' );
	await expect( page.locator( '#basicrum-consent-tab-generic' ) ).toBeFocused();
	await expect( page.locator( '#basicrum-consent-panel-generic' ) ).toBeVisible();
	await expect( page.locator( '.basicrum-consent-mode-announcement' ) ).toHaveText( 'Manual connection setup shown. Save Changes to apply this mode.' );
} );

test( 'switches consent tool tabs with pointer and keyboard controls', async ( { page } ) => {
	await page.setContent( `
		<div class="basicrum-consent-tabs">
			<div role="tablist">
				<button type="button" role="tab" id="tab-borlabs" aria-controls="panel-borlabs" aria-selected="true">Borlabs</button>
				<button type="button" role="tab" id="tab-generic" aria-controls="panel-generic" aria-selected="false" tabindex="-1">Generic</button>
			</div>
			<section id="panel-borlabs" role="tabpanel" aria-labelledby="tab-borlabs">Borlabs adapter</section>
			<section id="panel-generic" role="tabpanel" aria-labelledby="tab-generic">Generic adapter</section>
		</div>
	` );

	await page.addScriptTag( { content: SETTINGS_SCRIPT } );
	await page.evaluate( () => document.dispatchEvent( new Event( 'DOMContentLoaded' ) ) );

	const borlabsTab = page.locator( '#tab-borlabs' );
	const genericTab = page.locator( '#tab-generic' );

	await expect( page.locator( '#panel-borlabs' ) ).toBeVisible();
	await expect( page.locator( '#panel-generic' ) ).toBeHidden();

	await genericTab.click();
	await expect( genericTab ).toHaveAttribute( 'aria-selected', 'true' );
	await expect( page.locator( '#panel-generic' ) ).toBeVisible();

	await genericTab.press( 'ArrowLeft' );
	await expect( borlabsTab ).toBeFocused();
	await expect( borlabsTab ).toHaveAttribute( 'aria-selected', 'true' );
	await expect( page.locator( '#panel-generic' ) ).toBeHidden();
} );

test( 'copies a displayed consent tool snippet', async ( { page } ) => {
	await page.setContent( `
		<div class="basicrum-consent-tabs">
			<div role="tablist">
				<button type="button" role="tab" id="tab-generic" aria-controls="panel-generic" aria-selected="true">Generic</button>
			</div>
			<section id="panel-generic" role="tabpanel" aria-labelledby="tab-generic">
				<textarea id="snippet" readonly>window.OPT_IN_BASICRUM_LOADER_WRAPPER();</textarea>
				<p>
					<button type="button" class="basicrum-copy-text basicrum-copy-consent-snippet" data-copy-target="snippet" data-copied-label="Copied" data-copy-fallback-label="Press Ctrl+C or Command+C to copy.">Copy snippet</button>
					<span class="basicrum-copy-status" aria-live="polite"></span>
				</p>
			</section>
		</div>
	` );

	await page.evaluate( () => {
		document.execCommand = function( command ) {
			window.basicrumCopyCommand = command;
			window.basicrumCopiedText = document.activeElement.value;
			return true;
		};
	} );
	await page.addScriptTag( { content: SETTINGS_SCRIPT } );
	await page.evaluate( () => document.dispatchEvent( new Event( 'DOMContentLoaded' ) ) );
	await page.locator( '.basicrum-copy-consent-snippet' ).click();

	await expect( page.locator( '.basicrum-copy-status' ) ).toHaveText( 'Copied' );
	expect( await page.evaluate( () => window.basicrumCopyCommand ) ).toBe( 'copy' );
	expect( await page.evaluate( () => window.basicrumCopiedText ) ).toBe( 'window.OPT_IN_BASICRUM_LOADER_WRAPPER();' );
} );

test( 'selects the snippet and explains manual copying when clipboard access is unavailable', async ( { page } ) => {
	await page.setContent( `
		<div class="basicrum-consent-tabs">
			<div role="tablist">
				<button type="button" role="tab" id="tab-generic" aria-controls="panel-generic" aria-selected="true">Generic</button>
			</div>
			<section id="panel-generic" role="tabpanel" aria-labelledby="tab-generic">
				<textarea id="snippet" readonly>window.OPT_OUT_BASICRUM_LOADER_WRAPPER();</textarea>
				<p>
					<button type="button" class="basicrum-copy-text basicrum-copy-consent-snippet" data-copy-target="snippet" data-copied-label="Copied" data-copy-fallback-label="Press Ctrl+C or Command+C to copy.">Copy snippet</button>
					<span class="basicrum-copy-status" aria-live="polite"></span>
				</p>
			</section>
		</div>
	` );

	await page.evaluate( () => {
		document.execCommand = function() {
			return false;
		};
	} );
	await page.addScriptTag( { content: SETTINGS_SCRIPT } );
	await page.evaluate( () => document.dispatchEvent( new Event( 'DOMContentLoaded' ) ) );
	await page.locator( '.basicrum-copy-consent-snippet' ).click();

	await expect( page.locator( '.basicrum-copy-status' ) ).toHaveText( 'Press Ctrl+C or Command+C to copy.' );
	expect( await page.locator( '#snippet' ).evaluate( ( textarea ) => textarea.selectionEnd - textarea.selectionStart ) ).toBeGreaterThan( 0 );
} );
