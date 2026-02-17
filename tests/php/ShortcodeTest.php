<?php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Shortcode;
use Brain\Monkey\Functions;

class ShortcodeTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();

        Functions\when( 'absint' )->alias( 'intval' );
        Functions\when( 'shortcode_atts' )->alias(
            function ( $defaults, $atts ) {
                return array_merge( $defaults, (array) $atts );
            }
        );
        Functions\when( 'sanitize_title' )->returnArg();

        if ( ! defined( 'PIKARI_TEAM_DIR' ) ) {
            define( 'PIKARI_TEAM_DIR', '/tmp/pikari-team/' );
        }
    }

    public function test_add_shortcode_is_called(): void {
        Functions\expect( 'add_shortcode' )
            ->once()
            ->with( 'pikari_team_card', \Mockery::type( 'array' ) );

        new Shortcode();
    }

    public function test_shortcode_resolves_slug_to_post_id(): void {
        Functions\when( 'add_shortcode' )->justReturn( null );
        Functions\when( 'get_post_meta' )->justReturn( '' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );
        Functions\expect( 'get_posts' )
            ->once()
            ->with( \Mockery::on( function ( $args ) {
                return $args['post_type'] === 'pikari_team_member'
                    && $args['name'] === 'john-doe';
            } ) )
            ->andReturn( [ 42 ] );

        $shortcode = new Shortcode();
        $result    = $shortcode->shortcode_handler( [ 'slug' => 'john-doe' ] );

        $this->assertIsString( $result );
    }

    public function test_shortcode_uses_id_attribute_directly(): void {
        Functions\when( 'add_shortcode' )->justReturn( null );
        Functions\when( 'get_post_meta' )->justReturn( '' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );
        Functions\expect( 'get_posts' )->never();

        $shortcode = new Shortcode();
        $result    = $shortcode->shortcode_handler( [ 'id' => '42' ] );

        $this->assertIsString( $result );
    }

    public function test_render_card_returns_empty_for_invalid_post_id(): void {
        $result = Shortcode::render_card( 0 );

        $this->assertSame( '', $result );
    }
}
