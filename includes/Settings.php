<?php
/**
 * Admin settings page.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Settings {

    private const OPTION_KEY = 'pikari_team_settings';

    private const DEFAULTS = [
        'admin_label'      => 'Team Members',
        'company_name'     => '',
        'company_logo'     => 0,
        'brand_color'      => '#0073aa',
        'url_base'         => 'card',
        'field_visibility' => [],
    ];

    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
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
        $sanitized['url_base']     = sanitize_title( $input['url_base'] ?? 'card' );

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
        <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
