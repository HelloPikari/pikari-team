<?php
/**
 * Rewrite rules and template routing.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Template {

    public function __construct() {
        add_action( 'init', [ $this, 'register_routes' ] );
        add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
        add_filter( 'template_include', [ $this, 'route_template' ] );
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
            $base . '/([^/]+)/manifest\\.json/?$',
            'index.php?pikari_card_slug=$matches[1]&pikari_card_action=manifest',
            'top'
        );

        add_rewrite_rule(
            $base . '/([^/]+)/sw\\.js/?$',
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

    public function route_template( string $template ): string {
        $slug = get_query_var( 'pikari_card_slug' );

        if ( empty( $slug ) ) {
            return $template;
        }

        $posts = get_posts(
            [
                'post_type'      => 'pikari_team_member',
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

        // Hand off to VCard for download action (Phase 3).
        if ( 'download' === $action ) {
            do_action( 'pikari_team_card_download', $post );
            exit;
        }

        // Hand off to PWA for manifest action (Phase 4).
        if ( 'manifest' === $action ) {
            do_action( 'pikari_team_card_manifest', $post );
            exit;
        }

        // Hand off to PWA for service worker action (Phase 4).
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
