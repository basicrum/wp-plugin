const path = require( 'node:path' );
const { expect } = require( '@playwright/test' );

const REPOSITORY_ROOT = path.resolve( __dirname, '../../..' );
const LOADER_DIRECTORY = path.join(
	REPOSITORY_ROOT,
	'plugins/basicrum/assets/js/loaders'
);

const BOOMERANG_URL = 'https://collector.basicrum.test/boomerang-test.js';

const CONSENT_LOADERS = [
	path.join( LOADER_DIRECTORY, 'consent-boomerang-loader-v1-15.js' ),
	path.join( LOADER_DIRECTORY, 'consent-boomerang-loader-v1-15.min.js' ),
];

const STANDARD_LOADERS = [
	path.join( LOADER_DIRECTORY, 'boomerang-loader-v15.js' ),
	path.join( LOADER_DIRECTORY, 'boomerang-loader-v15.min.js' ),
];

const BOOMERANG_STUB = `
window.__boomrExecutionCount = ( window.__boomrExecutionCount || 0 ) + 1;
window.__boomrDisableCalls = window.__boomrDisableCalls || 0;
window.__boomrRemovedCookies = window.__boomrRemovedCookies || [];
window.BOOMR.version = 'test';
window.BOOMR.disable = function() {
	window.__boomrDisableCalls += 1;
};
window.BOOMR.utils = {
	removeCookie: function( name ) {
		window.__boomrRemovedCookies.push( name );
		document.cookie = name + '=; path=/; max-age=0; SameSite=Strict';
	}
};
`;

/**
 * Create an isolated browser page that serves only the synthetic page and the
 * intercepted Boomerang asset. Every other request is recorded and blocked.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @param {object} options Harness options.
 * @param {string} options.origin Page origin.
 * @return {Promise<object>} Loader test harness.
 */
async function createLoaderHarness( page, { origin = 'https://example.test' } = {} ) {
	const pageUrl = new URL( '/', origin ).href;
	const allowedPageUrls = new Set( [ pageUrl ] );
	const unexpectedRequests = [];
	let boomerangRequests = 0;

	await page.route( '**/*', async ( route ) => {
		const request = route.request();
		const requestUrl = request.url();

		if ( request.isNavigationRequest() && allowedPageUrls.has( requestUrl ) ) {
			await route.fulfill( {
				status: 200,
				contentType: 'text/html; charset=utf-8',
				body: '<!doctype html><html><head><link rel="icon" href="data:,"><title>Basicrum loader test</title></head><body></body></html>',
			} );
			return;
		}

		if ( requestUrl === BOOMERANG_URL ) {
			boomerangRequests += 1;
			await route.fulfill( {
				status: 200,
				contentType: 'application/javascript; charset=utf-8',
				body: BOOMERANG_STUB,
			} );
			return;
		}

		unexpectedRequests.push( requestUrl );
		await route.abort( 'blockedbyclient' );
	} );

	await page.goto( pageUrl );
	await page.evaluate( ( boomerangUrl ) => {
		window.BOOMR = { url: boomerangUrl };
		window.__boomrExecutionCount = 0;
		window.__boomrDisableCalls = 0;
		window.__boomrRemovedCookies = [];
	}, BOOMERANG_URL );

	return {
		origin: new URL( pageUrl ).origin,
		pageUrl,
		async load( loaderPath ) {
			await page.addScriptTag( { path: loaderPath } );
		},
		async state() {
			return page.evaluate( () => ( {
				executionCount: window.__boomrExecutionCount || 0,
				disableCalls: window.__boomrDisableCalls || 0,
				removedCookies: window.__boomrRemovedCookies || [],
				snippetExecuted: Boolean( window.BOOMR && window.BOOMR.snippetExecuted ),
				snippetMethod: window.BOOMR && window.BOOMR.snippetMethod,
				injectedScripts: document.querySelectorAll(
					'#boomr-scr-as, #boomr-if-as, #boomr-async'
				).length,
			} ) );
		},
		async waitForExecutions( expectedCount ) {
			await expect.poll(
				() => page.evaluate( () => window.__boomrExecutionCount || 0 )
			).toBe( expectedCount );
		},
		async cookieStringAt( url ) {
			const targetUrl = new URL( '/', url ).href;
			allowedPageUrls.add( targetUrl );
			await page.goto( targetUrl );
			return page.evaluate( () => document.cookie );
		},
		boomerangRequestCount() {
			return boomerangRequests;
		},
		unexpectedRequests() {
			return [ ...unexpectedRequests ];
		},
	};
}

module.exports = {
	BOOMERANG_URL,
	CONSENT_LOADERS,
	STANDARD_LOADERS,
	createLoaderHarness,
};
