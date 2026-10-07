<?php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Block_Bindings;
use Brain\Monkey\Functions;

class Block_BindingsTest extends TestCase {

    public function test_register_block_bindings_source_is_called(): void {
        Functions\expect( 'register_block_bindings_source' )
            ->once()
            ->with( 'pikari-team/meta', \Mockery::type( 'array' ) );

        $bindings = new Block_Bindings();
        $bindings->register();
    }

    /**
     * Stub get_post() and the access checks used by Shortcode::can_render().
     *
     * @param string $post_type Post type.
     * @param string $status    Post status.
     */
    private function mock_post( string $post_type = 'pikari_team_member', string $status = 'publish' ): void {
        Functions\when( 'get_post' )->justReturn(
            (object) [
                'ID'          => 42,
                'post_type'   => $post_type,
                'post_status' => $status,
            ]
        );
        Functions\when( 'is_post_publicly_viewable' )->justReturn( 'publish' === $status );
        Functions\when( 'current_user_can' )->justReturn( false );
        Functions\when( 'post_password_required' )->justReturn( false );
    }

    /**
     * Call get_binding_value() for one key on post 42.
     *
     * @param string $key Meta key.
     * @return string Binding value.
     */
    private function bind( string $key ): string {
        $block          = new \stdClass();
        $block->context = [ 'postId' => 42 ];

        return ( new Block_Bindings() )->get_binding_value( [ 'key' => $key ], $block, 'content' );
    }

    public function test_get_binding_value_returns_post_meta(): void {
        $this->mock_post();
        Functions\expect( 'get_post_meta' )
            ->once()
            ->with( 42, 'pikari_team_email', true )
            ->andReturn( 'test@example.com' );

        $block          = new \stdClass();
        $block->context = [ 'postId' => 42 ];

        $bindings = new Block_Bindings();
        $result   = $bindings->get_binding_value(
            [ 'key' => 'pikari_team_email' ],
            $block,
            'content'
        );

        $this->assertSame( 'test@example.com', $result );
    }

    public function test_editor_assets_enqueued_only_for_team_member_post_type(): void {
        $screen            = new \stdClass();
        $screen->post_type = 'post';

        Functions\expect( 'get_current_screen' )->once()->andReturn( $screen );
        Functions\expect( 'wp_enqueue_script' )->never();

        $bindings = new Block_Bindings();
        $bindings->enqueue_editor_assets();
    }

    public function test_get_binding_value_rejects_keys_outside_the_team_member_fields(): void {
        $this->mock_post();
        Functions\expect( 'get_post_meta' )->never();

        $this->assertSame( '', $this->bind( '_wp_page_template' ) );
    }

    public function test_get_binding_value_rejects_posts_of_another_type(): void {
        $this->mock_post( 'post' );
        Functions\expect( 'get_post_meta' )->never();

        $this->assertSame( '', $this->bind( 'pikari_team_phone' ) );
    }

    public function test_get_binding_value_rejects_unpublished_members(): void {
        $this->mock_post( 'pikari_team_member', 'draft' );
        Functions\expect( 'get_post_meta' )->never();

        $this->assertSame( '', $this->bind( 'pikari_team_phone' ) );
    }
}
