const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { test, expect } = require( '@playwright/test' );
const {
	CONSENT_LOADERS,
	createLoaderHarness,
} = require( './fixtures/browser' );

const REPOSITORY_ROOT = path.resolve( __dirname, '../..' );
const STANDARD_LOADER_PATH = path.join(
	REPOSITORY_ROOT,
	'plugins/basicrum/assets/js/loaders/boomerang-loader-v15.js'
);
const CONSENT_LOADER_PATH = path.join(
	REPOSITORY_ROOT,
	'plugins/basicrum/assets/js/loaders/consent-boomerang-loader-v1-15.js'
);

test( 'consent wrapper contains the standard loader without modifying it', () => {
	const standardLoader = fs.readFileSync( STANDARD_LOADER_PATH, 'utf8' );
	const consentLoader = fs.readFileSync( CONSENT_LOADER_PATH, 'utf8' );
	const wrappedLoader = consentLoader.match(
		/\/\* BEGIN BASICRUM STANDARD LOADER \*\/\n([\s\S]*?)\n    \/\* END BASICRUM STANDARD LOADER \*\//
	);

	expect( wrappedLoader ).not.toBeNull();
	expect( wrappedLoader[ 1 ] ).toBe( standardLoader );
} );

for ( const loaderPath of CONSENT_LOADERS ) {
	test.describe( path.basename( loaderPath ), () => {
		test( 'consent wrapper exposes only the opt-in and opt-out callbacks', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );

			expect( await page.evaluate( () => Object.keys( window )
				.filter( ( name ) => (
					name.endsWith( '_BASICRUM_LOADER_WRAPPER' ) && 'function' === typeof window[ name ]
				) )
				.sort() ) ).toEqual( [
				'OPT_IN_BASICRUM_LOADER_WRAPPER',
				'OPT_OUT_BASICRUM_LOADER_WRAPPER',
			] );
		} );

		test( 'does not load Boomerang until the opt-in callback runs', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
				snippetExecuted: false,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'loads exactly once across repeated opt-in callbacks', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await page.evaluate( () => {
				window.OPT_IN_BASICRUM_LOADER_WRAPPER();
				window.OPT_IN_BASICRUM_LOADER_WRAPPER();
			} );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
				snippetExecuted: true,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 1 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'opt-out before loading clears measurement cookies', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );

			await page.evaluate( () => {
				document.cookie = 'RT=test-round-trip; path=/; SameSite=Strict';
				document.cookie = 'BA=test-bandwidth; path=/; SameSite=Strict';
			} );
			await harness.load( loaderPath );
			await page.evaluate( () => window.OPT_OUT_BASICRUM_LOADER_WRAPPER() );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
				disableCalls: 0,
			} );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => [ 'RT', 'BA' ].includes( cookie.name ) ) ).toBe( false );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'opt-out clears parent-domain measurement cookies', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page, { origin: 'https://app.example.test' } );

			await page.evaluate( () => {
				document.cookie = 'RT=test-round-trip; path=/; domain=example.test; Secure; SameSite=Strict';
				document.cookie = 'BA=test-bandwidth; path=/; domain=example.test; Secure; SameSite=Strict';
			} );
			await harness.load( loaderPath );
			await page.evaluate( () => window.OPT_OUT_BASICRUM_LOADER_WRAPPER() );

			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => [ 'RT', 'BA' ].includes( cookie.name ) ) ).toBe( false );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'opt-out disables loaded Boomerang and removes its cookies', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await page.evaluate( () => window.OPT_IN_BASICRUM_LOADER_WRAPPER() );
			await harness.waitForExecutions( 1 );
			await page.evaluate( () => {
				document.cookie = 'RT=test-round-trip; path=/; SameSite=Strict';
				document.cookie = 'BA=test-bandwidth; path=/; SameSite=Strict';
				window.OPT_OUT_BASICRUM_LOADER_WRAPPER();
			} );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				disableCalls: 1,
				removedCookies: [ 'RT', 'BA' ],
			} );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => [ 'RT', 'BA' ].includes( cookie.name ) ) ).toBe( false );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'requires a reload to re-grant after loaded Boomerang is disabled', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await page.evaluate( () => window.OPT_IN_BASICRUM_LOADER_WRAPPER() );
			await harness.waitForExecutions( 1 );
			await page.evaluate( () => {
				window.OPT_OUT_BASICRUM_LOADER_WRAPPER();
				window.OPT_IN_BASICRUM_LOADER_WRAPPER();
			} );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				disableCalls: 1,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 1 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

	} );
}
