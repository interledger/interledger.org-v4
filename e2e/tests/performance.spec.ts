import { test, expect } from '@playwright/test';

// Simple budgets suitable for a content site; adjust as needed via env vars.
const MAX_REQUESTS = parseInt(process.env.PERF_MAX_REQUESTS || '', 10) || 100;
const MAX_BYTES = parseInt(process.env.PERF_MAX_BYTES || '', 10) || 3_000_000; // ~3 MB

test.describe('Homepage performance budget', () => {
	test('request count and transfer size within budget', async ({ page, context }) => {
		const responses: { url: string; status: number; bytes: number }[] = [];

		context.on('response', async (res) => {
			try {
				const url = res.url();
				const status = res.status();
				// Prefer Content-Length header if present; otherwise leave 0 (we avoid heavy body reads).
				const len = res.headers()['content-length'];
				const bytes = len ? parseInt(len, 10) || 0 : 0;
				responses.push({ url, status, bytes });
			} catch {
				// ignore
			}
		});

		await page.goto('/');
		await page.waitForLoadState('networkidle');

		const totalRequests = responses.length;
		const totalBytes = responses.reduce((sum, r) => sum + (Number.isFinite(r.bytes) ? r.bytes : 0), 0);

		const offenders = responses
			.slice()
			.sort((a, b) => b.bytes - a.bytes)
			.filter(r => r.bytes > 0)
			.slice(0, 10)
			.map(r => `${(r.bytes / 1024).toFixed(1)} KB - ${r.url}`);

		const summary =
			`Requests: ${totalRequests} (budget <= ${MAX_REQUESTS})\n` +
			`Transfer: ${(totalBytes / (1024 * 1024)).toFixed(2)} MB (budget <= ${(MAX_BYTES / (1024 * 1024)).toFixed(2)} MB)\n` +
			(offenders.length ? `Largest responses:\n${offenders.join('\n')}` : '');

		expect.soft(totalRequests, summary).toBeLessThanOrEqual(MAX_REQUESTS);
		expect(totalBytes, summary).toBeLessThanOrEqual(MAX_BYTES);
	});
});


