const path = require( 'node:path' );
const { test, expect } = require( '@playwright/test' );
const {
	CONSENT_LOADERS,
	createLoaderHarness,
} = require( './fixtures/browser' );

const REPOSITORY_ROOT = path.resolve( __dirname, '../..' );
const COOKIEYES_ADAPTER = path.join(
	REPOSITORY_ROOT,
	'plugins/basicrum/assets/js/integrations/cookieyes.js'
);
const COOKIEYES_TEST_DOUBLE = path.join(
	__dirname,
	'fixtures/cookieyes-test-double.js'
);

for ( const loaderPath of CONSENT_LOADERS ) {
	test.describe( `Connected CookieYes fallback adapter with ${ path.basename( loaderPath ) }`, () => {
		test( 'fails closed when CookieYes is unavailable', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );

			await page.evaluate( () => {
				document.cookie = 'RT=test-round-trip; path=/; SameSite=Strict';
				document.cookie = 'BA=test-bandwidth; path=/; SameSite=Strict';
			} );
			await harness.load( loaderPath );
			await harness.load( COOKIEYES_ADAPTER );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
			} );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => [ 'RT', 'BA' ].includes( cookie.name ) ) ).toBe( false );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'applies saved analytics opt-in when CookieYes initializes', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( COOKIEYES_TEST_DOUBLE );
			await harness.load( COOKIEYES_ADAPTER );
			await page.evaluate( () => {
				window.__COOKIEYES_TEST__.initialize( { analytics: true } );
			} );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( await page.evaluate( () => (
				window.__COOKIEYES_TEST__.getConsentCalls()
			) ) ).toBeGreaterThan( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'reads the current choice when the adapter loads after CookieYes initialization', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( COOKIEYES_TEST_DOUBLE );
			await page.evaluate( () => {
				window.__COOKIEYES_TEST__.initialize( { analytics: true } );
			} );
			await harness.load( COOKIEYES_ADAPTER );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'requires the callback wrapper before CookieYes reports its choice', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( COOKIEYES_TEST_DOUBLE );
			await harness.load( COOKIEYES_ADAPTER );
			await page.evaluate( () => {
				window.__COOKIEYES_TEST__.initialize( { analytics: true } );
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
			await harness.load( COOKIEYES_TEST_DOUBLE );
			await harness.load( COOKIEYES_ADAPTER );
			await page.evaluate( () => {
				window.__COOKIEYES_TEST__.initialize( { analytics: false } );
			} );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'does not treat Performance consent as Analytics consent', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( COOKIEYES_TEST_DOUBLE );
			await harness.load( COOKIEYES_ADAPTER );
			await page.evaluate( () => {
				window.__COOKIEYES_TEST__.initialize( {
					analytics: false,
					performance: true,
				} );
			} );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'loads from the documented accepted array after a consent update', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( COOKIEYES_TEST_DOUBLE );
			await harness.load( COOKIEYES_ADAPTER );
			await page.evaluate( () => {
				window.__COOKIEYES_TEST__.initialize( { analytics: false } );
				window.__COOKIEYES_TEST__.setCategory( 'analytics', true );
			} );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 1 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'loads once across duplicate CookieYes consent events', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( COOKIEYES_TEST_DOUBLE );
			await harness.load( COOKIEYES_ADAPTER );
			await page.evaluate( () => {
				window.__COOKIEYES_TEST__.initialize( { analytics: true } );
				window.__COOKIEYES_TEST__.dispatchConsentUpdate();
				window.__COOKIEYES_TEST__.dispatchConsentUpdate();
			} );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 1 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'uses the documented rejected state to withdraw and clear cookies', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( COOKIEYES_TEST_DOUBLE );
			await harness.load( COOKIEYES_ADAPTER );
			await page.evaluate( () => {
				window.__COOKIEYES_TEST__.initialize( { analytics: true } );
			} );
			await harness.waitForExecutions( 1 );
			await page.evaluate( () => {
				document.cookie = 'RT=test-round-trip; path=/; SameSite=Strict';
				document.cookie = 'BA=test-bandwidth; path=/; SameSite=Strict';
				window.__COOKIEYES_TEST__.setCategory( 'analytics', false );
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

		test( 'fails closed when CookieYes provides no current state', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( COOKIEYES_TEST_DOUBLE );
			await harness.load( COOKIEYES_ADAPTER );
			await page.evaluate( () => {
				window.__COOKIEYES_TEST__.initialize( { analytics: true } );
			} );
			await harness.waitForExecutions( 1 );
			await page.evaluate( () => {
				delete window.getCkyConsent;
				window.__COOKIEYES_TEST__.dispatchBannerLoad();
			} );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				disableCalls: 1,
			} );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'requires a reload after withdrawal before CookieYes can re-grant', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( COOKIEYES_TEST_DOUBLE );
			await harness.load( COOKIEYES_ADAPTER );
			await page.evaluate( () => {
				window.__COOKIEYES_TEST__.initialize( { analytics: true } );
			} );
			await harness.waitForExecutions( 1 );
			await page.evaluate( () => {
				window.__COOKIEYES_TEST__.setCategory( 'analytics', false );
				window.__COOKIEYES_TEST__.setCategory( 'analytics', true );
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
