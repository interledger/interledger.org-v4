import { defineConfig, devices } from '@playwright/test';

const isCI = !!process.env.CI;
const baseURL = process.env.BASE_URL || 'http://localhost:8080';

export default defineConfig({
	testDir: './tests',
	fullyParallel: true,
	retries: isCI ? 2 : 0,
	reporter: isCI ? [['list'], ['html', { outputFolder: 'playwright-report', open: 'never' }]] : [['list'], ['html']],
	use: {
		baseURL,
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure'
	},
	// When running locally against the default docker-compose stack,
	// automatically ensure it's up before tests. Skip in CI or when BASE_URL is custom.
	webServer: !isCI && baseURL.startsWith('http://localhost:8080')
		? {
			command: 'make -C ../local up',
			url: 'http://localhost:8080',
			reuseExistingServer: true,
			timeout: 120_000
		}
		: undefined,
	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] }
		},
		{
			name: 'firefox',
			use: { ...devices['Desktop Firefox'] }
		},
		{
			name: 'webkit',
			use: { ...devices['Desktop Safari'] }
		}
	]
	// If you later want the tests to start the local stack automatically,
	// consider wiring a webServer here (e.g., make -C local up) and a custom
	// timeout/wait condition. For now we expect the target site to be running.
});


