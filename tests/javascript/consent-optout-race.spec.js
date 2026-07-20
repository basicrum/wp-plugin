const path = require( 'node:path' );
const { test, expect } = require( '@playwright/test' );
const {
	BOOMERANG_URL,
	CONSENT_LOADERS,
	createLoaderHarness,
} = require( './fixtures/browser' );

const BEACON_URL = 'https://collector.basicrum.test/beacon';

// Boomerang stub that mimics the bundled boomerang tail's init glue contract:
//   window.basicRumInitConfig=BOOMR.window.basicRumBoomerangConfig;
//   window.basicRumInitConfig&&BOOMR.init(window.basicRumInitConfig)
// On execution it only "initializes" (records the init, recreates the RT
// cookie, and fires a beacon) when the inline config object is still truthy,
// exactly like the real bundle in assets/js/boomr/.
const INIT_GLUE_BOOMERANG_STUB = `
window.__boomrExecutionCount = ( window.__boomrExecutionCount || 0 ) + 1;
window.__boomrInitCalls = window.__boomrInitCalls || 0;
window.BOOMR = window.BOOMR || {};
window.BOOMR.version = 'test';
window.BOOMR.window = window;
window.BOOMR.init = function( config ) {
	window.__boomrInitCalls += 1;
	document.cookie = 'RT=recreated-by-late-script; path=/; SameSite=Strict';
	fetch( '${ BEACON_URL }', { method: 'POST', body: 'beacon' } ).catch( function() {} );
	return window.BOOMR;
};
window.basicRumInitConfig = window.BOOMR.window.basicRumBoomerangConfig;
window.basicRumInitConfig && window.BOOMR.init( window.basicRumInitConfig );
`;

/**
 * Install routes that shadow the harness defaults for this spec: the beacon
 * endpoint is fulfilled (and counted) instead of blocked, and the Boomerang
 * script response is gated on an explicit promise so tests control exactly
 * when the "download" finishes. Routes registered after the harness route
 * take precedence, so the harness never sees these requests.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<object>} Gate controls and request counters.
 */
async function installGatedBoomerang( page ) {
	let releaseBoomerang;
	const boomerangReleased = new Promise( ( resolve ) => {
		releaseBoomerang = resolve;
	} );
	let boomerangRequested;
	const boomerangRequestInFlight = new Promise( ( resolve ) => {
		boomerangRequested = resolve;
	} );
	let boomerangRequests = 0;
	let beaconRequests = 0;

	await page.route( BEACON_URL, async ( route ) => {
		beaconRequests += 1;
		await route.fulfill( {
			status: 204,
			headers: { 'access-control-allow-origin': '*' },
			body: '',
		} );
	} );

	await page.route( BOOMERANG_URL, async ( route ) => {
		boomerangRequests += 1;
		boomerangRequested();
		await boomerangReleased;
		await route.fulfill( {
			status: 200,
			contentType: 'application/javascript; charset=utf-8',
			body: INIT_GLUE_BOOMERANG_STUB,
		} );
	} );

	return {
		boomerangRequestInFlight,
		releaseBoomerang,
		boomerangRequests: () => boomerangRequests,
		beaconRequests: () => beaconRequests,
	};
}

/**
 * Mirror the inline configuration that Assets.php prints before the loader.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 */
async function injectInlineConfig( page ) {
	await page.evaluate( ( beaconUrl ) => {
		window.basicRumBoomerangConfig = {
			beacon_url: beaconUrl,
			instrument_xhr: false,
			secure_cookie: false,
			same_site_cookie: 'Strict',
		};
		window.__boomrInitCalls = 0;
	}, BEACON_URL );
}

for ( const loaderPath of CONSENT_LOADERS ) {
	test.describe( path.basename( loaderPath ), () => {
		test( 'initializes from the pending config when consent stays granted', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );
			const gate = await installGatedBoomerang( page );

			await harness.load( loaderPath );
			await injectInlineConfig( page );

			await page.evaluate( () => window.OPT_IN_BASICRUM_LOADER_WRAPPER() );
			await gate.boomerangRequestInFlight;
			gate.releaseBoomerang();
			await harness.waitForExecutions( 1 );

			// Sanity check for the stub's init-glue contract: with the config
			// still present the late script initializes, sets its cookie, and
			// beacons - proving the race assertions below are meaningful.
			expect( await page.evaluate( () => window.__boomrInitCalls ) ).toBe( 1 );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => 'RT' === cookie.name ) ).toBe( true );
			await expect.poll( () => gate.beaconRequests() ).toBe( 1 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'a deny before any opt-in must not block a later allow on the same page', async ( { page } ) => {
			const harness = await createLoaderHarness( page );
			const gate = await installGatedBoomerang( page );

			await harness.load( loaderPath );
			await injectInlineConfig( page );

			// 1. The consent tool reports deny or no-decision first: every
			//    packaged adapter fails closed and calls OPT_OUT before the
			//    visitor has made a choice.
			await page.evaluate( () => window.OPT_OUT_BASICRUM_LOADER_WRAPPER() );

			// 2. The visitor accepts moments later on the same page.
			await page.evaluate( () => window.OPT_IN_BASICRUM_LOADER_WRAPPER() );
			await gate.boomerangRequestInFlight;
			gate.releaseBoomerang();
			await harness.waitForExecutions( 1 );

			// The late grant must fully initialize monitoring: this is the
			// standard first-visit journey for every automatic adapter.
			expect( await page.evaluate( () => window.__boomrInitCalls ) ).toBe( 1 );
			await expect.poll( () => gate.beaconRequests() ).toBe( 1 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'a Boomerang script that arrives after opt-out must not initialize, set cookies, or beacon', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );
			const gate = await installGatedBoomerang( page );

			await harness.load( loaderPath );
			await injectInlineConfig( page );

			// 1. Consent granted: the loader starts the Boomerang download.
			await page.evaluate( () => window.OPT_IN_BASICRUM_LOADER_WRAPPER() );
			await gate.boomerangRequestInFlight;

			// 2. Consent withdrawn while boomerang.js is still downloading.
			await page.evaluate( () => window.OPT_OUT_BASICRUM_LOADER_WRAPPER() );

			// 3. Only now does the script "download" finish and execute.
			gate.releaseBoomerang();
			await harness.waitForExecutions( 1 );

			// Desired behavior: after the user opted out, the late-arriving
			// script must not initialize, recreate cookies, or send beacons.
			expect( await page.evaluate( () => window.__boomrInitCalls ) ).toBe( 0 );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => [ 'RT', 'BA' ].includes( cookie.name ) ) ).toBe( false );
			expect( gate.beaconRequests() ).toBe( 0 );
			expect( gate.boomerangRequests() ).toBe( 1 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );
	} );
}
