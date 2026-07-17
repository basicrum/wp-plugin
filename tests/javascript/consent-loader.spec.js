const path = require( 'node:path' );
const { test, expect } = require( '@playwright/test' );
const {
	CONSENT_LOADERS,
	createLoaderHarness,
} = require( './fixtures/browser' );

for ( const loaderPath of CONSENT_LOADERS ) {
	test.describe( path.basename( loaderPath ), () => {
		test( 'does not load Boomerang before opt-in', async ( { page } ) => {
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

		test( 'loads exactly once when an opted-in cookie already exists', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await page.evaluate( () => {
				document.cookie = 'BRUM_CONSENT="opted-in"; path=/; SameSite=Strict';
			} );
			await harness.load( loaderPath );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
				snippetExecuted: true,
			} );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'loads exactly once across repeated opt-in calls', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await page.evaluate( () => {
				window.OPT_IN_BASIC_RUM();
				window.OPT_IN_BASIC_RUM();
			} );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
				snippetExecuted: true,
			} );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'opts out safely before Boomerang has loaded', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await page.evaluate( () => window.OPT_OUT_BASIC_RUM() );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
				disableCalls: 0,
				removedCookies: [],
			} );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.find( ( cookie ) => 'BRUM_CONSENT' === cookie.name ).value ).toBe( '"opted-out"' );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'disables Boomerang and removes its cookies after opt-out', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );

			await page.evaluate( () => {
				document.cookie = 'RT=test-round-trip; path=/; SameSite=Strict';
				document.cookie = 'BA=test-bandwidth; path=/; SameSite=Strict';
			} );
			await harness.load( loaderPath );
			await page.evaluate( () => window.OPT_IN_BASIC_RUM() );
			await harness.waitForExecutions( 1 );
			await page.evaluate( () => window.OPT_OUT_BASIC_RUM() );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				disableCalls: 1,
				removedCookies: [ 'RT', 'BA' ],
			} );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.find( ( cookie ) => 'BRUM_CONSENT' === cookie.name ).value ).toBe( '"opted-out"' );
			expect( cookies.some( ( cookie ) => 'RT' === cookie.name ) ).toBe( false );
			expect( cookies.some( ( cookie ) => 'BA' === cookie.name ) ).toBe( false );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		for ( const cookieCase of [
			{ origin: 'http://example.test', secure: false },
			{ origin: 'https://app.example.test', secure: true },
			{ origin: 'http://localhost', secure: false },
		] ) {
			test( `sets the expected consent cookie attributes on ${ cookieCase.origin }`, async ( { context, page } ) => {
				const harness = await createLoaderHarness( page, { origin: cookieCase.origin } );

				await harness.load( loaderPath );
				await page.evaluate( () => window.OPT_IN_BASIC_RUM() );
				await harness.waitForExecutions( 1 );

				const cookies = await context.cookies( harness.pageUrl );
				const consentCookie = cookies.find( ( cookie ) => 'BRUM_CONSENT' === cookie.name );

				expect( consentCookie ).toMatchObject( {
					value: '"opted-in"',
					path: '/',
					secure: cookieCase.secure,
					sameSite: 'Strict',
				} );
				expect( consentCookie.expires ).toBeGreaterThan(
					Math.floor( Date.now() / 1000 ) + ( 300 * 24 * 60 * 60 )
				);
				expect( harness.unexpectedRequests() ).toEqual( [] );
			} );
		}

		test( 'shares consent with a child subdomain but not a sibling subdomain', async ( { page } ) => {
			const harness = await createLoaderHarness( page, { origin: 'https://app.example.test' } );

			await harness.load( loaderPath );
			await page.evaluate( () => window.OPT_IN_BASIC_RUM() );
			await harness.waitForExecutions( 1 );

			const childCookieString = await harness.cookieStringAt( 'https://child.app.example.test/' );
			expect( childCookieString ).toContain( 'BRUM_CONSENT=' );

			const siblingCookieString = await harness.cookieStringAt( 'https://shop.example.test/' );
			expect( siblingCookieString ).not.toContain( 'BRUM_CONSENT=' );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );
	} );
}
