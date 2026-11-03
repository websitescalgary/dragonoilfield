# Contact form 1.2.3

- Optional fixed visitor acknowledgements with per-recipient rate limiting; disabled by default.
- Administrator setting and read-only email-attempt panel on private enquiries.
- Versioned production ZIP and EMAIL-SETUP.md instructions. No live hosting, DNS or email settings changed.

# Contact form 1.2.2

- src/dragon-lead-form/dragon-lead-form.php: enqueue progressive form script, inline live status, JSON results for enhanced submissions, specific expired-nonce/rate-limit errors, scalar query-status guard.
- src/dragon-lead-form/assets/form.js: explicit browser validation and submission feedback, disabled sending state, same-origin JSON submission, preserved entries on error, timeout feedback, native fallback when scripting is unavailable.
- Updated production contact ZIP, handoff ZIP and checksums; added versioned contact plugin ZIP.

# Importer 1.0.2

- src/dragon-site-setup/dragon-site-setup.php: bundled verified originals replace remote downloading; manifest checksums and filename restrictions; correct local-media check for same-domain migration; updated admin explanation and version.
- src/dragon-site-setup/originals/: 11 approved original JPEGs plus source URLs, dimensions and SHA-256 checksums in manifest.json.
- Updated setup ZIP, handoff ZIP, checksums and handoff documentation.

# Changes from the supplied V4 archive

- src/dragon-oilfield-theme/functions.php
- src/dragon-oilfield-theme/style.css
- src/dragon-oilfield-theme/PHOTO-SOURCES-AND-LIMITATIONS.md
- src/dragon-oilfield-theme/parts/header.html
- src/dragon-oilfield-theme/parts/footer.html
- src/dragon-oilfield-theme/templates/index.html
- src/dragon-oilfield-theme/templates/front-page.html
- src/dragon-oilfield-theme/patterns/landing.php
- src/dragon-oilfield-theme/assets/css/site.css
- src/dragon-lead-form/dragon-lead-form.php
- src/dragon-site-setup/dragon-site-setup.php

Added fonts and licenses:

- src/dragon-oilfield-theme/assets/fonts/barlow-condensed-latin-500-normal.woff2
- src/dragon-oilfield-theme/assets/fonts/barlow-condensed-latin-800-normal.woff2
- src/dragon-oilfield-theme/assets/fonts/dm-sans-latin-700-normal.woff2
- src/dragon-oilfield-theme/assets/fonts/dm-sans-latin-600-normal.woff2
- src/dragon-oilfield-theme/assets/fonts/barlow-condensed-LICENSE
- src/dragon-oilfield-theme/assets/fonts/barlow-condensed-latin-600-normal.woff2
- src/dragon-oilfield-theme/assets/fonts/dm-sans-latin-400-normal.woff2
- src/dragon-oilfield-theme/assets/fonts/barlow-condensed-latin-900-normal.woff2
- src/dragon-oilfield-theme/assets/fonts/dm-sans-latin-500-normal.woff2
- src/dragon-oilfield-theme/assets/fonts/dm-sans-LICENSE
- src/dragon-oilfield-theme/assets/fonts/barlow-condensed-latin-700-normal.woff2

Added handoff, packaging and local QA files: README.md, REBEL-DEPLOYMENT.md, QA-REPORT.md, CHANGES.md, .gitignore, scripts/local-setup.sh, scripts/package.py, tests/*.cjs, tests/subdirectory.sh and tests/qa-hooks.php. Source was added to the initially empty checkout; no pre-existing tracked application files were replaced. Original supplied ZIPs are unchanged.
