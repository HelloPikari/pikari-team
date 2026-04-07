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

    public function test_prevent_file_redirect_returns_false_for_sw_js(): void {
        Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
        $template = new Template();

        $this->assertFalse(
            $template->prevent_file_redirect(
                'https://example.com/card/john-doe/sw.js/',
                'https://example.com/card/john-doe/sw.js'
            )
        );
    }

    public function test_prevent_file_redirect_returns_false_for_manifest_json(): void {
        Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
        $template = new Template();

        $this->assertFalse(
            $template->prevent_file_redirect(
                'https://example.com/card/john-doe/manifest.json/',
                'https://example.com/card/john-doe/manifest.json'
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
        Functions\when( 'locate_template' )->justReturn( '' );

        $template = new Template();
        $result   = $template->load_single_template( '/default/template.php' );

        $this->assertStringContainsString( 'templates/single-pikari_team_member.php', $result );
    }

    public function test_single_template_defers_to_theme_template(): void {
        Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );
        Functions\when( 'locate_template' )->justReturn( '/theme/single-pikari_team_member.php' );

        $template = new Template();
        $result   = $template->load_single_template( '/default/template.php' );

        $this->assertSame( '/theme/single-pikari_team_member.php', $result );
    }

    public function test_single_template_ignores_other_post_types(): void {
        Functions\when( 'get_post_type' )->justReturn( 'post' );

        $template = new Template();
        $result   = $template->load_single_template( '/default/template.php' );

        $this->assertSame( '/default/template.php', $result );
    }
}
