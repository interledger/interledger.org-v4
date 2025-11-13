import { test, expect } from '@playwright/test';

test.describe('Images', () => {
	test('visible images have alt text unless decorative', async ({ page }) => {
		await page.goto('/');

		const results = await page.evaluate(() => {
			const imgs = Array.from(document.querySelectorAll('img'));
			return imgs.map(img => {
				const styles = window.getComputedStyle(img);
				const isVisible = styles.display !== 'none' && styles.visibility !== 'hidden' && img.width > 0 && img.height > 0;
				const role = img.getAttribute('role') || '';
				const ariaHidden = img.getAttribute('aria-hidden') === 'true';
				const alt = img.getAttribute('alt');
				return {
					src: img.getAttribute('src') || '',
					isVisible,
					role,
					ariaHidden,
					alt
				};
			});
		});

		const failures = results.filter(r => {
			if (!r.isVisible) return false;
			if (r.ariaHidden) return false;
			if (r.role.toLowerCase() === 'presentation' || r.role.toLowerCase() === 'none') return false;
			return !(r.alt && r.alt.trim().length > 0);
		});

		const message = failures.map(f => `Missing alt: ${f.src}`).join('\n');
		expect(failures, message).toHaveLength(0);
	});
});


