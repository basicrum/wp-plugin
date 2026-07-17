const path = require( 'node:path' );
const { test, expect } = require( '@playwright/test' );
const {
	STANDARD_LOADERS,
	createLoaderHarness,
} = require( './fixtures/browser' );

for ( const loaderPath of STANDARD_LOADERS ) {
	test.describe( path.basename( loaderPath ), () => {
		test( 'loads Boomerang immediately', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
				snippetExecuted: true,
			} );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'does not inject duplicate scripts when evaluated repeatedly', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.waitForExecutions( 1 );
			await harness.load( loaderPath );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
				snippetExecuted: true,
			} );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'does not load without an own Boomerang URL', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await page.evaluate( () => {
				window.BOOMR = Object.create( { url: 'https://attacker.example/boomr.js' } );
			} );
			await harness.load( loaderPath );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
				snippetExecuted: false,
			} );
			expect( harness.boomerangRequestCount() ).toBe( 0 );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );
	} );
}
