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

`pikari-team.php` — Plugin headers, constants (`PIKARI_TEAM_VERSION`, `PIKARI_TEAM_DIR`, `PIKARI_TEAM_URL`), PSR-4 autoloader, `pikari_team_init()` on `init` hook, block registration from `build/blocks/card`.

### PHP Classes (`includes/`, namespace `Pikari\Team\`)

| Class            | Purpose                                                                                                                                                                                   |
| ---------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Settings`       | Admin settings page under CPT menu. Option key: `pikari_team_settings`. Defaults: admin_label, company_name, company_logo, brand_color, url_base, field_visibility.                       |
| `Post_Type`      | Registers `pikari_team_member` CPT with 17 meta fields (all prefixed `pikari_team_`). Dynamic labels from settings. Block template with bindings.                                         |
| `Meta_Box`       | Classic editor meta box with 4 field groups (Personal, Company, Address, Social). Nonce, capability, and sanitization on save.                                                            |
| `Block_Bindings` | Registers `pikari-team/meta` binding source. Editor sidebar assets enqueued only for team member post type.                                                                               |
| `Template`       | 4 rewrite rules: `/{base}/{slug}/`, `/download.vcf`, `/manifest.json`, `/sw.js`. Routes to templates or fires action hooks. Theme override via `{theme}/pikari-team/card-standalone.php`. |
| `Template_Parts` | Registers `pikari-card` template part area for Site Editor.                                                                                                                               |
| `VCard`          | vCard 3.0 generator with field escaping. Download handler on `pikari_team_card_download` action. Photo MIME detection via `wp_check_filetype()`.                                          |
| `QR_Code`        | SVG QR code generation via `chillerlan/php-qrcode`. Encodes vCard data without photo (capacity limit).                                                                                    |
| `PWA`            | Web app manifest handler on `pikari_team_card_manifest`. Service worker handler on `pikari_team_card_sw` with md5-based cache versioning.                                                 |
| `Shortcode`      | `[pikari_team_card id="" slug=""]` shortcode. Static `render_card()` used by both shortcode and block.                                                                                    |

### Templates & Parts

- `templates/card-standalone.php` — Full PWA HTML shell with inline CSS/JS, QR code, manifest link, apple-touch-icon
- `templates/card-embed.php` — Embeddable card partial for shortcode/block
- `templates/single-pikari_team_member.html`, `archive-pikari_team_member.html` — Block templates
- `parts/card-default.html`, `card-corporate.html`, `card-minimal.html` — Card template parts with block bindings

### JavaScript

- `src/editor/sidebar-panel.js` — Block editor sidebar with `useEntityProp` for all meta fields
- `src/blocks/card/` — Card embed block with `useEntityRecords` + `ComboboxControl`
- `assets/js/sw-register.js` — Vanilla JS service worker registration (no build step)

### Card URL Routes

All routes use the configurable URL base (default `card`):

- `/{base}/{slug}/` — Standalone PWA card page
- `/{base}/{slug}/download.vcf` — vCard file download
- `/{base}/{slug}/manifest.json` — Web app manifest
- `/{base}/{slug}/sw.js` — Service worker

## Development

See the monorepo root [CLAUDE.md](../CLAUDE.md) for shared development commands and coding standards.

## Testing

See the monorepo root [CLAUDE.md](../CLAUDE.md) for full TDD workflow, commands, and example patterns.

### Plugin-Specific Test Guidance

**PHP classes to prioritize for testing:**

- `Post_Type` — CPT registration args, meta field registration, dynamic label generation from settings
- `VCard` — vCard 3.0 string generation, field escaping (semicolons/commas), PHOTO base64 encoding
- `QR_Code` — QR data assembly, PHOTO field omission for size limits
- `Settings` — Option retrieval, default values, label generation
- `Meta_Box` — Field rendering, save handling, sanitization
- `Template` — Rewrite rules, template routing, theme override detection
- `Block_Bindings` — Binding source registration, value callbacks

**Note:** `VCard` and `QR_Code` are pure logic with no WordPress UI — ideal first candidates for TDD.

**Common Brain\Monkey mocks needed:**

- `register_post_type`, `register_post_meta`, `register_rest_route` — Registration
- `add_rewrite_rule`, `add_rewrite_tag` — Routing
- `get_post_meta`, `get_option`, `get_the_post_thumbnail_url` — Data retrieval
- `sanitize_text_field`, `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` — Sanitization/escaping

**JS note:** JavaScript is minimal in this plugin (vanilla JS for service worker + small React sidebar panel). JS tests are less critical initially — focus on PHP tests.
