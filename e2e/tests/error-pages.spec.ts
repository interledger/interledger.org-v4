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

	test('admin route is protected (403 or login redirect) for anonymous', async ({ page, context }) => {
		const res = await context.request.get('/admin', { maxRedirects: 0 });
		// Accept direct 403, or a redirect to login.
		const status = res.status();
		const location = res.headers()['location'] || '';
		const isLoginRedirect = status >= 300 && status < 400 && /\/user\/login/i.test(location);
		expect(status === 403 || isLoginRedirect).toBeTruthy();

		await page.goto('/admin');
		// If redirected, ensure we landed on login, else 403 content is shown.
		if (page.url().includes('/user/login')) {
			await expect(page.getByRole('heading', { name: /log in/i })).toBeVisible();
		} else {
			await expect(page.getByText(/(access denied|forbidden|403)/i)).toBeVisible();
		}
	});
});


