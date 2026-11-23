# Changelog

All notable changes to this project are listed here. Versions follow [Semantic Versioning](https://semver.org/); the theme version lives in `rashel-hanukkah/style.css` and `RSH_VERSION` and the two are bumped together.

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
