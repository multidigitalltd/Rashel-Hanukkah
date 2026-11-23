/**
 * Shared helpers for the end-to-end scripts.
 *
 * Each script is a plain Node program: it runs its checks in order, prints one
 * line per check and exits non-zero when any of them failed.
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const BASE = process.env.RSH_E2E_URL || 'http://127.0.0.1:8089';
const work = process.argv[2];

if (!work) {
	console.error('usage: node <script>.e2e.js <work-dir>');
	process.exit(2);
}

const failures = [];
let passed = 0;

/**
 * Record one check.
 *
 * @param {string}  name   What is being checked.
 * @param {boolean} ok     Whether it held.
 * @param {string}  detail Shown when it did not.
 */
function check(name, ok, detail) {
	if (ok) {
		passed++;
		console.log('  ok   ' + name);
		return;
	}
	failures.push(name);
	console.log('  FAIL ' + name + (detail ? '\n       ' + detail : ''));
}

/**
 * Run axe-core on the page and return the WCAG 2.2 A and AA violations.
 *
 * @param {import('playwright').Page} page Page.
 * @return {Promise<Array<{id:string, impact:string, nodes:number, target:string}>>}
 */
async function axe(page) {
	await page.addScriptTag({ path: require.resolve('axe-core/axe.min.js') });
	const result = await page.evaluate(() => window.axe.run(document, {
		runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'] }
	}));
	return result.violations.map((violation) => ({
		id: violation.id,
		impact: violation.impact,
		nodes: violation.nodes.length,
		target: violation.nodes.slice(0, 3).map((node) => node.target.join(' ')).join(' | ')
	}));
}

/**
 * Collect console errors, page errors and failed same-site requests of a page.
 *
 * @param {import('playwright').Page} page Page.
 * @return {string[]} Live list of problems.
 */
function watch(page) {
	const problems = [];
	page.on('console', (message) => {
		// Failed requests are judged below, by address: wp-admin also asks other hosts (avatars), which a sandbox blocks.
		if (message.type() === 'error' && !/Failed to load resource/.test(message.text())) {
			problems.push('console: ' + message.text());
		}
	});
	page.on('pageerror', (error) => problems.push('pageerror: ' + String(error)));
	page.on('response', (response) => {
		// The built-in server has no byte ranges, so a browser aborts the video itself.
		if (response.status() >= 400 && response.url().startsWith(BASE) && !/\.mp4$/.test(response.url())) {
			problems.push(response.status() + ' ' + response.url());
		}
	});
	return problems;
}

/**
 * Mark the first-visit privacy notice as already seen, for tests about something else.
 *
 * @param {import('playwright').BrowserContext} context Context.
 * @param {string}                              version Policy version the page prints.
 */
async function acknowledgePrivacy(context, version) {
	await context.addInitScript((value) => {
		try {
			window.localStorage.setItem('rsh_privacy', JSON.stringify({ v: value, t: Date.now() }));
		} catch (e) {
			// Storage is off: the notice simply shows.
		}
	}, version);
}

/**
 * The policy version the site prints on its privacy notice.
 *
 * @param {import('playwright').Browser} browser Browser.
 * @return {Promise<string>}
 */
async function privacyVersion(browser) {
	const context = await browser.newContext();
	const page = await context.newPage();
	await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
	const version = await page.getAttribute('[data-rsh-privacy]', 'data-rsh-privacy');
	await context.close();
	return version || '1';
}

/**
 * Sign in to wp-admin.
 *
 * @param {import('playwright').Page} page     Page.
 * @param {string}                    user     Login.
 * @param {string}                    password Password.
 */
async function signIn(page, user, password) {
	await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
	await page.fill('#user_login', user);
	await page.fill('#user_pass', password);
	await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('#wp-submit')]);
}

/**
 * Files the mail-capture plugin wrote, parsed.
 *
 * @return {Array<object>}
 */
function mails() {
	const dir = path.join(work, 'mails');
	return fs.existsSync(dir)
		? fs.readdirSync(dir).filter((file) => file.endsWith('.json')).map((file) => JSON.parse(fs.readFileSync(path.join(dir, file), 'utf8')))
		: [];
}

/**
 * Print the summary and exit with the right status.
 *
 * @param {import('playwright').Browser} browser Browser to close.
 */
async function finish(browser) {
	await browser.close();
	console.log('\n' + passed + ' passed, ' + failures.length + ' failed');
	if (failures.length) {
		console.log('failed: ' + failures.join('; '));
	}
	process.exit(failures.length ? 1 : 0);
}

module.exports = { BASE, work, chromium, check, axe, watch, acknowledgePrivacy, privacyVersion, signIn, mails, finish };
