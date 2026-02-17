<?php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Post_Type;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

class Post_TypeTest extends TestCase {

    public function test_register_post_type_is_called_with_correct_slug(): void {
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\expect( 'register_post_type' )
            ->once()
            ->with( 'pikari_team_member', \Mockery::type( 'array' ) );
        Functions\when( 'register_post_meta' )->justReturn( true );

        $post_type = new Post_Type();
        $post_type->register();
    }

    public function test_cpt_args_include_show_in_rest(): void {
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\expect( 'register_post_type' )
            ->once()
            ->with(
                'pikari_team_member',
                \Mockery::on( function ( $args ) {
                    return $args['show_in_rest'] === true;
                } )
            );
        Functions\when( 'register_post_meta' )->justReturn( true );

        $post_type = new Post_Type();
        $post_type->register();
    }

    public function test_cpt_args_include_public_true(): void {
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\expect( 'register_post_type' )
            ->once()
            ->with(
                'pikari_team_member',
                \Mockery::on( function ( $args ) {
                    return $args['public'] === true;
                } )
            );
        Functions\when( 'register_post_meta' )->justReturn( true );

        $post_type = new Post_Type();
        $post_type->register();
    }

    public function test_cpt_args_include_has_archive_false(): void {
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\expect( 'register_post_type' )
            ->once()
            ->with(
                'pikari_team_member',
                \Mockery::on( function ( $args ) {
                    return $args['has_archive'] === false;
                } )
            );
        Functions\when( 'register_post_meta' )->justReturn( true );

        $post_type = new Post_Type();
        $post_type->register();
    }

    public function test_register_post_meta_called_for_all_17_fields(): void {
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'register_post_type' )->justReturn( true );
        Functions\expect( 'register_post_meta' )
            ->times( 17 )
            ->with( 'pikari_team_member', \Mockery::type( 'string' ), \Mockery::type( 'array' ) );

        $post_type = new Post_Type();
        $post_type->register();
    }

    public function test_dynamic_labels_use_admin_label_from_settings(): void {
        Functions\when( 'get_option' )->justReturn( [ 'admin_label' => 'Staff' ] );
        Functions\expect( 'register_post_type' )
            ->once()
            ->with(
                'pikari_team_member',
                \Mockery::on( function ( $args ) {
                    return $args['labels']['name'] === 'Staff';
                } )
            );
        Functions\when( 'register_post_meta' )->justReturn( true );

        $post_type = new Post_Type();
        $post_type->register();
    }

    public function test_default_labels_are_team_members(): void {
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\expect( 'register_post_type' )
            ->once()
            ->with(
                'pikari_team_member',
                \Mockery::on( function ( $args ) {
                    return $args['labels']['name'] === 'Team Members'
                        && $args['labels']['singular_name'] === 'Team Member';
                } )
            );
        Functions\when( 'register_post_meta' )->justReturn( true );

        $post_type = new Post_Type();
        $post_type->register();
    }
}
