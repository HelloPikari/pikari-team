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

    public function test_get_binding_value_returns_post_meta(): void {
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
}
