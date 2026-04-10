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
        add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
        add_filter( 'template_include', [ $this, 'route_template' ] );
        add_filter( 'redirect_canonical', [ $this, 'prevent_file_redirect' ], 10, 2 );
        add_filter( 'single_template', [ $this, 'load_single_template' ] );
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
     * Load the plugin's single template if the theme doesn't provide one.
     *
     * @param string $template The path to the current template.
     * @return string The template path.
     */
    public function load_single_template( string $template ): string {
        if ( Post_Type::CPT_SLUG !== get_post_type() ) {
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

        if ( empty( $posts ) ) {
            return $template;
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
}
