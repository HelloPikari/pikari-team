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
}
