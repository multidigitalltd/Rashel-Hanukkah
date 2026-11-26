/**
 * End-to-end: the dashboard, the courses and the leads mini CRM, as an
 * administrator, and the same doors as someone who is not allowed in.
 *
 * Run after landing.e2e.js: it reads the leads that script submitted.
 *
 * Usage: node admin.e2e.js <work-dir>   (after setup.sh and landing.e2e.js)
 */
'use strict';

const { BASE, chromium, check, axe, watch, signIn, acknowledgePrivacy, privacyVersion, finish } = require('./lib');

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

	await page.fill('#rsh-coupon', 'light25');
	await save(page);
	const couponed = await frontHtml(browser);
	check('a new coupon code reaches the benefits and every card, in capitals', (await page.inputValue('#rsh-coupon')) === 'LIGHT25' && (couponed.match(/rsh-coupon__code/g) || []).length === 12 && couponed.includes('LIGHT25') && !couponed.includes('>H50<'));
	await page.fill('#rsh-coupon', 'H50 50%');
	await save(page);
	check('a coupon with anything but letters, digits and hyphens is refused', (await page.locator('.notice-warning').count()) === 1 && (await page.inputValue('#rsh-coupon')) === 'LIGHT25');
	await page.fill('#rsh-coupon', '');
	await save(page);
	check('an empty coupon removes every mention of it from the page', !(await frontHtml(browser)).includes('rsh-coupon'));
	await page.fill('#rsh-coupon', 'H50');
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
	await page.fill('#rsh-webhook_url', 'https://crm.example.test/hook');
	await save(page);
	check('a webhook address without a signing key warns that leads will not be sent unsigned', (await page.locator('.rsh-admin .notice-warning.inline').count()) === 1 && (await page.inputValue('#rsh-webhook_url')) === 'https://crm.example.test/hook');
	await page.fill('#rsh-webhook_secret', 'e2e-webhook-signing-key');
	await save(page);
	check('and the warning goes once a key is saved', (await page.locator('.rsh-admin .notice-warning.inline').count()) === 0);
	await page.fill('#rsh-webhook_url', '');
	await page.check('input[name="rsh[webhook_secret_clear]"]');
	await save(page);
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
	check('a course carries its syllabus for editing', (await page.inputValue('#rsh-course-syllabus')).includes('מאפים | ') && (await page.inputValue('#rsh-course-syllabus')).includes('* בונוס | '));
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

	/* ---------------------------------------------------------------- */
	console.log('\nPhotos and galleries');
	const MEDIA = DASHBOARD + '&tab=media';
	await page.goto(MEDIA, { waitUntil: 'load' });
	check('the media tab lists every replaceable file and both galleries', (await page.locator('[data-rsh-media]').count()) === 14 && (await page.locator('[data-rsh-list]').count()) === 2);
	const mediaViolations = (await axe(page)).filter((violation) => /rsh-/.test(violation.target));
	check('axe finds no violations in the media tab', mediaViolations.length === 0, JSON.stringify(mediaViolations));
	check('with no photos chosen, the page has no graduates\' work section', !(await frontHtml(browser)).includes('rsh-works'));

	// Twelve small photos, uploaded through the REST API as the administrator, and the ID of a page (not a photo).
	const uploaded = await page.evaluate(async () => {
		const nonce = await (await fetch('/wp-admin/admin-ajax.php?action=rest-nonce', { credentials: 'same-origin' })).text();
		const send = async (blob, name, alt) => {
			const body = new FormData();
			body.append('file', blob, name);
			if (alt) {
				body.append('alt_text', alt);
			}
			const response = await fetch('/wp-json/wp/v2/media', { method: 'POST', credentials: 'same-origin', headers: { 'X-WP-Nonce': nonce }, body });
			return (await response.json()).id;
		};
		const ids = [];
		for (let n = 1; n <= 12; n++) {
			const canvas = document.createElement('canvas');
			canvas.width = 900;
			canvas.height = 700;
			const pen = canvas.getContext('2d');
			pen.fillStyle = 'hsl(' + n * 29 + ', 55%, 62%)';
			pen.fillRect(0, 0, 900, 700);
			pen.fillStyle = '#3a2a2e';
			pen.font = 'bold 240px sans-serif';
			pen.fillText(String(n), 320, 440);
			const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.8));
			ids.push(await send(blob, 'work-' + String(n).padStart(2, '0') + '.jpg', n === 6 ? 'עוגיות מעוצבות של תלמידה' : ''));
		}
		const pages = await (await fetch('/wp-json/wp/v2/pages?per_page=1', { credentials: 'same-origin' })).json();
		return { ids, page: pages[0].id };
	});
	check('the test photos were uploaded', uploaded.ids.length === 12 && uploaded.ids.every((id) => Number.isInteger(id)) && Number.isInteger(uploaded.page), JSON.stringify(uploaded));

	await page.goto(MEDIA, { waitUntil: 'load' });
	const works = page.locator('[data-rsh-list]').first();
	await works.locator('[data-rsh-list-add]').click();
	await page.click('.media-modal:visible #menu-item-browse');
	await page.waitForSelector('.media-modal .attachments .attachment');
	await page.locator('.media-modal .attachments .attachment').first().click();
	await page.locator('.media-modal .attachments .attachment').nth(11).click({ modifiers: ['Shift'] });
	await page.click('.media-modal .media-button-select');
	check('photos chosen in the media library join the gallery list', (await works.locator('[data-rsh-list-item]').count()) === 12);

	const listIds = () => works.locator('[data-rsh-list-id]').evaluateAll((inputs) => inputs.map((input) => Number(input.value)));
	const firstOrder = await listIds();
	await works.locator('[data-rsh-list-item]').first().locator('[data-rsh-list-down]').click();
	const moved = await listIds();
	check('a photo can be moved with a button, and focus stays on that button', moved[0] === firstOrder[1] && moved[1] === firstOrder[0] && await page.evaluate(() => document.activeElement.hasAttribute('data-rsh-list-down')));
	await works.locator('[data-rsh-list-item]').last().locator('[data-rsh-list-remove]').click();
	const kept = await listIds();
	check('and removed', kept.length === 11);

	const portrait = page.locator('[data-rsh-media]:has(#rsh-portrait)');
	await portrait.locator('[data-rsh-media-pick]').click();
	await page.click('.media-modal:visible #menu-item-browse');
	await page.waitForSelector('.media-modal:visible .attachments .attachment');
	await page.locator('.media-modal:visible .attachments .attachment').first().click();
	await page.click('.media-modal:visible .media-button-select');
	const portraitId = Number(await page.inputValue('#rsh-portrait'));
	check('a single photo can be replaced from the media library', uploaded.ids.includes(portraitId) && (await portrait.locator('[data-rsh-media-reset]').isVisible()));
	await save(page);
	check('the media form saves', (await page.locator('.notice-success').count()) === 1 && (await listIds()).join() === kept.join());

	const withWorks = await frontHtml(browser);
	const worksOrder = [...withWorks.matchAll(/<li class="rsh-work[^"]*" data-rsh-batch="\d">\s*<img[^>]+src="[^"]*\/work-(\d+)[^"]*"/g)].map((match) => Number(match[1]));
	const expectedOrder = kept.map((id) => uploaded.ids.indexOf(id) + 1);
	check('the gallery reaches the page in the saved order', worksOrder.join() === expectedOrder.join(), worksOrder.join() + ' / ' + expectedOrder.join());
	check('a photo\'s alt text comes from the media library, with a fallback for the rest', withWorks.includes('alt="עוגיות מעוצבות של תלמידה"') && withWorks.includes('alt="עבודה של תלמידה"'));
	check('the replaced portrait is served from the uploads folder', /class="rsh-about"[\s\S]{0,1500}wp-content\/uploads/.test(withWorks));

	await page.evaluate((id) => {
		document.getElementById('rsh-portrait').value = String(id);
		const forged = document.createElement('input');
		forged.type = 'hidden';
		forged.name = 'rsh[works][]';
		forged.value = '999999';
		document.querySelector('.rsh-admin__form').appendChild(forged);
	}, uploaded.page);
	await save(page);
	check('a file that is not a photo, and an ID that does not exist, are refused; the rest stays', (await page.locator('.notice-warning').count()) === 1 && Number(await page.inputValue('#rsh-portrait')) === portraitId && (await listIds()).join() === kept.join());

	await page.locator('[data-rsh-media]:has(#rsh-portrait) [data-rsh-media-reset]').click();
	await save(page);
	check('"back to the original file" restores the bundled photo', (await frontHtml(browser)).includes('rashel-portrait.webp'));
	check('media changes are in the audit log', (await (await context.request.get(DASHBOARD + '&tab=log')).text()).includes('שינוי תמונות'));

	const gallery = await browser.newContext({ viewport: { width: 1280, height: 900 } });
	await acknowledgePrivacy(gallery, await privacyVersion(browser));
	const wide = await gallery.newPage();
	await wide.goto(BASE + '/', { waitUntil: 'load' });
	const visible = (target) => target.locator('.rsh-work').evaluateAll((items) => items.filter((item) => getComputedStyle(item).display !== 'none').length);
	await wide.locator('.rsh-works').scrollIntoViewIfNeeded();
	check('the section sits right after the testimonials, with nine photos and "show more"', await wide.evaluate(() => document.querySelector('.rsh-proof').nextElementSibling.classList.contains('rsh-works')) && (await visible(wide)) === 9 && (await wide.locator('[data-rsh-works-more]').isVisible()));
	const galleryViolations = await axe(wide);
	check('axe finds no violations with the gallery on the page', galleryViolations.length === 0, JSON.stringify(galleryViolations));
	await wide.click('[data-rsh-works-more]');
	check('"show more" opens the rest, moves focus to the first new photo and then leaves', (await visible(wide)) === 11 && await wide.evaluate(() => document.activeElement === document.querySelectorAll('.rsh-work')[9]) && !(await wide.locator('[data-rsh-works-more]').isVisible()));
	await wide.setViewportSize({ width: 375, height: 780 });
	await wide.reload({ waitUntil: 'load' });
	await wide.locator('.rsh-works').scrollIntoViewIfNeeded();
	check('on a phone the grid starts with six photos', (await visible(wide)) === 6);
	await wide.click('[data-rsh-works-more]');
	const second = await visible(wide);
	await wide.click('[data-rsh-works-more]');
	check('and opens in two more steps', second === 9 && (await visible(wide)) === 11 && !(await wide.locator('[data-rsh-works-more]').isVisible()));
	check('the grid never makes the phone page scroll sideways', await wide.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth));
	await gallery.close();

	const plainGallery = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 1280, height: 900 } });
	const still = await plainGallery.newPage();
	await still.goto(BASE + '/', { waitUntil: 'load' });
	check('without JavaScript every photo is shown and there is no "show more"', (await still.locator('.rsh-work:visible').count()) === 11 && (await still.locator('[data-rsh-works-more]:visible').count()) === 0);
	await plainGallery.close();
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
	const editorMedia = await editorContext.request.post(ADMIN + 'admin-post.php', { form: { action: 'rsh_save', rsh_tab: 'media', 'rsh[portrait]': '1' } });
	check('and so is a media save', editorMedia.status() === 403);
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
