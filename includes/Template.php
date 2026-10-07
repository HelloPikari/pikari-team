<?php
/**
 * Rewrite rules and template routing.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Template {

    /**
     * URL suffixes that should not receive a trailing-slash redirect.
     * Service worker registration fails if the script URL is redirected.
     */
    private const NO_REDIRECT_SUFFIXES = [ '/service-worker', '/manifest', '.vcf' ];

    public function __construct() {
        add_action( 'init', [ $this, 'register_routes' ] );
        add_action( 'init', [ $this, 'register_block_templates' ] );
        add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
        add_filter( 'template_include', [ $this, 'route_template' ] );
        add_filter( 'redirect_canonical', [ $this, 'prevent_file_redirect' ], 10, 2 );
        add_filter( 'single_template', [ $this, 'load_single_template' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_single_assets' ] );
    }

    public function register_routes(): void {
        $settings = get_option( 'pikari_team_settings', [] );
        $base     = $settings['url_base'] ?? 'card';

        add_rewrite_tag( '%pikari_card_slug%', '([^/]+)' );
        add_rewrite_tag( '%pikari_card_action%', '([^/]+)' );

        add_rewrite_rule(
            $base . '/([^/]+)/download\\.vcf/?$',
            'index.php?pikari_card_slug=$matches[1]&pikari_card_action=download',
            'top'
        );

        add_rewrite_rule(
            $base . '/([^/]+)/manifest/?$',
            'index.php?pikari_card_slug=$matches[1]&pikari_card_action=manifest',
            'top'
        );

        add_rewrite_rule(
            $base . '/([^/]+)/service-worker/?$',
            'index.php?pikari_card_slug=$matches[1]&pikari_card_action=sw',
            'top'
        );

        add_rewrite_rule(
            $base . '/([^/]+)/?$',
            'index.php?pikari_card_slug=$matches[1]',
            'top'
        );
    }

    public function register_query_vars( array $vars ): array {
        $vars[] = 'pikari_card_slug';
        $vars[] = 'pikari_card_action';
        return $vars;
    }

    /**
     * Prevent WordPress from adding a trailing slash to file-like card URLs.
     *
     * Service worker registration fails if the script URL is redirected.
     *
     * @param string $redirect_url  The URL WordPress wants to redirect to.
     * @param string $requested_url The originally requested URL.
     * @return string|false The redirect URL or false to cancel the redirect.
     */
    public function prevent_file_redirect( string $redirect_url, string $requested_url ) {
        $path = (string) wp_parse_url( $requested_url, PHP_URL_PATH );

        foreach ( self::NO_REDIRECT_SUFFIXES as $suffix ) {
            if ( str_ends_with( $path, $suffix ) ) {
                return false;
            }
        }

        return $redirect_url;
    }

    /**
     * Register the plugin's block template for block themes.
     *
     * A theme's own single-pikari_team_member.html and Site Editor edits
     * both take precedence over a plugin-registered template.
     */
    public function register_block_templates(): void {
        register_block_template(
            'pikari-team//single-pikari_team_member',
            [
                'title'       => __( 'Single Team Member', 'pikari-team' ),
                'description' => __( 'Displays a team member card and biography.', 'pikari-team' ),
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
                'content'     => (string) file_get_contents( PIKARI_TEAM_DIR . 'templates/single-pikari_team_member.html' ),
                'post_types'  => [ Post_Type::CPT_SLUG ],
            ]
        );
    }

    /**
     * Load the plugin's single template if the theme doesn't provide one.
     *
     * @param string $template The path to the current template.
     * @return string The template path.
     */
    public function load_single_template( string $template ): string {
        if ( Post_Type::CPT_SLUG !== get_post_type() ) {
            return $template;
        }

        // Block themes resolve the registered block template instead.
        if ( wp_is_block_theme() ) {
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

    /**
     * Enqueue the card styles and carousel script on the team member single.
     *
     * Uses the same CSS filters as the standalone card so overrides apply in
     * both views. Themes can wp_dequeue_style( 'pikari-team-card' ) or
     * wp_dequeue_script( 'pikari-team-carousel' ) on wp_enqueue_scripts at a
     * priority above 10 to opt out.
     */
    public function enqueue_single_assets(): void {
        if ( ! is_singular( Post_Type::CPT_SLUG ) ) {
            return;
        }

        $data         = Template_Tags::get_member_data( get_queried_object_id() );
        $settings     = get_option( Settings::OPTION_KEY, [] );
        $brand_color  = $settings['brand_color'] ?? '#0073aa';
        $default_file = PIKARI_TEAM_DIR . 'assets/css/card.css';

        /** This filter is documented in templates/card-standalone.php */
        $css_file = apply_filters( 'pikari_team_card_css_file', $default_file, $data );

        $src = false;
        $css = '';
        if ( $default_file === $css_file ) {
            $src = PIKARI_TEAM_URL . 'assets/css/card.css';
        } elseif ( $css_file && file_exists( $css_file ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file path from filter.
            $css = file_get_contents( $css_file );
        }

        $css .= ':root { --pikari-brand-color: ' . esc_attr( $brand_color ) . '; }';

        /** This filter is documented in templates/card-standalone.php */
        $css .= apply_filters( 'pikari_team_card_css', '', $data );

        wp_register_style( 'pikari-team-card', $src, [], PIKARI_TEAM_VERSION );
        wp_add_inline_style( 'pikari-team-card', $css );
        wp_enqueue_style( 'pikari-team-card' );

        wp_enqueue_script(
            'pikari-team-carousel',
            PIKARI_TEAM_URL . 'assets/js/carousel.js',
            [],
            PIKARI_TEAM_VERSION,
            [ 'in_footer' => true ]
        );
    }

    public function route_template( string $template ): string {
        $slug = get_query_var( 'pikari_card_slug' );

        if ( empty( $slug ) ) {
            return $template;
        }

        $posts = get_posts(
            [
                'post_type'      => Post_Type::CPT_SLUG,
                'name'           => $slug,
                'posts_per_page' => 1,
                'post_status'    => 'publish',
            ]
        );

        // Unknown and password-protected members get a real 404, so neither the
        // card, the vCard nor the PWA files reveal a protected member's details.
        if ( empty( $posts ) || post_password_required( $posts[0] ) ) {
            return $this->not_found( $template );
        }

        $post   = $posts[0];
        $action = get_query_var( 'pikari_card_action' );

        if ( 'download' === $action ) {
            do_action( 'pikari_team_card_download', $post );
            exit;
        }

        if ( 'manifest' === $action ) {
            do_action( 'pikari_team_card_manifest', $post );
            exit;
        }

        if ( 'sw' === $action ) {
            do_action( 'pikari_team_card_sw', $post );
            exit;
        }

        // Card page — set up global post data.
        $GLOBALS['post'] = $post;
        setup_postdata( $post );

        // Check for theme override.
        $theme_template = locate_template( 'pikari-team/card-standalone.php' );
        if ( $theme_template ) {
            return $theme_template;
        }

        return PIKARI_TEAM_DIR . 'templates/card-standalone.php';
    }

    /**
     * Turn the current request into a 404.
     *
     * @param string $template Fallback template if the theme has no 404 template.
     * @return string The 404 template path.
     */
    private function not_found( string $template ): string {
        global $wp_query;

        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();

        return get_404_template() ?: $template;
    }
}
