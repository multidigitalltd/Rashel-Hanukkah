/**
 * End-to-end: the dashboard, the courses and the leads mini CRM, as an
 * administrator, and the same doors as someone who is not allowed in.
 *
 * Run after landing.e2e.js: it reads the leads that script submitted.
 *
 * Usage: node admin.e2e.js <work-dir>   (after setup.sh and landing.e2e.js)
 */
'use strict';

const { BASE, chromium, check, axe, watch, signIn, finish } = require('./lib');

const ADMIN = BASE + '/wp-admin/';
const DASHBOARD = ADMIN + 'admin.php?page=rsh-dashboard';
const LEADS = ADMIN + 'edit.php?post_type=rsh_lead';
const SECRET = 'e2e-turnstile-secret-0123456789';

/**
 * Save the dashboard form on the current page and wait for the redirect.
 *
 * @param {import('playwright').Page} page Page.
 */
async function save(page) {
	await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('.rsh-admin__form #submit')]);
}

/**
 * The front page's HTML, fetched without a session.
 *
 * @param {import('playwright').Browser} browser Browser.
 * @return {Promise<string>}
 */
async function frontHtml(browser) {
	const context = await browser.newContext();
	const html = await (await context.request.get(BASE + '/')).text();
	await context.close();
	return html;
}

(async () => {
	const browser = await chromium.launch();

	/* ---------------------------------------------------------------- */
	console.log('\nDashboard (administrator)');
	const context = await browser.newContext({ viewport: { width: 1366, height: 900 }, acceptDownloads: true });
	const page = await context.newPage();
	const problems = watch(page);
	await signIn(page, 'e2e-admin', 'e2e-admin-password');

	const opened = await page.goto(DASHBOARD, { waitUntil: 'domcontentloaded' });
	check('the dashboard opens', opened.status() === 200 && (await page.locator('.rsh-admin h1').count()) === 1);
	check('it is in Hebrew, right to left, with an explanation beside the settings', (await page.getAttribute('.rsh-admin', 'dir')) === 'rtl' && (await page.locator('.rsh-admin .description').count()) >= 6);
	const dashboardViolations = (await axe(page)).filter((violation) => /rsh-/.test(violation.target));
	check('axe finds no violations in the dashboard screen', dashboardViolations.length === 0, JSON.stringify(dashboardViolations));

	await page.fill('#rsh-phone', '03-5550000');
	await save(page);
	check('a saved setting is confirmed', (await page.locator('.notice-success').count()) === 1);
	check('and reaches the front page', (await frontHtml(browser)).includes('03-5550000'));

	await page.fill('#rsh-phone', 'not a phone');
	// The browser would stop an out-of-range number itself; the server must refuse it too.
	await page.evaluate(() => document.getElementById('rsh-discount').removeAttribute('max'));
	await page.fill('#rsh-discount', '500');
	await save(page);
	check('invalid values are refused and reported, and the old values stay', (await page.locator('.notice-warning').count()) === 1 && (await page.inputValue('#rsh-phone')) === '03-5550000' && (await page.inputValue('#rsh-discount')) === '50');

	await page.fill('#rsh-phone', '054-9420423');
	await save(page);

	await page.goto(DASHBOARD + '&tab=texts', { waitUntil: 'domcontentloaded' });
	await page.fill('#rsh-cta', 'להרשמה <script>alert(1)</script>');
	await save(page);
	const changed = await frontHtml(browser);
	check('edited copy reaches the front page, escaped', changed.includes('להרשמה') && !changed.includes('<script>alert(1)</script>') && !changed.includes('אני רוצה לנצל את ההזדמנות!'));
	await page.fill('#rsh-cta', '');
	await save(page);
	check('an emptied field goes back to the design wording', (await frontHtml(browser)).includes('אני רוצה לנצל את ההזדמנות!') && (await page.inputValue('#rsh-cta')) === 'אני רוצה לנצל את ההזדמנות!');

	await page.goto(DASHBOARD + '&tab=connections', { waitUntil: 'domcontentloaded' });
	await page.fill('#rsh-turnstile_secret', SECRET);
	await page.fill('#rsh-webhook_url', 'http://insecure.example.test/hook');
	await save(page);
	const connections = await page.content();
	check('a saved secret is never sent back to the browser', !connections.includes(SECRET) && (await page.inputValue('#rsh-turnstile_secret')) === '' && /מפתח שמור/.test(connections));
	check('a webhook address that is not https is refused', (await page.locator('.notice-warning').count()) === 1 && (await page.inputValue('#rsh-webhook_url')) === '');
	check('the front page does not carry the secret', !(await frontHtml(browser)).includes(SECRET));
	await page.check('input[name="rsh[turnstile_secret_clear]"]');
	await save(page);
	check('a saved secret can be cleared', /לא הוגדר/.test(await page.evaluate(() => document.getElementById('rsh-turnstile_secret').parentNode.textContent)));

	await page.goto(DASHBOARD + '&tab=log', { waitUntil: 'domcontentloaded' });
	const log = await page.textContent('.rsh-admin table');
	check('the audit log names who changed which fields, without values', log.includes('e2e-admin') && log.includes('phone') && log.includes('cta') && log.includes('turnstile_secret: updated') && !log.includes(SECRET) && !log.includes('03-5550000'));

	/* ---------------------------------------------------------------- */
	console.log('\nCourses');
	await page.goto(ADMIN + 'edit.php?post_type=rsh_course', { waitUntil: 'domcontentloaded' });
	check('the eleven courses are listed for editing', (await page.locator('#the-list tr.type-rsh_course').count()) === 11);
	await page.click('#the-list a.row-title:has-text("פינגר פוד")');
	await page.waitForSelector('#rsh-course-price');
	await page.fill('#rsh-course-price', '500');
	await page.fill('#rsh-course-pay', 'https://evil.test/pay');
	await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('#publish')]);
	const priced = await frontHtml(browser);
	check('a new price shows on the front page at once, with its original price', priced.includes('500 ₪') && priced.includes('1,000 ₪'));
	check('a payment link outside the allowed host is not stored', !priced.includes('evil.test') && (await page.inputValue('#rsh-course-pay')) === '');
	await page.fill('#rsh-course-price', '480');
	await page.fill('#rsh-course-pay', 'https://secure.cardcom.solutions/e/7xawlDTOCUSOSJYdwCPVmA');
	await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('#publish')]);
	check('and can be put back', (await frontHtml(browser)).includes('https://secure.cardcom.solutions/e/7xawlDTOCUSOSJYdwCPVmA'));

	/* ---------------------------------------------------------------- */
	console.log('\nLeads mini CRM');
	await page.goto(LEADS, { waitUntil: 'domcontentloaded' });
	const rows = await page.locator('#the-list tr.type-rsh_lead').count();
	const list = await page.textContent('#the-list');
	// landing.e2e.js: the form, the form without JavaScript, one over REST, and two more before the rate limit closed the window.
	check('every accepted submission is stored, and the refused ones are not', rows === 5 && list.includes('בדיקה אוטומטית') && list.includes('בלי סקריפט') && !list.includes('HONEYPOT-BOT'), rows + ' rows');
	check('leads cannot be created by hand', (await page.locator('a.page-title-action').count()) === 0);
	check('a hostile name is shown as text, not run', (await page.locator('#the-list script').count()) === 0 && list.includes('=HYPERLINK'));

	await page.click('#the-list a.row-title:has-text("בדיקה אוטומטית")');
	await page.waitForSelector('#rsh-lead-status');
	const details = await page.textContent('#rsh-lead-details');
	check('a lead shows what was sent, its page, its campaign and the consent record', details.includes('054-1234567') && details.includes('visitor@example.test') && details.includes('facebook / cpc / hanukkah') && details.includes('גרסת המדיניות'));
	await page.selectOption('#rsh-lead-status', 'progress');
	await page.fill('#rsh-lead-notes', 'חזרנו אליה בטלפון');
	await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('#publish')]);
	check('status and notes are saved', (await page.inputValue('#rsh-lead-status')) === 'progress' && (await page.inputValue('#rsh-lead-notes')) === 'חזרנו אליה בטלפון');

	await page.goto(LEADS, { waitUntil: 'domcontentloaded' });
	const [download] = await Promise.all([page.waitForEvent('download'), page.click('.tablenav.top a.button:has-text("CSV")')]);
	const csv = require('fs').readFileSync(await download.path(), 'utf8');
	check('the export is a UTF-8 CSV with every lead', csv.charCodeAt(0) === 0xFEFF && csv.includes('בדיקה אוטומטית') && csv.includes('בטיפול') && csv.trim().split('\n').length === 6, csv.trim().split('\n').length + ' lines');
	check('a cell that starts like a formula is defused', csv.includes('"\'=HYPERLINK(""http://evil.test"")"'), csv.split('\n').find((line) => line.includes('HYPERLINK')));
	check('a source on another site and markup in the campaign were not stored', !csv.includes('evil.test/phish') && !csv.includes('<script>'));

	await page.goto(DASHBOARD + '&tab=log', { waitUntil: 'domcontentloaded' });
	const leadLog = await page.textContent('.rsh-admin table');
	check('viewing, changing and exporting leads are in the audit log', leadLog.includes('צפייה בליד') && leadLog.includes('שינוי סטטוס ליד') && leadLog.includes('ייצוא לידים') && !leadLog.includes('054-1234567'));
	check('no console errors or failed requests', problems.length === 0, problems.join('\n       '));

	const exportUrl = await (async () => {
		await page.goto(LEADS, { waitUntil: 'domcontentloaded' });
		return page.getAttribute('.tablenav.top a.button:has-text("CSV")', 'href');
	})();
	const forged = await context.request.get(ADMIN + 'admin-post.php?action=rsh_leads_export');
	check('an export without its nonce is refused, even for an administrator', forged.status() === 403);
	const forgedSave = await context.request.post(ADMIN + 'admin-post.php', { form: { action: 'rsh_save', rsh_tab: 'settings', 'rsh[phone]': '03-6660000' } });
	check('a settings save without its nonce is refused', forgedSave.status() === 403 && !(await frontHtml(browser)).includes('03-6660000'));
	await context.close();

	/* ---------------------------------------------------------------- */
	console.log('\nNot allowed in: an editor');
	const editorContext = await browser.newContext();
	const editor = await editorContext.newPage();
	await signIn(editor, 'e2e-editor', 'e2e-editor-password');
	check('the dashboard is refused', (await editor.goto(DASHBOARD, { waitUntil: 'domcontentloaded' })).status() === 403);
	check('the leads list is refused', (await editor.goto(LEADS, { waitUntil: 'domcontentloaded' })).status() === 403);
	check('the menus do not offer them', (await editor.locator('#adminmenu a[href*="rsh-dashboard"], #adminmenu a[href*="rsh_lead"]').count()) === 0);
	check('the export is refused even with a nonce taken from an administrator', (await editorContext.request.get(exportUrl)).status() === 403);
	const editorSave = await editorContext.request.post(ADMIN + 'admin-post.php', { form: { action: 'rsh_save', rsh_tab: 'settings', 'rsh[phone]': '03-7770000' } });
	check('a settings save is refused', editorSave.status() === 403);
	const types = await editorContext.request.get(BASE + '/wp-json/wp/v2/types');
	check('leads and courses are not exposed over the REST API', types.status() === 200 && !(await types.text()).includes('rsh_'));
	await editorContext.close();

	/* ---------------------------------------------------------------- */
	console.log('\nNot allowed in: a visitor');
	const anonymous = await browser.newContext();
	const visitorDashboard = await anonymous.request.get(DASHBOARD, { maxRedirects: 0 });
	check('the dashboard sends a visitor to the sign-in page', visitorDashboard.status() === 302 && /wp-login\.php/.test(visitorDashboard.headers().location || ''));
	const visitorExport = await anonymous.request.get(ADMIN + 'admin-post.php?action=rsh_leads_export', { maxRedirects: 0 });
	const visitorExportBody = await visitorExport.text();
	check('the export gives a visitor nothing', visitorExport.status() !== 200 || !visitorExportBody.includes('054-'), String(visitorExport.status()));
	const visitorSave = await anonymous.request.post(ADMIN + 'admin-post.php', { form: { action: 'rsh_save', rsh_tab: 'settings', 'rsh[phone]': '03-8880000' }, maxRedirects: 0 });
	check('a settings save from a visitor changes nothing', visitorSave.status() !== 200 && !(await frontHtml(browser)).includes('03-8880000'), String(visitorSave.status()));
	const leadPage = await anonymous.request.get(BASE + '/?post_type=rsh_lead&p=1');
	check('a lead has no public address', !(await leadPage.text()).includes('054-1234567'));
	await anonymous.close();

	await finish(browser);
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
