# Changelog

All notable changes to this project are listed here. Versions follow [Semantic Versioning](https://semver.org/); the theme version lives in `rashel-hanukkah/style.css` and `RSH_VERSION` and the two are bumped together.

## 2.1.0: 2026-11-26

### Added

- A "graduates' work" photo grid under the testimonials, shown once photos are chosen in the dashboard. Nine photos at a time (six on a phone) with a "show more" button; every photo is visible without JavaScript.
- A "photos and galleries" tab in the dashboard: replace any photo or video of the page from the media library, return to the bundled file, and manage the work gallery and the testimonial screenshots (add, reorder, remove). Every ID is checked on the server and changes are written to the audit log.

## 2.0.0: 2026-11-25

The landing page rebuilt from design handoff v11. The dashboard, the lead store and the security model are unchanged.

### Changed

- New page: rotating opening questions with a photo mosaic, announcement over a full-bleed photo with falling confetti, benefits with the coupon code, course cards with a colour per course, urgency band, photo strip, Rashel's section, testimonial carousel, consultation form with floating labels, sticky bottom bar.
- New palette (mauve, beige, yellow and six course colours) and the Oscar heading font.
- The form's success panel shows the star and one title.

### Added

- A syllabus per course (field in the course editor), shown in a native modal dialog. An upgrade routine fills the syllabus of courses created by the 1.0.0 installer.
- A coupon code setting (default `H50`); an empty value removes the coupon from the page.
- A pause button for the rotating questions.
- `content-visibility: auto` on the long sections below the fold, to keep the first layout short on phones.

### Removed

- The pinned opening, the arch gallery, the consultation ticket and their images.

## 1.0.0: 2026-11-23

First release.

### Added

- The Hanukkah landing page, rebuilt from the approved design handoff: scroll-pinned opening questions with the photo constellation, announcement with the lit heading and countdown, ticker strips, arch gallery, course menu with category tabs, Rashel's section with the drawn signature, quote, two videos and the testimonial wall, urgency band, consultation ticket and lead form, sticky bottom bar.
- Courses as their own post type (name, category, tag, description, sale and original price, Cardcom link, photo), seeded with the eleven courses of the design.
- Dashboard "ראשל חנוכה": settings, every sentence of the page, connections, and an audit log.
- Leads mini CRM: every submission stored first, status and notes, consent record, campaign source, own capability, audited CSV export with formula defusing, retention with automatic deletion, email notification and signed webhook, both retried.
- Cloudflare Turnstile on the form, verified on the server, with the secret sealed at rest.
- First-visit privacy notice, mandatory legal pages created on activation, footer legal links and the Multi Digital credit.
- Accessibility toolbar, skip link, reduced-motion and no-JavaScript paths.
- Tooling: PHPCS (WordPress-Extra, WordPress-Docs, PHPCompatibilityWP), PHPStan level 8, PHPUnit, Playwright with axe-core, Lighthouse budget, secret scan and Semgrep in CI.
