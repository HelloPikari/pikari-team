<?php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\PWA;
use Brain\Monkey\Functions;

class PWATest extends TestCase {

    private function mock_post_meta( int $post_id, array $meta ): void {
        Functions\when( 'get_post_meta' )->alias(
            function ( $id, $key, $single ) use ( $post_id, $meta ) {
                if ( $id !== $post_id ) {
                    return '';
                }
                return $meta[ $key ] ?? '';
            }
        );
    }

    private function get_standard_meta(): array {
        return [
            'pikari_team_first_name' => 'John',
            'pikari_team_last_name'  => 'Doe',
            'pikari_team_company'    => 'Acme Inc',
        ];
    }

    private function get_mock_post( array $overrides = [] ): object {
        return (object) array_merge(
            [
                'ID'            => 1,
                'post_name'     => 'john-doe',
                'post_modified' => '2025-01-15 10:30:00',
            ],
            $overrides
        );
    }

    public function test_generate_manifest_returns_valid_array(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn(
            [
                'url_base'    => 'card',
                'brand_color' => '#0073aa',
            ]
        );
        Functions\when( 'wp_get_attachment_image_url' )->justReturn( false );

        $pwa      = new PWA();
        $post     = $this->get_mock_post();
        $manifest = $pwa->generate_manifest( $post );

        $this->assertIsArray( $manifest );
        $this->assertArrayHasKey( 'name', $manifest );
        $this->assertArrayHasKey( 'short_name', $manifest );
        $this->assertArrayHasKey( 'start_url', $manifest );
        $this->assertArrayHasKey( 'display', $manifest );
    }

    public function test_manifest_short_name_is_first_last(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'wp_get_attachment_image_url' )->justReturn( false );

        $pwa      = new PWA();
        $manifest = $pwa->generate_manifest( $this->get_mock_post() );

        $this->assertSame( 'John Doe', $manifest['short_name'] );
    }

    public function test_manifest_start_url_uses_configured_base(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [ 'url_base' => 'team-card' ] );
        Functions\when( 'wp_get_attachment_image_url' )->justReturn( false );

        $pwa      = new PWA();
        $manifest = $pwa->generate_manifest( $this->get_mock_post() );

        $this->assertSame( '/team-card/john-doe/', $manifest['start_url'] );
    }

    public function test_manifest_theme_color_uses_brand_color(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [ 'brand_color' => '#ff6600' ] );
        Functions\when( 'wp_get_attachment_image_url' )->justReturn( false );

        $pwa      = new PWA();
        $manifest = $pwa->generate_manifest( $this->get_mock_post() );

        $this->assertSame( '#ff6600', $manifest['theme_color'] );
    }

    public function test_manifest_theme_color_defaults_to_0073aa(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'wp_get_attachment_image_url' )->justReturn( false );

        $pwa      = new PWA();
        $manifest = $pwa->generate_manifest( $this->get_mock_post() );

        $this->assertSame( '#0073aa', $manifest['theme_color'] );
    }

    public function test_manifest_icons_includes_192_and_512_when_logo_set(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [ 'company_logo' => 42 ] );
        Functions\when( 'wp_get_attachment_image_url' )->alias(
            function ( $id, $size ) {
                if ( 42 !== $id ) {
                    return false;
                }
                return 'https://example.com/logo-' . $size . '.png';
            }
        );

        $pwa      = new PWA();
        $manifest = $pwa->generate_manifest( $this->get_mock_post() );

        $this->assertCount( 2, $manifest['icons'] );

        $sizes = array_column( $manifest['icons'], 'sizes' );
        $this->assertContains( '192x192', $sizes );
        $this->assertContains( '512x512', $sizes );
    }

    public function test_service_worker_includes_cache_name_with_version_hash(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [ 'url_base' => 'card' ] );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $post = $this->get_mock_post( [ 'post_modified' => '2025-01-15 10:30:00' ] );
        $pwa  = new PWA();
        $js   = $pwa->generate_service_worker( $post );

        $expected_hash = md5( '2025-01-15 10:30:00' );
        $this->assertStringContainsString( $expected_hash, $js );
        $this->assertStringContainsString( 'pikari-card-john-doe-v', $js );
    }

    public function test_version_hash_changes_when_post_modified_changes(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [ 'url_base' => 'card' ] );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $pwa = new PWA();

        $post_a = $this->get_mock_post( [ 'post_modified' => '2025-01-15 10:30:00' ] );
        $js_a   = $pwa->generate_service_worker( $post_a );

        $post_b = $this->get_mock_post( [ 'post_modified' => '2025-06-01 08:00:00' ] );
        $js_b   = $pwa->generate_service_worker( $post_b );

        $this->assertNotSame( $js_a, $js_b );
    }

    public function test_service_worker_includes_precache_urls(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [ 'url_base' => 'card' ] );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( 'https://example.com/photo.jpg' );

        $pwa  = new PWA();
        $post = $this->get_mock_post();
        $js   = $pwa->generate_service_worker( $post );

        $this->assertStringContainsString( '/card/john-doe/', $js );
        $this->assertStringContainsString( '/card/john-doe/manifest', $js );
        $this->assertStringContainsString( 'https://example.com/photo.jpg', $js );
    }

    public function test_service_worker_does_not_include_absolute_css_url(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [ 'url_base' => 'card' ] );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $pwa  = new PWA();
        $post = $this->get_mock_post();
        $js   = $pwa->generate_service_worker( $post );

        $this->assertStringNotContainsString( 'assets/css/card.css', $js );
    }

    public function test_service_worker_uses_network_first_for_navigations(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [ 'url_base' => 'card' ] );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $pwa  = new PWA();
        $post = $this->get_mock_post();
        $js   = $pwa->generate_service_worker( $post );

        $this->assertStringContainsString( "event.request.mode === 'navigate'", $js );
        $this->assertStringContainsString( 'response.clone()', $js );
        $this->assertStringContainsString( 'cache.put(event.request, clone)', $js );
    }

    public function test_service_worker_includes_offline_navigation_fallback(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [ 'url_base' => 'card' ] );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $pwa  = new PWA();
        $post = $this->get_mock_post();
        $js   = $pwa->generate_service_worker( $post );

        $this->assertStringContainsString( "const START_URL = '/card/john-doe/'", $js );
        $this->assertStringContainsString( 'caches.match(START_URL)', $js );
    }

    public function test_service_worker_uses_allsettled_for_resilient_precache(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [ 'url_base' => 'card' ] );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $pwa  = new PWA();
        $post = $this->get_mock_post();
        $js   = $pwa->generate_service_worker( $post );

        $this->assertStringContainsString( 'Promise.allSettled', $js );
        $this->assertStringNotContainsString( 'cache.addAll', $js );
    }

    public function test_service_worker_includes_message_handler_for_client_caching(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_option' )->justReturn( [ 'url_base' => 'card' ] );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $pwa  = new PWA();
        $post = $this->get_mock_post();
        $js   = $pwa->generate_service_worker( $post );

        $this->assertStringContainsString( "addEventListener('message'", $js );
        $this->assertStringContainsString( "event.data.action === 'cache-page'", $js );
        $this->assertStringContainsString( 'cache.add(event.data.url)', $js );
    }
}
