# Pikari Team — Roadmap

Last updated: 2026-10-09

Started 2026-09-16. Anything earlier is in `_log/2026-04-07.md` and the git history. Carried-over items were checked against the code when this file was created, except where noted.

## Releases & distribution

- ~~Hotfix the release ZIP missing `vendor/*/src`~~ — ✅ DONE (2026-10-06). 1.0.2 shipped 0 `php-qrcode/src` files and every OSC `/team-member/` page returned 500; 1.0.3 ships 68. The unanchored `--exclude='src'` had come back via the #24 template sync. The build now fails if any Composer autoload path is missing. See #46.
- ~~1.0.4 published and installed on OSC~~ — ✅ DONE (2026-10-07). Contains #42–#44, #48 and #49.
- ~~1.0.5 published~~ — ✅ DONE (2026-10-07). Two security fixes, #50 and #52 (see Security). OSC update pending (todo #688).

## Security

- ~~Cards render only for members the viewer may see~~ — ✅ DONE (2026-10-07). Shortcode `id=`, block embed and block full view all go through `Shortcode::can_render()`. Drafts, private, trash, password-protected and non-member posts render nothing to anyone without `read_post`. The `pikari-team/meta` binding serves only member fields from allowed members. Before this, a contributor could expose any post's data. +21 tests. See #50.
- ~~Password-protected members hidden everywhere until unlocked~~ — ✅ DONE (2026-10-07). `/card/`, the vCard and the PWA files return 404, and so do unknown slugs, which used to show the home page with a 200. REST drops meta and the headshot for anyone who can't edit the member. Unlocked cards send no-cache headers. +9 tests. See #52.
- A password-protected member's name and slug still show in REST, feeds, oEmbed and the CPT sitemap. This is core behaviour for protected posts. Steve decides whether a name counts as a contact detail; if it does, filter `wp_sitemaps_posts_query_args` with `has_password => false`, and similar for the rest.
- The PWA service worker's offline cache outlives a newly added password on a device that already cached the card.
- The public template helpers (`pikari_team_the_qr_code()`, `Template_Tags::get_member_data()`, `VCard::generate_vcard()`) don't check `post_password_required()`, so theme code calling them directly can leak. Check inside them, or document that callers must.
- REST `?password=` still returns empty meta. This is stricter than core and within the policy; for parity, compare `$request['password']` with `hash_equals()` in `Post_Type::hide_protected_meta()`.

## Testing

- ~~JS unit tests for the v1 card-page scripts~~ — ✅ DONE (2026-09-16). `carousel.js` and `sw-register.js`, 11 tests; all 18 deliberate breaks caught. See #39.
- ~~JS unit tests for the block editor files~~ — ✅ DONE (2026-09-16). `sidebar-panel.js` and `blocks/card/edit.js`, 32 tests; 20 of 21 breaks caught, and the one miss can't be observed by any test. JS suite now 43. See #40.
- Coverage reports skip `assets/js/**`: `collectCoverageFrom` only includes `src/**`. The monorepo's shared jest template controls this and that session has been told, so don't edit `jest.config.js` here.

## v1 (Classic Editor)

- Add a card template selector to the Classic Editor meta box. `pikari_team_card_template` can only be edited in the block editor sidebar; `Meta_Box.php` has no field for it.
- Work through display issues on the standalone card page and shortcode embed. Carried over from 2026-04-07 and not re-checked. The single page is done (below); the shortcode embed still loads no CSS.
- ~~Style the single team member page inside the theme~~ — ✅ DONE (2026-10-06). The single page loads card.css and carousel.js. card.css's global `*` reset is scoped to the card, and `/card/` screenshots are byte-identical. See #48.
- ~~Single page layout and block theme support~~ — ✅ DONE (2026-10-07). Classic themes get an `article`/`.entry-content` container. Block themes get a registered block template instead of the forced PHP template, so 0 deprecation notices. The card name is the page h1, and the card is hidden on password-protected members. See #49.
- Members created in the block editor get the CPT editor `template` (featured image, bound name heading, bound job title), which now duplicates the card header on the single page. Slim the template to a bio paragraph. OSC isn't affected. The decision todo #687 was cancelled on 2026-10-07 because this roadmap item tracks it.
- Carousel a11y: `role="tablist"` has no tabpanels or arrow keys, and links on the hidden slide stay tabbable. Drop the tab roles or implement the full pattern. `carousel.js` also only wires up the first card on a page.
- TT1-style themes that open `<main>` in `header.php` get a nested `<main>`. The plugin's classic template opens its own, which \_s, TT17, TT19 and TT20 expect; a theme can override the template. TT1's `button:not(:hover):not(:active):not(.has-background)` rule (specificity 0,3,1) also overrides the carousel dot colours, so both dots look dark.
- The card block declares `align` support but `render.php` has no `get_block_wrapper_attributes()`, so alignment does nothing.

## v2 (Gutenberg)

- Replace the custom `pikari-team/meta` binding source with `core/post-meta`.

## Docs & housekeeping

- Fix the card routes in `CLAUDE.md`. It lists `/manifest.json` and `/sw.js`, but `Template.php` registers `/manifest/` and `/service-worker/`.
- `npm run lint:md:docs` fails on existing files: `plan.md`, `docs/hooks.md`, `CLAUDE.md`, `_log/2026-04-07.md`, `_specs/plans/2026-04-07-classic-editor-hooks-validation.md`. CI doesn't run it.
