# Pikari Team — Roadmap

Last updated: 2026-10-07

Started 2026-09-16. Anything earlier is in `_log/2026-04-07.md` and the git history. Carried-over items were checked against the code when this file was created, except where noted.

## Releases & distribution

- ~~Hotfix the release ZIP missing `vendor/*/src`~~ — ✅ DONE (2026-10-06). 1.0.2 shipped 0 `php-qrcode/src` files and every OSC `/team-member/` page returned 500; 1.0.3 ships 68. The unanchored `--exclude='src'` had come back via the #24 template sync. The build now fails if any Composer autoload path is missing. See #46.
- ~~1.0.4 published and installed on OSC~~ — ✅ DONE (2026-10-07). Contains #42–#44, #48 and #49.

## Testing

- ~~JS unit tests for the v1 card-page scripts~~ — ✅ DONE (2026-09-16). `carousel.js` and `sw-register.js`, 11 tests; all 18 deliberate breaks caught. See #39.
- ~~JS unit tests for the block editor files~~ — ✅ DONE (2026-09-16). `sidebar-panel.js` and `blocks/card/edit.js`, 32 tests; 20 of 21 breaks caught, and the one miss can't be observed by any test. JS suite now 43. See #40.
- Coverage reports skip `assets/js/**`: `collectCoverageFrom` only includes `src/**`. The monorepo's shared jest template controls this and that session has been told, so don't edit `jest.config.js` here.

## v1 (Classic Editor)

- Add a card template selector to the Classic Editor meta box. `pikari_team_card_template` can only be edited in the block editor sidebar; `Meta_Box.php` has no field for it.
- Work through display issues on the standalone card page and shortcode embed. Carried over from 2026-04-07 and not re-checked. The single page is done (below); the shortcode embed still loads no CSS.
- ~~Style the single team member page inside the theme~~ — ✅ DONE (2026-10-06). The single page loads card.css and carousel.js. card.css's global `*` reset is scoped to the card, and `/card/` screenshots are byte-identical. See #48.
- ~~Single page layout and block theme support~~ — ✅ DONE (2026-10-07). Classic themes get an `article`/`.entry-content` container. Block themes get a registered block template instead of the forced PHP template, so 0 deprecation notices. The card name is the page h1, and the card is hidden on password-protected members. See #49.
- Members created in the block editor get the CPT editor `template` (featured image, bound name heading, bound job title), which now duplicates the card header on the single page. Slim the template to a bio paragraph. Not seen on OSC, but untested there.
- Carousel a11y: `role="tablist"` has no tabpanels or arrow keys, and links on the hidden slide stay tabbable. Drop the tab roles or implement the full pattern. `carousel.js` also only wires up the first card on a page.
- The card block declares `align` support but `render.php` has no `get_block_wrapper_attributes()`, so alignment does nothing.

## v2 (Gutenberg)

- Replace the custom `pikari-team/meta` binding source with `core/post-meta`.

## Docs & housekeeping

- Fix the card routes in `CLAUDE.md`. It lists `/manifest.json` and `/sw.js`, but `Template.php` registers `/manifest/` and `/service-worker/`.
- `npm run lint:md:docs` fails on existing files: `plan.md`, `docs/hooks.md`, `CLAUDE.md`, `_log/2026-04-07.md`, `_specs/plans/2026-04-07-classic-editor-hooks-validation.md`. CI doesn't run it.
