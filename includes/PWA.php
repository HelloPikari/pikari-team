<?php
/**
 * Progressive Web App manifest, service worker, and meta tags.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class PWA {

    public function __construct() {
        add_action( 'pikari_team_card_manifest', [ $this, 'handle_manifest' ] );
        add_action( 'pikari_team_card_sw', [ $this, 'handle_service_worker' ] );
    }

    public function generate_manifest( object $post ): array {
        $post_id    = $post->ID;
        $first_name = (string) get_post_meta( $post_id, 'pikari_team_first_name', true );
        $last_name  = (string) get_post_meta( $post_id, 'pikari_team_last_name', true );
        $company    = (string) get_post_meta( $post_id, 'pikari_team_company', true );

        $settings    = get_option( 'pikari_team_settings', [] );
        $settings    = wp_parse_args(
            $settings,
            [
                'url_base' => 'card',
                'brand_color' => '#0073aa',
            ]
        );
        $base        = $settings['url_base'];
        $brand_color = $settings['brand_color'];

        $full_name  = trim( $first_name . ' ' . $last_name );
        $name_label = $company ? $full_name . ' - ' . $company : $full_name;

        $manifest = [
            'name'             => $name_label,
            'short_name'       => $full_name,
            'start_url'        => '/' . $base . '/' . $post->post_name . '/',
            'display'          => 'standalone',
            'theme_color'      => $brand_color,
            'background_color' => '#ffffff',
        ];

        $icons = $this->get_icons( $settings );
        if ( ! empty( $icons ) ) {
            $manifest['icons'] = $icons;
        }

        return $manifest;
    }

    public function handle_manifest( object $post ): void {
        $manifest = $this->generate_manifest( $post );

        nocache_headers();
        header( 'Content-Type: application/manifest+json' );
        echo wp_json_encode( $manifest );
        exit;
    }

    public function generate_service_worker( object $post ): string {
        $settings = get_option( 'pikari_team_settings', [] );
        $settings = wp_parse_args( $settings, [ 'url_base' => 'card' ] );
        $base     = $settings['url_base'];
        $slug     = $post->post_name;

        $version_hash = md5( $post->post_modified );
        $cache_name   = 'pikari-card-' . $slug . '-v' . $version_hash;

        $card_url     = '/' . $base . '/' . $slug . '/';
        $manifest_url = '/' . $base . '/' . $slug . '/manifest';

        $precache_urls = [
            $card_url,
            $manifest_url,
        ];

        $headshot = get_the_post_thumbnail_url( $post->ID, 'medium' );
        if ( $headshot ) {
            $precache_urls[] = $headshot;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- wp_json_encode() does not accept flags; JSON_UNESCAPED_SLASHES is required for valid JS URL literals.
        $urls_json = json_encode( $precache_urls, JSON_UNESCAPED_SLASHES );
        if ( false === $urls_json ) {
            $urls_json = '[]';
        }

        return <<<JS
const CACHE_NAME = '{$cache_name}';
const START_URL = '{$card_url}';
const PRECACHE_URLS = {$urls_json};

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) =>
            Promise.allSettled(PRECACHE_URLS.map((url) => cache.add(url)))
        )
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                    return response;
                })
                .catch(() => caches.match(event.request).then((r) => r || caches.match(START_URL)))
        );
        return;
    }
    event.respondWith(
        caches.match(event.request).then((cached) => cached || fetch(event.request))
    );
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.action === 'cache-page') {
        event.waitUntil(
            caches.open(CACHE_NAME).then((cache) => cache.add(event.data.url))
        );
    }
});
JS;
    }

    public function handle_service_worker( object $post ): void {
        nocache_headers();
        header( 'Content-Type: application/javascript' );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JavaScript output.
        echo $this->generate_service_worker( $post );
        exit;
    }

    private function get_icons( array $settings ): array {
        $logo_id = $settings['company_logo'] ?? 0;
        if ( empty( $logo_id ) ) {
            return [];
        }

        $icons = [];

        $url_192 = wp_get_attachment_image_url( $logo_id, 'medium' );
        if ( $url_192 ) {
            $icons[] = [
                'src'   => $url_192,
                'sizes' => '192x192',
                'type'  => 'image/png',
            ];
        }

        $url_512 = wp_get_attachment_image_url( $logo_id, 'full' );
        if ( $url_512 ) {
            $icons[] = [
                'src'   => $url_512,
                'sizes' => '512x512',
                'type'  => 'image/png',
            ];
        }

        return $icons;
    }
}
