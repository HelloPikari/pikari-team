# Pikari Team — Roadmap

Last updated: 2026-09-17

Started 2026-09-16. Anything earlier is in `_log/2026-04-07.md` and the git history. Carried-over items were checked against the code when this file was created, except where noted.

## Testing

- ~~JS unit tests for the v1 card-page scripts~~ — ✅ DONE (2026-09-16). `carousel.js` and `sw-register.js`, 11 tests; all 18 deliberate breaks caught. See #39.
- ~~JS unit tests for the block editor files~~ — ✅ DONE (2026-09-16). `sidebar-panel.js` and `blocks/card/edit.js`, 32 tests; 20 of 21 breaks caught, and the one miss can't be observed by any test. JS suite now 43. See #40.
- Coverage reports skip `assets/js/**`: `collectCoverageFrom` only includes `src/**`. The monorepo's shared jest template controls this and that session has been told, so don't edit `jest.config.js` here.

## v1 (Classic Editor)

- Add a card template selector to the Classic Editor meta box. `pikari_team_card_template` can only be edited in the block editor sidebar; `Meta_Box.php` has no field for it.
- Work through display issues on the standalone card page and shortcode embed. Carried over from 2026-04-07 and not re-checked.

## v2 (Gutenberg)

- Replace the custom `pikari-team/meta` binding source with `core/post-meta`.

## Docs & housekeeping

- Fix the card routes in `CLAUDE.md`. It lists `/manifest.json` and `/sw.js`, but `Template.php` registers `/manifest/` and `/service-worker/`.
- `npm run lint:md:docs` fails on existing files: `plan.md`, `docs/hooks.md`, `CLAUDE.md`, `_log/2026-04-07.md`, `_specs/plans/2026-04-07-classic-editor-hooks-validation.md`. CI doesn't run it.
