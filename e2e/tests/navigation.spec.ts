import { test, expect } from '@playwright/test';

function isInternal(href: string): boolean {
	return !!href && href.startsWith('/') && !href.startsWith('//') && !href.startsWith('/#');
}

test.describe('Navigation', () => {
	test('main navigation links resolve and pages have an H1', async ({ page, context }) => {
		await page.goto('/');
		const nav = page.getByRole('navigation').first();
		await expect(nav).toBeVisible();

		// Collect a few internal links from the nav
		const hrefs = await nav.locator('a[href]').evaluateAll((as: Element[]) =>
			Array.from(
				new Set(
					as
						.map(a => (a as HTMLAnchorElement).getAttribute('href') || '')
						.filter(Boolean)
				)
			)
		);

		const internal = hrefs.filter(isInternal).slice(0, 5);
		expect(internal.length, 'Expected at least one internal nav link').toBeGreaterThan(0);

		for (const href of internal) {
			const res = await context.request.get(href);
			expect(res.ok(), `GET ${href} -> ${res.status()}`).toBeTruthy();

			await page.goto(href);
			await expect(page.locator('h1')).toBeVisible();
		}
	});
});


