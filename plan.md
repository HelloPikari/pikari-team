# Pikari Team — Implementation Plan

A self-contained WordPress plugin that registers a team member custom post type with comprehensive meta fields for employee/staff information. The plugin serves double duty: it provides a reusable team member CPT for any WordPress site, and layers on a digital business card feature that renders mobile-optimized, PWA-enabled card pages with vCard QR codes. This needs to work across multiple client sites as a standalone plugin.

Each phase follows TDD: write failing tests first, then implement, then verify all tests pass. Each phase should be completed, linted, tested, and committed before moving to the next. See `CLAUDE.md` for coding standards, required agents, and full TDD workflow.

---

## Conventions

| Item              | Value                                                       |
| ----------------- | ----------------------------------------------------------- |
| Plugin slug       | `pikari-team`                                               |
| Text domain       | `pikari-team`                                               |
| PHP namespace     | `Pikari\Team\`                                              |
| Constants         | `PIKARI_TEAM_VERSION`, `PIKARI_TEAM_DIR`, `PIKARI_TEAM_URL` |
| CPT slug          | `pikari_team_member`                                        |
| Meta field prefix | `pikari_team_`                                              |
| Composer name     | `pikari-inc/pikari-team`                                    |
| Min WordPress     | 6.7+ (Block Bindings API)                                   |
| Min PHP           | 8.2                                                         |
| Indentation       | PHP: 4 spaces. JS: tabs (ESLint). CSS: Prettier.            |

### Coding standards

- **PHP**: WordPress Coding Standards with 4 spaces (not tabs), enforced by `phpcs.xml`. PSR-4 filenames (e.g., `Post_Type.php`, not `class-post-type.php`). Short array syntax `[]` allowed. All user-facing strings use `__()`, `_e()`, etc. with text domain `pikari-team`.
- **JavaScript**: WordPress ESLint config handles formatting. Prettier ignores JS files. Use `@wordpress/scripts` with `--experimental-modules` for builds.
- **CSS/SCSS**: WordPress Stylelint config. Prettier formats.
- **Blocks**: Register from `build/blocks/` paths (NOT `src/blocks/`). The `src/` directory is excluded from distribution ZIPs.
- **Security**: Escape all output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`). Sanitize all input (`sanitize_text_field`, `absint`, etc.). Verify nonces on form submissions.
- **Commits**: `type: Brief description` (types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`). No Co-Authored-By lines.

### Test-driven development

All feature development uses TDD. Write tests FIRST, watch them fail, then implement the minimum code to pass.

**Test infrastructure (already scaffolded):**

- `phpunit.xml.dist` — PHPUnit config, bootstrap at `tests/php/bootstrap.php`
- `tests/php/TestCase.php` — Base class with Brain\Monkey setup/teardown. Extend `Pikari\Tests\TestCase` (NOT `PHPUnit\Framework\TestCase`).
- `tests/php/bootstrap.php` — Loads Composer autoloader and TestCase
- Brain\Monkey + Mockery for mocking WordPress functions without loading WordPress
- Jest mocks at `tests/unit/__mocks__/@wordpress/` for Interactivity API

**Test file conventions:**

- PHP: `tests/php/{ClassName}Test.php` (e.g., `tests/php/VCardTest.php`)
- JS: `tests/unit/{module-name}.test.js`
- One test class per source class

**Running tests:**

```bash
composer test                                      # All PHP tests
vendor/bin/phpunit --testdox                       # Verbose output
vendor/bin/phpunit tests/php/VCardTest.php         # Single file
vendor/bin/phpunit --filter test_some_method        # Single method
npm test                                           # All JS tests
```

**TDD workflow per feature:**

1. Write a failing test describing expected behavior
2. Run `composer test` to confirm it fails for the right reason
3. Implement the minimum code to make it pass
4. Run `composer test` to confirm green
5. Refactor if needed, re-running tests after each change
6. Before committing: `npm run lint:all && composer test`

**What to test:** Pure logic (vCard generation, field escaping, label generation, URL validation), hook registration (verify `add_action`/`add_filter` called with correct args), state transitions, edge cases (empty inputs, missing meta).

**What NOT to test:** WordPress Core behavior, simple getters/setters, `render.php` templates (E2E covers those), third-party library internals.

**Priority classes for TDD (pure logic, ideal first candidates):**

- `VCard` — vCard string generation, field escaping (semicolons/commas), PHOTO encoding
- `QR_Code` — QR data assembly, PHOTO field omission
- `Settings` — Option retrieval, defaults, label generation
- `Post_Type` — CPT registration args, meta field registration, dynamic labels

### File structure

The scaffold has already created the standard monorepo files (main plugin file with autoloader, `package.json`, `composer.json`, CI workflows, linter configs, husky hooks, VS Code settings, `.wp-env.json`, `CLAUDE.md`). The following plugin-specific structure needs to be built out:

```text
pikari-team/
├── pikari-team.php                  (main plugin file + autoloader — already exists)
├── includes/
│   ├── Post_Type.php              (CPT + meta registration)
│   ├── Meta_Box.php               (classic editor meta box)
│   ├── Block_Bindings.php         (Gutenberg block bindings + editor asset enqueuing)
│   ├── Template.php               (rewrite rules + template routing)
│   ├── Template_Parts.php         (register card template part area)
│   ├── VCard.php                  (vCard 3.0 generator + download endpoint)
│   ├── QR_Code.php                (QR code SVG generation via chillerlan/php-qrcode)
│   ├── PWA.php                    (manifest + service worker + meta tags)
│   ├── Shortcode.php              (shortcode registration + shared render function)
│   └── Settings.php               (admin settings page)
├── src/
│   ├── blocks/
│   │   └── card/                  (pikari-team/card embed block)
│   │       ├── block.json
│   │       ├── index.js
│   │       ├── edit.js
│   │       └── render.php
│   └── editor/
│       ├── index.js               (entry point)
│       └── sidebar-panel.js       (registerPlugin sidebar for meta fields)
├── templates/
│   ├── card-standalone.php        (PWA HTML shell for /card/ route — classic theme fallback)
│   ├── card-embed.php             (embeddable card partial for shortcode/block)
│   ├── single-pikari_team_member.html  (default block template for single view)
│   └── archive-pikari_team_member.html (default block template for archive)
├── parts/
│   ├── card-default.html          (default business card template part)
│   ├── card-corporate.html        (formal card template part)
│   └── card-minimal.html          (minimal card template part)
├── assets/
│   ├── css/card.css               (standalone card page styles)
│   └── js/sw-register.js          (vanilla JS service worker registration)
└── languages/
```

### Meta fields (all registered via `register_post_meta()`, exposed to REST API)

- **Personal:** `pikari_team_first_name`, `pikari_team_last_name`, `pikari_team_designation`, `pikari_team_job_title`, `pikari_team_email`, `pikari_team_phone`, `pikari_team_cell`
- **Company:** `pikari_team_company`, `pikari_team_department`, `pikari_team_website`
- **Address:** `pikari_team_address_street`, `pikari_team_address_city`, `pikari_team_address_state`, `pikari_team_address_zip`, `pikari_team_address_country`
- **Social:** `pikari_team_linkedin`, `pikari_team_twitter`
- **Card:** `pikari_team_card_template` (selects which card template part to use, defaults to `card-default`)

### Editor compatibility

Both editors write to the same post meta keys:

| Context          | Classic Theme                                    | Block Theme                                         |
| :--------------- | ------------------------------------------------ | :-------------------------------------------------- |
| Editing data     | Meta boxes (add_meta_boxes)                      | Block bindings + sidebar panel (registerPlugin)     |
| Archive / Single | Standard template hierarchy (theme-controlled)   | Block templates (plugin default, theme-overridable) |
| Card design      | PHP template with theme override                 | Custom template part (editable in Site Editor)      |
| /card/{slug}/    | PWA HTML shell wrapping the card template output |                                                     |
| Embed on page    | `[pikari_team_card]` shortcode                   | `pikari-team/card` block                            |

---

## Phase 0: Stub Classes + Main Plugin File

**Goal:** Establish the full class architecture with empty stubs so the autoloader works and all hooks are wired up.

### Main plugin file (`pikari-team.php`)

The scaffold already provides constants (`PIKARI_TEAM_VERSION`, `PIKARI_TEAM_DIR`, `PIKARI_TEAM_URL`), the PSR-4 autoloader, and the ABSPATH check. Add:

1. An `init` hook calling `pikari_team_init()` that:
   - Calls `load_plugin_textdomain( 'pikari-team', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' )`
   - Instantiates all classes: `Post_Type`, `Meta_Box`, `Block_Bindings`, `Template`, `Template_Parts`, `VCard`, `QR_Code`, `PWA`, `Shortcode`, `Settings`
2. `register_activation_hook()` that flushes rewrite rules
3. `register_deactivation_hook()` that flushes rewrite rules

### Stub classes in `includes/`

Create each file with the `Pikari\Team` namespace, class declaration, and an empty constructor that will later register hooks:

| File                 | Class            | Purpose                                          |
| -------------------- | ---------------- | ------------------------------------------------ |
| `Post_Type.php`      | `Post_Type`      | CPT + meta field registration                    |
| `Meta_Box.php`       | `Meta_Box`       | Classic Editor meta box                          |
| `Block_Bindings.php` | `Block_Bindings` | Block Bindings API source + sidebar panel assets |
| `Template.php`       | `Template`       | Rewrite rules + template routing                 |
| `Template_Parts.php` | `Template_Parts` | Card template part area registration             |
| `VCard.php`          | `VCard`          | vCard 3.0 generator + download endpoint          |
| `QR_Code.php`        | `QR_Code`        | QR code SVG generation                           |
| `PWA.php`            | `PWA`            | Manifest, service worker, meta tags              |
| `Shortcode.php`      | `Shortcode`      | `[pikari_team_card]` shortcode + shared render   |
| `Settings.php`       | `Settings`       | Admin settings page                              |

### Verify

- Plugin activates without errors in wp-env
- `composer lint` passes
- `composer test` passes (no tests yet, but PHPUnit runs without errors)
- No PHP warnings/notices in debug.log
- Commit: `chore: scaffold stub classes and plugin init`

---

## Phase 1: CPT + Meta + Editor UIs

**Goal:** Register the team member CPT, all meta fields, and both editor experiences.

### 1-tests. Write tests first

Create test files before implementing any logic. Run `composer test` after each file to confirm tests fail for the right reason (class exists but methods don't do anything yet).

**`tests/php/SettingsTest.php`** — Test:

- `get_option` is called with `pikari_team_settings`
- Default admin label is "Team Members" when option is empty
- Default company name falls back to `get_bloginfo('name')`
- Default brand color is `#0073aa`
- `register_setting` is called on `admin_init`
- `add_submenu_page` is called on `admin_menu`

**`tests/php/Post_TypeTest.php`** — Test:

- `register_post_type` is called with slug `pikari_team_member`
- CPT args include `show_in_rest => true`, `public => true`, `has_archive => false`
- `register_post_meta` is called for each meta field (all 17 fields)
- Dynamic labels use the admin label from settings
- Default labels are "Team Members" / "Team Member"

**`tests/php/Meta_BoxTest.php`** — Test:

- `add_meta_boxes` hook is registered in constructor
- Meta box is NOT registered when block editor is active
- Save handler verifies nonce and user capability
- Save handler calls `update_post_meta` for each field

**`tests/php/Block_BindingsTest.php`** — Test:

- `register_block_bindings_source` is called with name `pikari-team/meta`
- `get_binding_value` returns the correct post meta value for a given key
- Editor assets are only enqueued for `pikari_team_member` post type

### 1a. Settings (`Settings.php`)

Build this first because `Post_Type` depends on the admin label setting.

- Register settings via `register_setting()` + WordPress Settings API
- Settings stored as `pikari_team_settings` option (single array)
- Fields:
  - `admin_label` (string, default "Team Members") — dynamically populates all CPT label arguments (`menu_name`, `name`, `singular_name`, `add_new`, `edit_item`, etc.)
  - `company_name` (string, default empty — falls back to `get_bloginfo('name')`)
  - `company_logo` (attachment ID, use `wp_media` uploader)
  - `brand_color` (hex string, default `#0073aa`)
  - `url_base` (string, default `card`)
  - `field_visibility` (array of field keys => boolean)
- Add settings page as submenu under CPT: `add_submenu_page( 'edit.php?post_type=pikari_team_member', ... )`
  - Register the submenu on `admin_menu` hook. Since CPT is registered on `init`, the submenu parent will exist.
- Sanitize all inputs with appropriate callbacks

### 1b. Custom Post Type (`Post_Type.php`)

- Register `pikari_team_member` CPT on `init` hook
- Dynamic labels from settings:
  ```php
  $settings = get_option( 'pikari_team_settings', [] );
  $label = $settings['admin_label'] ?? 'Team Members';
  $singular = rtrim( $label, 's' ); // Simple approach, or store both
  ```
- CPT args: `'public' => true`, `'has_archive' => false`, `'supports' => ['title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes']`, `'show_in_rest' => true` (required for block editor), `'menu_icon' => 'dashicons-id-alt'`
- Register all meta fields via `register_post_meta( 'pikari_team_member', ... )`:
  - All fields: `'type' => 'string'`, `'single' => true`, `'show_in_rest' => true`, `'sanitize_callback' => 'sanitize_text_field'`
  - See the full field list in the Conventions section above

### 1c. Classic Editor Meta Box (`Meta_Box.php`)

- Hook `add_meta_boxes` to register a meta box for `pikari_team_member`
- Only load when block editor is NOT active:
  ```php
  if ( function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( 'pikari_team_member' ) ) {
      return;
  }
  ```
- Render callback: labeled input fields for all meta, organized in fieldsets (Personal, Company, Address, Social, Card)
- Save callback on `save_post_pikari_team_member`: verify nonce, check `current_user_can( 'edit_post', $post_id )`, sanitize and save each field via `update_post_meta()`
- Respect the field visibility setting from Settings

### 1d. Block Bindings (`Block_Bindings.php`)

- Register a block bindings source via `register_block_bindings_source()`:
  ```php
  register_block_bindings_source( 'pikari-team/meta', [
      'label'              => __( 'Team Member Data', 'pikari-team' ),
      'get_value_callback' => [ $this, 'get_binding_value' ],
      'uses_context'       => [ 'postId', 'postType' ],
  ] );
  ```
- `get_binding_value()` reads from `get_post_meta()` for the given field key
- Register a CPT block template via the `'template'` arg in `register_post_type()`:
  ```php
  'template' => [
      [ 'core/post-featured-image' ],
      [ 'core/heading', [
          'metadata' => [ 'bindings' => [ 'content' => [
              'source' => 'pikari-team/meta',
              'args'   => [ 'key' => 'pikari_team_first_name' ],
          ] ] ],
      ] ],
      [ 'core/paragraph', [
          'metadata' => [ 'bindings' => [ 'content' => [
              'source' => 'pikari-team/meta',
              'args'   => [ 'key' => 'pikari_team_job_title' ],
          ] ] ],
      ] ],
  ]
  ```
- Enqueue sidebar panel JS on `enqueue_block_editor_assets` (only for `pikari_team_member` post type):
  ```php
  $screen = get_current_screen();
  if ( $screen && 'pikari_team_member' === $screen->post_type ) {
      wp_enqueue_script( ... );
  }
  ```

### 1e. Sidebar Panel (`src/editor/sidebar-panel.js`)

- Use `registerPlugin` from `@wordpress/plugins`
- Create a `PluginDocumentSettingPanel` with fields for: phone, cell, email, company, department, website, address fields, social links, card template selector
- Use `useEntityProp` to read/write post meta:
  ```js
  const [meta, setMeta] = useEntityProp(
  	'postType',
  	'pikari_team_member',
  	'meta'
  );
  // Access: meta.pikari_team_first_name
  // Update: setMeta( { ...meta, pikari_team_first_name: value } )
  ```
- Use `TextControl`, `SelectControl` from `@wordpress/components`
- Only render when post type is `pikari_team_member`

### 1f. Block registration for editor assets

- Set up `src/editor/index.js` as the entry point that imports `sidebar-panel.js`
- The build outputs to `build/editor/index.js` and `build/editor/index.asset.php`
- Enqueue from `Block_Bindings.php` using the asset file pattern:
  ```php
  $asset_file = PIKARI_TEAM_DIR . 'build/editor/index.asset.php';
  if ( file_exists( $asset_file ) ) {
      $assets = include $asset_file;
      wp_enqueue_script(
          'pikari-team-editor',
          PIKARI_TEAM_URL . 'build/editor/index.js',
          $assets['dependencies'],
          $assets['version']
      );
  }
  ```
- A custom webpack config may be needed if `@wordpress/scripts` doesn't auto-discover the `src/editor/index.js` entry point alongside `src/blocks/card/index.js`. Configure multiple entry points if necessary.

### Verify

- `composer test` — all Phase 1 tests pass
- Create a team member post in Classic Editor — all fields save and load
- Create a team member post in Block Editor — sidebar panel shows, bindings work
- Settings page renders and saves all options
- `npm run lint:all && composer lint && composer test` all pass
- Commit: `feat: add CPT, meta fields, classic meta box, block bindings, and settings`

---

## Phase 2: Routing + Templates + Shortcode

**Goal:** Set up the card route, block templates for archive/single, template parts for cards, and the shortcode/embed block.

### 2-tests. Write tests first

**`tests/php/TemplateTest.php`** — Test:

- `add_rewrite_rule` is called for each route pattern (card page, download, manifest, sw)
- `add_rewrite_tag` is called for `%pikari_card_slug%` and `%pikari_card_action%`
- Custom query vars are registered via `query_vars` filter
- URL base is read from settings (defaults to `card`)
- `template_include` filter is registered

**`tests/php/Template_PartsTest.php`** — Test:

- `default_wp_template_part_areas` filter is registered
- Filter callback adds `pikari-card` area with correct label and icon

**`tests/php/ShortcodeTest.php`** — Test:

- `add_shortcode` is called with `pikari_team_card`
- Shortcode handler resolves `slug` attribute to post ID via query
- Shortcode handler uses `id` attribute directly when provided
- `render_card()` returns empty string for invalid/zero post ID

### 2a. Template Routing (`Template.php`)

- On `init`, register rewrite rules:
  ```php
  $base = get_option( 'pikari_team_settings', [] )['url_base'] ?? 'card';
  add_rewrite_tag( '%pikari_card_slug%', '([^/]+)' );
  add_rewrite_tag( '%pikari_card_action%', '([^/]+)' );
  add_rewrite_rule(
      $base . '/([^/]+)/download\.vcf/?$',
      'index.php?pikari_card_slug=$matches[1]&pikari_card_action=download',
      'top'
  );
  add_rewrite_rule(
      $base . '/([^/]+)/manifest\.json/?$',
      'index.php?pikari_card_slug=$matches[1]&pikari_card_action=manifest',
      'top'
  );
  add_rewrite_rule(
      $base . '/([^/]+)/sw\.js/?$',
      'index.php?pikari_card_slug=$matches[1]&pikari_card_action=sw',
      'top'
  );
  add_rewrite_rule(
      $base . '/([^/]+)/?$',
      'index.php?pikari_card_slug=$matches[1]',
      'top'
  );
  ```
- Hook `query_vars` to register `pikari_card_slug` and `pikari_card_action`
- Hook `template_include` to intercept card routes:
  - Query the `pikari_team_member` post by slug
  - If no action: load the PWA HTML shell (`templates/card-standalone.php`)
  - For `download` action: hand off to `VCard` class (Phase 3)
  - For `manifest` action: hand off to `PWA` class (Phase 4)
  - For `sw` action: hand off to `PWA` class (Phase 4)
- Flush rewrite rules on plugin activation/deactivation (already wired in Phase 0)

### 2b. Card Standalone Template (`templates/card-standalone.php`)

The PWA HTML shell — a complete HTML document independent of the active theme:

- Full `<!DOCTYPE html>` document, no `wp_head()`/`wp_footer()`
- Mobile-first viewport meta: `<meta name="viewport" content="width=device-width, initial-scale=1">`
- Inline critical CSS from `assets/css/card.css`
- Render the card content:
  - **Block theme active:** Render the selected template part (from `pikari_team_card_template` meta) inside the shell. Block bindings pull data from post meta.
  - **Classic theme active:** Check for theme override at `{theme}/pikari-team/card-standalone.php`, then fall back to the plugin's built-in PHP rendering.
- Include `tel:` and `mailto:` tappable links, QR code section, and "Save Contact" `.vcf` download button
- PWA meta tags (placeholder for Phase 4)

### 2c. Template Parts (`Template_Parts.php`)

- Filter `default_wp_template_part_areas` to register the `pikari-card` area:
  ```php
  add_filter( 'default_wp_template_part_areas', function( $areas ) {
      $areas[] = [
          'area'        => 'pikari-card',
          'label'       => __( 'Business Cards', 'pikari-team' ),
          'description' => __( 'Card templates for digital business cards.', 'pikari-team' ),
          'icon'        => 'id-alt',
      ];
      return $areas;
  } );
  ```
- Create `parts/card-default.html` — block markup with bindings:
  ```html
  <!-- wp:group {"layout":{"type":"constrained"}} -->
  <div class="wp-block-group">
  	<!-- wp:post-featured-image {"isLink":false} /-->
  	<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"pikari-team/meta","args":{"key":"pikari_team_first_name"}}}}} -->
  	<h2 class="wp-block-heading"></h2>
  	<!-- /wp:heading -->
  	<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"pikari-team/meta","args":{"key":"pikari_team_job_title"}}}}} -->
  	<p></p>
  	<!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
  ```
- Create `parts/card-corporate.html` — more formal, structured layout
- Create `parts/card-minimal.html` — just name, title, QR code
- Admins can edit these or create new ones in the Site Editor under Template Parts > Business Cards. Each team member selects which part via `pikari_team_card_template` meta (defaults to `card-default`).

### 2d. Block Templates for Archive/Single

- Create `templates/single-pikari_team_member.html`:
  ```html
  <!-- wp:template-part {"slug":"header","area":"header"} /-->
  <!-- wp:group {"layout":{"type":"constrained"}} -->
  <div class="wp-block-group">
  	<!-- wp:post-featured-image /-->
  	<!-- wp:post-title /-->
  	<!-- wp:post-content /-->
  </div>
  <!-- /wp:group -->
  <!-- wp:template-part {"slug":"footer","area":"footer"} /-->
  ```
- Create `templates/archive-pikari_team_member.html` with a query loop over team members
- WordPress auto-discovers templates in plugin `templates/` directories (WP 6.7+). Theme authors override by placing their own templates in their theme.

### 2e. Shortcode (`Shortcode.php`)

- Create a shared render function: `public static function render_card( int $post_id ): string`
  - Accepts a post ID, returns HTML for an embeddable card
  - Uses `templates/card-embed.php` as the template (output buffered)
  - Escapes all output
- Register shortcode `[pikari_team_card]` with `id` and `slug` attributes:
  ```php
  add_shortcode( 'pikari_team_card', [ $this, 'shortcode_handler' ] );
  ```
  - If `slug` provided, query by slug to resolve the post ID
  - Call `render_card()` with the resolved ID

### 2f. Card Embed Block (`src/blocks/card/`)

- `block.json`:
  - `"name": "pikari-team/card"`
  - `"attributes": { "postId": { "type": "number" } }`
  - `"render": "file:./render.php"`
- `edit.js`: Use a `ComboboxControl` or similar to search/select team members via REST API (`/wp/v2/pikari_team_member?search=...`). Use `useEntityRecords` for data fetching.
- `render.php`: Call `\Pikari\Team\Shortcode::render_card( $attributes['postId'] )` for server-side rendering
- Register in main plugin file: `register_block_type( PIKARI_TEAM_DIR . 'build/blocks/card' )`

### 2g. Card Embed Template (`templates/card-embed.php`)

- HTML partial for an embeddable inline card
- Shows: headshot, name, title, company, phone, email
- CSS class prefix: `pikari-team-card-embed`

### 2h. Card Standalone CSS (`assets/css/card.css`)

- Mobile-first styles for the PWA card page
- CSS custom properties for theme color (from plugin settings `brand_color`)
- Responsive layout targeting 375px primary, scaling up
- Styles for: header, headshot, contact info, social links, QR section, save button
- Target WCAG AA accessibility

### Verify

- `composer test` — all Phase 2 tests pass (plus Phase 1 still green)
- Visit `/card/{slug}/` for a team member — PWA shell renders with card content
- Theme override works: create `{theme}/pikari-team/card-standalone.php`
- `[pikari_team_card slug="test"]` renders an inline card on any page
- `pikari-team/card` block works in the editor (select team member, preview renders)
- Block templates appear in Site Editor for block themes
- `npm run lint:all && composer lint && composer test` all pass
- Commit: `feat: add card routing, templates, template parts, and shortcode`

---

## Phase 3: vCard + QR Code

**Goal:** Generate downloadable vCard files and QR codes containing vCard data. These classes are pure logic — ideal for thorough TDD.

### 3-tests. Write tests first

**`tests/php/VCardTest.php`** — Test extensively (pure logic, no WordPress UI):

- `generate_vcard` returns a string starting with `BEGIN:VCARD` and ending with `END:VCARD`
- `VERSION:3.0` is present in output
- `FN` property contains first + last name
- `N` property has last;first format
- `ORG`, `TITLE`, `TEL`, `EMAIL`, `ADR`, `URL` properties map correctly from meta
- Semicolons in field values are escaped (e.g., company name "Foo; Bar" → `Foo\; Bar`)
- Commas in field values are escaped
- Backslashes in field values are escaped
- Empty/missing fields are omitted from the vCard (no blank properties)
- `include_photo: false` omits the `PHOTO` property entirely
- `include_photo: true` includes base64-encoded `PHOTO` when featured image exists
- `include_photo: true` omits `PHOTO` when no featured image exists
- Download headers: correct `Content-Type` and `Content-Disposition`

**`tests/php/QR_CodeTest.php`** — Test:

- `generate_qr_svg` returns a string containing `<svg`
- The QR data uses the vCard string without photo (calls `generate_vcard` with `include_photo: false`)
- Result is valid SVG markup (contains `</svg>`)

### 3a. vCard Generator (`VCard.php`)

- Method: `public function generate_vcard( int $post_id, bool $include_photo = true ): string`
- Build a vCard 3.0 string:
  ```text
  BEGIN:VCARD
  VERSION:3.0
  FN:{first_name} {last_name}
  N:{last_name};{first_name};;;
  ORG:{company}
  TITLE:{job_title}
  TEL;TYPE=WORK,VOICE:{phone}
  TEL;TYPE=CELL,VOICE:{cell}
  EMAIL;TYPE=INTERNET:{email}
  ADR;TYPE=WORK:;;{street};{city};{state};{zip};{country}
  URL:{website}
  END:VCARD
  ```
- If `$include_photo` is true and post has a featured image:
  - Get the image file path, read its contents
  - Base64 encode: `PHOTO;ENCODING=b;TYPE=JPEG:{base64_data}`
- **Sanitize vCard special characters:** escape semicolons, commas, and backslashes in field values (these have special meaning in vCard format)
- Handle the download endpoint (called from `Template.php` when action is `download`):
  ```php
  header( 'Content-Type: text/vcard; charset=utf-8' );
  header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $name ) . '.vcf"' );
  echo $vcard_string;
  exit;
  ```

### 3b. QR Code Generator (`QR_Code.php`)

- Method: `public function generate_qr_svg( int $post_id ): string`
- Get the vCard string **without photo** (pass `include_photo: false`) — the PHOTO field would exceed QR data capacity limits
- Use `chillerlan/php-qrcode` (already in `composer.json`):

  ```php
  use chillerlan\QRCode\{QRCode, QROptions};
  use chillerlan\QRCode\Output\QROutputInterface;

  $options = new QROptions();
  $options->outputType   = QROutputInterface::MARKUP_SVG;
  $options->eccLevel     = QRCode::ECC_M;
  $options->addQuietzone = true;

  $qr = new QRCode( $options );
  return $qr->render( $vcard_string );
  ```

- The SVG is embedded inline in the card template (no external file needed)
- Scanning the QR code immediately offers to save the contact (no URL intermediary)

### Verify

- `composer test` — all Phase 3 tests pass (plus Phases 1-2 still green)
- Visit `/card/{slug}/download.vcf` — downloads a valid .vcf file
- Import the .vcf into a Contacts app — all fields populate correctly
- Featured image appears as contact photo
- QR code renders as inline SVG on the card page
- Scan QR code with a phone — offers to save contact with all fields
- `npm run lint:all && composer lint && composer test` all pass
- Commit: `feat: add vCard generation and QR code`

---

## Phase 4: Progressive Web App

**Goal:** Make the card pages installable as standalone PWAs with offline support.

### 4-tests. Write tests first

**`tests/php/PWATest.php`** — Test:

- Manifest handler outputs valid JSON with correct `Content-Type` header
- Manifest `short_name` is `{first_name} {last_name}`
- Manifest `start_url` uses the configured URL base
- Manifest `theme_color` uses brand color from settings (default `#0073aa`)
- Manifest `icons` array includes 192px and 512px entries when logo is set
- Service worker output includes the correct cache name with version hash
- Version hash changes when `post_modified` changes
- Service worker includes precache URLs for the card page, manifest, and CSS

### 4a. Web App Manifest (`PWA.php`)

- Handler for the `manifest` action (called from `Template.php`):
  ```php
  header( 'Content-Type: application/manifest+json' );
  echo wp_json_encode( [
      'name'             => $first_name . ' ' . $last_name . ' - ' . $company,
      'short_name'       => $first_name . ' ' . $last_name,
      'start_url'        => '/' . $base . '/' . $post->post_name . '/',
      'display'          => 'standalone',
      'theme_color'      => $settings['brand_color'] ?? '#0073aa',
      'background_color' => '#ffffff',
      'icons'            => $icons_array,
  ] );
  exit;
  ```
- Icons: Use the company logo from plugin settings (generate 192px and 512px via `wp_get_attachment_image_src()` or WordPress image sizes)

### 4b. Service Worker (`PWA.php`)

- Handler for the `sw` action — outputs a PHP-rendered JS file with `Content-Type: application/javascript`:

  ```js
  const CACHE_NAME = 'pikari-card-{slug}-v{hash}';
  const PRECACHE_URLS = [
  	'/{base}/{slug}/',
  	'/{base}/{slug}/manifest.json',
  	'{card_css_url}',
  	'{headshot_url}',
  ];

  self.addEventListener('install', (event) => {
  	event.waitUntil(
  		caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS))
  	);
  	self.skipWaiting();
  });

  self.addEventListener('activate', (event) => {
  	event.waitUntil(
  		caches
  			.keys()
  			.then((keys) =>
  				Promise.all(
  					keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
  				)
  			)
  	);
  	self.clients.claim();
  });

  self.addEventListener('fetch', (event) => {
  	event.respondWith(
  		caches
  			.match(event.request)
  			.then((cached) => cached || fetch(event.request))
  	);
  });
  ```

- **Cache invalidation:** Version hash is `md5( $post->post_modified )` — changes when the post is edited, triggering SW re-install and cache refresh

### 4c. Service Worker Registration (`assets/js/sw-register.js`)

- Vanilla JS, no build step needed:
  ```js
  if ('serviceWorker' in navigator) {
  	navigator.serviceWorker.register('./sw.js');
  }
  ```
- Loaded via `<script>` tag in the card standalone template

### 4d. PWA Meta Tags

- Add to the card standalone template `<head>`:
  ```html
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="default" />
  <link rel="apple-touch-icon" href="{logo_192}" />
  <link rel="manifest" href="/{base}/{slug}/manifest.json" />
  <meta name="theme-color" content="{brand_color}" />
  ```

### Verify

- `composer test` — all Phase 4 tests pass (plus Phases 1-3 still green)
- Card page has `<link rel="manifest">` pointing to valid JSON
- Service worker registers (check DevTools > Application > Service Workers)
- Card page works offline after first visit
- Editing a team member and refreshing invalidates the old cache
- Lighthouse PWA audit passes
- Lighthouse performance targets 95+
- `npm run lint:all && composer lint && composer test` all pass
- Commit: `feat: add PWA manifest, service worker, and offline caching`

---

## Final Checklist

After all phases are complete:

- [ ] All tests pass: `composer test` (PHP), `npm test` (JS)
- [ ] All linters pass: `npm run lint:all`, `composer lint`
- [ ] Plugin activates/deactivates cleanly (no errors, rewrite rules flush)
- [ ] Classic Editor: meta box renders, saves, loads all fields
- [ ] Block Editor: sidebar panel works, block bindings populate content
- [ ] Settings page: all options save and reflect in CPT labels and card rendering
- [ ] `/card/{slug}/` renders PWA shell with card content
- [ ] `/card/{slug}/download.vcf` downloads valid vCard with all fields + photo
- [ ] QR code scans correctly on mobile (offers contact save)
- [ ] Shortcode `[pikari_team_card]` renders card inline on any page
- [ ] `pikari-team/card` block works in the editor
- [ ] Block templates appear in Site Editor for block themes
- [ ] Card template parts editable in Site Editor under "Business Cards"
- [ ] Classic theme override works (`{theme}/pikari-team/card-standalone.php`)
- [ ] PWA installs on mobile, works offline
- [ ] Lighthouse 95+ on card page
- [ ] WCAG AA: all interactive elements accessible, proper focus management, ARIA attributes
- [ ] No console errors or PHP warnings in debug mode
- [ ] Update `CLAUDE.md` with architecture documentation reflecting what was built
