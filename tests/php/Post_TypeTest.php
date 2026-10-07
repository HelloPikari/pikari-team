<?php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Post_Type;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

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

    public function test_register_post_meta_called_for_all_18_fields(): void {
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'register_post_type' )->justReturn( true );
        Functions\expect( 'register_post_meta' )
            ->times( 18 )
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

    public function test_post_type_args_are_filterable(): void {
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'register_post_meta' )->justReturn( true );

        Filters\expectApplied( 'pikari_team_post_type_args' )
            ->once()
            ->with( \Mockery::type( 'array' ) );

        Functions\expect( 'register_post_type' )
            ->once()
            ->with( 'pikari_team_member', \Mockery::type( 'array' ) );

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

    // -------------------------------------------------------------------------
    // hide_protected_meta()
    // -------------------------------------------------------------------------

    /**
     * Build a REST response mock holding the given data.
     *
     * @param array $data Response data.
     * @return \Mockery\MockInterface
     */
    private function rest_response( array $data ) {
        $response = \Mockery::mock( 'WP_REST_Response' );
        $response->shouldReceive( 'get_data' )->andReturn( $data );

        return $response;
    }

    public function test_rest_prepare_filter_is_registered(): void {
        \Brain\Monkey\Filters\expectAdded( 'rest_prepare_pikari_team_member' )->once();

        new Post_Type();
    }

    public function test_hide_protected_meta_empties_meta_for_visitors_without_the_password(): void {
        Functions\when( 'post_password_required' )->justReturn( true );
        Functions\when( 'current_user_can' )->justReturn( false );

        $response = $this->rest_response( [ 'id' => 42, 'meta' => [ 'pikari_team_phone' => '555' ] ] );
        $response->shouldReceive( 'set_data' )->once()->with( [ 'id' => 42, 'meta' => [] ] );

        ( new Post_Type() )->hide_protected_meta( $response, (object) [ 'ID' => 42 ] );
    }

    public function test_hide_protected_meta_keeps_meta_for_editors(): void {
        Functions\when( 'post_password_required' )->justReturn( true );
        Functions\when( 'current_user_can' )->justReturn( true );

        $response = $this->rest_response( [ 'meta' => [ 'pikari_team_phone' => '555' ] ] );
        $response->shouldReceive( 'set_data' )->never();

        ( new Post_Type() )->hide_protected_meta( $response, (object) [ 'ID' => 42 ] );
    }

    public function test_hide_protected_meta_keeps_meta_for_unprotected_members(): void {
        Functions\when( 'post_password_required' )->justReturn( false );

        $response = $this->rest_response( [ 'meta' => [ 'pikari_team_phone' => '555' ] ] );
        $response->shouldReceive( 'set_data' )->never();

        ( new Post_Type() )->hide_protected_meta( $response, (object) [ 'ID' => 42 ] );
    }
}
