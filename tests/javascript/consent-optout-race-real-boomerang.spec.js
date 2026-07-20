const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { test, expect } = require( '@playwright/test' );
const {
	BOOMERANG_URL,
	CONSENT_LOADERS,
	createLoaderHarness,
} = require( './fixtures/browser' );

const BEACON_URL = 'https://collector.basicrum.test/beacon';
const REPOSITORY_ROOT = path.resolve( __dirname, '../..' );
const REAL_BOOMERANG = fs.readFileSync(
	path.join(
		REPOSITORY_ROOT,
		'plugins/basicrum/assets/js/boomr/boomerang-1.815.60.cutting-edge.min.js'
	),
	'utf8'
);

// How long to watch for a beacon after the real bundle has executed before
// concluding that none will be sent.
const BEACON_SILENCE_MS = 1500;

/**
 * Install routes that serve the REAL bundled Boomerang build through a gate
 * the test controls, and count beacons to the configured collector. Routes
 * registered after the harness route take precedence, so the harness never
 * blocks these requests.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<object>} Gate controls and request counters.
 */
async function installGatedRealBoomerang( page ) {
	let releaseBoomerang;
	const boomerangReleased = new Promise( ( resolve ) => {
		releaseBoomerang = resolve;
	} );
	let boomerangRequested;
	const boomerangRequestInFlight = new Promise( ( resolve ) => {
		boomerangRequested = resolve;
	} );
	let beaconRequests = 0;

	await page.route( `${ BEACON_URL }*`, async ( route ) => {
		beaconRequests += 1;
		await route.fulfill( {
			status: 204,
			headers: { 'access-control-allow-origin': '*' },
			body: '',
		} );
	} );

	await page.route( BOOMERANG_URL, async ( route ) => {
		boomerangRequested();
		await boomerangReleased;
		await route.fulfill( {
			status: 200,
			contentType: 'application/javascript; charset=utf-8',
			body: REAL_BOOMERANG,
		} );
	} );

	return {
		boomerangRequestInFlight,
		releaseBoomerang,
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
			Continuity: { enabled: true },
			secure_cookie: false,
			same_site_cookie: 'Strict',
		};
	}, BEACON_URL );
}

/**
 * Wait until the real bundle has executed in the page.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 */
async function waitForRealBoomerang( page ) {
	await expect
		.poll( () => page.evaluate( () => window.BOOMR && window.BOOMR.version ) )
		.toBe( '1.815.60' );
}

for ( const loaderPath of CONSENT_LOADERS ) {
	test.describe( `Real bundled Boomerang with ${ path.basename( loaderPath ) }`, () => {
		test( 'granted consent initializes the real bundle: cookie set and beacon sent', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );
			const gate = await installGatedRealBoomerang( page );

			await harness.load( loaderPath );
			await injectInlineConfig( page );

			await page.evaluate( () => window.OPT_IN_BASICRUM_LOADER_WRAPPER() );
			await gate.boomerangRequestInFlight;
			gate.releaseBoomerang();
			await waitForRealBoomerang( page );

			// Positive control proving the instrument: the real bundle, given
			// an intact config, initializes, sets its RT cookie, and beacons.
			await expect.poll( () => gate.beaconRequests(), { timeout: 10000 } ).toBeGreaterThan( 0 );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => 'RT' === cookie.name ) ).toBe( true );
		} );

		test( 'withdrawal during the real download leaves the arrived bundle inert: no cookie, no beacon', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );
			const gate = await installGatedRealBoomerang( page );

			await harness.load( loaderPath );
			await injectInlineConfig( page );

			// 1. Consent granted: the loader starts the real download.
			await page.evaluate( () => window.OPT_IN_BASICRUM_LOADER_WRAPPER() );
			await gate.boomerangRequestInFlight;

			// 2. Consent withdrawn while the real bundle is still in transit.
			await page.evaluate( () => window.OPT_OUT_BASICRUM_LOADER_WRAPPER() );

			// 3. The real bundle arrives and executes anyway.
			gate.releaseBoomerang();
			await waitForRealBoomerang( page );

			// The bundle executed but must not have initialized: its config
			// bootstrap saw the neutralized config, so no cookie and no beacon.
			await page.waitForTimeout( BEACON_SILENCE_MS );
			expect( gate.beaconRequests() ).toBe( 0 );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => [ 'RT', 'BA' ].includes( cookie.name ) ) ).toBe( false );
			expect(
				await page.evaluate( () => window.basicRumInitConfig || null )
			).toBe( null );
		} );

		test( 'a deny before any opt-in does not block a later allow with the real bundle', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );
			const gate = await installGatedRealBoomerang( page );

			await harness.load( loaderPath );
			await injectInlineConfig( page );

			// Fail-closed adapters report deny before the visitor decides.
			await page.evaluate( () => window.OPT_OUT_BASICRUM_LOADER_WRAPPER() );

			// The visitor accepts moments later on the same page.
			await page.evaluate( () => window.OPT_IN_BASICRUM_LOADER_WRAPPER() );
			await gate.boomerangRequestInFlight;
			gate.releaseBoomerang();
			await waitForRealBoomerang( page );

			await expect.poll( () => gate.beaconRequests(), { timeout: 10000 } ).toBeGreaterThan( 0 );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => 'RT' === cookie.name ) ).toBe( true );
		} );
	} );
}
