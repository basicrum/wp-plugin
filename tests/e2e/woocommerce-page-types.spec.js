const { test, expect } = require( '@playwright/test' );

const COLLECTOR_URL_PATTERN = 'https://collector.basicrum.test/**';
const FIXTURE_PATH = '/wp-content/uploads/basicrum-woocommerce-e2e.json';
const BRUM_SITE_ID = '550e8400-e29b-41d4-a716-446655440000';

const expectedPageTypes = [
	[ 'shop', 'shop' ],
	[ 'product', 'product' ],
	[ 'cart', 'cart' ],
	[ 'checkout', 'checkout' ],
	[ 'order_received', 'checkout_success' ],
];

let fixture;

function parseBeaconParameters( request ) {
	const parameters = new URL( request.url() ).searchParams;
	const postData = request.postData();

	if ( postData ) {
		const bodyParameters = new URLSearchParams( postData );

		for ( const [ key, value ] of bodyParameters ) {
			parameters.set( key, value );
		}
	}

	return parameters;
}

async function visitAndCaptureBeacon( page, route, expectedPageType, prepare ) {
	const beacons = [];

	await page.route( COLLECTOR_URL_PATTERN, async ( interceptedRoute ) => {
		beacons.push( parseBeaconParameters( interceptedRoute.request() ) );
		await interceptedRoute.fulfill( { status: 204 } );
	} );

	if ( prepare ) {
		await prepare();
	}

	const response = await page.goto( route, { waitUntil: 'domcontentloaded' } );

	expect( response ).not.toBeNull();
	expect( response.ok() ).toBeTruthy();
	await expect( page.locator( '#basicrum-loader-js' ) ).toHaveCount( 1 );

	const inlineConfig = await page.locator( 'script' ).evaluateAll( ( scripts ) => {
		return scripts
			.map( ( script ) => script.textContent || '' )
			.find( ( text ) => text.includes( 'basicRumBoomerangConfig' ) );
	} );

	expect( inlineConfig ).toContain( `"p_type":"${ expectedPageType }"` );

	await expect.poll(
		() => {
			const matchingBeacon = beacons.find(
				( beacon ) => beacon.get( 'p_type' ) === expectedPageType
			);

			return matchingBeacon ? matchingBeacon.get( 'p_type' ) : null;
		},
		{ timeout: 20_000 }
	).toBe( expectedPageType );

	const matchingBeacon = beacons.find(
		( beacon ) => beacon.get( 'p_type' ) === expectedPageType
	);

	expect( matchingBeacon.get( 'p_gen' ) ).toBe( 'wp' );
	expect( matchingBeacon.get( 'brum_site_id' ) ).toBe( BRUM_SITE_ID );
}

test.beforeAll( async ( { request } ) => {
	const response = await request.get( FIXTURE_PATH );

	expect( response.ok() ).toBeTruthy();
	fixture = await response.json();
} );

for ( const [ routeName, expectedPageType ] of expectedPageTypes ) {
	test( `emits ${ expectedPageType } for the WooCommerce ${ routeName } page`, async ( { page } ) => {
		const prepare = routeName === 'checkout'
			? async () => {
				const cartResponse = await page.goto( fixture.routes.cart_add, { waitUntil: 'domcontentloaded' } );

				expect( cartResponse ).not.toBeNull();
				expect( cartResponse.ok() ).toBeTruthy();
			}
			: null;

		await visitAndCaptureBeacon( page, fixture.routes[ routeName ], expectedPageType, prepare );
	} );
}
