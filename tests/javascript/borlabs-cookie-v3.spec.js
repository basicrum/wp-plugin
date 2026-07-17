const path = require( 'node:path' );
const { test, expect } = require( '@playwright/test' );
const {
	CONSENT_LOADERS,
	createLoaderHarness,
} = require( './fixtures/browser' );

const REPOSITORY_ROOT = path.resolve( __dirname, '../..' );
const BORLABS_ADAPTER = path.join(
	REPOSITORY_ROOT,
	'examples/integrations/borlabs-cookie-v3.js'
);
const BORLABS_TEST_DOUBLE = path.join(
	__dirname,
	'fixtures/borlabs-cookie-v3-test-double.js'
);

for ( const loaderPath of CONSENT_LOADERS ) {
	test.describe( `Borlabs Cookie v3 adapter contract with ${ path.basename( loaderPath ) }`, () => {
		test( 'fails closed when Borlabs Cookie is unavailable', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );

			await page.evaluate( () => {
				document.cookie = 'RT=test-round-trip; path=/; SameSite=Strict';
				document.cookie = 'BA=test-bandwidth; path=/; SameSite=Strict';
			} );
			await harness.load( loaderPath );
			await harness.load( BORLABS_ADAPTER );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
			} );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => [ 'RT', 'BA' ].includes( cookie.name ) ) ).toBe( false );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'applies saved opt-in when Borlabs initializes', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( BORLABS_TEST_DOUBLE );
			await harness.load( BORLABS_ADAPTER );
			await page.evaluate( () => {
				window.__BORLABS_COOKIE_TEST__.initialize( { basicrum: true } );
			} );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( await page.evaluate( () => (
				window.__BORLABS_COOKIE_TEST__.getHasConsentCalls()
			) ) ).toContainEqual( {
				serviceId: 'basicrum',
				serviceGroupId: undefined,
			} );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'reads the current choice when the adapter loads after Borlabs initialization', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( BORLABS_TEST_DOUBLE );
			await page.evaluate( () => {
				window.__BORLABS_COOKIE_TEST__.initialize( { basicrum: true } );
			} );
			await harness.load( BORLABS_ADAPTER );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'requires the callback wrapper before Borlabs reports its choice', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( BORLABS_TEST_DOUBLE );
			await harness.load( BORLABS_ADAPTER );
			await page.evaluate( () => {
				window.__BORLABS_COOKIE_TEST__.initialize( { basicrum: true } );
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
			await harness.load( BORLABS_TEST_DOUBLE );
			await harness.load( BORLABS_ADAPTER );
			await page.evaluate( () => {
				window.__BORLABS_COOKIE_TEST__.initialize( { basicrum: false } );
			} );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'loads after Borlabs changes a denial to a grant', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( BORLABS_TEST_DOUBLE );
			await harness.load( BORLABS_ADAPTER );
			await page.evaluate( () => {
				window.__BORLABS_COOKIE_TEST__.initialize( { basicrum: false } );
				window.__BORLABS_COOKIE_TEST__.saveConsent( 'basicrum', true );
			} );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 1 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'loads once across duplicate Borlabs consent events', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( BORLABS_TEST_DOUBLE );
			await harness.load( BORLABS_ADAPTER );
			await page.evaluate( () => {
				window.__BORLABS_COOKIE_TEST__.initialize( { basicrum: true } );
				window.__BORLABS_COOKIE_TEST__.dispatchConsentSaved();
				window.__BORLABS_COOKIE_TEST__.dispatchConsentSaved();
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
			await harness.load( BORLABS_TEST_DOUBLE );
			await harness.load( BORLABS_ADAPTER );
			await page.evaluate( () => {
				window.__BORLABS_COOKIE_TEST__.initialize( { basicrum: true } );
			} );
			await harness.waitForExecutions( 1 );
			await page.evaluate( () => {
				document.cookie = 'RT=test-round-trip; path=/; SameSite=Strict';
				document.cookie = 'BA=test-bandwidth; path=/; SameSite=Strict';
				window.__BORLABS_COOKIE_TEST__.saveConsent( 'basicrum', false );
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

		test( 'fails closed when the Borlabs consent API becomes unavailable', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( BORLABS_TEST_DOUBLE );
			await harness.load( BORLABS_ADAPTER );
			await page.evaluate( () => {
				window.__BORLABS_COOKIE_TEST__.initialize( { basicrum: true } );
			} );
			await harness.waitForExecutions( 1 );
			await page.evaluate( () => {
				delete window.BorlabsCookie.Consents;
				window.dispatchEvent( new window.Event( 'borlabs-cookie-consent-saved' ) );
			} );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				disableCalls: 1,
			} );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'requires a reload after withdrawal before Borlabs can re-grant', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( BORLABS_TEST_DOUBLE );
			await harness.load( BORLABS_ADAPTER );
			await page.evaluate( () => {
				window.__BORLABS_COOKIE_TEST__.initialize( { basicrum: true } );
			} );
			await harness.waitForExecutions( 1 );
			await page.evaluate( () => {
				window.__BORLABS_COOKIE_TEST__.saveConsent( 'basicrum', false );
				window.__BORLABS_COOKIE_TEST__.saveConsent( 'basicrum', true );
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
