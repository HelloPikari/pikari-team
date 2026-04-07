<?php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Meta_Box;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

class Meta_BoxTest extends TestCase {

    public function test_add_meta_boxes_hook_is_registered(): void {
        Actions\expectAdded( 'add_meta_boxes' )->once();

        new Meta_Box();
    }

    public function test_meta_box_not_registered_when_block_editor_active(): void {
        Functions\when( 'use_block_editor_for_post_type' )->justReturn( true );

        Functions\expect( 'add_meta_box' )->never();

        $meta_box = new Meta_Box();
        $meta_box->register_meta_box();
    }

    public function test_save_handler_verifies_nonce(): void {
        Functions\expect( 'wp_verify_nonce' )
            ->once()
            ->andReturn( false );

        Functions\expect( 'update_post_meta' )->never();

        // Simulate POST data with invalid nonce.
        $_POST['pikari_team_meta_nonce'] = 'invalid';

        $meta_box = new Meta_Box();
        $meta_box->save_meta( 1 );

        unset( $_POST['pikari_team_meta_nonce'] );
    }

    public function test_save_handler_checks_user_capability(): void {
        Functions\when( 'wp_verify_nonce' )->justReturn( true );
        Functions\expect( 'current_user_can' )
            ->once()
            ->with( 'edit_post', 1 )
            ->andReturn( false );

        Functions\expect( 'update_post_meta' )->never();

        $_POST['pikari_team_meta_nonce'] = 'valid';

        $meta_box = new Meta_Box();
        $meta_box->save_meta( 1 );

        unset( $_POST['pikari_team_meta_nonce'] );
    }

    public function test_save_handler_calls_update_post_meta(): void {
        Functions\when( 'wp_verify_nonce' )->justReturn( true );
        Functions\when( 'current_user_can' )->justReturn( true );
        Functions\when( 'sanitize_text_field' )->returnArg();

        // Expect update_post_meta for each field.
        Functions\expect( 'update_post_meta' )
            ->times( 17 );

        Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );
        Functions\when( 'get_post_meta' )->justReturn( '' );

        $_POST['pikari_team_meta_nonce'] = 'valid';

        $meta_box = new Meta_Box();
        $meta_box->save_meta( 1 );

        unset( $_POST['pikari_team_meta_nonce'] );
    }

    public function test_save_meta_triggers_validation_notice(): void {
        Functions\when( 'wp_verify_nonce' )->justReturn( true );
        Functions\when( 'current_user_can' )->justReturn( true );
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'update_post_meta' )->justReturn( true );
        Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );
        Functions\when( 'get_post_meta' )->justReturn( '' );

        Actions\expectAdded( 'admin_notices' )->once();

        $_POST['pikari_team_meta_nonce'] = 'valid';
        $meta_box = new Meta_Box();
        $meta_box->save_meta( 1 );
        unset( $_POST['pikari_team_meta_nonce'] );
    }

    public function test_render_meta_box_outputs_required_asterisks(): void {
        $post     = \Mockery::mock( 'WP_Post' );
        $post->ID = 1;
        Functions\when( 'get_post_meta' )->justReturn( '' );
        Functions\when( 'wp_nonce_field' )->justReturn( '' );

        $meta_box = new Meta_Box();
        ob_start();
        $meta_box->render_meta_box( $post );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'class="pikari-team-required"', $output );
    }

    public function test_render_meta_box_uses_email_input_type(): void {
        $post     = \Mockery::mock( 'WP_Post' );
        $post->ID = 1;
        Functions\when( 'get_post_meta' )->justReturn( '' );
        Functions\when( 'wp_nonce_field' )->justReturn( '' );

        $meta_box = new Meta_Box();
        ob_start();
        $meta_box->render_meta_box( $post );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'type="email"', $output );
        $this->assertStringContainsString( 'type="tel"', $output );
        $this->assertStringContainsString( 'type="url"', $output );
    }
}
