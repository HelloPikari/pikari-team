<?php
/**
 * Admin settings page.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Settings {

    public const OPTION_KEY = 'pikari_team_settings';

    /**
     * Maps meta field keys (without prefix) to settings keys for fallback defaults.
     */
    public const FIELD_DEFAULTS_MAP = [
        'company'         => 'company_name',
        'email'           => 'default_email',
        'phone'           => 'default_phone',
        'website'         => 'default_website',
        'address_street'  => 'default_street',
        'address_city'    => 'default_city',
        'address_state'   => 'default_state',
        'address_zip'     => 'default_zip',
        'address_country' => 'default_country',
    ];

    private const DEFAULTS = [
        'admin_label'      => 'Team Members',
        'company_name'     => '',
        'company_logo'     => 0,
        'brand_color'      => '#0073aa',
        'post_type_slug'   => 'team',
        'url_base'         => 'card',
        'default_email'    => '',
        'default_phone'    => '',
        'default_website'  => '',
        'default_street'   => '',
        'default_city'     => '',
        'default_state'    => '',
        'default_zip'      => '',
        'default_country'  => '',
        'field_visibility' => [],
    ];

    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_init', [ $this, 'maybe_flush_rewrite_rules' ] );
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'update_option_' . self::OPTION_KEY, [ $this, 'schedule_rewrite_flush' ], 10, 2 );
    }

    /**
     * Flag that rewrite rules need flushing on the next page load.
     *
     * Flushing during update_option is too early — the CPT is still registered
     * with the old slug. The transient defers the flush to the redirected request.
     *
     * @param mixed $old_value Previous option value.
     * @param mixed $new_value Updated option value.
     */
    public function schedule_rewrite_flush( $old_value, $new_value ): void {
        $slug_changed    = ( $old_value['post_type_slug'] ?? '' ) !== ( $new_value['post_type_slug'] ?? '' );
        $urlbase_changed = ( $old_value['url_base'] ?? '' ) !== ( $new_value['url_base'] ?? '' );

        if ( $slug_changed || $urlbase_changed ) {
            set_transient( 'pikari_team_flush_rewrite', 1, 30 );
        }
    }

    /**
     * Flush rewrite rules if flagged by a previous settings save.
     */
    public function maybe_flush_rewrite_rules(): void {
        if ( get_transient( 'pikari_team_flush_rewrite' ) ) {
            delete_transient( 'pikari_team_flush_rewrite' );
            flush_rewrite_rules();
        }
    }

    public function get_settings(): array {
        $options = get_option( self::OPTION_KEY, [] );

        return wp_parse_args( $options, self::DEFAULTS );
    }

    public function get_company_name(): string {
        $settings = $this->get_settings();

        if ( ! empty( $settings['company_name'] ) ) {
            return $settings['company_name'];
        }

        return get_bloginfo( 'name' );
    }

    public function register_settings(): void {
        register_setting(
            'pikari_team_settings_group',
            self::OPTION_KEY,
            [
                'type'              => 'array',
                'sanitize_callback' => [ $this, 'sanitize_settings' ],
                'default'           => self::DEFAULTS,
            ]
        );
    }

    public function sanitize_settings( array $input ): array {
        $sanitized = [];

        $sanitized['admin_label']  = sanitize_text_field( $input['admin_label'] ?? '' );
        $sanitized['company_name'] = sanitize_text_field( $input['company_name'] ?? '' );
        $sanitized['company_logo'] = absint( $input['company_logo'] ?? 0 );
        $sanitized['brand_color']  = sanitize_hex_color( $input['brand_color'] ?? '#0073aa' );
        $sanitized['post_type_slug'] = sanitize_title( $input['post_type_slug'] ?? 'team' );
        $sanitized['url_base']       = sanitize_title( $input['url_base'] ?? 'card' );
        $sanitized['default_email']   = sanitize_email( $input['default_email'] ?? '' );
        $sanitized['default_phone']   = sanitize_text_field( $input['default_phone'] ?? '' );
        $sanitized['default_website'] = esc_url_raw( $input['default_website'] ?? '' );
        $sanitized['default_street']  = sanitize_text_field( $input['default_street'] ?? '' );
        $sanitized['default_city']    = sanitize_text_field( $input['default_city'] ?? '' );
        $sanitized['default_state']   = sanitize_text_field( $input['default_state'] ?? '' );
        $sanitized['default_zip']     = sanitize_text_field( $input['default_zip'] ?? '' );
        $sanitized['default_country'] = sanitize_text_field( $input['default_country'] ?? '' );

        $sanitized['field_visibility'] = [];
        if ( ! empty( $input['field_visibility'] ) && is_array( $input['field_visibility'] ) ) {
            foreach ( $input['field_visibility'] as $key => $value ) {
                $sanitized['field_visibility'][ sanitize_key( $key ) ] = (bool) $value;
            }
        }

        return $sanitized;
    }

    public function add_settings_page(): void {
        add_submenu_page(
            'edit.php?post_type=pikari_team_member',
            __( 'Pikari Team Settings', 'pikari-team' ),
            __( 'Settings', 'pikari-team' ),
            'manage_options',
            'pikari-team-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    public function render_settings_page(): void {
        $settings = $this->get_settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Pikari Team Settings', 'pikari-team' ); ?></h1>
            <form method="post" action="options.php">
        <?php
        settings_fields( 'pikari_team_settings_group' );
        ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_admin_label">
        <?php esc_html_e( 'Admin Label', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="pikari_team_admin_label"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[admin_label]"
                                value="<?php echo esc_attr( $settings['admin_label'] ); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_company_name">
        <?php esc_html_e( 'Company Name', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="pikari_team_company_name"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[company_name]"
                                value="<?php echo esc_attr( $settings['company_name'] ); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_brand_color">
        <?php esc_html_e( 'Brand Color', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="pikari_team_brand_color"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[brand_color]"
                                value="<?php echo esc_attr( $settings['brand_color'] ); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_post_type_slug">
        <?php esc_html_e( 'URL Slug', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="pikari_team_post_type_slug"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[post_type_slug]"
                                value="<?php echo esc_attr( $settings['post_type_slug'] ); ?>"
                                class="regular-text" />
                            <p class="description">
        <?php esc_html_e( 'URL slug for team member pages (e.g., "team" gives /team/member-name/). Save permalinks after changing.', 'pikari-team' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_url_base">
        <?php esc_html_e( 'Card URL Base', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="pikari_team_url_base"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[url_base]"
                                value="<?php echo esc_attr( $settings['url_base'] ); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Default Contact Information', 'pikari-team' ); ?></h2>
                <p class="description"><?php esc_html_e( 'These values are used when a team member\'s field is left blank.', 'pikari-team' ); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_default_email">
        <?php esc_html_e( 'Email', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="email"
                                id="pikari_team_default_email"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_email]"
                                value="<?php echo esc_attr( $settings['default_email'] ); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_default_phone">
        <?php esc_html_e( 'Phone', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="tel"
                                id="pikari_team_default_phone"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_phone]"
                                value="<?php echo esc_attr( $settings['default_phone'] ); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_default_website">
        <?php esc_html_e( 'Website', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="url"
                                id="pikari_team_default_website"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_website]"
                                value="<?php echo esc_attr( $settings['default_website'] ); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_default_street">
        <?php esc_html_e( 'Street', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="pikari_team_default_street"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_street]"
                                value="<?php echo esc_attr( $settings['default_street'] ); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_default_city">
        <?php esc_html_e( 'City', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="pikari_team_default_city"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_city]"
                                value="<?php echo esc_attr( $settings['default_city'] ); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_default_state">
        <?php esc_html_e( 'State / Province', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="pikari_team_default_state"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_state]"
                                value="<?php echo esc_attr( $settings['default_state'] ); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_default_zip">
        <?php esc_html_e( 'ZIP / Postal Code', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="pikari_team_default_zip"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_zip]"
                                value="<?php echo esc_attr( $settings['default_zip'] ); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pikari_team_default_country">
        <?php esc_html_e( 'Country', 'pikari-team' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                id="pikari_team_default_country"
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_country]"
                                value="<?php echo esc_attr( $settings['default_country'] ); ?>"
                                class="regular-text" />
                        </td>
                    </tr>
                </table>
        <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
