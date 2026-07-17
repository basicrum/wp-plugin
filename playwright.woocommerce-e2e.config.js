const { defineConfig } = require( '@playwright/test' );

const baseURL = process.env.WOOCOMMERCE_E2E_BASE_URL;

if ( ! baseURL ) {
	throw new Error( 'WOOCOMMERCE_E2E_BASE_URL must be set for WooCommerce E2E tests.' );
}

module.exports = defineConfig( {
	testDir: './tests/e2e',
	fullyParallel: false,
	forbidOnly: Boolean( process.env.CI ),
	retries: process.env.CI ? 1 : 0,
	workers: 1,
	reporter: process.env.CI
		? [
			[ 'line' ],
			[ 'html', { outputFolder: 'test-results/woocommerce-e2e/report', open: 'never' } ],
		]
		: 'list',
	timeout: 45_000,
	expect: {
		timeout: 20_000,
	},
	use: {
		baseURL,
		browserName: 'chromium',
		headless: true,
		ignoreHTTPSErrors: true,
		actionTimeout: 10_000,
		navigationTimeout: 30_000,
		screenshot: 'only-on-failure',
		trace: 'retain-on-failure',
		video: 'retain-on-failure',
	},
	outputDir: 'test-results/woocommerce-e2e/results',
} );
