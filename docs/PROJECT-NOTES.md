# Project notes

<!-- Created once from the dev-standard repository. This file belongs to the project: edit it freely and keep it short. No secrets here. -->

## What this project is

The Hanukkah campaign landing page of RASHEL Digital School: one long right-to-left Hebrew page that sells short digital courses in culinary arts and event design at 50% off. The visitor picks a course and goes to that course's payment page at Cardcom, or leaves details for a consultation call. Delivered as a WordPress theme, `rashel-hanukkah/`.

## Risk level

**Level 1.** Decided by the owner on 2026-11-23. Public marketing content and one lead form that stores a name, a phone number and an optional email address. No accounts, no payments on this site (Cardcom hosts the payment pages), no sensitive categories of data.

## Stack

- WordPress 6.8+ (tested on 7.1.5), PHP 8.3+.
- MySQL 8 / MariaDB 10.11 in production. The end-to-end suite runs on SQLite.
- No runtime dependencies, no plugins required, no jQuery, no build step on the server. Composer and npm hold development tools only; both lock files are committed.
- Mail through `wp_mail()`.

## Commands

```bash
composer install                 # dev tools
composer lint                    # PHPCS: WordPress-Extra, WordPress-Docs, PHPCompatibilityWP
composer analyse                 # PHPStan level 8
composer test                    # PHPUnit: the pure logic in inc/logic.php
php bin/build-assets.php         # *.min.css and *.min.js (committed; CI fails when they are stale)
bash bin/build-zip.sh            # dist/rashel-hanukkah.zip, ready to upload

cd tests/e2e && npm ci --ignore-scripts
bash tests/e2e/setup.sh /tmp/rsh-e2e          # throwaway WordPress on SQLite, served on 127.0.0.1:8089
node tests/e2e/landing.e2e.js /tmp/rsh-e2e    # visitor journey, axe-core, negative form cases
node tests/e2e/admin.e2e.js /tmp/rsh-e2e      # dashboard, courses, leads CRM, negative authorization (run after landing)
node tests/e2e/perf.e2e.js /tmp/rsh-e2e       # Lighthouse budget, phone and desktop
```

The end-to-end scripts build on each other and share one rate-limit window: run `setup.sh` again before repeating `landing.e2e.js`.

## Architecture

- `rashel-hanukkah/functions.php` loads one file per responsibility from `inc/`. Templates only print.
  - `inc/logic.php`: pure functions with no WordPress calls (prices, countdown, marked-up copy, CSV cells, Turnstile answer, sealing, webhook signature). Unit-tested on their own.
  - `inc/content.php`: every sentence of the page, declared once with the design's wording as the default. `rsh_text( $key )` returns the dashboard override or the default. Overrides live in the option `rsh_content`.
  - `inc/settings.php`: plain settings (`rsh_settings`) and sealed secrets (`rsh_secrets`, never autoloaded). The sealing key is `RSH_KEY` from `wp-config.php`, or derived from the site salts. A `wp-config.php` constant always wins and locks the field.
  - `inc/courses.php`: post type `rsh_course`. The whole list is one query, cached in the transient `rsh_courses` until a course changes. Payment links are kept only on allowed hosts (`RSH_PAY_HOSTS`, filter `rsh_pay_hosts`).
  - `inc/leads.php`, `inc/leads-crm.php`: post type `rsh_lead` with its own capability. Public REST routes `rashel/v1/nonce` and `rashel/v1/lead`, plus an `admin-post.php` fallback without JavaScript. One function validates and stores: `rsh_process_lead()`.
  - `inc/media.php`: the replaceable photos and videos ("slots") and the two gallery lists. Choices are attachment IDs in the option `rsh_media`; a slot without a choice prints the bundled file. Templates print through `rsh_slot_image()` and `rsh_slot_url()`.
  - `inc/dashboard-media.php` (admin only): the "photos and galleries" tab; `assets/js/admin.js` opens the media library.
  - `inc/dashboard.php` (admin only): the "ראשל חנוכה" screen. One save handler, `admin_post_rsh_save`.
  - `inc/audit.php`: one log for settings, copy and leads, in the option `rsh_audit`.
  - `inc/course-data.php`: the eleven default courses with their syllabus, as plain data.
  - `inc/install.php`: runs once on activation: the eleven courses and the three mandatory pages. `rsh_upgrade()` migrates stored data between versions (option `rsh_installed`).
- Front end: `assets/css/main.css` on every page; `landing.css`, `landing.js` and `forms.js` only where the landing page renders (`rsh_is_landing()`). No inline styles apart from per-word animation delays.
- Motion is opt-in. An inline script in `<head>` adds `rsh-js` to `<html>`, and `rsh-motion` only when neither the system nor the accessibility toolbar asked for reduced motion. Every animated rule sits under `.rsh-motion`; without it the page is static and complete.
- Scheduled jobs (WP-Cron): `rsh_lead_cleanup` daily; `rsh_lead_notify_retry` and `rsh_lead_webhook` as single events.

## Environments

- Production: not deployed yet. Upload `dist/rashel-hanukkah.zip` and activate.
- Staging: required before production (section 10.4). Not set up yet.
- Local: `tests/e2e/setup.sh`, or any WordPress with the theme folder linked in.

## Notes for AI agents

- The Hebrew copy is the client's approved wording. Change defaults in `inc/content.php` only when the client asks; `tests/e2e/landing.e2e.js` fails when a sentence of the design is missing.
- Releases: bump `Version` in `style.css` and `RSH_VERSION` in `functions.php` together, and add a `CHANGELOG.md` entry.
- After editing a file under `assets/css` or `assets/js`, run `php bin/build-assets.php` and commit the `.min` files. The script also writes `landing-bundle.min.js` and `landing-bundle.min.css`, the single files the landing page loads.
- On a phone the opening photos are the LCP element. Keep the first screen light: a new bundled photo there needs smaller copies (`widths` in `rsh_media_slots()`), and nothing should measure layout at start-up.
- The landing page must stay cacheable as one HTML for everyone: no nonce the script depends on, no per-visitor output, no cookie for guests. The form fetches its nonce over REST; the privacy notice decides on the client.
- Small white text never sits on the base mauve or the base course colours; use `--rsh-mauve-strong` and the `strong` shade from `rsh_course_color()` (unit-tested for 4.5:1).
- The long sections below the fold use `content-visibility: auto`; a test that measures them must scroll them into view first.
- Never insert content above existing content after load (layout shift). The privacy notice, the toolbar and the sticky bar are all fixed.
- Leads are personal data: never log their values. `rsh_audit()` takes IDs and field names only.
- Composer in a sandbox without GitHub API access: `composer install --prefer-source` works for everything except `phpstan/phpstan`, which ships as a download only.
