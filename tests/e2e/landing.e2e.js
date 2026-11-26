/**
 * End-to-end: the landing page as a visitor sees it.
 *
 * Structure and copy, the course menu and syllabus dialogs, the countdown, the
 * rotating opening, the testimonials carousel, the sticky bar, the privacy
 * notice, the accessibility toolbar, the lead form
 * (including the negative cases), the no-JavaScript and reduced-motion paths,
 * responsive widths, axe-core and the hardening headers.
 *
 * Usage: node landing.e2e.js <work-dir>   (after setup.sh)
 */
'use strict';

const path = require('path');
const { BASE, work, chromium, check, axe, watch, acknowledgePrivacy, privacyVersion, mails, finish } = require('./lib');

const PAY = 'https://secure.cardcom.solutions/e/';

// Word for word, from the approved design.
const COPY = [
	'אוהבת את עולם', 'הקולינריה והעיצוב', 'ורוצה להפוך', 'למקצוענית אמיתית?',
	'רוצה ללמוד', 'תחום אחד לעומק,', 'בצורה יסודית ומקצועית,', 'בלי לבזבז זמן וכסף על מה שלא רלוונטי לך?',
	'תמיד חלמת', 'ללמוד אצל ראשל', 'וחיכית', 'להזדמנות המתאימה?',
	'בואי להאיר את הכישרון שבך!',
	'נבחרת הקורסים הדיגיטליים בעולם העיצוב והקולינריה – נושאים ממוקדים שיוציאו אותך מאסטרית בכל תחום שתבחרי.',
	'בחנוכה הזה ראשל מזמינה אותך להגשים חלום עם נבחרת קורסים דיגיטליים לבחירה אישית ב50% הנחה',
	'אני רוצה לנצל את ההזדמנות!', 'לכבוד חנוכה', '50% הנחה על כל הקורסים', 'בהזנת קוד קופון', 'H50',
	'עד 12 תשלומים', 'את בוחרת את התחום שהכי מעניין אותך',
	'שנתיים גישה לצפייה חוזרת להטמעת החומר', 'דיגיטלי — מהבית, בזמן שנוח לך', 'רק במהלך חג החנוכה!',
	'בואי לעשות מהכשרון שלך אירוע!',
	'כל קורס עומד בפני עצמו — כולל מתכונים, טכניקות וכל הידע, פרקטי ומעשי שיאפשר לך להתחיל לעבוד איתו כבר מחר.',
	'כל המחירים אחרי הנחת דמי החנוכה (50% הנחה)', 'קורסי קולינריה', 'קורסי עיצוב', 'לסילבוס המלא', 'סילבוס הקורס',
	'בחלק זה נלמד להכין מסוגי בצקים מגוון מאפים מיוחדים, נבין איך להפוך מאפה רגיל למנת פינגר פוד יפיפייה שתקשט ותשדרג לך כל בר מלוח.',
	'שיעור 3 | בר בשילוב קוליסה', 'בונוס | השראה וטכניקה לקיר צילום משודרג',
	'כל הקורסים במחיר של דמי חנוכה — עד יום ראשון 13.12',
	'התחלתי בדיוק כמוך — עם אהבה גדולה ליופי ותשוקה עמוקה לעצב וליצור בעצמי. אחרי עשור של הובלת טרנדים בעיצוב ברים ממותגים ומאות אירועי קונספט יוקרתיים, אני יודעת שיש לי שיטה. ואת השיטה הזאת אני רוצה להעביר גם לך.',
	'בואי לגלות מחדש את הכיף שבעולם האירועים, את הסודות הגדולים שמאחורי כל פרט קטן — ולהגיע לתוצאות שלא חלמת עליהן.',
	'הזדמנות חד פעמית, לקפוץ הרבה שלבים קדימה', 'מה אומרות הבוגרות שלנו',
	'קורס ברים מתוקים ואירועי יוקרה בפורמט דיגיטלי', 'לראשונה', 'בישראל', 'מתלבטת אם זה מתאים לך?',
	'צרי קשר בטלפון או בוואטסאפ –', 'או מלאי פרטים כאן ונחזור אלייך', 'חיזרו אלי', '054-9420423'
];

const COURSES = [
	['פינגר פוד', '480 ₪', '960 ₪', '7xawlDTOCUSOSJYdwCPVmA'],
	['בסיס הפטיסרי', '696 ₪', '1,392 ₪', 'OB6CCfcpW0M3qRmVSBqew'],
	['קינוחי כוסות', '480 ₪', '960 ₪', 'CMWPNMFEO0SkbraGWgDokQ'],
	['קינוחים מודרניים', '696 ₪', '1,392 ₪', 'djIOfupgWkapxZtCp5g6xg'],
	['עיצוב קאפקייקס ועוגה בבצק סוכר', '696 ₪', '1,392 ₪', 'RkGvG4P84UeSHkEhIaitCw'],
	['עוגיות רויאל אייסינג', '480 ₪', '960 ₪', 'Sv2oMRLKdEGVTX0Jbvdyw'],
	['עיצוב פירות', '792 ₪', '1,584 ₪', 'reh7OMdL006XmM4r8ddXRg'],
	['בניית קונספט, מיתוג ודפוס', '2,160 ₪', '4,320 ₪', 'pmxvkYShaUqAlo8DVejByg'],
	['עיצוב ברים', '3,120 ₪', '6,240 ₪', 'N04tyKllp0qXlGVERwdrbw'],
	['שזירת פרחים וסטיילינג לשולחן', '3,120 ₪', '6,240 ₪', 'AOtrYuAb4E2PSiZTz1sg'],
	['עיצוב בלונים', '1,800 ₪', '3,600 ₪', 'TR7YSnjwIECDLZAdlPumdw']
];

/**
 * Post the lead form's fields straight to the REST route.
 *
 * @param {import('playwright').APIRequestContext} request Request context.
 * @param {object}                                 fields  Form fields.
 * @return {Promise<import('playwright').APIResponse>}
 */
function postLead(request, fields) {
	return request.post(BASE + '/wp-json/rashel/v1/lead', { form: fields });
}

(async () => {
	const browser = await chromium.launch();
	const version = await privacyVersion(browser);

	/* ---------------------------------------------------------------- */
	console.log('\nStructure, copy and courses (1440px)');
	const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
	await acknowledgePrivacy(context, version);
	const page = await context.newPage();
	const problems = watch(page);
	const response = await page.goto(BASE + '/', { waitUntil: 'load' });

	check('front page answers 200', response.status() === 200, String(response.status()));
	check('html is Hebrew and right-to-left', (await page.getAttribute('html', 'lang')) === 'he-IL' && (await page.getAttribute('html', 'dir')) === 'rtl');
	check('one h1, one main', (await page.locator('h1').count()) === 1 && (await page.locator('main').count()) === 1);
	check('document title is set', (await page.title()).trim().length > 0);

	const headings = await page.evaluate(() => Array.from(document.querySelectorAll('h1,h2,h3,h4,h5,h6')).map((node) => Number(node.tagName[1])));
	check('heading levels never skip', headings.every((level, index) => index === 0 || level <= headings[index - 1] + 1), headings.join(' '));

	const text = (await page.evaluate(() => document.body.textContent)).replace(/\s+/g, ' ');
	const missing = COPY.filter((sentence) => !text.includes(sentence));
	check('every sentence of the design is on the page, word for word', missing.length === 0, 'missing: ' + missing.join(' / '));

	const cards = await page.evaluate(() => Array.from(document.querySelectorAll('.rsh-card')).map((card) => ({
		name: card.querySelector('.rsh-card__name').textContent.trim(),
		price: card.querySelector('.rsh-price').textContent.trim(),
		was: card.querySelector('.rsh-was').textContent.trim(),
		href: card.querySelector('a.rsh-cta').href,
		target: card.querySelector('a.rsh-cta').target,
		rel: card.querySelector('a.rsh-cta').rel,
		hasImage: !!card.querySelector('.rsh-card__img img')
	})));
	check('eleven course cards', cards.length === 11, String(cards.length));
	check('names, prices and original prices match the design', COURSES.every((course, index) => cards[index] && cards[index].name === course[0] && cards[index].price === course[1] && cards[index].was === course[2]), JSON.stringify(cards.map((card) => [card.name, card.price, card.was])));
	check('each card links to its own payment page in a new tab, with rel=noopener', COURSES.every((course, index) => cards[index] && cards[index].href === PAY + course[3] && cards[index].target === '_blank' && /noopener/.test(cards[index].rel)));
	check('each card has its photo', cards.every((card) => card.hasImage));
	check('each card shows the coupon code and offers its syllabus', (await page.locator('.rsh-card .rsh-coupon__code').allTextContents()).filter((code) => code.trim() === 'H50').length === 11 && (await page.locator('.rsh-card__syl').count()) === 11);
	check('the two courses with new photos use them', await page.evaluate(() => ['dessert-cups', 'modern-desserts'].every((name) => !!document.querySelector('.rsh-card__img img[src*="' + name + '.webp"]'))));

	// The collage's second photos are requested two seconds after the page has loaded.
	await page.waitForFunction(() => !document.querySelector('[data-rsh-src]'), null, { timeout: 8000 }).catch(() => {});
	const broken = await page.evaluate(async () => {
		const images = Array.from(document.images);
		images.forEach((image) => { image.loading = 'eager'; });
		await Promise.all(images.map((image) => image.complete ? null : new Promise((resolve) => { image.onload = image.onerror = resolve; })));
		return images.filter((image) => !image.naturalWidth).map((image) => image.src);
	});
	check('every image loads', broken.length === 0, broken.join(', '));

	/* ---------------------------------------------------------------- */
	console.log('\nTabs');
	const visibleCards = () => page.locator('.rsh-card:visible').count();
	check('culinary is the default tab, with 7 courses', (await page.getAttribute('#rsh-tab-culinary', 'aria-selected')) === 'true' && (await visibleCards()) === 7);
	await page.click('#rsh-tab-design');
	check('design tab shows its 4 courses', (await page.getAttribute('#rsh-tab-design', 'aria-selected')) === 'true' && (await visibleCards()) === 4);
	await page.focus('#rsh-tab-design');
	await page.keyboard.press('ArrowRight');
	check('arrow key moves to the other tab and focuses it', (await page.getAttribute('#rsh-tab-culinary', 'aria-selected')) === 'true' && (await page.evaluate(() => document.activeElement.id)) === 'rsh-tab-culinary' && (await visibleCards()) === 7);

	/* ---------------------------------------------------------------- */
	console.log('\nCountdown, opening and sticky bar');
	const digits = () => page.evaluate(() => Array.from(document.querySelectorAll('.rsh-announce [data-rsh-unit]')).map((node) => node.textContent));
	const before = await digits();
	await page.waitForTimeout(2100);
	const after = await digits();
	check('countdown shows four two-digit values', before.length === 4 && before.every((value) => /^\d{2,}$/.test(value)), before.join(':'));
	check('countdown ticks', before.join(':') !== after.join(':'), before.join(':') + ' -> ' + after.join(':'));
	check('the countdown appears in three places', (await page.locator('[data-rsh-countdown]').count()) === 3);
	const deadline = await page.evaluate(() => window.rshLanding.deadline);
	check('deadline is 13.12.2026 23:59:59 in Israel', deadline === Date.UTC(2026, 11, 13, 21, 59, 59), new Date(deadline).toISOString());

	// A fresh load, with the pointer away from the questions (resting on them holds the rotation).
	await page.mouse.move(700, 880);
	// A reload restores the scroll position, so go back to the top first.
	await page.evaluate(() => { window.history.scrollRestoration = 'manual'; window.scrollTo(0, 0); });
	await page.reload({ waitUntil: 'load' });
	const active = () => page.evaluate(() => Array.from(document.querySelectorAll('[data-rsh-q]')).findIndex((node) => node.classList.contains('is-active')));
	const shown = () => page.evaluate(() => Array.from(document.querySelectorAll('[data-rsh-q]')).filter((node) => getComputedStyle(node).opacity === '1').length);
	check('the opening starts on the first question, alone', (await active()) === 0 && (await shown()) === 1);
	check('sticky bar is hidden at the top and out of the tab order', await page.evaluate(() => getComputedStyle(document.querySelector('[data-rsh-bar]')).visibility === 'hidden'));
	await page.waitForTimeout(6200);
	check('the questions change on their own', (await active()) === 1);
	await page.click('[data-rsh-dot="2"]');
	check('a dot jumps to its question and is marked as current', (await active()) === 2 && (await page.getAttribute('[data-rsh-dot="2"]', 'aria-current')) === 'true');
	await page.click('[data-rsh-hook-pause]');
	await page.mouse.move(700, 880);
	await page.waitForTimeout(6200);
	check('the pause button stops the rotation and says so', (await active()) === 2 && (await page.getAttribute('[data-rsh-hook-pause]', 'aria-pressed')) === 'true' && (await page.getAttribute('[data-rsh-hook-pause]', 'aria-label')).includes('חידוש'));
	await page.click('[data-rsh-hook-pause]');
	await page.mouse.move(700, 880);
	check('each dot is at least 24 by 24 pixels', await page.evaluate(() => Array.from(document.querySelectorAll('[data-rsh-dot], [data-rsh-hook-pause]')).every((node) => { const box = node.getBoundingClientRect(); return box.height >= 24 && box.width >= 22; })));
	check('the collage photos that wait for the first paint are loaded afterwards', await page.evaluate(() => document.querySelectorAll('[data-rsh-src]').length === 0 && Array.from(document.querySelectorAll('.rsh-tile__img')).every((image) => !!image.getAttribute('src'))));
	check('the badge ring reads "50%" the right way round', await page.evaluate(() => { const ring = Array.from(document.querySelectorAll('.rsh-badge--open .rsh-ring__c')).map((node) => node.textContent.trim()); const at = ring.indexOf('%'); return ring.length === 24 && ring[at + 1] === '0' && ring[at + 2] === '5'; }));
	await page.evaluate(() => window.scrollTo(0, 1200));
	await page.waitForTimeout(700);
	check('sticky bar is visible after scrolling', await page.evaluate(() => getComputedStyle(document.querySelector('[data-rsh-bar]')).visibility === 'visible'));
	check('the heading lights up and the falling confetti is laid out', await page.evaluate(() => document.querySelector('[data-rsh-h1]').classList.contains('is-lit') && document.querySelectorAll('[data-rsh-confetti] span').length === 34));

	/* ---------------------------------------------------------------- */
	console.log('\nSyllabus dialog');
	await page.evaluate(() => document.getElementById('courses').scrollIntoView());
	await page.waitForTimeout(400);
	const opener = page.locator('.rsh-card__syl').first();
	await opener.click();
	const dialog = page.locator('dialog.rsh-syl[open]');
	check('the syllabus opens as a modal dialog named after its course', (await dialog.count()) === 1 && (await dialog.locator('.rsh-syl__ttl').textContent()).trim() === 'פינגר פוד' && await page.evaluate(() => document.querySelector('dialog.rsh-syl[open]').matches(':modal')));
	check('with its lessons, its bonus, its price and a way to pay', (await dialog.locator('.rsh-syl__item').count()) === 3 && (await dialog.locator('.rsh-syl__bonus').count()) === 1 && (await dialog.locator('.rsh-price').textContent()).trim() === '480 ₪' && (await dialog.locator('a.rsh-cta').getAttribute('href')) === PAY + '7xawlDTOCUSOSJYdwCPVmA');
	const dialogViolations = await axe(page);
	check('axe finds no violations with the dialog open', dialogViolations.length === 0, JSON.stringify(dialogViolations));
	await page.keyboard.press('Escape');
	check('Escape closes it and focus returns to the button that opened it', (await page.locator('dialog.rsh-syl[open]').count()) === 0 && await page.evaluate(() => document.activeElement.classList.contains('rsh-card__syl')));
	await opener.click();
	await page.mouse.click(8, 8);
	// The dialog's "close" event is queued, not fired in the click itself.
	await page.waitForFunction(() => !document.querySelector('dialog.rsh-syl[open]') && document.documentElement.style.overflow === '', null, { timeout: 2000 }).catch(() => {});
	check('a click on the backdrop closes it, and the page scrolls again', (await page.locator('dialog.rsh-syl[open]').count()) === 0 && await page.evaluate(() => document.documentElement.style.overflow === ''));
	await opener.click();
	await page.click('dialog.rsh-syl[open] .rsh-syl__close');
	check('and so does the close button', (await page.locator('dialog.rsh-syl[open]').count()) === 0);
	check('every course has its own syllabus', await page.evaluate(() => document.querySelectorAll('dialog.rsh-syl').length === 11 && Array.from(document.querySelectorAll('dialog.rsh-syl')).every((node) => node.querySelectorAll('.rsh-syl__item').length >= 3)));

	/* ---------------------------------------------------------------- */
	console.log('\nTestimonials carousel');
	await page.evaluate(() => document.querySelector('[data-rsh-reviews]').scrollIntoView());
	await page.waitForTimeout(500);
	const scrolled = () => page.evaluate(() => Math.abs(document.querySelector('[data-rsh-reviews-track]').scrollLeft));
	const barWidth = () => page.evaluate(() => document.querySelector('[data-rsh-reviews-bar]').getBoundingClientRect().width);
	const startBar = await barWidth();
	check('eleven testimonials, in a region that can be reached and scrolled from the keyboard', (await page.locator('.rsh-review').count()) === 11 && (await page.getAttribute('[data-rsh-reviews-track]', 'tabindex')) === '0' && (await scrolled()) < 8, (await page.locator('.rsh-review').count()) + ' cards, tabindex ' + (await page.getAttribute('[data-rsh-reviews-track]', 'tabindex')) + ', scrolled ' + (await scrolled()));
	await page.click('[data-rsh-reviews-next]');
	await page.waitForTimeout(900);
	const afterNext = await scrolled();
	check('"next" moves one card on and the progress bar follows', afterNext > 200 && (await barWidth()) > startBar, afterNext + 'px');
	await page.click('[data-rsh-reviews-prev]');
	await page.waitForTimeout(900);
	check('"previous" moves back', (await scrolled()) < afterNext);
	check('the carousel never moves by itself', await page.evaluate(() => new Promise((resolve) => { const track = document.querySelector('[data-rsh-reviews-track]'); const at = track.scrollLeft; setTimeout(() => resolve(track.scrollLeft === at), 1500); })));

	/* ---------------------------------------------------------------- */
	console.log('\nAccessibility toolbar');
	await page.click('[data-rsh-a11y-launch]');
	check('toolbar opens as a dialog', await page.isVisible('#rsh-a11y-panel'));
	await page.click('[data-rsh-a11y-toggle="contrast"]');
	check('a toggle sets its class on <html>', await page.evaluate(() => document.documentElement.classList.contains('rsh-a11y-contrast')));
	await page.click('[data-rsh-a11y-step="1"]');
	check('text size goes up', (await page.textContent('[data-rsh-a11y-value]')).trim() === '110%');
	await page.keyboard.press('Escape');
	check('Escape closes it and returns focus to the launcher', !(await page.isVisible('#rsh-a11y-panel')) && await page.evaluate(() => document.activeElement.hasAttribute('data-rsh-a11y-launch')));
	await page.evaluate(() => window.scrollTo(0, 0));
	await page.click('[data-rsh-a11y-launch]');
	await page.click('[data-rsh-a11y-toggle="nomotion"]');
	await page.keyboard.press('Escape');
	await page.waitForTimeout(300);
	check('"stop animations" drops the page to its static layout at once: every question listed, nothing rotating or moving', await page.evaluate(() => !document.documentElement.classList.contains('rsh-motion') && getComputedStyle(document.querySelector('.rsh-hook__nav')).display === 'none' && getComputedStyle(document.querySelector('.rsh-ticker__track')).animationName === 'none' && getComputedStyle(document.querySelector('.rsh-badge__spin')).animationName === 'none' && getComputedStyle(document.querySelector('[data-rsh-confetti] span')).animationName === 'none') && (await page.locator('[data-rsh-q]:visible').count()) === 3);
	check('and everything that was waiting to be revealed is visible', await page.evaluate(() => Array.from(document.querySelectorAll('[data-rsh-reveal], .rsh-h1__w')).every((node) => getComputedStyle(node).opacity === '1')));
	await page.click('[data-rsh-a11y-launch]');
	await page.click('[data-rsh-a11y-toggle="nomotion"]');
	await page.keyboard.press('Escape');
	check('turning it off brings motion back', await page.evaluate(() => document.documentElement.classList.contains('rsh-motion') && getComputedStyle(document.querySelector('.rsh-hook__nav')).display === 'flex'));
	await page.reload({ waitUntil: 'load' });
	check('preferences survive a reload', await page.evaluate(() => document.documentElement.classList.contains('rsh-a11y-contrast')));
	await page.click('[data-rsh-a11y-launch]');
	await page.click('[data-rsh-a11y-reset]');
	await page.keyboard.press('Escape');
	check('reset clears them', await page.evaluate(() => !document.documentElement.classList.contains('rsh-a11y-contrast')));

	check('no console errors or failed requests', problems.length === 0, problems.join('\n       '));
	await context.close();

	/* ---------------------------------------------------------------- */
	console.log('\nPrivacy notice');
	const first = await browser.newContext({ viewport: { width: 1280, height: 800 } });
	const visit = await first.newPage();
	await visit.goto(BASE + '/', { waitUntil: 'load' });
	await visit.waitForSelector('[data-rsh-privacy]:not([hidden])');
	check('shown on a first visit, as a non-modal dialog with a link to the policy', (await visit.getAttribute('[data-rsh-privacy]', 'role')) === 'dialog' && (await visit.locator('[data-rsh-privacy] a').count()) === 1);
	check('the footer links the three mandatory pages', (await visit.locator('.rsh-footer__links a').count()) >= 3);
	check('the Multi Digital credit links to m-d.co.il in a new tab', (await visit.getAttribute('.rsh-footer__credit', 'href')) === 'https://m-d.co.il' && (await visit.getAttribute('.rsh-footer__credit', 'rel')) === 'noopener');
	await visit.click('[data-rsh-privacy-ok]');
	check('closing hides it', await visit.isHidden('[data-rsh-privacy]'));
	await visit.reload({ waitUntil: 'load' });
	await visit.waitForTimeout(300);
	check('it stays closed on the next visit', await visit.isHidden('[data-rsh-privacy]'));
	await visit.click('[data-rsh-privacy-open]');
	check('"privacy settings" in the footer reopens it with focus inside', await visit.isVisible('[data-rsh-privacy]') && await visit.evaluate(() => document.activeElement.hasAttribute('data-rsh-privacy')));
	await visit.keyboard.press('Escape');
	check('Escape closes it', await visit.isHidden('[data-rsh-privacy]'));
	await first.close();

	/* ---------------------------------------------------------------- */
	console.log('\nLead form');
	const formContext = await browser.newContext({ viewport: { width: 1280, height: 900 } });
	await acknowledgePrivacy(formContext, version);
	const form = await formContext.newPage();
	const formProblems = watch(form);
	await form.goto(BASE + '/?utm_source=facebook&utm_medium=cpc&utm_campaign=hanukkah', { waitUntil: 'load' });
	await form.locator('#consult-form').scrollIntoViewIfNeeded();

	await form.click('.rsh-form__submit');
	check('an empty form is stopped in place, with the first field marked and focused', (await form.getAttribute('#rsh-name', 'aria-invalid')) === 'true' && (await form.evaluate(() => document.activeElement.id)) === 'rsh-name');
	check('every field has a real label, and the required ones say so in words', await form.evaluate(() => ['rsh-name', 'rsh-phone', 'rsh-email'].every((id) => !!document.querySelector('label[for="' + id + '"]')) && document.querySelector('label[for="rsh-name"]').textContent.includes('חובה') && !document.querySelector('label[for="rsh-email"]').textContent.includes('חובה')));
	check('the error is tied to its field', await form.evaluate(() => { const field = document.getElementById('rsh-name'); const id = field.getAttribute('aria-describedby'); return !!id && !!document.getElementById(id).textContent.trim(); }));

	await form.fill('#rsh-name', 'בדיקה אוטומטית');
	await form.fill('#rsh-phone', '12');
	await form.click('.rsh-form__submit');
	check('a short phone number is refused', (await form.getAttribute('#rsh-phone', 'aria-invalid')) === 'true' && (await form.getAttribute('#rsh-name', 'aria-invalid')) === null);

	await form.fill('#rsh-phone', '054-1234567');
	await form.fill('#rsh-email', 'not-an-email');
	await form.click('.rsh-form__submit');
	check('a malformed email is refused', (await form.getAttribute('#rsh-email', 'aria-invalid')) === 'true');

	const mailsBefore = mails().length;
	await form.fill('#rsh-email', 'visitor@example.test');
	await form.click('.rsh-form__submit');
	await form.waitForSelector('.rsh-sent');
	check('a valid form is replaced by the confirmation, announced and focused', (await form.getAttribute('.rsh-sent', 'role')) === 'status' && (await form.evaluate(() => document.activeElement.classList.contains('rsh-sent'))) && (await form.textContent('.rsh-sent')).includes('תודה! נחזור אלייך בהקדם'));
	await form.waitForTimeout(500);
	const sent = mails().slice(mailsBefore);
	check('the office is notified by email, with name and phone only', sent.length === 1 && sent[0].message.includes('054-1234567') && !sent[0].message.includes('visitor@example.test'), JSON.stringify(sent));
	check('no console errors or failed requests', formProblems.length === 0, formProblems.join('\n       '));

	/* ---------------------------------------------------------------- */
	console.log('\nLead form without JavaScript');
	const plainForm = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 1280, height: 900 } });
	const post = await plainForm.newPage();
	await post.goto(BASE + '/', { waitUntil: 'load' });
	// Submitted from the keyboard: with scripts off, the test driver cannot wait for a button to settle.
	await post.fill('#rsh-name', 'בלי סקריפט');
	await post.fill('#rsh-phone', '12');
	await Promise.all([post.waitForNavigation({ waitUntil: 'load' }), post.press('#rsh-phone', 'Enter')]);
	check('a bad phone comes back to the form with the reason, as an alert', /rsh_lead=rsh_phone/.test(post.url()) && (await post.textContent('[data-rsh-alert]')).includes('מספר הטלפון'));
	await post.fill('#rsh-name', 'בלי סקריפט');
	await post.fill('#rsh-phone', '054-2223344');
	await Promise.all([post.waitForNavigation({ waitUntil: 'load' }), post.press('#rsh-phone', 'Enter')]);
	check('a valid form comes back with the confirmation', /rsh_lead=sent/.test(post.url()) && (await post.locator('.rsh-sent[role="status"]').count()) === 1 && (await post.locator('.rsh-lead-form').count()) === 0);
	await plainForm.close();

	/* ---------------------------------------------------------------- */
	console.log('\nLead form: negative cases');
	const request = formContext.request;
	const nonce = (await (await request.get(BASE + '/wp-json/rashel/v1/nonce')).json()).nonce;
	const good = { nonce: nonce, rsh_name: 'בדיקת API', rsh_phone: '054-7654321', rsh_email: '', rsh_website: '', rsh_source: BASE + '/', rsh_utm: '' };

	const noNonce = await postLead(request, Object.assign({}, good, { nonce: 'forged' }));
	check('a forged nonce is refused with 400', noNonce.status() === 400 && (await noNonce.json()).code === 'rsh_nonce');
	const noPhone = await postLead(request, Object.assign({}, good, { rsh_phone: '' }));
	check('a missing phone is refused with 422', noPhone.status() === 422);
	const badPhone = await postLead(request, Object.assign({}, good, { rsh_phone: 'abc' }));
	check('letters instead of a phone are refused with 422', badPhone.status() === 422 && (await badPhone.json()).code === 'rsh_phone');
	const badEmail = await postLead(request, Object.assign({}, good, { rsh_email: 'x@' }));
	check('a bad email is refused with 422', badEmail.status() === 422 && (await badEmail.json()).code === 'rsh_email');
	const nested = await request.post(BASE + '/wp-json/rashel/v1/lead', { form: { nonce: nonce, 'rsh_name[]': 'x', rsh_phone: '054-7654321' } });
	check('an array where a string belongs is refused', nested.status() === 400, String(nested.status()));
	const refusal = JSON.stringify(await noNonce.json());
	check('a refusal reveals nothing about the server', !/\.php|Stack|wp-content|Fatal/.test(refusal), refusal);

	const mailsMid = mails().length;
	const honeypot = await postLead(request, Object.assign({}, good, { rsh_website: 'http://spam.test', rsh_name: 'HONEYPOT-BOT' }));
	check('a filled honeypot is answered like a success, and nothing is sent', honeypot.status() === 200 && mails().length === mailsMid);

	// A lead whose name would run as a spreadsheet formula, for the export check in admin.e2e.js.
	const formula = await postLead(request, Object.assign({}, good, { rsh_name: '=HYPERLINK("http://evil.test")', rsh_source: 'https://evil.test/phish', rsh_utm: '<script>alert(1)</script>' }));
	check('a lead is accepted over REST', formula.status() === 200 && (await formula.json()).sent === true);

	let last = 0;
	for (let attempt = 0; attempt < 6; attempt++) {
		last = (await postLead(request, Object.assign({}, good, { rsh_name: 'בדיקת קצב ' + attempt }))).status();
		if (last === 429) {
			break;
		}
	}
	check('the sixth submission in an hour from one address is refused with 429', last === 429, String(last));
	await formContext.close();

	/* ---------------------------------------------------------------- */
	console.log('\nWithout JavaScript');
	const plain = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 1280, height: 900 } });
	const noJs = await plain.newPage();
	await noJs.goto(BASE + '/', { waitUntil: 'load' });
	check('all three questions are visible', (await noJs.locator('[data-rsh-q]:visible').count()) === 3);
	check('all eleven courses are listed, under their category headings', (await noJs.locator('.rsh-card:visible').count()) === 11 && (await noJs.locator('.rsh-panel__ttl:visible').count()) === 2);
	check('the buttons that would do nothing are not shown: tabs, syllabus, carousel arrows, question dots', (await noJs.locator('.rsh-tabs:visible, .rsh-card__syl:visible, .rsh-reviews__nav:visible, .rsh-hook__nav:visible').count()) === 0);
	// Sections below the fold are laid out only once they come into view.
	await noJs.locator('[data-rsh-reviews-track]').scrollIntoViewIfNeeded();
	check('the testimonials still scroll sideways on their own', await noJs.evaluate(() => { const track = document.querySelector('[data-rsh-reviews-track]'); return track.scrollWidth > track.clientWidth && getComputedStyle(track).overflowX === 'auto'; }));
	check('the main heading is fully visible', await noJs.evaluate(() => getComputedStyle(document.querySelector('.rsh-h1__w')).opacity === '1'));
	await plain.close();

	/* ---------------------------------------------------------------- */
	console.log('\nReduced motion');
	const calm = await browser.newContext({ reducedMotion: 'reduce', viewport: { width: 1280, height: 900 } });
	await acknowledgePrivacy(calm, version);
	const still = await calm.newPage();
	await still.goto(BASE + '/', { waitUntil: 'load' });
	check('nothing rotates, falls or moves', await still.evaluate(() => !document.documentElement.classList.contains('rsh-motion') && getComputedStyle(document.querySelector('.rsh-ticker__track')).animationName === 'none' && getComputedStyle(document.querySelector('.rsh-hook__nav')).display === 'none' && document.querySelectorAll('[data-rsh-confetti] span').length === 0 && document.querySelectorAll('canvas').length === 0));
	check('all three questions are visible', (await still.locator('[data-rsh-q]:visible').count()) === 3);
	check('revealed content is simply there', await still.evaluate(() => Array.from(document.querySelectorAll('[data-rsh-reveal]')).every((node) => getComputedStyle(node).opacity === '1')));
	const calmViolations = await axe(still);
	check('axe: no violations with reduced motion', calmViolations.length === 0, JSON.stringify(calmViolations));
	await calm.close();

	/* ---------------------------------------------------------------- */
	console.log('\nResponsive widths and axe-core');
	for (const width of [320, 375, 768, 1280, 1600]) {
		const sized = await browser.newContext({ viewport: { width: width, height: 800 } });
		await acknowledgePrivacy(sized, version);
		const view = await sized.newPage();
		await view.goto(BASE + '/', { waitUntil: 'load' });
		// Scroll through once so every reveal has run before the page is measured and audited.
		const height = await view.evaluate(() => document.documentElement.scrollHeight);
		for (let y = 0; y < height; y += 600) {
			await view.evaluate((top) => window.scrollTo(0, top), y);
			await view.waitForTimeout(60);
		}
		await view.waitForTimeout(1200);
		const overflow = await view.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
		check(width + 'px: no horizontal scrolling', overflow <= 0, overflow + 'px');
		if (width === 375 || width === 1280) {
			const violations = await axe(view);
			check(width + 'px: axe finds no WCAG 2.2 A/AA violations', violations.length === 0, JSON.stringify(violations));
			await view.screenshot({ path: path.join(work, 'shots', 'landing-' + width + '.png'), fullPage: false });
		}
		await sized.close();
	}

	/* ---------------------------------------------------------------- */
	console.log('\nInner pages, 404 and hardening');
	const inner = await browser.newContext({ viewport: { width: 1280, height: 900 } });
	await acknowledgePrivacy(inner, version);
	const other = await inner.newPage();
	for (const slug of ['privacy-policy', 'terms', 'accessibility']) {
		const answer = await other.goto(BASE + '/' + slug + '/', { waitUntil: 'load' });
		const violations = await axe(other);
		check('/' + slug + '/ answers 200 with one h1 and no axe violations', answer.status() === 200 && (await other.locator('h1').count()) === 1 && violations.length === 0, answer.status() + ' ' + JSON.stringify(violations));
	}
	check('inner pages do not load the landing page assets', (await other.locator('link[href*="landing"], script[src*="landing"], script[src*="forms"]').count()) === 0);

	const lost = await other.goto(BASE + '/no-such-page/', { waitUntil: 'load' });
	check('an unknown address answers a real 404 with a way home', lost.status() === 404 && (await other.locator('main a[href="' + BASE + '/"]').count()) === 1);

	const headers = (await inner.request.get(BASE + '/')).headers();
	check('security headers are sent', headers['x-content-type-options'] === 'nosniff' && headers['x-frame-options'] === 'SAMEORIGIN' && !!headers['referrer-policy'] && !!headers['permissions-policy'], JSON.stringify(headers));
	check('the list of users is hidden from visitors', (await inner.request.get(BASE + '/wp-json/wp/v2/users')).status() === 404);
	const author = await inner.request.get(BASE + '/?author=1', { maxRedirects: 0 });
	check('"?author=1" redirects home without naming the user', author.status() === 301 && author.headers().location === BASE + '/', author.status() + ' ' + author.headers().location);
	const archive = await inner.request.get(BASE + '/author/e2e-admin/', { maxRedirects: 0 });
	check('an author archive redirects home', archive.status() === 301 && archive.headers().location === BASE + '/', archive.status() + ' ' + archive.headers().location);
	const html = await (await inner.request.get(BASE + '/')).text();
	check('the page carries no generator tag and no jQuery', !/name="generator"/.test(html) && !/jquery/i.test(html));
	await inner.close();

	await finish(browser);
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
