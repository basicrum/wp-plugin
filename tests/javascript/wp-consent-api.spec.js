const path = require( 'node:path' );
const { test, expect } = require( '@playwright/test' );
const {
	CONSENT_LOADERS,
	createLoaderHarness,
} = require( './fixtures/browser' );

const REPOSITORY_ROOT = path.resolve( __dirname, '../..' );
const WP_CONSENT_API_ADAPTER = path.join(
	REPOSITORY_ROOT,
	'plugins/basicrum/assets/js/integrations/wp-consent-api.js'
);
const WP_CONSENT_API_TEST_DOUBLE = path.join(
	__dirname,
	'fixtures/wp-consent-api-test-double.js'
);

for ( const loaderPath of CONSENT_LOADERS ) {
	test.describe( `WP Consent API adapter contract with ${ path.basename( loaderPath ) }`, () => {
		test( 'fails closed when WP Consent API is unavailable', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );

			await page.evaluate( () => {
				document.cookie = 'RT=test-round-trip; path=/; SameSite=Strict';
				document.cookie = 'BA=test-bandwidth; path=/; SameSite=Strict';
			} );
			await harness.load( loaderPath );
			await harness.load( WP_CONSENT_API_ADAPTER );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
			} );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => [ 'RT', 'BA' ].includes( cookie.name ) ) ).toBe( false );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'applies saved opt-in when WP Consent API initializes', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( WP_CONSENT_API_TEST_DOUBLE );
			await harness.load( WP_CONSENT_API_ADAPTER );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
			} );
			expect( await page.evaluate( () => (
				window.__WP_CONSENT_API_TEST__.getHasConsentCalls()
			) ) ).toEqual( [] );

			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.initialize( { statistics: 'allow' } );
			} );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( await page.evaluate( () => (
				window.__WP_CONSENT_API_TEST__.getHasConsentCalls()
			) ) ).toContain( 'statistics' );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'reads the current choice when the adapter loads after initialization', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( WP_CONSENT_API_TEST_DOUBLE );
			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.initialize( { statistics: 'allow' } );
			} );
			await harness.load( WP_CONSENT_API_ADAPTER );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'requires the callback wrapper before WP Consent API reports its choice', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( WP_CONSENT_API_TEST_DOUBLE );
			await harness.load( WP_CONSENT_API_ADAPTER );
			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.initialize( { statistics: 'allow' } );
			} );
			await harness.load( loaderPath );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'keeps Boomerang blocked for a saved denial', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( WP_CONSENT_API_TEST_DOUBLE );
			await harness.load( WP_CONSENT_API_ADAPTER );
			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.initialize( { statistics: 'deny' } );
			} );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'blocks an unset choice in an opt-in region', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( WP_CONSENT_API_TEST_DOUBLE );
			await harness.load( WP_CONSENT_API_ADAPTER );
			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.initialize( {}, 'optin' );
			} );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'allows an unset choice in an opt-out region', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( WP_CONSENT_API_TEST_DOUBLE );
			await harness.load( WP_CONSENT_API_ADAPTER );
			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.initialize( {}, 'optout' );
			} );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 1 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'honors an explicit denial in an opt-out region', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( WP_CONSENT_API_TEST_DOUBLE );
			await harness.load( WP_CONSENT_API_ADAPTER );
			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.initialize( { statistics: 'deny' }, 'optout' );
			} );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'loads after WP Consent API changes a denial to a grant', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( WP_CONSENT_API_TEST_DOUBLE );
			await harness.load( WP_CONSENT_API_ADAPTER );
			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.initialize( { statistics: 'deny' } );
				window.__WP_CONSENT_API_TEST__.setConsent( 'statistics', 'allow' );
			} );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 1 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'loads once across duplicate WP Consent API grant events', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( WP_CONSENT_API_TEST_DOUBLE );
			await harness.load( WP_CONSENT_API_ADAPTER );
			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.initialize( { statistics: 'allow' } );
				window.__WP_CONSENT_API_TEST__.dispatchChange( { statistics: 'allow' } );
				window.__WP_CONSENT_API_TEST__.dispatchChange( { statistics: 'allow' } );
			} );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 1 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'withdraws consent and clears cookies after Boomerang has loaded', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( WP_CONSENT_API_TEST_DOUBLE );
			await harness.load( WP_CONSENT_API_ADAPTER );
			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.initialize( { statistics: 'allow' } );
			} );
			await harness.waitForExecutions( 1 );
			await page.evaluate( () => {
				document.cookie = 'RT=test-round-trip; path=/; SameSite=Strict';
				document.cookie = 'BA=test-bandwidth; path=/; SameSite=Strict';
				window.__WP_CONSENT_API_TEST__.setConsent( 'statistics', 'deny' );
			} );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				disableCalls: 1,
				removedCookies: [ 'RT', 'BA' ],
			} );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => [ 'RT', 'BA' ].includes( cookie.name ) ) ).toBe( false );
			expect( harness.boomerangRequestCount() ).toBe( 1 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'fails closed when WP Consent API becomes unavailable', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( WP_CONSENT_API_TEST_DOUBLE );
			await harness.load( WP_CONSENT_API_ADAPTER );
			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.initialize( { statistics: 'allow' } );
			} );
			await harness.waitForExecutions( 1 );
			await page.evaluate( () => {
				delete window.wp_has_consent;
				document.dispatchEvent( new window.Event( 'wp_consent_type_defined' ) );
			} );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				disableCalls: 1,
			} );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'requires a reload after withdrawal before WP Consent API can re-grant', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( WP_CONSENT_API_TEST_DOUBLE );
			await harness.load( WP_CONSENT_API_ADAPTER );
			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.initialize( { statistics: 'allow' } );
			} );
			await harness.waitForExecutions( 1 );
			await page.evaluate( () => {
				window.__WP_CONSENT_API_TEST__.setConsent( 'statistics', 'deny' );
				window.__WP_CONSENT_API_TEST__.setConsent( 'statistics', 'allow' );
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
