/**
 * End-to-end: the performance and accessibility budget (standard 9.1, 8.10).
 *
 * Runs Lighthouse on the landing page, phone and desktop, and fails when a
 * budget is missed: performance 90 / 95, accessibility 95, LCP 2.5 s, CLS 0.1
 * and TBT 200 ms (the lab stand-in for INP). The numbers are lab numbers from
 * PHP's built-in server; production is measured again in PageSpeed Insights.
 *
 * Usage: node perf.e2e.js <work-dir>   (after setup.sh)
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const BASE = process.env.RSH_E2E_URL || 'http://127.0.0.1:8089';
const work = process.argv[2];

if (!work) {
	console.error('usage: node perf.e2e.js <work-dir>');
	process.exit(2);
}

const BUDGETS = {
	mobile: { performance: 90, accessibility: 95 },
	desktop: { performance: 95, accessibility: 95 }
};
const LIMITS = { 'largest-contentful-paint': 2500, 'cumulative-layout-shift': 0.1, 'total-blocking-time': 200 };

(async () => {
	const { default: lighthouse } = await import('lighthouse');
	const { default: desktopConfig } = await import('lighthouse/core/config/desktop-config.js');
	const chromeLauncher = await import('chrome-launcher');

	const chrome = await chromeLauncher.launch({
		chromePath: chromium.executablePath(),
		chromeFlags: ['--headless=new', '--no-sandbox', '--disable-gpu']
	});

	let failed = 0;

	for (const device of ['mobile', 'desktop']) {
		const result = await lighthouse(
			BASE + '/',
			{ port: chrome.port, output: 'json', logLevel: 'error', onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'] },
			'desktop' === device ? desktopConfig : undefined
		);
		const report = result.lhr;
		const scores = {};

		Object.keys(report.categories).forEach((key) => {
			scores[key] = Math.round(report.categories[key].score * 100);
		});

		console.log('\n' + device + ': ' + JSON.stringify(scores));

		Object.keys(BUDGETS[device]).forEach((key) => {
			const ok = scores[key] >= BUDGETS[device][key];
			failed += ok ? 0 : 1;
			console.log((ok ? '  ok   ' : '  FAIL ') + key + ' ' + scores[key] + ' (budget ' + BUDGETS[device][key] + ')');
		});

		Object.keys(LIMITS).forEach((key) => {
			const value = report.audits[key].numericValue;
			const ok = value <= LIMITS[key];
			failed += ok ? 0 : 1;
			console.log((ok ? '  ok   ' : '  FAIL ') + key + ' ' + (value < 10 ? value.toFixed(3) : Math.round(value)) + ' (limit ' + LIMITS[key] + ')');
		});

		const flagged = Object.values(report.audits)
			.filter((audit) => audit.score !== null && audit.score < 0.9 && audit.scoreDisplayMode !== 'informative' && audit.scoreDisplayMode !== 'manual')
			.map((audit) => audit.id + (audit.displayValue ? ' (' + audit.displayValue + ')' : ''));
		if (flagged.length) {
			console.log('  note: ' + flagged.join(', '));
		}

		fs.writeFileSync(path.join(work, 'lighthouse-' + device + '.json'), JSON.stringify(report));
	}

	await chrome.kill();
	console.log('\n' + (failed ? failed + ' budget(s) missed' : 'all budgets met'));
	process.exit(failed ? 1 : 0);
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
