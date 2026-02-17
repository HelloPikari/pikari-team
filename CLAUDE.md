# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with the Pikari Team plugin.

## Project Overview

Team member CPT with digital business cards, PWA-enabled card pages, vCard QR codes, and customizable card templates

## Required Agents

Always use these agents proactively:

- **`wordpress-core-expert`** — Review all PHP and JavaScript code changes
- **`accessibility-expert`** — Review interactive UI components, keyboard navigation, focus management, ARIA attributes

## Architecture

TODO: Document plugin architecture as it develops.

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
