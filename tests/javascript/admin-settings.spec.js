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
