import { test, expect } from '@playwright/test';

test.describe('Error pages', () => {
	test('unknown path returns a 404 and themed page', async ({ page, context }) => {
		const randomPath = `/this-path-should-not-exist-${Date.now()}`;

		const res = await context.request.get(randomPath);
		expect([404, 410]).toContain(res.status());

		await page.goto(randomPath);
		// Allow either standard "Page not found" text or a generic themed 404 heading.
		const notFoundText = page.getByText(/(page not found|not found|404)/i);
		await expect(notFoundText).toBeVisible();
	});
});


