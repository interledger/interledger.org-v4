import { test, expect } from '@playwright/test';

test.describe('Console and runtime errors', () => {
	test('homepage has no console errors', async ({ page }) => {
		const errors: string[] = [];
		page.on('console', msg => {
			if (msg.type() === 'error') {
				errors.push(msg.text());
			}
		});
		page.on('pageerror', err => {
			errors.push(`PageError: ${err.message}`);
		});

		await page.goto('/');
		await page.waitForLoadState('networkidle');
		expect(errors.join('\n')).toBe('');
	});
});


