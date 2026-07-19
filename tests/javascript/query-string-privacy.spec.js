const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { test, expect } = require( '@playwright/test' );

const REPOSITORY_ROOT = path.resolve( __dirname, '../..' );
const BOOMERANG_SOURCE = fs.readFileSync(
	path.join(
		REPOSITORY_ROOT,
		'plugins/basicrum/assets/js/boomr/boomerang-1.815.60.cutting-edge.min.js'
	),
	'utf8'
);

const PAGE_ORIGIN = 'https://query-privacy.example.test';
const BOOMERANG_URL = `${ PAGE_ORIGIN }/boomerang.js`;
const COLLECTOR_URL = 'https://collector.basicrum.test/beacon';
const SECRET = 'BASICRUM_PRIVATE_QUERY_VALUE_7c0f1e';
const PAGE_URL = `${ PAGE_ORIGIN }/?search=${ SECRET }`;
const REFERRER_URL = `${ PAGE_ORIGIN }/previous-page?source=${ SECRET }`;
const RESOURCE_URL = `${ PAGE_ORIGIN }/private-resource.css?token=${ SECRET }`;

/**
 * Merge GET and POST beacon parameters.
 *
 * @param {import('@playwright/test').Request} request Beacon request.
 * @return {URLSearchParams} Decoded beacon parameters.
 */
function parseBeaconParameters( request ) {
	const parameters = new URL( request.url() ).searchParams;
	const postData = request.postData();

	if ( postData ) {
		for ( const [ key, value ] of new URLSearchParams( postData ) ) {
			parameters.set( key, value );
		}
	}

	return parameters;
}

test( 'strips query strings from every real Boomerang beacon field', async ( { page } ) => {
	const beacons = [];
	const unexpectedRequests = [];
	const config = {
		beacon_url: COLLECTOR_URL,
		instrument_xhr: false,
		strip_query_string: true,
		Continuity: { enabled: false },
		ResourceTiming: {
			enabled: true,
			splitAtPath: true,
		},
		secure_cookie: true,
		same_site_cookie: 'Strict',
	};

	await page.route( '**/*', async ( route ) => {
		const request = route.request();
		const requestUrl = request.url();

		if ( PAGE_URL === requestUrl ) {
			await route.fulfill( {
				status: 200,
				contentType: 'text/html; charset=utf-8',
				body: `<!doctype html>
<html>
<head>
<link rel="icon" href="data:,">
<link rel="stylesheet" href="${ RESOURCE_URL }">
<script>
window.BOOMR = { url: ${ JSON.stringify( BOOMERANG_URL ) } };
window.basicRumBoomerangConfig = ${ JSON.stringify( config ) };
</script>
<script src="${ BOOMERANG_URL }"></script>
</head>
<body>Query-string privacy test</body>
</html>`,
			} );
			return;
		}

		if ( RESOURCE_URL === requestUrl ) {
			await route.fulfill( {
				status: 200,
				contentType: 'text/css; charset=utf-8',
				body: 'body { color: #222; }',
			} );
			return;
		}

		if ( BOOMERANG_URL === requestUrl ) {
			await route.fulfill( {
				status: 200,
				contentType: 'application/javascript; charset=utf-8',
				body: BOOMERANG_SOURCE,
			} );
			return;
		}

		if ( requestUrl.startsWith( COLLECTOR_URL ) ) {
			beacons.push( parseBeaconParameters( request ) );
			await route.fulfill( { status: 204 } );
			return;
		}

		unexpectedRequests.push( requestUrl );
		await route.abort( 'blockedbyclient' );
	} );

	const response = await page.goto( PAGE_URL, {
		waitUntil: 'load',
		referer: REFERRER_URL,
	} );

	expect( response ).not.toBeNull();
	expect( response.ok() ).toBeTruthy();
	await expect.poll( () => beacons.length ).toBeGreaterThan( 0 );

	const serializedBeacons = JSON.stringify(
		beacons.map( ( parameters ) => [ ...parameters.entries() ] )
	);

	expect( serializedBeacons ).not.toContain( SECRET );
	expect(
		beacons.some( ( parameters ) => {
			const pageUrl = parameters.get( 'u' );
			return pageUrl && pageUrl.includes( '?qs-redacted' );
		} )
	).toBe( true );
	expect(
		beacons.some( ( parameters ) => {
			const referrerUrl = parameters.get( 'r' );
			return referrerUrl && referrerUrl.includes( '?qs-redacted' );
		} )
	).toBe( true );
	expect(
		beacons.some( ( parameters ) => {
			const resourceTiming = parameters.get( 'restiming' );
			return resourceTiming && resourceTiming.includes( 'qs-redacted' );
		} )
	).toBe( true );
	expect( unexpectedRequests ).toEqual( [] );
} );
