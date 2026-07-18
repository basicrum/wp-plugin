const path = require( 'node:path' );
const { test, expect } = require( '@playwright/test' );
const {
	CONSENT_LOADERS,
	createLoaderHarness,
} = require( './fixtures/browser' );

const REPOSITORY_ROOT = path.resolve( __dirname, '../..' );
const GENERIC_OPT_IN = path.join(
	REPOSITORY_ROOT,
	'plugins/basicrum/assets/js/integrations/generic-opt-in.js'
);
const GENERIC_OPT_OUT = path.join(
	REPOSITORY_ROOT,
	'plugins/basicrum/assets/js/integrations/generic-opt-out.js'
);

for ( const loaderPath of CONSENT_LOADERS ) {
	test.describe( `Generic consent snippets with ${ path.basename( loaderPath ) }`, () => {
		test( 'loads Boomerang from the opt-in callback', async ( { page } ) => {
			const harness = await createLoaderHarness( page );

			await harness.load( loaderPath );
			await harness.load( GENERIC_OPT_IN );
			await harness.waitForExecutions( 1 );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 1,
				injectedScripts: 1,
			} );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );

		test( 'keeps Boomerang disabled and removes cookies from the opt-out callback', async ( { context, page } ) => {
			const harness = await createLoaderHarness( page );

			await page.evaluate( () => {
				document.cookie = 'RT=test-round-trip; path=/; SameSite=Strict';
				document.cookie = 'BA=test-bandwidth; path=/; SameSite=Strict';
			} );
			await harness.load( loaderPath );
			await harness.load( GENERIC_OPT_OUT );

			expect( await harness.state() ).toMatchObject( {
				executionCount: 0,
				injectedScripts: 0,
			} );
			const cookies = await context.cookies( harness.pageUrl );
			expect( cookies.some( ( cookie ) => [ 'RT', 'BA' ].includes( cookie.name ) ) ).toBe( false );
			expect( harness.unexpectedRequests() ).toEqual( [] );
		} );
	} );
}
