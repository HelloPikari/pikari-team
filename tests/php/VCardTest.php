<?php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\VCard;
use Brain\Monkey\Functions;

class VCardTest extends TestCase {

    private function mock_post_meta( int $post_id, array $meta ): void {
        Functions\when( 'get_post_meta' )->alias(
            function ( $id, $key, $single ) use ( $post_id, $meta ) {
                if ( $id !== $post_id ) {
                    return '';
                }
                return $meta[ $key ] ?? '';
            }
        );
    }

    private function get_standard_meta(): array {
        return [
            'pikari_team_first_name'      => 'John',
            'pikari_team_last_name'        => 'Doe',
            'pikari_team_job_title'        => 'Developer',
            'pikari_team_email'            => 'john@example.com',
            'pikari_team_phone'            => '+1-555-0100',
            'pikari_team_cell'             => '+1-555-0101',
            'pikari_team_company'          => 'Acme Inc',
            'pikari_team_department'       => 'Engineering',
            'pikari_team_website'          => 'https://example.com',
            'pikari_team_designation'      => 'BSc',
            'pikari_team_address_street'   => '123 Main St',
            'pikari_team_address_city'     => 'Springfield',
            'pikari_team_address_state'    => 'IL',
            'pikari_team_address_zip'      => '62701',
            'pikari_team_address_country'  => 'US',
            'pikari_team_linkedin'         => 'https://linkedin.com/in/johndoe',
            'pikari_team_twitter'          => 'https://x.com/johndoe',
        ];
    }

    public function test_generate_vcard_starts_with_begin_vcard(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $vcard  = new VCard();
        $result = $vcard->generate_vcard( 1, false );

        $this->assertStringStartsWith( 'BEGIN:VCARD', $result );
    }

    public function test_generate_vcard_ends_with_end_vcard(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $vcard  = new VCard();
        $result = $vcard->generate_vcard( 1, false );

        $this->assertStringEndsWith( "END:VCARD\r\n", $result );
    }

    public function test_generate_vcard_contains_version_30(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $vcard  = new VCard();
        $result = $vcard->generate_vcard( 1, false );

        $this->assertStringContainsString( 'VERSION:3.0', $result );
    }

    public function test_fn_contains_first_and_last_name(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $vcard  = new VCard();
        $result = $vcard->generate_vcard( 1, false );

        $this->assertStringContainsString( 'FN:John Doe', $result );
    }

    public function test_n_property_has_last_semicolon_first(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $vcard  = new VCard();
        $result = $vcard->generate_vcard( 1, false );

        $this->assertStringContainsString( 'N:Doe;John;;;', $result );
    }

    public function test_org_title_tel_email_adr_url_map_correctly(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $vcard  = new VCard();
        $result = $vcard->generate_vcard( 1, false );

        $this->assertStringContainsString( 'ORG:Acme Inc', $result );
        $this->assertStringContainsString( 'TITLE:Developer', $result );
        $this->assertStringContainsString( 'TEL;TYPE=WORK,VOICE:+1-555-0100', $result );
        $this->assertStringContainsString( 'TEL;TYPE=CELL,VOICE:+1-555-0101', $result );
        $this->assertStringContainsString( 'EMAIL;TYPE=INTERNET:john@example.com', $result );
        $this->assertStringContainsString( 'ADR;TYPE=WORK:;;123 Main St;Springfield;IL;62701;US', $result );
        $this->assertStringContainsString( 'URL:https://example.com', $result );
    }

    public function test_semicolons_in_field_values_are_escaped(): void {
        $meta = $this->get_standard_meta();
        $meta['pikari_team_company'] = 'Foo; Bar';

        $this->mock_post_meta( 1, $meta );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $vcard  = new VCard();
        $result = $vcard->generate_vcard( 1, false );

        $this->assertStringContainsString( 'ORG:Foo\\; Bar', $result );
    }

    public function test_commas_in_field_values_are_escaped(): void {
        $meta = $this->get_standard_meta();
        $meta['pikari_team_company'] = 'Foo, Bar';

        $this->mock_post_meta( 1, $meta );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $vcard  = new VCard();
        $result = $vcard->generate_vcard( 1, false );

        $this->assertStringContainsString( 'ORG:Foo\\, Bar', $result );
    }

    public function test_backslashes_in_field_values_are_escaped(): void {
        $meta = $this->get_standard_meta();
        $meta['pikari_team_company'] = 'Foo\\Bar';

        $this->mock_post_meta( 1, $meta );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $vcard  = new VCard();
        $result = $vcard->generate_vcard( 1, false );

        $this->assertStringContainsString( 'ORG:Foo\\\\Bar', $result );
    }

    public function test_empty_fields_are_omitted(): void {
        $meta = [
            'pikari_team_first_name' => 'John',
            'pikari_team_last_name'  => 'Doe',
        ];

        $this->mock_post_meta( 1, $meta );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $vcard  = new VCard();
        $result = $vcard->generate_vcard( 1, false );

        $this->assertStringNotContainsString( 'ORG:', $result );
        $this->assertStringNotContainsString( 'TITLE:', $result );
        $this->assertStringNotContainsString( 'TEL;', $result );
        $this->assertStringNotContainsString( 'EMAIL;', $result );
    }

    public function test_include_photo_false_omits_photo(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( 'https://example.com/photo.jpg' );

        $vcard  = new VCard();
        $result = $vcard->generate_vcard( 1, false );

        $this->assertStringNotContainsString( 'PHOTO;', $result );
    }

    public function test_include_photo_true_omits_when_no_featured_image(): void {
        $this->mock_post_meta( 1, $this->get_standard_meta() );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

        $vcard  = new VCard();
        $result = $vcard->generate_vcard( 1, true );

        $this->assertStringNotContainsString( 'PHOTO;', $result );
    }
}
