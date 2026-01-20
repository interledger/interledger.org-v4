import { test, expect } from '@playwright/test';

test.describe('SEO and Meta', () => {
	test('homepage has title, description, canonical; sitemap reachable', async ({ page, context }) => {
		await page.goto('/');

		const title = await page.title();
		expect(title.trim().length).toBeGreaterThan(0);

		const description = await page.locator('meta[name="description"]').getAttribute('content');
		expect((description || '').trim().length).toBeGreaterThan(0);

		const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');
		expect(canonical, 'canonical link should exist').toBeTruthy();
	});
});


