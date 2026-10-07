<?php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Shortcode;
use Brain\Monkey\Actions;
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

    /**
     * Stub get_post() and the access checks for one post.
     *
     * @param string $post_type    Post type.
     * @param string $status       Post status.
     * @param bool   $can_read     current_user_can( 'read_post' ) result.
     * @param bool   $has_password post_password_required() result.
     */
    private function mock_post(
        string $post_type = 'pikari_team_member',
        string $status = 'publish',
        bool $can_read = false,
        bool $has_password = false
    ): void {
        Functions\when( 'get_post' )->justReturn(
            (object) [
                'ID'          => 42,
                'post_type'   => $post_type,
                'post_status' => $status,
            ]
        );
        Functions\when( 'current_user_can' )->justReturn( $can_read );
        Functions\when( 'post_password_required' )->justReturn( $has_password );
    }

    /**
     * Stub what Card_Renderer::render() needs to build member data.
     */
    private function mock_member_data(): void {
        Functions\when( 'get_post_meta' )->alias(
            function ( $id, $key = '', $single = false ) {
                return '' === $key ? [] : '';
            }
        );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'test' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();
    }

    public function test_add_shortcode_is_called(): void {
        Functions\expect( 'add_shortcode' )
            ->once()
            ->with( 'pikari_team_card', \Mockery::type( 'array' ) );

        new Shortcode();
    }

    public function test_shortcode_resolves_slug_to_post_id(): void {
        $this->mock_post();
        Functions\when( 'add_shortcode' )->justReturn( null );
        Functions\when( 'get_post_meta' )->alias(
            function ( $id, $key = '', $single = false ) {
                return '' === $key ? [] : '';
            }
        );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'john-doe' );
        Functions\when( 'home_url' )->returnArg();
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
        $this->mock_post();
        Functions\when( 'add_shortcode' )->justReturn( null );
        Functions\when( 'get_post_meta' )->alias(
            function ( $id, $key = '', $single = false ) {
                return '' === $key ? [] : '';
            }
        );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'test' );
        Functions\when( 'home_url' )->returnArg();
        Functions\expect( 'get_posts' )->never();

        $shortcode = new Shortcode();
        $result    = $shortcode->shortcode_handler( [ 'id' => '42' ] );

        $this->assertIsString( $result );
    }

    public function test_render_card_returns_empty_for_invalid_post_id(): void {
        $result = Shortcode::render_card( 0 );

        $this->assertSame( '', $result );
    }

    public function test_render_card_fires_embed_hooks(): void {
        $this->mock_post();
        Functions\when( 'get_post_meta' )->alias(
            function ( $id, $key = '', $single = false ) {
                return '' === $key ? [] : '';
            }
        );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'test' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();

        // Shortcode uses 'shortcode' context → only header+contact hooks.
        Actions\expectDone( 'pikari_team_card_header' )->once();
        Actions\expectDone( 'pikari_team_card_contact' )->once();
        Actions\expectDone( 'pikari_team_card_address' )->never();

        Shortcode::render_card( 1 );
    }


    public function test_resolve_post_id_prefers_the_block_attribute(): void {
        $this->assertSame(
            7,
            Shortcode::resolve_post_id(
                [ 'postId' => 7 ],
                [ 'postId' => 3, 'postType' => 'pikari_team_member' ]
            )
        );
    }

    public function test_resolve_post_id_uses_team_member_context(): void {
        $this->assertSame(
            3,
            Shortcode::resolve_post_id(
                [ 'postId' => 0, 'view' => 'full' ],
                [ 'postId' => 3, 'postType' => 'pikari_team_member' ]
            )
        );
    }

    public function test_resolve_post_id_ignores_context_from_other_post_types(): void {
        $this->assertSame(
            0,
            Shortcode::resolve_post_id(
                [ 'postId' => 0, 'view' => 'full' ],
                [ 'postId' => 3, 'postType' => 'post' ]
            )
        );
    }

    public function test_resolve_post_id_ignores_context_for_embed_view(): void {
        $this->assertSame(
            0,
            Shortcode::resolve_post_id(
                [ 'postId' => 0 ],
                [ 'postId' => 3, 'postType' => 'pikari_team_member' ]
            )
        );
    }

    // -------------------------------------------------------------------------
    // can_render()
    // -------------------------------------------------------------------------

    public function test_can_render_allows_a_published_member(): void {
        $this->mock_post();

        $this->assertTrue( Shortcode::can_render( 42 ) );
    }

    public function test_can_render_rejects_a_missing_post(): void {
        Functions\when( 'get_post' )->justReturn( null );

        $this->assertFalse( Shortcode::can_render( 42 ) );
    }

    public function test_can_render_rejects_another_post_type(): void {
        $this->mock_post( 'post' );

        $this->assertFalse( Shortcode::can_render( 42 ) );
    }

    public function test_can_render_rejects_a_draft_without_read_capability(): void {
        $this->mock_post( 'pikari_team_member', 'draft' );

        $this->assertFalse( Shortcode::can_render( 42 ) );
    }

    public function test_can_render_allows_a_draft_the_user_can_read(): void {
        $this->mock_post( 'pikari_team_member', 'draft', true );

        $this->assertTrue( Shortcode::can_render( 42 ) );
    }

    public function test_can_render_rejects_a_private_member_without_read_capability(): void {
        $this->mock_post( 'pikari_team_member', 'private' );

        $this->assertFalse( Shortcode::can_render( 42 ) );
    }

    public function test_can_render_rejects_trash_even_when_readable(): void {
        $this->mock_post( 'pikari_team_member', 'trash', true );

        $this->assertFalse( Shortcode::can_render( 42 ) );
    }

    public function test_can_render_rejects_a_password_protected_member(): void {
        $this->mock_post( 'pikari_team_member', 'publish', false, true );

        $this->assertFalse( Shortcode::can_render( 42 ) );
    }

    // -------------------------------------------------------------------------
    // Entry points: shortcode id=, block embed (render_card), block full
    // -------------------------------------------------------------------------

    public function test_shortcode_id_renders_nothing_for_a_draft(): void {
        Functions\when( 'add_shortcode' )->justReturn( null );
        $this->mock_post( 'pikari_team_member', 'draft' );

        $this->assertSame( '', ( new Shortcode() )->shortcode_handler( [ 'id' => '42' ] ) );
    }

    public function test_shortcode_id_renders_a_published_member(): void {
        Functions\when( 'add_shortcode' )->justReturn( null );
        $this->mock_post();
        $this->mock_member_data();

        $this->assertStringContainsString(
            'pikari-team-card',
            ( new Shortcode() )->shortcode_handler( [ 'id' => '42' ] )
        );
    }

    public function test_render_card_renders_nothing_for_another_post_type(): void {
        $this->mock_post( 'page' );

        $this->assertSame( '', Shortcode::render_card( 42 ) );
    }

    public function test_render_card_renders_a_published_member(): void {
        $this->mock_post();
        $this->mock_member_data();

        $this->assertStringContainsString( 'pikari-team-card', Shortcode::render_card( 42 ) );
    }

    public function test_render_full_card_renders_nothing_for_a_private_member(): void {
        $this->mock_post( 'pikari_team_member', 'private' );

        $this->assertSame( '', Shortcode::render_full_card( 42 ) );
    }

    public function test_render_full_card_renders_nothing_for_a_password_protected_member(): void {
        $this->mock_post( 'pikari_team_member', 'publish', false, true );

        $this->assertSame( '', Shortcode::render_full_card( 42 ) );
    }

    public function test_render_full_card_renders_nothing_without_a_post_id(): void {
        $this->assertSame( '', Shortcode::render_full_card( 0 ) );
    }

    public function test_render_full_card_renders_the_single_card_for_a_published_member(): void {
        $this->mock_post();
        $this->mock_member_data();

        Actions\expectDone( 'pikari_team_card_carousel' )->once();

        $this->assertStringContainsString( 'pikari-team-card', Shortcode::render_full_card( 42 ) );
    }
}
