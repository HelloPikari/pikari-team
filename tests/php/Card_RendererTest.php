<?php
/**
 * Tests for Card_Renderer class.
 *
 * @package pikari-team
 */

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Card_Renderer;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

class Card_RendererTest extends TestCase {

    private function mock_member_data(): void {
        Functions\expect( 'get_post_meta' )
            ->andReturnUsing(
                function ( $id, $key = '', $single = false ) {
                    $meta = [
                        'pikari_team_first_name' => 'Jane',
                        'pikari_team_last_name'  => 'Doe',
                        'pikari_team_email'      => 'jane@example.com',
                        'pikari_team_job_title'  => 'CTO',
                        'pikari_team_company'    => 'Acme',
                    ];
                    if ( '' === $key ) {
                        $all = [];
                        foreach ( $meta as $k => $v ) {
                            $all[ $k ] = [ $v ];
                        }
                        return $all;
                    }
                    return $meta[ $key ] ?? '';
                }
            );
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\when( 'get_post_field' )->justReturn( 'jane-doe' );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( '' );
        Functions\when( 'home_url' )->returnArg();
    }

    public function test_render_fires_all_section_hooks_for_single_context(): void {
        $this->mock_member_data();

        // We need to track which hooks are fired.
        // Since Brain\Monkey intercepts do_action, we use expectDone.
        Actions\expectDone( 'pikari_team_card_header' )->once();
        Actions\expectDone( 'pikari_team_card_contact' )->once();
        Actions\expectDone( 'pikari_team_card_address' )->once();
        Actions\expectDone( 'pikari_team_card_social' )->once();
        Actions\expectDone( 'pikari_team_card_qr' )->once();
        Actions\expectDone( 'pikari_team_card_footer' )->once();

        Card_Renderer::render( 1, 'single' );
    }

    public function test_render_fires_limited_hooks_for_embed_context(): void {
        $this->mock_member_data();

        Actions\expectDone( 'pikari_team_card_header' )->once();
        Actions\expectDone( 'pikari_team_card_contact' )->once();
        // These should NOT be fired in embed context.
        Actions\expectDone( 'pikari_team_card_address' )->never();
        Actions\expectDone( 'pikari_team_card_social' )->never();
        Actions\expectDone( 'pikari_team_card_qr' )->never();
        Actions\expectDone( 'pikari_team_card_footer' )->never();

        Card_Renderer::render( 1, 'embed' );
    }

    public function test_render_fires_limited_hooks_for_shortcode_context(): void {
        $this->mock_member_data();

        Actions\expectDone( 'pikari_team_card_header' )->once();
        Actions\expectDone( 'pikari_team_card_contact' )->once();
        Actions\expectDone( 'pikari_team_card_address' )->never();
        Actions\expectDone( 'pikari_team_card_social' )->never();
        Actions\expectDone( 'pikari_team_card_qr' )->never();
        Actions\expectDone( 'pikari_team_card_footer' )->never();

        Card_Renderer::render( 1, 'shortcode' );
    }

    public function test_register_defaults_hooks_into_all_sections(): void {
        Actions\expectAdded( 'pikari_team_card_header' )->once();
        Actions\expectAdded( 'pikari_team_card_contact' )->once();
        Actions\expectAdded( 'pikari_team_card_address' )->once();
        Actions\expectAdded( 'pikari_team_card_social' )->once();
        Actions\expectAdded( 'pikari_team_card_qr' )->once();
        Actions\expectAdded( 'pikari_team_card_footer' )->once();

        Card_Renderer::register_defaults();
    }
}
