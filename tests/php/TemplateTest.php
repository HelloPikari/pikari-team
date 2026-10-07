<?php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Template;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;

class TemplateTest extends TestCase {

    public function test_add_rewrite_rule_is_called_for_card_page(): void {
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\expect( 'add_rewrite_rule' )
            ->atLeast()
            ->times( 4 );
        Functions\when( 'add_rewrite_tag' )->justReturn( null );

        $template = new Template();
        $template->register_routes();
    }

    public function test_add_rewrite_tag_is_called_for_slug_and_action(): void {
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'add_rewrite_rule' )->justReturn( null );

        Functions\expect( 'add_rewrite_tag' )
            ->once()
            ->with( '%pikari_card_slug%', '([^/]+)' );
        Functions\expect( 'add_rewrite_tag' )
            ->once()
            ->with( '%pikari_card_action%', '([^/]+)' );

        $template = new Template();
        $template->register_routes();
    }

    public function test_query_vars_filter_is_registered(): void {
        Filters\expectAdded( 'query_vars' )->once();

        new Template();
    }

    public function test_url_base_is_read_from_settings(): void {
        Functions\when( 'get_option' )->justReturn( [ 'url_base' => 'team-card' ] );

        Functions\expect( 'add_rewrite_rule' )
            ->atLeast()
            ->once()
            ->with(
                \Mockery::on( function ( $pattern ) {
                    return str_starts_with( $pattern, 'team-card/' );
                } ),
                \Mockery::any(),
                \Mockery::any()
            );
        Functions\when( 'add_rewrite_tag' )->justReturn( null );

        $template = new Template();
        $template->register_routes();
    }

    public function test_template_include_filter_is_registered(): void {
        Filters\expectAdded( 'template_include' )->once();

        new Template();
    }

    public function test_redirect_canonical_filter_is_registered(): void {
        Filters\expectAdded( 'redirect_canonical' )->once();

        new Template();
    }

    public function test_prevent_file_redirect_returns_false_for_service_worker(): void {
        Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
        $template = new Template();

        $this->assertFalse(
            $template->prevent_file_redirect(
                'https://example.com/card/john-doe/service-worker/',
                'https://example.com/card/john-doe/service-worker'
            )
        );
    }

    public function test_prevent_file_redirect_returns_false_for_manifest(): void {
        Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
        $template = new Template();

        $this->assertFalse(
            $template->prevent_file_redirect(
                'https://example.com/card/john-doe/manifest/',
                'https://example.com/card/john-doe/manifest'
            )
        );
    }

    public function test_prevent_file_redirect_returns_false_for_download_vcf(): void {
        Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
        $template = new Template();

        $this->assertFalse(
            $template->prevent_file_redirect(
                'https://example.com/card/john-doe/download.vcf/',
                'https://example.com/card/john-doe/download.vcf'
            )
        );
    }

    public function test_prevent_file_redirect_passes_through_normal_urls(): void {
        Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
        $template = new Template();

        $this->assertSame(
            'https://example.com/card/john-doe/',
            $template->prevent_file_redirect(
                'https://example.com/card/john-doe/',
                'https://example.com/card/john-doe'
            )
        );
    }

    public function test_single_template_filter_is_registered(): void {
        Filters\expectAdded( 'single_template' )->once();

        new Template();
    }

    public function test_single_template_loads_plugin_template_for_team_member(): void {
        Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );
        Functions\when( 'wp_is_block_theme' )->justReturn( false );
        Functions\when( 'locate_template' )->justReturn( '' );

        $template = new Template();
        $result   = $template->load_single_template( '/default/template.php' );

        $this->assertStringContainsString( 'templates/single-pikari_team_member.php', $result );
    }

    public function test_single_template_defers_to_theme_template(): void {
        Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );
        Functions\when( 'wp_is_block_theme' )->justReturn( false );
        Functions\when( 'locate_template' )->justReturn( '/theme/single-pikari_team_member.php' );

        $template = new Template();
        $result   = $template->load_single_template( '/default/template.php' );

        $this->assertSame( '/theme/single-pikari_team_member.php', $result );
    }

    public function test_single_template_leaves_block_themes_to_block_templates(): void {
        Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );
        Functions\when( 'wp_is_block_theme' )->justReturn( true );
        Functions\expect( 'locate_template' )->never();

        $template = new Template();
        $result   = $template->load_single_template( '/wp-includes/template-canvas.php' );

        $this->assertSame( '/wp-includes/template-canvas.php', $result );
    }

    public function test_register_block_templates_is_hooked_on_init(): void {
        Actions\expectAdded( 'init' )->with( \Mockery::type( 'array' ) )->twice();

        new Template();
    }

    public function test_register_block_templates_registers_single_template(): void {
        Functions\expect( 'register_block_template' )
            ->once()
            ->with(
                'pikari-team//single-pikari_team_member',
                \Mockery::on(
                    function ( $args ) {
                        return [ 'pikari_team_member' ] === $args['post_types']
                            && str_contains( $args['content'], '<!-- wp:pikari-team/card {"view":"full"} /-->' )
                            && str_contains( $args['content'], '<!-- wp:post-content' )
                            && ! empty( $args['title'] );
                    }
                )
            );

        ( new Template() )->register_block_templates();
    }

    public function test_single_template_ignores_other_post_types(): void {
        Functions\when( 'get_post_type' )->justReturn( 'post' );

        $template = new Template();
        $result   = $template->load_single_template( '/default/template.php' );

        $this->assertSame( '/default/template.php', $result );
    }


    // -------------------------------------------------------------------------
    // enqueue_single_assets()
    // -------------------------------------------------------------------------

    /**
     * Stub what enqueue_single_assets() needs on a team member single.
     *
     * @param array $settings Plugin settings returned by get_option().
     */
    private function mock_single_page( array $settings = [ 'brand_color' => '#ff6600' ] ): void {
        Functions\when( 'is_singular' )->justReturn( true );
        Functions\when( 'get_queried_object_id' )->justReturn( 7 );
        Functions\when( 'get_post_meta' )->justReturn( [] );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'get_option' )->justReturn( $settings );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'home_url' )->alias(
            function ( $path ) {
                return 'https://example.com' . $path;
            }
        );
    }

    public function test_wp_enqueue_scripts_action_is_registered(): void {
        Actions\expectAdded( 'wp_enqueue_scripts' )->once();

        new Template();
    }

    public function test_enqueue_single_assets_skips_other_pages(): void {
        Functions\expect( 'is_singular' )->once()->with( 'pikari_team_member' )->andReturn( false );
        Functions\expect( 'wp_register_style' )->never();
        Functions\expect( 'wp_enqueue_style' )->never();
        Functions\expect( 'wp_enqueue_script' )->never();

        ( new Template() )->enqueue_single_assets();
    }

    public function test_enqueue_single_assets_enqueues_card_css_on_team_member_single(): void {
        $this->mock_single_page();
        Functions\when( 'wp_add_inline_style' )->justReturn( true );

        Functions\expect( 'wp_register_style' )
            ->once()
            ->with( 'pikari-team-card', PIKARI_TEAM_URL . 'assets/css/card.css', [], PIKARI_TEAM_VERSION );
        Functions\expect( 'wp_enqueue_style' )->once()->with( 'pikari-team-card' );
        Functions\when( 'wp_enqueue_script' )->justReturn( null );

        ( new Template() )->enqueue_single_assets();
    }

    public function test_enqueue_single_assets_enqueues_carousel_script_in_footer(): void {
        $this->mock_single_page();
        Functions\when( 'wp_register_style' )->justReturn( true );
        Functions\when( 'wp_add_inline_style' )->justReturn( true );
        Functions\when( 'wp_enqueue_style' )->justReturn( null );

        Functions\expect( 'wp_enqueue_script' )
            ->once()
            ->with(
                'pikari-team-carousel',
                PIKARI_TEAM_URL . 'assets/js/carousel.js',
                [],
                PIKARI_TEAM_VERSION,
                [ 'in_footer' => true ]
            );

        ( new Template() )->enqueue_single_assets();
    }

    public function test_enqueue_single_assets_adds_brand_color_and_custom_css_inline(): void {
        $this->mock_single_page( [ 'brand_color' => '#ff6600' ] );
        Functions\when( 'wp_enqueue_style' )->justReturn( null );
        Functions\when( 'wp_enqueue_script' )->justReturn( null );
        Functions\when( 'wp_register_style' )->justReturn( true );

        Filters\expectApplied( 'pikari_team_card_css' )
            ->once()
            ->with( '', \Mockery::type( 'array' ) )
            ->andReturn( '.pikari-team-card{border:0}' );

        Functions\expect( 'wp_add_inline_style' )
            ->once()
            ->with(
                'pikari-team-card',
                \Mockery::on(
                    function ( $css ) {
                        return str_contains( $css, '--pikari-brand-color: #ff6600;' )
                            && str_contains( $css, '.pikari-team-card{border:0}' );
                    }
                )
            );

        ( new Template() )->enqueue_single_assets();
    }

    public function test_enqueue_single_assets_inlines_filtered_css_file(): void {
        $this->mock_single_page();
        Functions\when( 'wp_enqueue_style' )->justReturn( null );
        Functions\when( 'wp_enqueue_script' )->justReturn( null );
        $custom_file = tempnam( sys_get_temp_dir(), 'pikari-css' );
        file_put_contents( $custom_file, '.theme-card{color:red}' );

        Filters\expectApplied( 'pikari_team_card_css_file' )
            ->once()
            ->with( PIKARI_TEAM_DIR . 'assets/css/card.css', \Mockery::type( 'array' ) )
            ->andReturn( $custom_file );

        Functions\expect( 'wp_register_style' )
            ->once()
            ->with( 'pikari-team-card', false, [], PIKARI_TEAM_VERSION );
        Functions\expect( 'wp_add_inline_style' )
            ->once()
            ->with(
                'pikari-team-card',
                \Mockery::on(
                    function ( $css ) {
                        return str_starts_with( $css, '.theme-card{color:red}' );
                    }
                )
            );

        ( new Template() )->enqueue_single_assets();

        unlink( $custom_file );
    }

    public function test_enqueue_single_assets_skips_file_when_filter_returns_false(): void {
        $this->mock_single_page();
        Functions\when( 'wp_enqueue_style' )->justReturn( null );
        Functions\when( 'wp_enqueue_script' )->justReturn( null );

        Filters\expectApplied( 'pikari_team_card_css_file' )->once()->andReturn( false );

        Functions\expect( 'wp_register_style' )
            ->once()
            ->with( 'pikari-team-card', false, [], PIKARI_TEAM_VERSION );
        Functions\expect( 'wp_add_inline_style' )
            ->once()
            ->with(
                'pikari-team-card',
                \Mockery::on(
                    function ( $css ) {
                        return str_starts_with( trim( $css ), ':root' );
                    }
                )
            );

        ( new Template() )->enqueue_single_assets();
    }

    // -------------------------------------------------------------------------
    // route_template()
    // -------------------------------------------------------------------------

    /**
     * Stub a /card/ request and return the wp_query mock.
     *
     * @param array  $posts  get_posts() result.
     * @param string $action pikari_card_action query var.
     * @return \Mockery\MockInterface
     */
    private function mock_card_request( array $posts, string $action = '' ) {
        Functions\when( 'get_query_var' )->alias(
            function ( $var ) use ( $action ) {
                return 'pikari_card_slug' === $var ? 'jane-doe' : $action;
            }
        );
        Functions\when( 'get_posts' )->justReturn( $posts );

        $wp_query            = \Mockery::mock( 'WP_Query' );
        $GLOBALS['wp_query'] = $wp_query;

        return $wp_query;
    }

    public function test_route_template_returns_404_for_a_password_protected_member(): void {
        $wp_query = $this->mock_card_request( [ (object) [ 'ID' => 42 ] ], 'download' );
        Functions\when( 'post_password_required' )->justReturn( true );
        Functions\when( 'nocache_headers' )->justReturn( null );
        Functions\when( 'get_404_template' )->justReturn( '/theme/404.php' );

        $wp_query->shouldReceive( 'set_404' )->once();
        Functions\expect( 'status_header' )->once()->with( 404 );
        Actions\expectDone( 'pikari_team_card_download' )->never();

        $this->assertSame( '/theme/404.php', ( new Template() )->route_template( '/index.php' ) );
    }

    public function test_route_template_returns_404_for_an_unknown_member(): void {
        $wp_query = $this->mock_card_request( [] );
        Functions\when( 'nocache_headers' )->justReturn( null );
        Functions\when( 'get_404_template' )->justReturn( '/theme/404.php' );

        $wp_query->shouldReceive( 'set_404' )->once();
        Functions\expect( 'status_header' )->once()->with( 404 );

        $this->assertSame( '/theme/404.php', ( new Template() )->route_template( '/index.php' ) );
    }

    public function test_route_template_serves_the_card_for_a_published_member(): void {
        $this->mock_card_request( [ (object) [ 'ID' => 42, 'post_password' => '' ] ] );
        Functions\when( 'post_password_required' )->justReturn( false );
        Functions\when( 'setup_postdata' )->justReturn( true );
        Functions\when( 'locate_template' )->justReturn( '' );
        Functions\expect( 'status_header' )->never();
        Functions\expect( 'nocache_headers' )->never();

        $this->assertStringEndsWith(
            'templates/card-standalone.php',
            ( new Template() )->route_template( '/index.php' )
        );
    }

    public function test_route_template_sends_no_cache_headers_for_an_unlocked_protected_card(): void {
        $this->mock_card_request( [ (object) [ 'ID' => 42, 'post_password' => 'x' ] ] );
        Functions\when( 'post_password_required' )->justReturn( false );
        Functions\when( 'setup_postdata' )->justReturn( true );
        Functions\when( 'locate_template' )->justReturn( '' );

        Functions\expect( 'nocache_headers' )->once();

        ( new Template() )->route_template( '/index.php' );
    }

    public function test_route_template_404_fallback_drops_the_home_posts(): void {
        $wp_query = $this->mock_card_request( [] );
        Functions\when( 'nocache_headers' )->justReturn( null );
        Functions\when( 'status_header' )->justReturn( null );
        Functions\when( 'get_404_template' )->justReturn( '' );
        $wp_query->shouldReceive( 'set_404' )->once();
        $wp_query->posts      = [ (object) [ 'ID' => 1 ] ];
        $wp_query->post_count = 1;

        $this->assertSame( '/index.php', ( new Template() )->route_template( '/index.php' ) );
        $this->assertSame( [], $wp_query->posts );
        $this->assertSame( 0, $wp_query->post_count );
    }
}
