# Classic Editor, Hookable Templates & Validation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the Pikari Team plugin production-ready for classic editor usage with a hookable template system, public helper API, field validation, and developer documentation.

**Architecture:** New `Template_Tags` class provides the public helper API (`pikari_team_get_member_data()`, template tag functions). Templates are refactored to use action hooks with default callbacks, receiving a structured `$data` array and `$context` string. Meta box gains HTML5 input types, required field indicators, and server-side validation with admin notices. A `single-pikari_team_member.php` template renders within the active theme using `get_header()`/`get_footer()`.

**Tech Stack:** PHP 8.2, WordPress 6.8+, Brain\Monkey/PHPUnit for testing

---

## File Map

| File                                      | Action | Responsibility                                                                                                        |
| ----------------------------------------- | ------ | --------------------------------------------------------------------------------------------------------------------- |
| `includes/Template_Tags.php`              | Create | Public helper API: `pikari_team_get_member_data()`, formatted helpers, template tag functions                         |
| `includes/Card_Renderer.php`              | Create | Hookable card rendering: registers default hook callbacks, provides `render()` method used by templates and shortcode |
| `includes/Meta_Box.php`                   | Modify | HTML5 input types, required field asterisks, phone validation                                                         |
| `includes/Post_Type.php`                  | Modify | `pikari_team_post_type_args` filter                                                                                   |
| `includes/Shortcode.php`                  | Modify | Use `Card_Renderer::render()` instead of raw template include                                                         |
| `includes/Validation.php`                 | Create | Shared validation logic: required fields check, phone format, admin notices                                           |
| `templates/card-standalone.php`           | Modify | Refactor to use action hooks + `pikari_team_card_css` filter                                                          |
| `templates/card-embed.php`                | Remove | Replaced by `Card_Renderer`                                                                                           |
| `templates/single-pikari_team_member.php` | Create | In-theme single post template using `get_header()`/`get_footer()` + `Card_Renderer`                                   |
| `pikari-team.php`                         | Modify | Instantiate new classes, load template tag functions file                                                             |
| `tests/php/Template_TagsTest.php`         | Create | Tests for `pikari_team_get_member_data()` and helper functions                                                        |
| `tests/php/Card_RendererTest.php`         | Create | Tests for hook firing, context parameter, default section output                                                      |
| `tests/php/ValidationTest.php`            | Create | Tests for required fields, phone format validation                                                                    |
| `tests/php/Meta_BoxTest.php`              | Modify | Tests for HTML5 input types, required indicators                                                                      |
| `tests/php/Post_TypeTest.php`             | Modify | Test for `pikari_team_post_type_args` filter                                                                          |
| `docs/hooks.md`                           | Create | Developer reference for all hooks and filters                                                                         |

---

## Task 1: CPT Args Filter

**Files:**

- Modify: `includes/Post_Type.php:89-128`
- Modify: `tests/php/Post_TypeTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/php/Post_TypeTest.php — add this test method

public function test_post_type_args_are_filterable(): void {
    Functions\when( 'get_option' )->justReturn( [] );
    Functions\when( 'register_post_meta' )->justReturn( true );

    Filters\expectApplied( 'pikari_team_post_type_args' )
        ->once()
        ->with( \Mockery::type( 'array' ) );

    Functions\expect( 'register_post_type' )
        ->once()
        ->with( 'pikari_team_member', \Mockery::type( 'array' ) );

    $post_type = new Post_Type();
    $post_type->register();
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/Post_TypeTest.php --filter test_post_type_args_are_filterable -v`
Expected: FAIL — `pikari_team_post_type_args` filter was not applied

- [ ] **Step 3: Implement the filter**

In `includes/Post_Type.php`, extract the args array to a variable and pass through `apply_filters` before `register_post_type`:

```php
// In register() method, replace the register_post_type call.
// Build $args array first, then filter it.

$args = [
    'labels'       => $labels,
    'public'       => true,
    'has_archive'  => false,
    'show_in_rest' => true,
    'supports'     => [ 'title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes' ],
    'menu_icon'    => 'dashicons-id-alt',
    'template'     => [
        [ 'core/post-featured-image' ],
        [
            'core/heading',
            [
                'metadata' => [
                    'bindings' => [
                        'content' => [
                            'source' => 'pikari-team/meta',
                            'args'   => [ 'key' => 'pikari_team_first_name' ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'core/paragraph',
            [
                'metadata' => [
                    'bindings' => [
                        'content' => [
                            'source' => 'pikari-team/meta',
                            'args'   => [ 'key' => 'pikari_team_job_title' ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];

/**
 * Filters the post type registration arguments.
 *
 * Allows themes and plugins to modify CPT args (supports, capabilities,
 * labels, etc.) without deregistering and re-registering.
 *
 * @param array $args Post type arguments passed to register_post_type().
 */
$args = apply_filters( 'pikari_team_post_type_args', $args );

register_post_type( 'pikari_team_member', $args );
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/Post_TypeTest.php -v`
Expected: All Post_Type tests PASS

- [ ] **Step 5: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add includes/Post_Type.php tests/php/Post_TypeTest.php
git commit -m "feat: add pikari_team_post_type_args filter for CPT customization"
```

---

## Task 2: Validation Class

**Files:**

- Create: `includes/Validation.php`
- Create: `tests/php/ValidationTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/php/ValidationTest.php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Validation;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

class ValidationTest extends TestCase {

    public function test_required_fields_returns_correct_keys(): void {
        $this->assertSame(
            [ 'pikari_team_first_name', 'pikari_team_last_name', 'pikari_team_email' ],
            Validation::get_required_fields()
        );
    }

    public function test_is_required_returns_true_for_required_field(): void {
        $this->assertTrue( Validation::is_required( 'pikari_team_first_name' ) );
        $this->assertTrue( Validation::is_required( 'pikari_team_last_name' ) );
        $this->assertTrue( Validation::is_required( 'pikari_team_email' ) );
    }

    public function test_is_required_returns_false_for_optional_field(): void {
        $this->assertFalse( Validation::is_required( 'pikari_team_phone' ) );
        $this->assertFalse( Validation::is_required( 'pikari_team_company' ) );
    }

    public function test_validate_phone_accepts_valid_formats(): void {
        $this->assertTrue( Validation::validate_phone( '4155551234' ) );
        $this->assertTrue( Validation::validate_phone( '(415) 555-1234' ) );
        $this->assertTrue( Validation::validate_phone( '+1 415 555 1234' ) );
        $this->assertTrue( Validation::validate_phone( '415-555-1234' ) );
        $this->assertTrue( Validation::validate_phone( '+44 20 7946 0958' ) );
    }

    public function test_validate_phone_rejects_too_short(): void {
        $this->assertFalse( Validation::validate_phone( '123' ) );
        $this->assertFalse( Validation::validate_phone( '12345' ) );
    }

    public function test_validate_phone_accepts_empty_string(): void {
        $this->assertTrue( Validation::validate_phone( '' ) );
    }

    public function test_validate_phone_rejects_non_numeric(): void {
        $this->assertFalse( Validation::validate_phone( 'not a phone' ) );
    }

    public function test_get_missing_required_fields_returns_empty_when_all_present(): void {
        $meta = [
            'pikari_team_first_name' => 'Jane',
            'pikari_team_last_name'  => 'Doe',
            'pikari_team_email'      => 'jane@example.com',
        ];

        $this->assertSame( [], Validation::get_missing_required_fields( $meta ) );
    }

    public function test_get_missing_required_fields_returns_missing_keys(): void {
        $meta = [
            'pikari_team_first_name' => 'Jane',
            'pikari_team_last_name'  => '',
            'pikari_team_email'      => '',
        ];

        $missing = Validation::get_missing_required_fields( $meta );
        $this->assertContains( 'pikari_team_last_name', $missing );
        $this->assertContains( 'pikari_team_email', $missing );
        $this->assertNotContains( 'pikari_team_first_name', $missing );
    }

    public function test_get_field_label_returns_human_readable(): void {
        $this->assertSame( 'First Name', Validation::get_field_label( 'pikari_team_first_name' ) );
        $this->assertSame( 'Email', Validation::get_field_label( 'pikari_team_email' ) );
    }

    public function test_maybe_add_admin_notice_hooks_when_fields_missing(): void {
        Functions\when( 'get_post_meta' )->justReturn( '' );
        Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );

        Actions\expectAdded( 'admin_notices' )->once();

        Validation::maybe_add_admin_notice( 1 );
    }

    public function test_maybe_add_admin_notice_does_nothing_when_complete(): void {
        Functions\expect( 'get_post_meta' )
            ->with( 1, 'pikari_team_first_name', true )->andReturn( 'Jane' );
        Functions\expect( 'get_post_meta' )
            ->with( 1, 'pikari_team_last_name', true )->andReturn( 'Doe' );
        Functions\expect( 'get_post_meta' )
            ->with( 1, 'pikari_team_email', true )->andReturn( 'jane@example.com' );
        Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );

        Actions\expectAdded( 'admin_notices' )->never();

        Validation::maybe_add_admin_notice( 1 );
    }

    public function test_maybe_add_admin_notice_skips_wrong_post_type(): void {
        Functions\when( 'get_post_type' )->justReturn( 'post' );

        Actions\expectAdded( 'admin_notices' )->never();

        Validation::maybe_add_admin_notice( 1 );
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/ValidationTest.php -v`
Expected: FAIL — class `Pikari\Team\Validation` not found

- [ ] **Step 3: Implement Validation class**

```php
<?php
// includes/Validation.php

/**
 * Shared validation logic for team member fields.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Validation {

    /**
     * Required meta field keys.
     *
     * @var string[]
     */
    private const REQUIRED_FIELDS = [
        'pikari_team_first_name',
        'pikari_team_last_name',
        'pikari_team_email',
    ];

    /**
     * Human-readable labels for meta fields.
     *
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'pikari_team_first_name'      => 'First Name',
        'pikari_team_last_name'       => 'Last Name',
        'pikari_team_designation'     => 'Designation',
        'pikari_team_job_title'       => 'Job Title',
        'pikari_team_email'           => 'Email',
        'pikari_team_phone'           => 'Phone',
        'pikari_team_cell'            => 'Cell',
        'pikari_team_company'         => 'Company',
        'pikari_team_department'      => 'Department',
        'pikari_team_website'         => 'Website',
        'pikari_team_address_street'  => 'Street',
        'pikari_team_address_city'    => 'City',
        'pikari_team_address_state'   => 'State/Province',
        'pikari_team_address_zip'     => 'ZIP/Postal Code',
        'pikari_team_address_country' => 'Country',
        'pikari_team_linkedin'        => 'LinkedIn URL',
        'pikari_team_twitter'         => 'Twitter/X URL',
    ];

    /**
     * Minimum number of digits for a valid phone number.
     */
    private const MIN_PHONE_DIGITS = 7;

    public static function get_required_fields(): array {
        return self::REQUIRED_FIELDS;
    }

    public static function is_required( string $field_key ): bool {
        return in_array( $field_key, self::REQUIRED_FIELDS, true );
    }

    public static function get_field_label( string $field_key ): string {
        return self::FIELD_LABELS[ $field_key ] ?? $field_key;
    }

    /**
     * Validate a phone number.
     *
     * Accepts empty strings (phone is optional). For non-empty values,
     * strips all non-digit characters (except leading +) and checks
     * minimum digit count.
     *
     * @param string $phone The phone number to validate.
     * @return bool True if valid or empty.
     */
    public static function validate_phone( string $phone ): bool {
        if ( '' === $phone ) {
            return true;
        }

        // Strip everything except digits.
        $digits = preg_replace( '/[^0-9]/', '', $phone );

        // Must have at least MIN_PHONE_DIGITS digits.
        if ( strlen( $digits ) < self::MIN_PHONE_DIGITS ) {
            return false;
        }

        return true;
    }

    /**
     * Check which required fields are missing from a meta array.
     *
     * @param array<string, string> $meta Key-value pairs of field key => value.
     * @return string[] Array of missing field keys.
     */
    public static function get_missing_required_fields( array $meta ): array {
        $missing = [];

        foreach ( self::REQUIRED_FIELDS as $key ) {
            if ( empty( $meta[ $key ] ) ) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * Conditionally add an admin notice if required fields are missing.
     *
     * Called after save. Adds a hook to display the notice on the next page load.
     *
     * @param int $post_id The post ID to check.
     */
    public static function maybe_add_admin_notice( int $post_id ): void {
        if ( 'pikari_team_member' !== get_post_type( $post_id ) ) {
            return;
        }

        $meta = [];
        foreach ( self::REQUIRED_FIELDS as $key ) {
            $meta[ $key ] = (string) get_post_meta( $post_id, $key, true );
        }

        $missing = self::get_missing_required_fields( $meta );

        if ( empty( $missing ) ) {
            return;
        }

        $labels = array_map( [ self::class, 'get_field_label' ], $missing );

        add_action(
            'admin_notices',
            function () use ( $labels ) {
                $list = implode( ', ', $labels );
                echo '<div class="notice notice-warning is-dismissible"><p>';
                echo esc_html(
                    sprintf(
                        /* translators: %s: comma-separated list of field names */
                        __( 'Team member saved, but the following required fields are missing: %s', 'pikari-team' ),
                        $list
                    )
                );
                echo '</p></div>';
            }
        );
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/ValidationTest.php -v`
Expected: All tests PASS

- [ ] **Step 5: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add includes/Validation.php tests/php/ValidationTest.php
git commit -m "feat: add Validation class with required fields and phone validation"
```

---

## Task 3: Meta Box — HTML5 Input Types, Required Indicators, Validation Integration

**Files:**

- Modify: `includes/Meta_Box.php`
- Modify: `tests/php/Meta_BoxTest.php`

- [ ] **Step 1: Write the failing tests**

Add to `tests/php/Meta_BoxTest.php`:

```php
public function test_save_meta_triggers_validation_notice(): void {
    Functions\when( 'wp_verify_nonce' )->justReturn( true );
    Functions\when( 'current_user_can' )->justReturn( true );
    Functions\when( 'sanitize_text_field' )->returnArg();
    Functions\when( 'update_post_meta' )->justReturn( true );
    Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );
    Functions\when( 'get_post_meta' )->justReturn( '' );

    // Validation should add admin_notices when required fields are empty.
    Actions\expectAdded( 'admin_notices' )->once();

    $_POST['pikari_team_meta_nonce'] = 'valid';

    $meta_box = new Meta_Box();
    $meta_box->save_meta( 1 );

    unset( $_POST['pikari_team_meta_nonce'] );
}

public function test_render_meta_box_outputs_required_asterisks(): void {
    $post     = \Mockery::mock( 'WP_Post' );
    $post->ID = 1;

    Functions\when( 'get_post_meta' )->justReturn( '' );
    Functions\when( 'wp_nonce_field' )->justReturn( '' );

    $meta_box = new Meta_Box();

    ob_start();
    $meta_box->render_meta_box( $post );
    $output = ob_get_clean();

    // Required fields should have an asterisk.
    $this->assertStringContainsString( 'class="pikari-team-required"', $output );
}

public function test_render_meta_box_uses_email_input_type(): void {
    $post     = \Mockery::mock( 'WP_Post' );
    $post->ID = 1;

    Functions\when( 'get_post_meta' )->justReturn( '' );
    Functions\when( 'wp_nonce_field' )->justReturn( '' );

    $meta_box = new Meta_Box();

    ob_start();
    $meta_box->render_meta_box( $post );
    $output = ob_get_clean();

    $this->assertStringContainsString( 'type="email"', $output );
    $this->assertStringContainsString( 'type="tel"', $output );
    $this->assertStringContainsString( 'type="url"', $output );
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/Meta_BoxTest.php --filter "test_save_meta_triggers|test_render_meta_box_outputs_required|test_render_meta_box_uses_email" -v`
Expected: FAIL

- [ ] **Step 3: Implement meta box changes**

Replace `Meta_Box.php` with the updated version. Key changes:

1. Add `INPUT_TYPES` constant mapping field keys to HTML5 types
2. Add required indicator logic using `Validation::is_required()`
3. Call `Validation::maybe_add_admin_notice()` at the end of `save_meta()`

```php
<?php
/**
 * Classic Editor meta box.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Meta_Box {

    private const NONCE_ACTION = 'pikari_team_meta_save';
    private const NONCE_FIELD  = 'pikari_team_meta_nonce';

    /**
     * HTML5 input types for specific fields. Fields not listed default to "text".
     *
     * @var array<string, string>
     */
    private const INPUT_TYPES = [
        'pikari_team_email'   => 'email',
        'pikari_team_phone'   => 'tel',
        'pikari_team_cell'    => 'tel',
        'pikari_team_website' => 'url',
        'pikari_team_linkedin' => 'url',
        'pikari_team_twitter'  => 'url',
    ];

    private const FIELD_GROUPS = [
        'Personal' => [
            'pikari_team_first_name'  => 'First Name',
            'pikari_team_last_name'   => 'Last Name',
            'pikari_team_designation' => 'Designation',
            'pikari_team_job_title'   => 'Job Title',
            'pikari_team_email'       => 'Email',
            'pikari_team_phone'       => 'Phone',
            'pikari_team_cell'        => 'Cell',
        ],
        'Company'  => [
            'pikari_team_company'    => 'Company',
            'pikari_team_department' => 'Department',
            'pikari_team_website'    => 'Website',
        ],
        'Address'  => [
            'pikari_team_address_street'  => 'Street',
            'pikari_team_address_city'    => 'City',
            'pikari_team_address_state'   => 'State/Province',
            'pikari_team_address_zip'     => 'ZIP/Postal Code',
            'pikari_team_address_country' => 'Country',
        ],
        'Social'   => [
            'pikari_team_linkedin' => 'LinkedIn URL',
            'pikari_team_twitter'  => 'Twitter/X URL',
        ],
    ];

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
        add_action( 'save_post_pikari_team_member', [ $this, 'save_meta' ] );
    }

    public function register_meta_box(): void {
        if ( function_exists( 'use_block_editor_for_post_type' )
            && use_block_editor_for_post_type( 'pikari_team_member' ) ) {
            return;
        }

        add_meta_box(
            'pikari-team-meta',
            __( 'Team Member Details', 'pikari-team' ),
            [ $this, 'render_meta_box' ],
            'pikari_team_member',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ): void {
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

        foreach ( self::FIELD_GROUPS as $group_label => $fields ) {
            echo '<fieldset class="pikari-team-fieldset"><legend><strong>';
            echo esc_html( $group_label );
            echo '</strong></legend>';

            foreach ( $fields as $key => $label ) {
                $value      = get_post_meta( $post->ID, $key, true );
                $input_type = self::INPUT_TYPES[ $key ] ?? 'text';
                $required   = Validation::is_required( $key );

                echo '<p>';
                echo '<label for="' . esc_attr( $key ) . '">';
                echo esc_html( $label );
                if ( $required ) {
                    echo ' <span class="pikari-team-required" aria-label="' . esc_attr__( 'required', 'pikari-team' ) . '">*</span>';
                }
                echo '</label><br>';
                echo '<input type="' . esc_attr( $input_type ) . '" id="' . esc_attr( $key ) . '" ';
                echo 'name="' . esc_attr( $key ) . '" ';
                echo 'value="' . esc_attr( $value ) . '" class="widefat">';
                echo '</p>';
            }

            echo '</fieldset>';
        }
    }

    public function save_meta( int $post_id ): void {
        if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
            return;
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );

        if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        foreach ( self::FIELD_GROUPS as $fields ) {
            foreach ( $fields as $key => $label ) {
                $value = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
                update_post_meta( $post_id, $key, $value );
            }
        }

        Validation::maybe_add_admin_notice( $post_id );
    }
}
```

- [ ] **Step 4: Run all Meta_Box tests**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/Meta_BoxTest.php -v`
Expected: All tests PASS

- [ ] **Step 5: Run full test suite**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && composer test`
Expected: All tests PASS

- [ ] **Step 6: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add includes/Meta_Box.php tests/php/Meta_BoxTest.php
git commit -m "feat: add HTML5 input types, required indicators, and validation to meta box"
```

---

## Task 4: Template Tags — Public Helper API

**Files:**

- Create: `includes/Template_Tags.php`
- Create: `tests/php/Template_TagsTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/php/Template_TagsTest.php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Template_Tags;
use Brain\Monkey\Functions;

class Template_TagsTest extends TestCase {

    private function mock_all_meta( int $post_id, array $overrides = [] ): void {
        $defaults = [
            'pikari_team_first_name'      => 'Jane',
            'pikari_team_last_name'       => 'Doe',
            'pikari_team_designation'     => 'CTO',
            'pikari_team_job_title'       => 'Chief Technology Officer',
            'pikari_team_email'           => 'jane@example.com',
            'pikari_team_phone'           => '(415) 555-1234',
            'pikari_team_cell'            => '(415) 555-5678',
            'pikari_team_company'         => 'Acme Inc.',
            'pikari_team_department'      => 'Engineering',
            'pikari_team_website'         => 'https://acme.com',
            'pikari_team_address_street'  => '123 Main St',
            'pikari_team_address_city'    => 'Toronto',
            'pikari_team_address_state'   => 'ON',
            'pikari_team_address_zip'     => 'M5V 1A1',
            'pikari_team_address_country' => 'Canada',
            'pikari_team_linkedin'        => 'https://linkedin.com/in/janedoe',
            'pikari_team_twitter'         => 'https://x.com/janedoe',
            'pikari_team_card_template'   => '',
        ];

        $meta = array_merge( $defaults, $overrides );

        Functions\expect( 'get_post_meta' )
            ->andReturnUsing(
                function ( $id, $key, $single ) use ( $post_id, $meta ) {
                    if ( $id === $post_id && $single && isset( $meta[ $key ] ) ) {
                        return $meta[ $key ];
                    }
                    return '';
                }
            );
    }

    public function test_get_member_data_returns_full_name(): void {
        $this->mock_all_meta( 1 );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();

        $data = Template_Tags::get_member_data( 1 );

        $this->assertSame( 'Jane Doe', $data['full_name'] );
    }

    public function test_get_member_data_returns_all_raw_meta(): void {
        $this->mock_all_meta( 1 );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();

        $data = Template_Tags::get_member_data( 1 );

        $this->assertSame( 'Jane', $data['first_name'] );
        $this->assertSame( 'Doe', $data['last_name'] );
        $this->assertSame( 'jane@example.com', $data['email'] );
        $this->assertSame( 'Chief Technology Officer', $data['job_title'] );
    }

    public function test_get_member_data_returns_grouped_address(): void {
        $this->mock_all_meta( 1 );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();

        $data = Template_Tags::get_member_data( 1 );

        $this->assertSame( '123 Main St', $data['address']['street'] );
        $this->assertSame( 'Toronto', $data['address']['city'] );
        $this->assertSame( 'ON', $data['address']['state'] );
        $this->assertSame( 'M5V 1A1', $data['address']['zip'] );
        $this->assertSame( 'Canada', $data['address']['country'] );
    }

    public function test_get_member_data_returns_grouped_social(): void {
        $this->mock_all_meta( 1 );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();

        $data = Template_Tags::get_member_data( 1 );

        $this->assertSame( 'https://linkedin.com/in/janedoe', $data['social']['linkedin'] );
        $this->assertSame( 'https://x.com/janedoe', $data['social']['twitter'] );
    }

    public function test_get_member_data_returns_has_flags(): void {
        $this->mock_all_meta( 1 );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( 'https://example.com/photo.jpg' );
        Functions\when( 'home_url' )->returnArg();

        $data = Template_Tags::get_member_data( 1 );

        $this->assertTrue( $data['has_photo'] );
        $this->assertTrue( $data['has_designation'] );
        $this->assertTrue( $data['has_job_title'] );
        $this->assertTrue( $data['has_phone'] );
        $this->assertTrue( $data['has_cell'] );
        $this->assertTrue( $data['has_company'] );
        $this->assertTrue( $data['has_department'] );
        $this->assertTrue( $data['has_website'] );
        $this->assertTrue( $data['has_address'] );
        $this->assertTrue( $data['has_social'] );
        $this->assertTrue( $data['has_linkedin'] );
        $this->assertTrue( $data['has_twitter'] );
    }

    public function test_get_member_data_has_flags_false_when_empty(): void {
        $this->mock_all_meta(
            1,
            [
                'pikari_team_designation'     => '',
                'pikari_team_phone'           => '',
                'pikari_team_cell'            => '',
                'pikari_team_company'         => '',
                'pikari_team_department'      => '',
                'pikari_team_website'         => '',
                'pikari_team_address_street'  => '',
                'pikari_team_address_city'    => '',
                'pikari_team_address_state'   => '',
                'pikari_team_address_zip'     => '',
                'pikari_team_address_country' => '',
                'pikari_team_linkedin'        => '',
                'pikari_team_twitter'         => '',
            ]
        );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();

        $data = Template_Tags::get_member_data( 1 );

        $this->assertFalse( $data['has_photo'] );
        $this->assertFalse( $data['has_designation'] );
        $this->assertFalse( $data['has_phone'] );
        $this->assertFalse( $data['has_cell'] );
        $this->assertFalse( $data['has_company'] );
        $this->assertFalse( $data['has_department'] );
        $this->assertFalse( $data['has_website'] );
        $this->assertFalse( $data['has_address'] );
        $this->assertFalse( $data['has_social'] );
        $this->assertFalse( $data['has_linkedin'] );
        $this->assertFalse( $data['has_twitter'] );
    }

    public function test_get_member_data_returns_computed_urls(): void {
        $this->mock_all_meta( 1 );
        Functions\when( 'get_option' )->justReturn( [ 'url_base' => 'card' ] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->alias(
            function ( $path ) {
                return 'https://example.com' . $path;
            }
        );

        $data = Template_Tags::get_member_data( 1 );

        $this->assertSame( 'https://example.com/card/jane-doe/', $data['card_url'] );
        $this->assertSame( 'https://example.com/card/jane-doe/download.vcf', $data['vcard_url'] );
    }

    public function test_get_formatted_address_returns_string(): void {
        $this->mock_all_meta( 1 );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();

        $address = Template_Tags::get_formatted_address( 1 );

        $this->assertStringContainsString( '123 Main St', $address );
        $this->assertStringContainsString( 'Toronto', $address );
        $this->assertStringContainsString( 'ON', $address );
        $this->assertStringContainsString( 'M5V 1A1', $address );
        $this->assertStringContainsString( 'Canada', $address );
    }

    public function test_get_formatted_address_returns_empty_when_no_address(): void {
        $this->mock_all_meta(
            1,
            [
                'pikari_team_address_street'  => '',
                'pikari_team_address_city'    => '',
                'pikari_team_address_state'   => '',
                'pikari_team_address_zip'     => '',
                'pikari_team_address_country' => '',
            ]
        );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();

        $this->assertSame( '', Template_Tags::get_formatted_address( 1 ) );
    }

    public function test_get_social_links_returns_array_with_platform_metadata(): void {
        $this->mock_all_meta( 1 );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();

        $links = Template_Tags::get_social_links( 1 );

        $this->assertCount( 2, $links );
        $this->assertSame( 'linkedin', $links[0]['platform'] );
        $this->assertSame( 'https://linkedin.com/in/janedoe', $links[0]['url'] );
        $this->assertSame( 'LinkedIn', $links[0]['label'] );
        $this->assertSame( 'twitter', $links[1]['platform'] );
    }

    public function test_get_social_links_omits_empty_platforms(): void {
        $this->mock_all_meta( 1, [ 'pikari_team_twitter' => '' ] );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();

        $links = Template_Tags::get_social_links( 1 );

        $this->assertCount( 1, $links );
        $this->assertSame( 'linkedin', $links[0]['platform'] );
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/Template_TagsTest.php -v`
Expected: FAIL — class not found

- [ ] **Step 3: Implement Template_Tags class**

```php
<?php
// includes/Template_Tags.php

/**
 * Public helper API for theme developers.
 *
 * Provides pikari_team_get_member_data() and formatted helper functions.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Template_Tags {

    /**
     * Social platform definitions.
     *
     * @var array<string, array{key: string, label: string}>
     */
    private const SOCIAL_PLATFORMS = [
        'linkedin' => [
            'key'   => 'pikari_team_linkedin',
            'label' => 'LinkedIn',
        ],
        'twitter'  => [
            'key'   => 'pikari_team_twitter',
            'label' => 'Twitter/X',
        ],
    ];

    /**
     * Get all member data as a structured array.
     *
     * Returns raw meta fields (without the pikari_team_ prefix), computed values,
     * grouped sub-arrays for address and social, and has_* boolean flags.
     *
     * @param int $post_id The team member post ID.
     * @return array<string, mixed> Structured member data.
     */
    public static function get_member_data( int $post_id ): array {
        $meta_keys = [
            'first_name'      => 'pikari_team_first_name',
            'last_name'       => 'pikari_team_last_name',
            'designation'     => 'pikari_team_designation',
            'job_title'       => 'pikari_team_job_title',
            'email'           => 'pikari_team_email',
            'phone'           => 'pikari_team_phone',
            'cell'            => 'pikari_team_cell',
            'company'         => 'pikari_team_company',
            'department'      => 'pikari_team_department',
            'website'         => 'pikari_team_website',
            'address_street'  => 'pikari_team_address_street',
            'address_city'    => 'pikari_team_address_city',
            'address_state'   => 'pikari_team_address_state',
            'address_zip'     => 'pikari_team_address_zip',
            'address_country' => 'pikari_team_address_country',
            'linkedin'        => 'pikari_team_linkedin',
            'twitter'         => 'pikari_team_twitter',
        ];

        // Raw meta fields.
        $data = [];
        foreach ( $meta_keys as $short_key => $meta_key ) {
            $data[ $short_key ] = (string) get_post_meta( $post_id, $meta_key, true );
        }

        // Computed values.
        $data['full_name'] = trim( $data['first_name'] . ' ' . $data['last_name'] );
        $data['photo_url'] = (string) get_the_post_thumbnail_url( $post_id, 'medium' );

        $settings = get_option( 'pikari_team_settings', [] );
        $base     = $settings['url_base'] ?? 'card';
        $slug     = get_post_field( 'post_name', $post_id );

        $data['card_url']  = home_url( '/' . $base . '/' . $slug . '/' );
        $data['vcard_url'] = home_url( '/' . $base . '/' . $slug . '/download.vcf' );

        // Grouped sub-arrays.
        $data['address'] = [
            'street'  => $data['address_street'],
            'city'    => $data['address_city'],
            'state'   => $data['address_state'],
            'zip'     => $data['address_zip'],
            'country' => $data['address_country'],
        ];

        $data['social'] = [
            'linkedin' => $data['linkedin'],
            'twitter'  => $data['twitter'],
        ];

        // has_* boolean flags.
        $data['has_photo']       = '' !== $data['photo_url'];
        $data['has_designation'] = '' !== $data['designation'];
        $data['has_job_title']   = '' !== $data['job_title'];
        $data['has_phone']       = '' !== $data['phone'];
        $data['has_cell']        = '' !== $data['cell'];
        $data['has_company']     = '' !== $data['company'];
        $data['has_department']  = '' !== $data['department'];
        $data['has_website']     = '' !== $data['website'];
        $data['has_linkedin']    = '' !== $data['linkedin'];
        $data['has_twitter']     = '' !== $data['twitter'];

        $data['has_address'] = '' !== $data['address_street']
            || '' !== $data['address_city']
            || '' !== $data['address_state']
            || '' !== $data['address_zip']
            || '' !== $data['address_country'];

        $data['has_social'] = '' !== $data['linkedin']
            || '' !== $data['twitter'];

        return $data;
    }

    /**
     * Get a formatted address string.
     *
     * Returns "Street, City, State ZIP, Country" with empty parts omitted.
     *
     * @param int $post_id The team member post ID.
     * @return string Formatted address or empty string.
     */
    public static function get_formatted_address( int $post_id ): string {
        $data = self::get_member_data( $post_id );

        if ( ! $data['has_address'] ) {
            return '';
        }

        $parts = [];

        if ( $data['address']['street'] ) {
            $parts[] = $data['address']['street'];
        }

        $city_state_zip = '';
        if ( $data['address']['city'] ) {
            $city_state_zip = $data['address']['city'];
        }
        if ( $data['address']['state'] ) {
            $city_state_zip .= $city_state_zip ? ', ' . $data['address']['state'] : $data['address']['state'];
        }
        if ( $data['address']['zip'] ) {
            $city_state_zip .= $city_state_zip ? ' ' . $data['address']['zip'] : $data['address']['zip'];
        }
        if ( $city_state_zip ) {
            $parts[] = $city_state_zip;
        }

        if ( $data['address']['country'] ) {
            $parts[] = $data['address']['country'];
        }

        return implode( ', ', $parts );
    }

    /**
     * Get social links with platform metadata.
     *
     * Returns an array of arrays with 'platform', 'url', and 'label' keys.
     * Empty platforms are omitted.
     *
     * @param int $post_id The team member post ID.
     * @return array<int, array{platform: string, url: string, label: string}>
     */
    public static function get_social_links( int $post_id ): array {
        $data  = self::get_member_data( $post_id );
        $links = [];

        foreach ( self::SOCIAL_PLATFORMS as $platform => $config ) {
            $short_key = $platform;
            if ( ! empty( $data[ $short_key ] ) ) {
                $links[] = [
                    'platform' => $platform,
                    'url'      => $data[ $short_key ],
                    'label'    => $config['label'],
                ];
            }
        }

        return $links;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/Template_TagsTest.php -v`
Expected: All tests PASS

- [ ] **Step 5: Create global template tag functions**

Add to the bottom of `includes/Template_Tags.php`, outside the class — these are the convenience functions for theme developers:

```php
// --- Global template tag functions ---
// These are defined in the Pikari\Team namespace but aliased globally
// via function file loaded in pikari-team.php.
```

Create `includes/template-tag-functions.php`:

```php
<?php
/**
 * Global template tag functions for theme developers.
 *
 * @package pikari-team
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get all member data as a structured array.
 *
 * @param int $post_id The team member post ID.
 * @return array<string, mixed> Structured member data.
 */
function pikari_team_get_member_data( int $post_id ): array {
    return \Pikari\Team\Template_Tags::get_member_data( $post_id );
}

/**
 * Get a formatted address string.
 *
 * @param int $post_id The team member post ID.
 * @return string Formatted address or empty string.
 */
function pikari_team_get_address( int $post_id ): string {
    return \Pikari\Team\Template_Tags::get_formatted_address( $post_id );
}

/**
 * Echo a formatted address string.
 *
 * @param int $post_id The team member post ID.
 */
function pikari_team_the_address( int $post_id ): void {
    echo esc_html( \Pikari\Team\Template_Tags::get_formatted_address( $post_id ) );
}

/**
 * Get social links with platform metadata.
 *
 * @param int $post_id The team member post ID.
 * @return array<int, array{platform: string, url: string, label: string}>
 */
function pikari_team_get_social_links( int $post_id ): array {
    return \Pikari\Team\Template_Tags::get_social_links( $post_id );
}

/**
 * Echo the QR code SVG for a team member.
 *
 * @param int $post_id The team member post ID.
 */
function pikari_team_the_qr_code( int $post_id ): void {
    if ( ! class_exists( '\Pikari\Team\QR_Code' ) ) {
        return;
    }

    $qr = new \Pikari\Team\QR_Code();
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG generated by plugin.
    echo $qr->generate_qr_svg( $post_id );
}
```

- [ ] **Step 6: Load the template tag functions in plugin entry point**

Add to `pikari-team.php` inside `pikari_team_init()`, after the autoloader:

```php
require_once PIKARI_TEAM_DIR . 'includes/template-tag-functions.php';
```

- [ ] **Step 7: Run full test suite**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && composer test`
Expected: All tests PASS

- [ ] **Step 8: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add includes/Template_Tags.php includes/template-tag-functions.php tests/php/Template_TagsTest.php pikari-team.php
git commit -m "feat: add public template tags API with member data, address, social helpers"
```

---

## Task 5: Card Renderer — Hookable Section System

**Files:**

- Create: `includes/Card_Renderer.php`
- Create: `tests/php/Card_RendererTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/php/Card_RendererTest.php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Card_Renderer;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

class Card_RendererTest extends TestCase {

    private function mock_member_data(): void {
        Functions\expect( 'get_post_meta' )
            ->andReturnUsing(
                function ( $id, $key, $single ) {
                    $meta = [
                        'pikari_team_first_name' => 'Jane',
                        'pikari_team_last_name'  => 'Doe',
                        'pikari_team_email'      => 'jane@example.com',
                        'pikari_team_job_title'  => 'CTO',
                        'pikari_team_company'    => 'Acme',
                    ];
                    return $meta[ $key ] ?? '';
                }
            );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();
    }

    public function test_render_fires_all_section_hooks_for_single_context(): void {
        $this->mock_member_data();

        $hooks_fired = [];
        Functions\when( 'do_action' )->alias(
            function ( $hook ) use ( &$hooks_fired ) {
                if ( str_starts_with( $hook, 'pikari_team_card_' ) ) {
                    $hooks_fired[] = $hook;
                }
            }
        );

        Card_Renderer::render( 1, 'single' );

        $this->assertContains( 'pikari_team_card_header', $hooks_fired );
        $this->assertContains( 'pikari_team_card_contact', $hooks_fired );
        $this->assertContains( 'pikari_team_card_address', $hooks_fired );
        $this->assertContains( 'pikari_team_card_social', $hooks_fired );
        $this->assertContains( 'pikari_team_card_qr', $hooks_fired );
        $this->assertContains( 'pikari_team_card_footer', $hooks_fired );
    }

    public function test_render_fires_limited_hooks_for_embed_context(): void {
        $this->mock_member_data();

        $hooks_fired = [];
        Functions\when( 'do_action' )->alias(
            function ( $hook ) use ( &$hooks_fired ) {
                if ( str_starts_with( $hook, 'pikari_team_card_' ) ) {
                    $hooks_fired[] = $hook;
                }
            }
        );

        Card_Renderer::render( 1, 'embed' );

        $this->assertContains( 'pikari_team_card_header', $hooks_fired );
        $this->assertContains( 'pikari_team_card_contact', $hooks_fired );
        $this->assertNotContains( 'pikari_team_card_address', $hooks_fired );
        $this->assertNotContains( 'pikari_team_card_social', $hooks_fired );
        $this->assertNotContains( 'pikari_team_card_qr', $hooks_fired );
        $this->assertNotContains( 'pikari_team_card_footer', $hooks_fired );
    }

    public function test_render_passes_data_and_context_to_hooks(): void {
        $this->mock_member_data();

        $captured_args = [];
        Functions\when( 'do_action' )->alias(
            function ( $hook, ...$args ) use ( &$captured_args ) {
                if ( 'pikari_team_card_header' === $hook ) {
                    $captured_args = $args;
                }
            }
        );

        Card_Renderer::render( 1, 'single' );

        $this->assertCount( 2, $captured_args );
        $this->assertIsArray( $captured_args[0] ); // $data
        $this->assertSame( 'single', $captured_args[1] ); // $context
        $this->assertSame( 'Jane Doe', $captured_args[0]['full_name'] );
    }

    public function test_register_defaults_hooks_into_all_sections(): void {
        Actions\expectAdded( 'pikari_team_card_header' )->once();
        Actions\expectAdded( 'pikari_team_card_contact' )->once();
        Actions\expectAdded( 'pikari_team_card_address' )->once();
        Actions\expectAdded( 'pikari_team_card_social' )->once();
        Actions\expectAdded( 'pikari_team_card_qr' )->once();
        Actions\expectAdded( 'pikari_team_card_footer' )->once();

        Card_Renderer::register_defaults();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/Card_RendererTest.php -v`
Expected: FAIL — class not found

- [ ] **Step 3: Implement Card_Renderer class**

```php
<?php
// includes/Card_Renderer.php

/**
 * Hookable card rendering system.
 *
 * Fires action hooks for each card section, allowing theme developers
 * to replace individual sections without overriding entire templates.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Card_Renderer {

    /**
     * Sections rendered in 'single' context.
     *
     * @var string[]
     */
    private const SINGLE_SECTIONS = [
        'header',
        'contact',
        'address',
        'social',
        'qr',
        'footer',
    ];

    /**
     * Sections rendered in 'embed' context.
     *
     * @var string[]
     */
    private const EMBED_SECTIONS = [
        'header',
        'contact',
    ];

    /**
     * Render a team member card by firing section hooks.
     *
     * @param int    $post_id The team member post ID.
     * @param string $context Rendering context: 'single', 'embed', or 'shortcode'.
     * @return string Rendered HTML.
     */
    public static function render( int $post_id, string $context = 'single' ): string {
        $data = Template_Tags::get_member_data( $post_id );

        $sections = 'embed' === $context || 'shortcode' === $context
            ? self::EMBED_SECTIONS
            : self::SINGLE_SECTIONS;

        ob_start();

        echo '<div class="pikari-team-card">';

        foreach ( $sections as $section ) {
            /**
             * Fires to render a card section.
             *
             * @param array  $data    Structured member data from Template_Tags::get_member_data().
             * @param string $context Rendering context: 'single', 'embed', or 'shortcode'.
             */
            do_action( 'pikari_team_card_' . $section, $data, $context );
        }

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Register default hook callbacks for all card sections.
     *
     * Called during plugin init. Theme developers can unhook these
     * and provide their own implementations.
     */
    public static function register_defaults(): void {
        add_action( 'pikari_team_card_header', [ self::class, 'render_header' ], 10, 2 );
        add_action( 'pikari_team_card_contact', [ self::class, 'render_contact' ], 10, 2 );
        add_action( 'pikari_team_card_address', [ self::class, 'render_address' ], 10, 2 );
        add_action( 'pikari_team_card_social', [ self::class, 'render_social' ], 10, 2 );
        add_action( 'pikari_team_card_qr', [ self::class, 'render_qr' ], 10, 2 );
        add_action( 'pikari_team_card_footer', [ self::class, 'render_footer' ], 10, 2 );
    }

    /**
     * Default header section: photo, name, designation, job title, company.
     *
     * @param array  $data    Member data.
     * @param string $context Rendering context.
     */
    public static function render_header( array $data, string $context ): void {
        echo '<div class="pikari-team-card__header">';

        if ( $data['has_photo'] ) {
            echo '<img class="pikari-team-card__headshot" src="' . esc_url( $data['photo_url'] ) . '" alt="' . esc_attr( $data['full_name'] ) . '" width="120" height="120">';
        }

        echo '<h2 class="pikari-team-card__name">' . esc_html( $data['full_name'] ) . '</h2>';

        if ( $data['has_designation'] ) {
            echo '<p class="pikari-team-card__designation">' . esc_html( $data['designation'] ) . '</p>';
        }

        if ( $data['has_job_title'] ) {
            echo '<p class="pikari-team-card__title">' . esc_html( $data['job_title'] ) . '</p>';
        }

        if ( $data['has_company'] ) {
            echo '<p class="pikari-team-card__company">' . esc_html( $data['company'] ) . '</p>';
        }

        echo '</div>';
    }

    /**
     * Default contact section: phone, cell, email, website.
     *
     * @param array  $data    Member data.
     * @param string $context Rendering context.
     */
    public static function render_contact( array $data, string $context ): void {
        if ( ! $data['has_phone'] && ! $data['has_cell'] && ! $data['email'] && ! $data['has_website'] ) {
            return;
        }

        echo '<div class="pikari-team-card__contact">';

        if ( $data['has_phone'] ) {
            echo '<a href="tel:' . esc_attr( $data['phone'] ) . '" class="pikari-team-card__link">';
            echo '<span class="pikari-team-card__label">' . esc_html__( 'Phone', 'pikari-team' ) . '</span>';
            echo '<span class="pikari-team-card__value">' . esc_html( $data['phone'] ) . '</span>';
            echo '</a>';
        }

        if ( $data['has_cell'] ) {
            echo '<a href="tel:' . esc_attr( $data['cell'] ) . '" class="pikari-team-card__link">';
            echo '<span class="pikari-team-card__label">' . esc_html__( 'Cell', 'pikari-team' ) . '</span>';
            echo '<span class="pikari-team-card__value">' . esc_html( $data['cell'] ) . '</span>';
            echo '</a>';
        }

        if ( $data['email'] ) {
            echo '<a href="mailto:' . esc_attr( $data['email'] ) . '" class="pikari-team-card__link">';
            echo '<span class="pikari-team-card__label">' . esc_html__( 'Email', 'pikari-team' ) . '</span>';
            echo '<span class="pikari-team-card__value">' . esc_html( $data['email'] ) . '</span>';
            echo '</a>';
        }

        if ( $data['has_website'] ) {
            echo '<a href="' . esc_url( $data['website'] ) . '" class="pikari-team-card__link" target="_blank" rel="noopener noreferrer">';
            echo '<span class="pikari-team-card__label">' . esc_html__( 'Website', 'pikari-team' ) . '</span>';
            echo '<span class="pikari-team-card__value">' . esc_html( $data['website'] ) . '</span>';
            echo '</a>';
        }

        echo '</div>';
    }

    /**
     * Default address section.
     *
     * @param array  $data    Member data.
     * @param string $context Rendering context.
     */
    public static function render_address( array $data, string $context ): void {
        if ( ! $data['has_address'] ) {
            return;
        }

        $address = \Pikari\Team\Template_Tags::get_formatted_address_from_data( $data );

        echo '<div class="pikari-team-card__address">';
        echo '<span class="pikari-team-card__label">' . esc_html__( 'Address', 'pikari-team' ) . '</span>';
        echo '<span class="pikari-team-card__value">' . esc_html( $address ) . '</span>';
        echo '</div>';
    }

    /**
     * Default social section.
     *
     * @param array  $data    Member data.
     * @param string $context Rendering context.
     */
    public static function render_social( array $data, string $context ): void {
        if ( ! $data['has_social'] ) {
            return;
        }

        echo '<div class="pikari-team-card__social">';

        if ( $data['has_linkedin'] ) {
            echo '<a href="' . esc_url( $data['linkedin'] ) . '" class="pikari-team-card__social-link" target="_blank" rel="noopener noreferrer">';
            echo esc_html__( 'LinkedIn', 'pikari-team' );
            echo '</a>';
        }

        if ( $data['has_twitter'] ) {
            echo '<a href="' . esc_url( $data['twitter'] ) . '" class="pikari-team-card__social-link" target="_blank" rel="noopener noreferrer">';
            echo esc_html__( 'Twitter/X', 'pikari-team' );
            echo '</a>';
        }

        echo '</div>';
    }

    /**
     * Default QR code section.
     *
     * @param array  $data    Member data.
     * @param string $context Rendering context.
     */
    public static function render_qr( array $data, string $context ): void {
        if ( ! class_exists( '\Pikari\Team\QR_Code' ) ) {
            return;
        }

        // The QR code needs post_id — derive from vcard_url slug.
        // Instead, we store post_id in the data array.
        if ( empty( $data['post_id'] ) ) {
            return;
        }

        $qr  = new \Pikari\Team\QR_Code();
        $svg = $qr->generate_qr_svg( $data['post_id'] );

        if ( ! $svg ) {
            return;
        }

        echo '<div class="pikari-team-card__qr">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG generated by plugin.
        echo $svg;
        echo '</div>';
    }

    /**
     * Default footer section with save contact button.
     *
     * @param array  $data    Member data.
     * @param string $context Rendering context.
     */
    public static function render_footer( array $data, string $context ): void {
        if ( empty( $data['vcard_url'] ) ) {
            return;
        }

        echo '<div class="pikari-team-card__actions">';
        echo '<a href="' . esc_url( $data['vcard_url'] ) . '" class="pikari-team-card__save-btn">';
        echo esc_html__( 'Save Contact', 'pikari-team' );
        echo '</a>';
        echo '</div>';
    }
}
```

**Important:** Add `post_id` to the `$data` array in `Template_Tags::get_member_data()`:

```php
// Add after the $data array is built, before the return:
$data['post_id'] = $post_id;
```

Also add `get_formatted_address_from_data()` to `Template_Tags`:

```php
/**
 * Get formatted address from an existing data array.
 *
 * Avoids re-fetching meta when the data is already available.
 *
 * @param array $data Member data array from get_member_data().
 * @return string Formatted address.
 */
public static function get_formatted_address_from_data( array $data ): string {
    if ( ! $data['has_address'] ) {
        return '';
    }

    $parts = [];

    if ( $data['address']['street'] ) {
        $parts[] = $data['address']['street'];
    }

    $city_state_zip = '';
    if ( $data['address']['city'] ) {
        $city_state_zip = $data['address']['city'];
    }
    if ( $data['address']['state'] ) {
        $city_state_zip .= $city_state_zip ? ', ' . $data['address']['state'] : $data['address']['state'];
    }
    if ( $data['address']['zip'] ) {
        $city_state_zip .= $city_state_zip ? ' ' . $data['address']['zip'] : $data['address']['zip'];
    }
    if ( $city_state_zip ) {
        $parts[] = $city_state_zip;
    }

    if ( $data['address']['country'] ) {
        $parts[] = $data['address']['country'];
    }

    return implode( ', ', $parts );
}
```

Refactor `get_formatted_address()` to use `get_formatted_address_from_data()`:

```php
public static function get_formatted_address( int $post_id ): string {
    $data = self::get_member_data( $post_id );
    return self::get_formatted_address_from_data( $data );
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/Card_RendererTest.php -v`
Expected: All tests PASS

- [ ] **Step 5: Run full test suite**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && composer test`
Expected: All tests PASS

- [ ] **Step 6: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add includes/Card_Renderer.php includes/Template_Tags.php tests/php/Card_RendererTest.php
git commit -m "feat: add hookable Card_Renderer with section-based action hooks"
```

---

## Task 6: Single Post Template

**Files:**

- Create: `templates/single-pikari_team_member.php`
- Modify: `includes/Template.php`

- [ ] **Step 1: Create the single post template**

```php
<?php
/**
 * Single team member template.
 *
 * Renders within the active theme using get_header()/get_footer().
 * Uses the hookable Card_Renderer for the card content.
 *
 * Theme developers can override this by creating
 * single-pikari_team_member.php in their theme.
 *
 * @package pikari-team
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="main" class="pikari-team-single">
    <?php
    while ( have_posts() ) :
        the_post();

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Card_Renderer callbacks.
        echo \Pikari\Team\Card_Renderer::render( get_the_ID(), 'single' );

        // Display post content (biography) if present.
        $content = get_the_content();
        if ( $content ) :
            ?>
            <div class="pikari-team-single__bio">
                <?php the_content(); ?>
            </div>
            <?php
        endif;

    endwhile;
    ?>
</main>

<?php
get_footer();
```

- [ ] **Step 2: Add template_include filter for single posts**

Add a method to `Template.php` that loads the plugin's single template for `pikari_team_member` posts when the theme doesn't provide one. Add in the constructor:

```php
add_filter( 'single_template', [ $this, 'load_single_template' ] );
```

Add the method:

```php
/**
 * Load the plugin's single template if the theme doesn't provide one.
 *
 * @param string $template The path to the current template.
 * @return string The template path.
 */
public function load_single_template( string $template ): string {
    if ( 'pikari_team_member' !== get_post_type() ) {
        return $template;
    }

    // Theme already provides a template — use it.
    $theme_template = locate_template( 'single-pikari_team_member.php' );
    if ( $theme_template ) {
        return $theme_template;
    }

    $plugin_template = PIKARI_TEAM_DIR . 'templates/single-pikari_team_member.php';
    if ( file_exists( $plugin_template ) ) {
        return $plugin_template;
    }

    return $template;
}
```

- [ ] **Step 3: Write test for single template loading**

Add to `tests/php/TemplateTest.php`:

```php
public function test_single_template_loads_plugin_template_for_team_member(): void {
    Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );
    Functions\when( 'locate_template' )->justReturn( '' );

    Functions\when( 'get_option' )->justReturn( [] );
    Functions\when( 'get_query_var' )->justReturn( '' );

    $template = new Template();
    $result   = $template->load_single_template( '/default/template.php' );

    $this->assertStringContainsString( 'templates/single-pikari_team_member.php', $result );
}

public function test_single_template_defers_to_theme_template(): void {
    Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );
    Functions\when( 'locate_template' )->justReturn( '/theme/single-pikari_team_member.php' );

    Functions\when( 'get_option' )->justReturn( [] );
    Functions\when( 'get_query_var' )->justReturn( '' );

    $template = new Template();
    $result   = $template->load_single_template( '/default/template.php' );

    $this->assertSame( '/theme/single-pikari_team_member.php', $result );
}

public function test_single_template_ignores_other_post_types(): void {
    Functions\when( 'get_post_type' )->justReturn( 'post' );

    Functions\when( 'get_option' )->justReturn( [] );
    Functions\when( 'get_query_var' )->justReturn( '' );

    $template = new Template();
    $result   = $template->load_single_template( '/default/template.php' );

    $this->assertSame( '/default/template.php', $result );
}
```

- [ ] **Step 4: Run tests**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/TemplateTest.php -v`
Expected: All tests PASS

- [ ] **Step 5: Run full test suite**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && composer test`
Expected: All tests PASS

- [ ] **Step 6: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add templates/single-pikari_team_member.php includes/Template.php tests/php/TemplateTest.php
git commit -m "feat: add single post template with hookable card rendering"
```

---

## Task 7: Refactor Shortcode to Use Card_Renderer

**Files:**

- Modify: `includes/Shortcode.php`
- Modify: `tests/php/ShortcodeTest.php`

- [ ] **Step 1: Update Shortcode to use Card_Renderer**

Replace `Shortcode::render_card()` to use `Card_Renderer::render()`:

```php
public static function render_card( int $post_id ): string {
    if ( ! $post_id ) {
        return '';
    }

    return Card_Renderer::render( $post_id, 'shortcode' );
}
```

- [ ] **Step 2: Update shortcode tests**

Update `tests/php/ShortcodeTest.php` to verify Card_Renderer integration. The existing tests should continue to pass — `render_card` still returns a string. Add a test confirming hooks fire:

```php
public function test_render_card_fires_card_hooks(): void {
    Functions\when( 'get_post_meta' )->justReturn( '' );
    Functions\when( 'get_option' )->justReturn( [] );
    Functions\when( 'get_post_field' )->justReturn( 'test' );
    Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
    Functions\when( 'home_url' )->returnArg();

    $hooks_fired = [];
    Functions\when( 'do_action' )->alias(
        function ( $hook ) use ( &$hooks_fired ) {
            if ( str_starts_with( $hook, 'pikari_team_card_' ) ) {
                $hooks_fired[] = $hook;
            }
        }
    );

    Shortcode::render_card( 1 );

    // Embed context: only header + contact.
    $this->assertContains( 'pikari_team_card_header', $hooks_fired );
    $this->assertContains( 'pikari_team_card_contact', $hooks_fired );
}
```

- [ ] **Step 3: Delete `templates/card-embed.php`**

This file is no longer needed — the Card_Renderer handles embed output.

```bash
git rm templates/card-embed.php
```

- [ ] **Step 4: Run tests**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && composer test`
Expected: All tests PASS

- [ ] **Step 5: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add includes/Shortcode.php tests/php/ShortcodeTest.php
git rm templates/card-embed.php
git commit -m "refactor: shortcode uses Card_Renderer instead of raw template"
```

---

## Task 8: Refactor PWA Standalone Template to Use Hooks

**Files:**

- Modify: `templates/card-standalone.php`

- [ ] **Step 1: Refactor the template body to use hooks**

Replace the inline card sections in `card-standalone.php` with action hook calls. Keep the HTML shell (doctype, head, body wrapper) as-is. Add the `pikari_team_card_head` hook and `pikari_team_card_css` filter.

Key changes to the template:

In the `<head>` section, after the existing meta/link tags:

```php
<?php
/**
 * Fires inside the <head> of the standalone card template.
 *
 * Allows injecting custom meta tags, links, or styles.
 *
 * @param array $data Structured member data.
 */
do_action( 'pikari_team_card_head', $data );
?>
```

In the `<style>` block, after the card.css include:

```php
<?php
/**
 * Filters additional CSS to inline in the standalone card template.
 *
 * @param string $css  Additional CSS string (empty by default).
 * @param array  $data Structured member data.
 */
$custom_css = apply_filters( 'pikari_team_card_css', '', $data );
if ( $custom_css ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS from filter.
    echo $custom_css;
}
?>
```

Replace the card body sections with:

```php
<div class="pikari-team-card">
    <?php
    $sections = [ 'header', 'contact', 'address', 'social', 'qr', 'footer' ];
    foreach ( $sections as $section ) {
        /**
         * Fires to render a card section in the standalone PWA template.
         *
         * @param array  $data    Structured member data.
         * @param string $context Always 'standalone' for this template.
         */
        do_action( 'pikari_team_card_' . $section, $data, 'standalone' );
    }
    ?>
</div>
```

Build `$data` at the top of the template using `Template_Tags::get_member_data()` instead of manual `get_post_meta()` calls:

```php
$post_id = get_the_ID();
$data    = \Pikari\Team\Template_Tags::get_member_data( $post_id );

$settings    = get_option( 'pikari_team_settings', [] );
$brand_color = $settings['brand_color'] ?? '#0073aa';
$url_base    = $settings['url_base'] ?? 'card';
$slug        = get_post_field( 'post_name', $post_id );
```

- [ ] **Step 2: Verify existing PWA tests still pass**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/PWATest.php -v`
Expected: All tests PASS (PWA tests cover manifest/SW generation, not the template)

- [ ] **Step 3: Run full test suite**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && composer test`
Expected: All tests PASS

- [ ] **Step 4: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add templates/card-standalone.php
git commit -m "refactor: standalone card template uses hookable sections and CSS filter"
```

---

## Task 9: Wire Up Plugin Entry Point

**Files:**

- Modify: `pikari-team.php`

- [ ] **Step 1: Update plugin initialization**

Add to `pikari_team_init()`:

```php
new \Pikari\Team\Validation();  // Not needed — all static methods, no hooks to register.

// Register default card rendering hooks.
\Pikari\Team\Card_Renderer::register_defaults();
```

The `require_once` for `template-tag-functions.php` was already added in Task 4.

- [ ] **Step 2: Run full test suite**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && composer test`
Expected: All tests PASS

- [ ] **Step 3: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add pikari-team.php
git commit -m "feat: wire up Card_Renderer defaults and template tag functions"
```

---

## Task 10: Developer Documentation

**Files:**

- Create: `docs/hooks.md`

- [ ] **Step 1: Write the hooks reference**

````markdown
# Pikari Team — Hooks & Filters Reference

## Action Hooks

### Card Section Hooks

These hooks fire during card rendering. Each receives two parameters:

- `$data` (array) — Structured member data from `pikari_team_get_member_data()`.
- `$context` (string) — Rendering context: `'single'`, `'embed'`, `'shortcode'`, or `'standalone'`.

| Hook                       | Description                                  | Default Contexts   |
| -------------------------- | -------------------------------------------- | ------------------ |
| `pikari_team_card_header`  | Photo, name, designation, job title, company | all                |
| `pikari_team_card_contact` | Phone, cell, email, website links            | all                |
| `pikari_team_card_address` | Formatted street address                     | single, standalone |
| `pikari_team_card_social`  | LinkedIn, Twitter/X links                    | single, standalone |
| `pikari_team_card_qr`      | QR code SVG (vCard data)                     | single, standalone |
| `pikari_team_card_footer`  | Save contact button                          | single, standalone |

#### Example: Replace the header section

```php
// Remove the default header.
remove_action( 'pikari_team_card_header', [ \Pikari\Team\Card_Renderer::class, 'render_header' ], 10 );

// Add your own.
add_action( 'pikari_team_card_header', function ( array $data, string $context ) {
    echo '<div class="my-custom-header">';
    echo '<h1>' . esc_html( $data['full_name'] ) . '</h1>';
    if ( $data['has_job_title'] ) {
        echo '<p>' . esc_html( $data['job_title'] ) . '</p>';
    }
    echo '</div>';
}, 10, 2 );
```
````

#### Example: Add QR code to embed context

```php
add_action( 'pikari_team_card_qr', function ( array $data, string $context ) {
    if ( 'embed' !== $context ) {
        return; // Let the default handle non-embed contexts.
    }
    pikari_team_the_qr_code( $data['post_id'] );
}, 10, 2 );
```

### PWA Card Hooks

#### `pikari_team_card_head`

Fires inside the `<head>` of the standalone PWA card template.

**Parameters:**

- `$data` (array) — Structured member data.

```php
add_action( 'pikari_team_card_head', function ( array $data ) {
    echo '<meta property="og:title" content="' . esc_attr( $data['full_name'] ) . '">';
} );
```

### Existing Action Hooks

| Hook                        | Description                        | Parameters      |
| --------------------------- | ---------------------------------- | --------------- |
| `pikari_team_card_download` | Fires to serve vCard file download | `WP_Post $post` |
| `pikari_team_card_manifest` | Fires to serve PWA manifest JSON   | `WP_Post $post` |
| `pikari_team_card_sw`       | Fires to serve service worker JS   | `WP_Post $post` |

---

## Filters

### `pikari_team_post_type_args`

Filters the post type registration arguments before `register_post_type()` is called.

**Parameters:**

- `$args` (array) — Post type arguments.

**Return:** array

```php
add_filter( 'pikari_team_post_type_args', function ( array $args ) {
    // Add taxonomy support.
    $args['taxonomies'] = [ 'department' ];
    return $args;
} );
```

### `pikari_team_card_css`

Filters additional CSS to inline in the standalone PWA card template.

**Parameters:**

- `$css` (string) — Additional CSS string (empty by default).
- `$data` (array) — Structured member data.

**Return:** string

```php
add_filter( 'pikari_team_card_css', function ( string $css, array $data ) {
    return $css . '.pikari-team-card__name { font-family: "Comic Sans MS"; }';
}, 10, 2 );
```

---

## Template Tag Functions

### `pikari_team_get_member_data( int $post_id ): array`

Returns a structured array of all member data.

**Array keys:**

| Key               | Type   | Description                                 |
| ----------------- | ------ | ------------------------------------------- |
| `post_id`         | int    | The post ID                                 |
| `first_name`      | string | First name                                  |
| `last_name`       | string | Last name                                   |
| `full_name`       | string | First + last name                           |
| `designation`     | string | Designation/suffix                          |
| `job_title`       | string | Job title                                   |
| `email`           | string | Email address                               |
| `phone`           | string | Work phone                                  |
| `cell`            | string | Cell phone                                  |
| `company`         | string | Company name                                |
| `department`      | string | Department                                  |
| `website`         | string | Website URL                                 |
| `photo_url`       | string | Featured image URL                          |
| `card_url`        | string | Standalone card URL                         |
| `vcard_url`       | string | vCard download URL                          |
| `address`         | array  | `street`, `city`, `state`, `zip`, `country` |
| `social`          | array  | `linkedin`, `twitter`                       |
| `has_photo`       | bool   | Has featured image                          |
| `has_designation` | bool   | Has designation                             |
| `has_job_title`   | bool   | Has job title                               |
| `has_phone`       | bool   | Has phone                                   |
| `has_cell`        | bool   | Has cell                                    |
| `has_company`     | bool   | Has company                                 |
| `has_department`  | bool   | Has department                              |
| `has_website`     | bool   | Has website                                 |
| `has_address`     | bool   | Has any address field                       |
| `has_social`      | bool   | Has any social link                         |
| `has_linkedin`    | bool   | Has LinkedIn URL                            |
| `has_twitter`     | bool   | Has Twitter/X URL                           |

### `pikari_team_get_address( int $post_id ): string`

Returns formatted address string (e.g., "123 Main St, Toronto, ON M5V 1A1, Canada").

### `pikari_team_the_address( int $post_id ): void`

Echoes the formatted address (escaped).

### `pikari_team_get_social_links( int $post_id ): array`

Returns array of social link entries:

```php
[
    [
        'platform' => 'linkedin',
        'url'      => 'https://linkedin.com/in/janedoe',
        'label'    => 'LinkedIn',
    ],
    // ...
]
```

### `pikari_team_the_qr_code( int $post_id ): void`

Echoes the QR code SVG for the member's vCard data.

---

## Template Overrides

### Single Post Template

Place `single-pikari_team_member.php` in your theme to override the plugin's single post template entirely.

### Standalone PWA Card

Place `pikari-team/card-standalone.php` in your theme to override the standalone card template entirely.

````

- [ ] **Step 2: Verify PHPDoc is present on all hooks in source files**

Review each `do_action()` and `apply_filters()` call in the source files to ensure PHPDoc comments are present. These were included in the implementation steps above.

- [ ] **Step 3: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add docs/hooks.md
git commit -m "docs: add hooks, filters, and template tags reference"
````

---

## Task 11: Final Integration Verification

- [ ] **Step 1: Run full test suite**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && composer test`
Expected: All tests PASS

- [ ] **Step 2: Run linter**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && npm run lint:php`
Expected: No errors

- [ ] **Step 3: Verify all new files load correctly**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && php -l includes/Validation.php && php -l includes/Template_Tags.php && php -l includes/Card_Renderer.php && php -l includes/template-tag-functions.php && php -l templates/single-pikari_team_member.php`
Expected: No syntax errors

- [ ] **Step 4: Final commit if any lint fixes were needed**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add -A
git commit -m "fix: lint corrections from final verification"
```
