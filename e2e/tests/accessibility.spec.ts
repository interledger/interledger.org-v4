import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test.describe('Accessibility', () => {
	test('homepage has no serious/critical violations', async ({ page }) => {
		await page.goto('/');
		const results = await new AxeBuilder({ page }).analyze();
		const issues = results.violations.filter(v => v.impact === 'critical' || v.impact === 'serious');
		const message = issues.map(v => `${v.impact}: ${v.id} (${v.nodes.length})`).join('\n');
		expect(issues, message).toHaveLength(0);
	});
});


