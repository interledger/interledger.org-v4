import { test, expect } from '@playwright/test';

test.describe('Smoke', () => {
	test('homepage loads successfully', async ({ page }) => {
		const response = await page.goto('/');
		expect(response, 'Response should be defined').toBeTruthy();
		expect(response?.ok(), `Expected 2xx/3xx but got ${response?.status()}`).toBeTruthy();
		await expect(page.locator('body')).toBeVisible();
	});

	test('has a non-empty <title>', async ({ page }) => {
		await page.goto('/');
		const title = await page.title();
		expect(title.trim().length).toBeGreaterThan(0);
	});
});


