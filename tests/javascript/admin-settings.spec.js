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

test( 'switches consent integration tabs with pointer and keyboard controls', async ( { page } ) => {
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

test( 'copies a displayed consent integration snippet', async ( { page } ) => {
	await page.setContent( `
		<div class="basicrum-consent-tabs">
			<div role="tablist">
				<button type="button" role="tab" id="tab-generic" aria-controls="panel-generic" aria-selected="true">Generic</button>
			</div>
			<section id="panel-generic" role="tabpanel" aria-labelledby="tab-generic">
				<textarea id="snippet" readonly>window.OPT_IN_BASICRUM_LOADER_WRAPPER();</textarea>
				<p>
					<button type="button" class="basicrum-copy-consent-snippet" data-copy-target="snippet" data-copied-label="Copied" data-copy-fallback-label="Press Ctrl+C or Command+C to copy.">Copy snippet</button>
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
					<button type="button" class="basicrum-copy-consent-snippet" data-copy-target="snippet" data-copied-label="Copied" data-copy-fallback-label="Press Ctrl+C or Command+C to copy.">Copy snippet</button>
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
