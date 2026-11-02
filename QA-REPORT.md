# Importer update — version 1.0.2

The original-photo limitation described in the historical report below is resolved for the updated package. All 11 approved originals were downloaded with TLS verification enabled and verified as JPEGs. The setup plugin now bundles these photos and a SHA-256 source manifest, then copies each verified file into a temporary file for WordPress media sideloading. It requires no old-host network access. No TLS verification was disabled. Existing Home content is preserved.

New WordPress runtime checks passed with outbound HTTP explicitly blocked: 11 attachments with physical files matching all source checksums; 16 image/cover blocks with IDs; published Home; successful same-domain import at https://dragonoilfield.com; rerun content preservation; rejection of an unapproved source URL; PHP syntax. The same-domain guard previously rejected legitimate new local upload URLs and is fixed. Rebel's reported cURL hostname mismatch remains a hosting/routing issue, but no longer blocks this installer. Production HTTPS still needs configuration.

All five responsive widths (1440, 1024, 768, 390, 320), image decoding, overflow checks, mobile menu and reduced motion also passed with the real imported photos. Theme templates validate in Gutenberg. Rebel installation of this update and real inbox receipt remain pending. No DNS or hosting changes were made by Codex. Historical fixture test results below describe the earlier release.

---

# Dragon V4 QA report

Prepared 2026-11-02. Status: installable packages prepared and local development verified; NOT cleared for production launch.

## What passed

Isolated Docker WordPress 6.4.3, PHP 8.2.17, Apache and MariaDB 11.4. All three V4 components activate. Both installation and repeat invocation of scripts/local-setup.sh completed, with database readiness and representative homepage HTTP checks. Database and WordPress files use named Docker volumes; service startup is recorded separately from retained filesystem state. Fresh-task restoration/publication has not been tested.

- 24/24 automated workflow checks in .qa/results.json: failed and partial import refuse Home creation; fixture retry completes; 11 attachment records are created; saved Home has no old-host photo URLs; rerun preserves edited content without contacting the source host; 178 Gutenberg blocks validate; five viewport checks; legitimate and invalid form cases; nonce and honeypot checks; recorded mail failure; rate limiting.
- Five final viewport checks at 1440, 1024, 768, 390 and 320: all fixture images decode, no document overflow or out-of-bounds content, no category links. Separate checks pass for mobile navigation open/close and section selection, and reduced-motion scrolling. Focus-visible styling is present; a full assistive-technology audit was not performed. Screenshots in .qa/ use logo fixtures and do not show the final photography.
- All theme parts/templates validate with Gutenberg's actual JavaScript parser: header 11 blocks, footer 5, front-page 4, page 5, index 12. Front-page renders native saved post content inside a main landmark. All 178 Home blocks are editable, with 16 image/cover instances linked to 11 unique Media Library attachment IDs. Editor styles are registered through add_editor_style.
- Subdirectory test at a local /staging/ base path passes: logo URL/file, theme URL, homepage link, navigation, media URLs, form action and success redirect. The test restores the original local URL afterward. Local root HTTP routing and the subdirectory alias are verified; Rebel's actual rewrite/permalink rules still require staging verification.
- All four PHP files pass php -l. Theme JSON parses, block comments balance, theme headers declare WordPress 6.4+/PHP 7.4+. Only PHP 8.2/WordPress 6.4.3 were runtime-tested; a full PHP/core compatibility matrix was not run.
- Tools → Dragon Site Setup is registered for administrators. Ordinary editors cannot list private Dragon Inquiries. Form fields are separate and labeled. Phone-only, email-only and both contacts save private enquiries. Missing/invalid contacts, malformed nonce arrays, bad nonces and honeypot submissions create no enquiries. Immediate repeated legitimate requests are rejected. The mail-failure case still saves the enquiry and records wp_mail_failed.

## Fixes made

- Home/navigation/CTA links and bundled logo now respect WordPress base paths; secondary-page section links return to the homepage.
- Imported image and cover blocks gain actual attachment IDs and correct image classes; stale attachment files are not silently reused; local upload URL/file checks run before publishing. Existing edited Home is preserved before any download attempt.
- Fixed a missing Query wrapper closing tag in the fallback template. Added a homepage main landmark and proper Gutenberg editor stylesheet registration.
- Form nonce arrays are rejected safely; stored enquiries are limited to administrators rather than ordinary content editors.
- Removed decorative arrow glyphs from links/buttons, added explicit keyboard focus and narrow-width text protection. Category groups remain non-clickable native blocks.
- Fonts are served locally with included font licenses, avoiding Google Fonts dependencies. Updated misleading photo notes to V4 and the complete 11-source manifest.

See CHANGES.md for individual files. Theme version 1.1.1, lead form 1.2.1, setup plugin 1.0.1.

## Not passed / still pending

- Actual original-photo downloads: cloud proxy returned HTTP 403 for the approved old photo host. The saved network draft adds dragonoilfield.com and www.dragonoilfield.com, but has not been applied/published. Import mechanics were exercised with local-only HTTP fixtures using the bundled logo; this is NOT a claim that original photography was imported or reviewed. Inline photos supplied in chat were visible references but were not available as downloadable files to the runner. An original image ZIP or approved source-host access is needed to finish this check.
- Rebel staging: no staging URL or secure hosting connection supplied; no deployment, hosting PHP/resource/HTTPS checks, real-media browser review or performance measurement performed there.
- Real email: no SMTP/API binding configured. Every local test notification was intercepted before delivery; wp_mail acceptance/rejection was simulated. No email was sent to Billy and inbox receipt is unverified. Configure authenticated mail on staging with a verified domain sender, then confirm actual receipt with Billy.
- No live website, GoDaddy DNS, MX, SPF, DKIM or DMARC changes, no production backups or cutover. Separate explicit go-live/DNS permissions remain required. The production checklist records these gates.
- Latest WordPress/supportable production PHP and actual Rebel permalink/cache/security-plugin behavior must be checked on staging. Minimum-version local QA is not a production runtime recommendation.

## Reusable environment configuration

Saved install_script and start_skill for the verified local development workflow, plus the original-photo host network additions. Draft persistence was confirmed by the configuration service. Review and save the draft in environment settings, then publish the environment; saving the draft does not apply runtime changes, run scripts, or establish a new-task restoration test. Fonts are now local, so Google Fonts hosts are not required.

Only dist/*-final.zip are installed in WordPress. Do not deploy local hooks, fixtures, QA login credentials, test reports, database exports or containers.
