# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with the Pikari Team plugin.

## Project Overview

Team member CPT with digital business cards, PWA-enabled card pages, vCard QR codes, and customizable card templates

## Required Agents

Always use these agents proactively:

- **`wordpress-core-expert`** — Review all PHP and JavaScript code changes
- **`accessibility-expert`** — Review interactive UI components, keyboard navigation, focus management, ARIA attributes

## Architecture

### Entry Point

`pikari-team.php` — Plugin headers, Composer autoloader, PSR-4 autoloader, `pikari_team_init()` on `plugins_loaded` hook (classes hook into `init` themselves), block registration on `init`.

### PHP Classes (`includes/`, namespace `Pikari\Team\`)

| Class            | Purpose                                                                                                                                                                                                              |
| ---------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Settings`       | Admin settings page under CPT menu. Option key: `pikari_team_settings`. Defaults: admin_label, company_name, company_logo, brand_color, url_base, field_visibility.                                                  |
| `Post_Type`      | Registers `pikari_team_member` CPT with 18 meta fields (all prefixed `pikari_team_`). Dynamic labels from settings. Block template with bindings.                                                                    |
| `Meta_Box`       | Classic editor meta box with 4 field groups (Personal, Company, Address, Social). Nonce, capability, and sanitization on save.                                                                                       |
| `Block_Bindings` | Registers `pikari-team/meta` binding source. Callback signature: `(array $args, $block, string $attribute_name)`. Post context via `$block->context`. Editor sidebar assets enqueued only for team member post type. |
| `Template`       | 4 rewrite rules: `/{base}/{slug}/`, `/download.vcf`, `/manifest.json`, `/sw.js`. Routes to templates or fires action hooks. Theme override via `{theme}/pikari-team/card-standalone.php`.                            |
| `Template_Parts` | Registers `pikari-card` template part area for Site Editor.                                                                                                                                                          |
| `VCard`          | vCard 3.0 generator with field escaping. Download handler on `pikari_team_card_download` action. Photo MIME detection via `wp_check_filetype()`.                                                                     |
| `QR_Code`        | SVG QR code generation via `chillerlan/php-qrcode`. Encodes vCard data without photo (capacity limit). Requires `outputBase64 = false` for raw SVG.                                                                  |
| `PWA`            | Web app manifest handler on `pikari_team_card_manifest`. Service worker handler on `pikari_team_card_sw` with md5-based cache versioning.                                                                            |
| `Shortcode`      | `[pikari_team_card id="" slug=""]` shortcode. Static `render_card()` used by both shortcode and block.                                                                                                               |

### Templates & Parts

- `templates/card-standalone.php` — Full PWA HTML shell with inline CSS/JS, QR code, manifest link, apple-touch-icon
- `templates/card-embed.php` — Embeddable card partial for shortcode/block
- `templates/single-pikari_team_member.html`, `archive-pikari_team_member.html` — Block templates
- `parts/card-default.html`, `card-corporate.html`, `card-minimal.html` — Card template parts with block bindings

### JavaScript

- `src/editor/sidebar-panel.js` — Block editor sidebar with `useEntityProp` for all meta fields and card template selector
- `src/blocks/card/` — Card embed block with `useEntityRecords` + `ComboboxControl`
- `assets/js/sw-register.js` — Vanilla JS service worker registration (no build step)

### Card URL Routes

All routes use the configurable URL base (default `card`):

- `/{base}/{slug}/` — Standalone PWA card page
- `/{base}/{slug}/download.vcf` — vCard file download
- `/{base}/{slug}/manifest.json` — Web app manifest
- `/{base}/{slug}/sw.js` — Service worker

## Coding Standards

- **PHP**: WordPress Coding Standards with **4 spaces indentation (NOT tabs)** — enforced by `phpcs.xml`
- **JavaScript**: WordPress ESLint config handles all JS formatting. Prettier is configured to **ignore** JS files.
- **CSS/SCSS**: WordPress Stylelint config. Prettier formats CSS/SCSS.
- All user-facing strings must use WordPress i18n functions (`__()`, `_e()`, `_n()`, `_x()`) with text domain `pikari-team`
- Use `useEntityRecords` (not `useSelect` + `getEntityRecords`) for WordPress data fetching in React components
- **Block registration must use `build/blocks/` paths** (NOT `src/blocks/`). Only `build/` ships in production.

## Key Patterns

- `plugins_loaded` hook for plugin init — classes hook into `init` themselves
- Block Bindings API callback receives `(array $source_args, WP_Block $block_instance, string $attribute_name)` — post context via `$block->context['postId']`
- `chillerlan/php-qrcode` v5 requires `$options->outputBase64 = false` for raw SVG output; use `phpcs:ignore` for camelCase properties
- Service worker uses `json_encode()` with `JSON_UNESCAPED_SLASHES` (not `wp_json_encode()`) for valid JS URL literals
- All new meta fields must be added to both `Post_Type::META_FIELDS` and `register_post_meta()` — otherwise the REST API silently drops them on save

## Testing

All feature development uses TDD. Write tests FIRST, watch them fail, then implement.

### Running Tests

```bash
# PHP unit tests (Brain\Monkey — no wp-env needed)
composer test                                      # Run all PHP tests
vendor/bin/phpunit --testdox                       # Verbose, human-readable output
vendor/bin/phpunit tests/php/SomeClassTest.php     # Single file
vendor/bin/phpunit --filter test_some_method        # Single test method

# JavaScript unit tests (Jest)
npm test                                           # Run all JS tests
npm test -- --testPathPattern=sidebar-panel        # Single file by pattern
npm test -- --testNamePattern="should persist"     # Single test by name
npm test -- --watch                                # Watch mode for development
```

### Test File Conventions

**PHP Tests:**

- Location: `tests/php/`
- Naming: `{ClassName}Test.php` (e.g., `PWATest.php`)
- Extend: `Pikari\Tests\TestCase` (NOT `PHPUnit\Framework\TestCase`)
- One test class per source class

**JavaScript Tests:**

- Location: `tests/unit/`
- Naming: `{module-name}.test.js` (e.g., `sidebar-panel.test.js`)
- Mirror source structure: `src/editor/sidebar-panel.js` → `tests/unit/editor/sidebar-panel.test.js`
- Group with `describe()` blocks matching the module name

### What to Test vs What NOT to Test

**DO test:**

- Pure logic: vCard string generation, QR code encoding, manifest structure, URL building
- State transitions: store actions that modify state
- Edge cases: empty inputs, missing context, error states
- WordPress hook registration: verify `add_action`/`add_filter` called with correct args

**Do NOT test:**

- WordPress Core behavior (`WP_Query` results, `register_block_type` internals)
- Simple getters/setters with no logic
- Third-party library internals
- `render.php` / template files (covered by E2E tests, not unit tests)

### PHP Testing with Brain\Monkey

Brain\Monkey mocks all WordPress functions without loading WordPress. The base `TestCase` class handles setup/teardown.

```php
<?php
namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Post_Type;
use Brain\Monkey\Functions;

class Post_TypeTest extends TestCase {

    public function test_register_post_type_is_called_with_correct_slug(): void {
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\expect( 'register_post_type' )
            ->once()
            ->with( 'pikari_team_member', \Mockery::type( 'array' ) );
        Functions\when( 'register_post_meta' )->justReturn( true );

        $post_type = new Post_Type();
        $post_type->register();
    }
}
```

### TDD Workflow

1. **Before implementing a feature:** Write a failing test that describes the expected behavior
2. **Run the test** to confirm it fails for the right reason
3. **Implement** the minimum code to make the test pass
4. **Run all tests** to ensure nothing is broken: `composer test && npm test`
5. **Refactor** if needed, running tests after each change
6. **Before committing:** Run `npm run lint:all && composer test && npm test`

### Test Infrastructure

- **Base class:** `Pikari\Tests\TestCase` (extends PHPUnit, sets up Brain\Monkey). All test classes extend this.
- **Bootstrap:** `tests/php/bootstrap.php` — Autoloader, plugin constants (`ABSPATH`, `PIKARI_TEAM_DIR`, `PIKARI_TEAM_VERSION`, `PIKARI_TEAM_URL`).
- **Global stubs** (in `TestCase::setUp`): `stubTranslationFunctions()`, `stubEscapeFunctions()`, `wp_unslash`, `sanitize_text_field`, `wp_parse_args`.
- **Mockery assertion bridge** (in `TestCase::tearDown`): Bridges `mockery_getExpectationCount()` into PHPUnit so Brain\Monkey-only tests aren't marked risky.

### Common Brain\Monkey Mocks

These are the most frequently needed per-test mocks (beyond the global stubs in `TestCase`):

- `register_post_type`, `register_post_meta` — CPT/meta registration
- `add_rewrite_rule`, `add_rewrite_tag` — Routing
- `get_post_meta`, `get_option`, `get_the_post_thumbnail_url` — Data retrieval
- `get_posts`, `add_shortcode`, `shortcode_atts` — Shortcode tests
- `wp_get_attachment_image_url` — PWA manifest icons

## Git Workflow

### Commit Format

```
type: Brief description
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

**Do NOT include `Co-Authored-By` lines or "Generated with Claude Code" attribution in commits.**

### Pre-commit Hooks

Husky + lint-staged runs automatically on commit:

- Lints staged PHP, JS, CSS/SCSS files
- Auto-updates lock files when `package.json` or `composer.json` changes

## Access Restrictions

- Do NOT read or modify `.env`, `.env.*`, `secrets/`, or `depreciated/` directories

## Session Logging

This project uses a session log system in `_log/` to maintain continuity between sessions.

### At the START of every session:

1. Read `.claude/memory/MEMORY.md` to load project memory and user preferences
2. Read `_specs/roadmap.md` to understand the current state of the project
3. Read the most recent `_log/YYYY-MM-DD*.md` file to pick up where we left off

These files are your "memory" — treat them as the source of truth for what's been done, how to work, and what's next.

### At the END of every session (or when the user says they're wrapping up):

1. **Update `_specs/roadmap.md`** — Check off completed tasks, add new tasks discovered during the session, update the "Last updated" date
2. **Create or append to `_log/YYYY-MM-DD.md`** — Write a session summary covering:
   - What happened (key actions, decisions, results)
   - Key decisions made and why
   - What's next (immediate next steps for the following session)
   - Files changed
3. **Git commit** — Stage all changed files and create a commit. Use a descriptive message summarizing the session's work. Do NOT push unless asked.
4. Remind the user that the log has been updated and the commit has been created so they can review

### File conventions:

- `_log/YYYY-MM-DD.md` — Daily session logs. One file per day, append if multiple sessions in a day. Never edit past entries.
- `_specs/roadmap.md` — Living task list organized by project phase. Evolves over time.

## Specs & Plans

- Design specs go in `_specs/` (e.g., `_specs/2026-03-30-database-schema.md`)
- Implementation plans go in `_specs/plans/` (e.g., `_specs/plans/2026-03-30-seed-from-airtable.md`)
- Use `YYYY-MM-DD-` date prefix in filenames to keep folders sorted chronologically
