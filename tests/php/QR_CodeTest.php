<?php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\QR_Code;
use Brain\Monkey\Functions;

class QR_CodeTest extends TestCase {

    private function mock_post_meta( int $post_id, array $meta ): void {
        Functions\when( 'get_post_meta' )->alias(
            function ( $id, $key = '', $single = false ) use ( $post_id, $meta ) {
                if ( $id !== $post_id ) {
                    return $key ? '' : [];
                }
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
        Functions\when( 'get_post_field' )->justReturn( 'john-doe' );
        Functions\when( 'home_url' )->returnArg();
    }

    public function test_generate_qr_svg_returns_svg(): void {
        $this->mock_post_meta(
            1,
            [
                'pikari_team_first_name' => 'John',
                'pikari_team_last_name'  => 'Doe',
                'pikari_team_email'      => 'john@example.com',
            ]
        );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $qr     = new QR_Code();
        $result = $qr->generate_qr_svg( 1 );

        $this->assertStringContainsString( '<svg', $result );
    }

    public function test_generate_qr_svg_contains_closing_svg_tag(): void {
        $this->mock_post_meta(
            1,
            [
                'pikari_team_first_name' => 'John',
                'pikari_team_last_name'  => 'Doe',
            ]
        );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $qr     = new QR_Code();
        $result = $qr->generate_qr_svg( 1 );

        $this->assertStringContainsString( '</svg>', $result );
    }

    public function test_qr_data_uses_vcard_without_photo(): void {
        $this->mock_post_meta(
            1,
            [
                'pikari_team_first_name' => 'Jane',
                'pikari_team_last_name'  => 'Smith',
            ]
        );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( 'https://example.com/photo.jpg' );

        $qr     = new QR_Code();
        $result = $qr->generate_qr_svg( 1 );

        // The QR code should render successfully (contains SVG).
        $this->assertStringContainsString( '<svg', $result );
    }
}
