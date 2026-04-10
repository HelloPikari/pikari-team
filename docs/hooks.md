# Pikari Team — Hooks, Filters & Template Tags Reference

Complete reference for all action hooks, filters, template tag functions, and template overrides provided by the Pikari Team plugin.

---

## Action Hooks

### Card Section Hooks

Six action hooks fire in sequence when a team member card is rendered. Each hook passes the same two arguments: the member data array and the rendering context string.

| Hook                       | Default callback                  | Priority |
| -------------------------- | --------------------------------- | -------- |
| `pikari_team_card_header`  | `Card_Renderer::render_header()`  | 10       |
| `pikari_team_card_contact` | `Card_Renderer::render_contact()` | 10       |
| `pikari_team_card_address` | `Card_Renderer::render_address()` | 10       |
| `pikari_team_card_social`  | `Card_Renderer::render_social()`  | 10       |
| `pikari_team_card_qr`      | `Card_Renderer::render_qr()`      | 10       |
| `pikari_team_card_footer`  | `Card_Renderer::render_footer()`  | 10       |

**Parameters (all six hooks):**

| Parameter  | Type     | Description                                                                                               |
| ---------- | -------- | --------------------------------------------------------------------------------------------------------- |
| `$data`    | `array`  | Structured member data. See [Template Tag Functions](#template-tag-functions) for the full key reference. |
| `$context` | `string` | Rendering context: `'single'`, `'embed'`, `'shortcode'`, or `'standalone'`.                               |

**Which sections fire in each context:**

| Context                                                        | Sections                                     |
| -------------------------------------------------------------- | -------------------------------------------- |
| `standalone` (PWA card page via `card-standalone.php`)         | header, contact, address, social, qr, footer |
| `single` (WordPress single post via `Card_Renderer::render()`) | header, contact, address, social, qr, footer |
| `embed` (card embed block)                                     | header, contact                              |
| `shortcode` (`[pikari_team_card]`)                             | header, contact                              |

**Example: Replace the header section with a custom layout**

```php
/**
 * Replace the default card header with a custom layout.
 *
 * @param array  $data    Member data from pikari_team_get_member_data().
 * @param string $context Rendering context.
 */
function my_theme_custom_card_header( array $data, string $context ): void {
    echo '<div class="my-card-header">';

    if ( $data['has_photo'] ) {
        echo '<img src="' . esc_url( $data['photo_url'] ) . '" alt="' . esc_attr( $data['full_name'] ) . '">';
    }

    echo '<h1>' . esc_html( $data['full_name'] ) . '</h1>';

    if ( $data['has_job_title'] ) {
        echo '<p class="title">' . esc_html( $data['job_title'] ) . '</p>';
    }

    echo '</div>';
}

// Remove the default header callback, then add the custom one.
remove_action( 'pikari_team_card_header', [ 'Pikari\Team\Card_Renderer', 'render_header' ], 10 );
add_action( 'pikari_team_card_header', 'my_theme_custom_card_header', 10, 2 );
```

**Example: Add a QR code to the embed context**

By default the `qr` section does not fire in the `embed` or `shortcode` contexts. You can inject it after the contact section:

```php
/**
 * Add a QR code after the contact section in embed contexts.
 *
 * @param array  $data    Member data.
 * @param string $context Rendering context.
 */
function my_plugin_embed_qr( array $data, string $context ): void {
    if ( 'embed' !== $context && 'shortcode' !== $context ) {
        return;
    }

    pikari_team_the_qr_code( (int) $data['post_id'] );
}

add_action( 'pikari_team_card_contact', 'my_plugin_embed_qr', 20, 2 );
```

**Example: Append a section after the footer**

```php
/**
 * Add a custom "Schedule a Meeting" button after the footer.
 *
 * @param array  $data    Member data.
 * @param string $context Rendering context.
 */
function my_plugin_meeting_link( array $data, string $context ): void {
    $meeting_url = get_post_meta( (int) $data['post_id'], 'my_meeting_url', true );

    if ( empty( $meeting_url ) ) {
        return;
    }

    echo '<div class="my-meeting-link">';
    echo '<a href="' . esc_url( $meeting_url ) . '">' . esc_html__( 'Schedule a Meeting', 'my-plugin' ) . '</a>';
    echo '</div>';
}

add_action( 'pikari_team_card_footer', 'my_plugin_meeting_link', 20, 2 );
```

---

### `pikari_team_card_head`

Fires inside the `<head>` element of the standalone PWA card template. Use this to inject custom `<meta>` tags, `<link>` elements, or `<script>` tags.

**Parameters:**

| Parameter | Type    | Description             |
| --------- | ------- | ----------------------- |
| `$data`   | `array` | Structured member data. |

**Example: Add Open Graph meta tags**

```php
/**
 * Add Open Graph meta tags to standalone card pages.
 *
 * @param array $data Member data.
 */
function my_theme_card_og_tags( array $data ): void {
    echo '<meta property="og:title" content="' . esc_attr( $data['full_name'] ) . '">';
    echo '<meta property="og:type" content="profile">';

    if ( $data['has_photo'] ) {
        echo '<meta property="og:image" content="' . esc_url( $data['photo_url'] ) . '">';
    }

    if ( ! empty( $data['card_url'] ) ) {
        echo '<meta property="og:url" content="' . esc_url( $data['card_url'] ) . '">';
    }
}

add_action( 'pikari_team_card_head', 'my_theme_card_og_tags' );
```

---

### PWA and Download Hooks

These hooks fire for special card URL actions. Each receives the `WP_Post` object for the team member. The post has already been validated as `publish` status before the hook fires.

| Hook                        | Fires at URL                   | Default handler                                                              |
| --------------------------- | ------------------------------ | ---------------------------------------------------------------------------- |
| `pikari_team_card_download` | `/{base}/{slug}/download.vcf`  | `VCard::handle_download()` — outputs vCard 3.0 and exits                     |
| `pikari_team_card_manifest` | `/{base}/{slug}/manifest.json` | `PWA::handle_manifest()` — outputs Web App Manifest JSON and exits           |
| `pikari_team_card_sw`       | `/{base}/{slug}/sw.js`         | `PWA::handle_service_worker()` — outputs service worker JavaScript and exits |

**Parameters (all three hooks):**

| Parameter | Type      | Description                     |
| --------- | --------- | ------------------------------- |
| `$post`   | `WP_Post` | The published team member post. |

**Note:** These hooks terminate execution with `exit` via their default handlers. If you add your own handler and want to replace the default output entirely, remove the default action first, then output headers and content from your callback and call `exit`.

**Example: Log vCard downloads**

```php
/**
 * Log when a vCard is downloaded.
 *
 * @param WP_Post $post The team member post.
 */
function my_plugin_log_vcard_download( WP_Post $post ): void {
    // Log runs before VCard::handle_download() because priority 5 < 10.
    error_log( 'vCard downloaded: post ID ' . $post->ID );
}

add_action( 'pikari_team_card_download', 'my_plugin_log_vcard_download', 5 );
```

---

## Filters

### `pikari_team_post_type_args`

Filters the arguments array passed to `register_post_type()` for the `pikari_team_member` CPT. Use this to modify CPT configuration — such as adding taxonomy support, changing capability requirements, or altering rewrite rules — without deregistering and re-registering the post type.

**Parameters:**

| Parameter | Type    | Description                                      |
| --------- | ------- | ------------------------------------------------ |
| `$args`   | `array` | The full `register_post_type()` arguments array. |

**Return:** `array` — The (optionally modified) arguments array.

**Example: Add a custom taxonomy to the team CPT**

```php
/**
 * Add a Department taxonomy to the team member CPT.
 *
 * @param array $args Post type registration arguments.
 * @return array
 */
function my_plugin_team_add_taxonomy_support( array $args ): array {
    $args['taxonomies'] = array_merge(
        $args['taxonomies'] ?? [],
        [ 'department' ]
    );

    return $args;
}

add_filter( 'pikari_team_post_type_args', 'my_plugin_team_add_taxonomy_support' );
```

**Example: Change the menu position and icon**

```php
/**
 * Customize the team CPT admin menu appearance.
 *
 * @param array $args Post type registration arguments.
 * @return array
 */
function my_theme_team_menu_position( array $args ): array {
    $args['menu_position'] = 5;
    $args['menu_icon']     = 'dashicons-groups';

    return $args;
}

add_filter( 'pikari_team_post_type_args', 'my_theme_team_menu_position' );
```

---

### `pikari_team_card_css`

Filters additional CSS to inline into the `<style>` block of the standalone card template. The filtered string is appended after the plugin's built-in `card.css` and the `:root { --pikari-brand-color }` declaration.

**Parameters:**

| Parameter | Type     | Description                                     |
| --------- | -------- | ----------------------------------------------- |
| `$css`    | `string` | Additional CSS string. Empty string by default. |
| `$data`   | `array`  | Structured member data for the current card.    |

**Return:** `string` — The CSS string to inject (may be empty).

**Example: Add per-member accent colour from custom meta**

```php
/**
 * Inject a custom accent colour for each team member.
 *
 * @param string $css  Existing CSS to append to.
 * @param array  $data Member data.
 * @return string
 */
function my_theme_member_accent_css( string $css, array $data ): string {
    $accent = get_post_meta( (int) $data['post_id'], 'my_accent_color', true );

    if ( empty( $accent ) ) {
        return $css;
    }

    $css .= '.pikari-team-card__save-btn { background-color: ' . sanitize_hex_color( $accent ) . '; }';

    return $css;
}

add_filter( 'pikari_team_card_css', 'my_theme_member_accent_css', 10, 2 );
```

---

## Template Tag Functions

Global convenience functions available from any template or plugin. Each is a thin wrapper around the corresponding `Pikari\Team\Template_Tags` static method.

---

### `pikari_team_get_member_data( int $post_id ): array`

Returns a fully structured data array for a team member. This is the same array passed to all card section hooks.

**Parameters:**

| Parameter  | Type  | Description              |
| ---------- | ----- | ------------------------ |
| `$post_id` | `int` | The team member post ID. |

**Return:** `array` — Associative array with the following keys:

**Raw meta fields** (all `string`, empty string when not set):

| Key               | Description                                           |
| ----------------- | ----------------------------------------------------- |
| `first_name`      | First name                                            |
| `last_name`       | Last name                                             |
| `designation`     | Honorific or post-nominal letters (e.g. "Dr.", "PhD") |
| `job_title`       | Job title                                             |
| `email`           | Email address                                         |
| `phone`           | Work phone number                                     |
| `cell`            | Mobile / cell phone number                            |
| `company`         | Company or organisation name                          |
| `department`      | Department name                                       |
| `website`         | Website URL                                           |
| `address_street`  | Street address                                        |
| `address_city`    | City                                                  |
| `address_state`   | State or province                                     |
| `address_zip`     | ZIP or postal code                                    |
| `address_country` | Country                                               |
| `linkedin`        | LinkedIn profile URL                                  |
| `twitter`         | Twitter/X profile URL                                 |

**Computed values:**

| Key         | Type     | Description                                                      |
| ----------- | -------- | ---------------------------------------------------------------- |
| `post_id`   | `int`    | The team member post ID                                          |
| `full_name` | `string` | `first_name` + `last_name`, trimmed                              |
| `photo_url` | `string` | URL of the featured image at `medium` size; empty string if none |
| `card_url`  | `string` | Full URL to the standalone card page                             |
| `vcard_url` | `string` | Full URL to the `.vcf` download endpoint                         |

**Grouped sub-arrays:**

| Key       | Type    | Description                                                      |
| --------- | ------- | ---------------------------------------------------------------- |
| `address` | `array` | Keys: `street`, `city`, `state`, `zip`, `country` (all `string`) |
| `social`  | `array` | Keys: `linkedin`, `twitter` (all `string`)                       |

**Presence flags** (`bool`, `true` when the corresponding field is non-empty):

| Key               | Corresponds to                           |
| ----------------- | ---------------------------------------- |
| `has_photo`       | `photo_url`                              |
| `has_designation` | `designation`                            |
| `has_job_title`   | `job_title`                              |
| `has_phone`       | `phone`                                  |
| `has_cell`        | `cell`                                   |
| `has_company`     | `company`                                |
| `has_department`  | `department`                             |
| `has_website`     | `website`                                |
| `has_linkedin`    | `linkedin`                               |
| `has_twitter`     | `twitter`                                |
| `has_address`     | `true` if any address field is non-empty |
| `has_social`      | `true` if any social field is non-empty  |

**Example:**

```php
$data = pikari_team_get_member_data( get_the_ID() );

if ( $data['has_photo'] ) {
    echo '<img src="' . esc_url( $data['photo_url'] ) . '" alt="' . esc_attr( $data['full_name'] ) . '">';
}

echo '<h2>' . esc_html( $data['full_name'] ) . '</h2>';

if ( $data['has_job_title'] ) {
    echo '<p>' . esc_html( $data['job_title'] ) . '</p>';
}
```

---

### `pikari_team_get_address( int $post_id ): string`

Returns a formatted address string for a team member. Parts are omitted when empty. Format: `Street, City, State ZIP, Country`.

**Parameters:**

| Parameter  | Type  | Description              |
| ---------- | ----- | ------------------------ |
| `$post_id` | `int` | The team member post ID. |

**Return:** `string` — Formatted address, or an empty string if no address fields are set.

**Example:**

```php
$address = pikari_team_get_address( get_the_ID() );

if ( $address ) {
    echo '<address>' . esc_html( $address ) . '</address>';
}
```

---

### `pikari_team_the_address( int $post_id ): void`

Outputs the formatted address string, escaped with `esc_html()`. Returns nothing.

**Parameters:**

| Parameter  | Type  | Description              |
| ---------- | ----- | ------------------------ |
| `$post_id` | `int` | The team member post ID. |

**Example:**

```php
<?php pikari_team_the_address( get_the_ID() ); ?>
```

---

### `pikari_team_get_social_links( int $post_id ): array`

Returns an array of populated social link entries. Platforms with an empty URL are omitted.

**Parameters:**

| Parameter  | Type  | Description              |
| ---------- | ----- | ------------------------ |
| `$post_id` | `int` | The team member post ID. |

**Return:** `array` — Indexed array of entries. Each entry is an associative array:

| Key        | Type     | Description                                         |
| ---------- | -------- | --------------------------------------------------- |
| `platform` | `string` | Platform slug: `'linkedin'` or `'twitter'`          |
| `url`      | `string` | The profile URL                                     |
| `label`    | `string` | Human-readable label: `'LinkedIn'` or `'Twitter/X'` |

**Example:**

```php
$links = pikari_team_get_social_links( get_the_ID() );

foreach ( $links as $link ) {
    printf(
        '<a href="%s" rel="noopener noreferrer">%s</a>',
        esc_url( $link['url'] ),
        esc_html( $link['label'] )
    );
}
```

---

### `pikari_team_the_qr_code( int $post_id ): void`

Outputs the SVG QR code encoding the team member's vCard data. The QR code is generated by the `chillerlan/php-qrcode` library and does not include the photo (to stay within QR capacity limits). Outputs nothing if the library is unavailable or the post ID is invalid.

**Parameters:**

| Parameter  | Type  | Description              |
| ---------- | ----- | ------------------------ |
| `$post_id` | `int` | The team member post ID. |

**Example:**

```php
<div class="my-card-qr">
    <?php pikari_team_the_qr_code( get_the_ID() ); ?>
</div>
```

---

## Template Overrides

### Single Post Template

The standard WordPress single post view for `pikari_team_member` posts is served by:

```
templates/single-pikari_team_member.php
```

**To override from a theme**, create the file at:

```
{theme}/single-pikari_team_member.php
```

WordPress's `locate_template()` is used to discover the override, so child themes are also supported. If the theme provides the file it takes precedence over the plugin's version.

---

### Standalone PWA Card

The standalone digital business card page (accessible at `/{url_base}/{slug}/`) is served by:

```
templates/card-standalone.php
```

This is a complete, self-contained HTML document independent of the active theme. It includes inlined CSS, the brand colour variable, PWA manifest link, apple-touch-icon, service worker registration, and fires all six card section hooks with the `'standalone'` context.

**To override from a theme**, create the file at:

```
{theme}/pikari-team/card-standalone.php
```

The plugin checks for this path with `locate_template()` before falling back to its own template. When overriding, you are responsible for including the PWA plumbing (manifest link, service worker registration) if you wish to retain PWA functionality.

**Recommended: use hooks instead of a full template override.** For most customisations — changing styles, adding sections, or injecting `<head>` content — the [`pikari_team_card_css`](#pikari_team_card_css), [`pikari_team_card_head`](#pikari_team_card_head), and [card section hooks](#card-section-hooks) give you targeted control without duplicating the full template.
