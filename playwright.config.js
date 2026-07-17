const { defineConfig } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: './tests/javascript',
	fullyParallel: false,
	forbidOnly: Boolean( process.env.CI ),
	retries: process.env.CI ? 1 : 0,
	workers: process.env.CI ? 1 : undefined,
	reporter: process.env.CI ? 'line' : 'list',
	timeout: 15_000,
	expect: {
		timeout: 5_000,
	},
	use: {
		browserName: 'chromium',
		headless: true,
		ignoreHTTPSErrors: true,
		actionTimeout: 5_000,
		navigationTimeout: 10_000,
	},
	outputDir: 'test-results/playwright',
} );
