<?php
/**
 * Tests for the Validation class.
 *
 * @package pikari-team
 */

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Validation;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

class ValidationTest extends TestCase {

    public function test_required_fields_returns_correct_keys(): void {
        $this->assertSame(
            [
                'pikari_team_first_name',
                'pikari_team_last_name',
                'pikari_team_email',
            ],
            Validation::get_required_fields()
        );
    }

    public function test_is_required_returns_true_for_required_field(): void {
        $this->assertTrue( Validation::is_required( 'pikari_team_first_name' ) );
        $this->assertTrue( Validation::is_required( 'pikari_team_last_name' ) );
        $this->assertTrue( Validation::is_required( 'pikari_team_email' ) );
    }

    public function test_is_required_returns_false_for_optional_field(): void {
        $this->assertFalse( Validation::is_required( 'pikari_team_phone' ) );
        $this->assertFalse( Validation::is_required( 'pikari_team_company' ) );
    }

    public function test_validate_phone_accepts_valid_formats(): void {
        $this->assertTrue( Validation::validate_phone( '555-123-4567' ) );
        $this->assertTrue( Validation::validate_phone( '(555) 123-4567' ) );
        $this->assertTrue( Validation::validate_phone( '+1 555 123 4567' ) );
        $this->assertTrue( Validation::validate_phone( '5551234567' ) );
        $this->assertTrue( Validation::validate_phone( '+44 20 7946 0958' ) );
    }

    public function test_validate_phone_rejects_too_short(): void {
        $this->assertFalse( Validation::validate_phone( '123456' ) );
        $this->assertFalse( Validation::validate_phone( '12-34' ) );
    }

    public function test_validate_phone_accepts_empty_string(): void {
        $this->assertTrue( Validation::validate_phone( '' ) );
    }

    public function test_validate_phone_rejects_non_numeric(): void {
        $this->assertFalse( Validation::validate_phone( 'not a phone' ) );
    }

    public function test_get_missing_required_fields_returns_empty_when_all_present(): void {
        $meta = [
            'pikari_team_first_name' => 'Jane',
            'pikari_team_last_name'  => 'Doe',
            'pikari_team_email'      => 'jane@example.com',
        ];

        $this->assertSame( [], Validation::get_missing_required_fields( $meta ) );
    }

    public function test_get_missing_required_fields_returns_missing_keys(): void {
        $meta = [
            'pikari_team_first_name' => 'Jane',
            'pikari_team_last_name'  => '',
            'pikari_team_email'      => '',
        ];

        $missing = Validation::get_missing_required_fields( $meta );

        $this->assertContains( 'pikari_team_last_name', $missing );
        $this->assertContains( 'pikari_team_email', $missing );
        $this->assertNotContains( 'pikari_team_first_name', $missing );
    }

    public function test_get_field_label_returns_human_readable(): void {
        $this->assertSame( 'First Name', Validation::get_field_label( 'pikari_team_first_name' ) );
        $this->assertSame( 'Last Name', Validation::get_field_label( 'pikari_team_last_name' ) );
        $this->assertSame( 'Email', Validation::get_field_label( 'pikari_team_email' ) );
        $this->assertSame( 'State/Province', Validation::get_field_label( 'pikari_team_address_state' ) );
        $this->assertSame( 'ZIP/Postal Code', Validation::get_field_label( 'pikari_team_address_zip' ) );
    }

    public function test_maybe_add_admin_notice_hooks_when_fields_missing(): void {
        Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );
        Functions\when( 'get_post_meta' )->justReturn( '' );

        Actions\expectAdded( 'admin_notices' )->once();

        Validation::maybe_add_admin_notice( 1 );
    }

    public function test_maybe_add_admin_notice_does_nothing_when_complete(): void {
        Functions\when( 'get_post_type' )->justReturn( 'pikari_team_member' );
        Functions\when( 'get_post_meta' )->alias(
            function ( $post_id, $key, $single ) {
                $values = [
                    'pikari_team_first_name' => 'Jane',
                    'pikari_team_last_name'  => 'Doe',
                    'pikari_team_email'      => 'jane@example.com',
                ];
                return $values[ $key ] ?? '';
            }
        );

        // No admin_notices hook should be added.
        Actions\expectAdded( 'admin_notices' )->never();

        Validation::maybe_add_admin_notice( 1 );
    }

    public function test_maybe_add_admin_notice_skips_wrong_post_type(): void {
        Functions\when( 'get_post_type' )->justReturn( 'post' );

        // get_post_meta should never be called for wrong post type.
        Actions\expectAdded( 'admin_notices' )->never();

        Validation::maybe_add_admin_notice( 1 );
    }
}
