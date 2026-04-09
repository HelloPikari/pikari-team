<?php
/**
 * Tests for Template_Tags class.
 *
 * @package pikari-team
 */

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Template_Tags;
use Brain\Monkey\Functions;

class Template_TagsTest extends TestCase {

    /**
     * Default meta values for a fully-populated team member.
     */
    private function default_meta(): array {
        return [
            'pikari_team_first_name'      => 'Jane',
            'pikari_team_last_name'       => 'Doe',
            'pikari_team_designation'     => 'Dr.',
            'pikari_team_job_title'       => 'Lead Engineer',
            'pikari_team_email'           => 'jane@example.com',
            'pikari_team_phone'           => '416-555-0100',
            'pikari_team_cell'            => '416-555-0101',
            'pikari_team_company'         => 'Acme Corp',
            'pikari_team_department'      => 'Engineering',
            'pikari_team_website'         => 'https://example.com',
            'pikari_team_address_street'  => '123 Main St',
            'pikari_team_address_city'    => 'Toronto',
            'pikari_team_address_state'   => 'ON',
            'pikari_team_address_zip'     => 'M5V 1A1',
            'pikari_team_address_country' => 'Canada',
            'pikari_team_linkedin'        => 'https://linkedin.com/in/janedoe',
            'pikari_team_twitter'         => 'https://twitter.com/janedoe',
        ];
    }

    /**
     * Set up get_post_meta mock using a lookup map.
     *
     * @param int   $post_id   The post ID to match.
     * @param array $overrides Key=>value overrides for default meta.
     */
    private function mock_all_meta( int $post_id = 1, array $overrides = [] ): void {
        $meta = array_merge( $this->default_meta(), $overrides );

        Functions\expect( 'get_post_meta' )->andReturnUsing(
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
    }

    /**
     * Set up all required WordPress function mocks for get_member_data().
     *
     * @param int    $post_id       Post ID.
     * @param array  $meta_overrides Meta overrides.
     * @param string $post_name     Post slug.
     * @param string $url_base      URL base from settings.
     * @param string $photo_url     Photo URL or empty string.
     */
    private function mock_get_member_data(
        int $post_id = 1,
        array $meta_overrides = [],
        string $post_name = 'jane-doe',
        string $url_base = 'card',
        string $photo_url = 'https://example.com/photo.jpg'
    ): void {
        $this->mock_all_meta( $post_id, $meta_overrides );

        Functions\when( 'get_option' )->justReturn( [ 'url_base' => $url_base ] );
        Functions\when( 'get_post_field' )->justReturn( $post_name );
        Functions\when( 'get_the_post_thumbnail_url' )->justReturn( $photo_url );
        Functions\when( 'home_url' )->alias(
            function ( $path ) {
                return 'https://example.com' . $path;
            }
        );
    }

    // -------------------------------------------------------------------------
    // get_member_data() — basic fields
    // -------------------------------------------------------------------------

    public function test_get_member_data_returns_full_name(): void {
        $this->mock_get_member_data();

        $data = Template_Tags::get_member_data( 1 );

        $this->assertSame( 'Jane Doe', $data['full_name'] );
    }

    public function test_get_member_data_returns_all_raw_meta(): void {
        $this->mock_get_member_data();

        $data = Template_Tags::get_member_data( 1 );

        $this->assertSame( 'Jane', $data['first_name'] );
        $this->assertSame( 'Doe', $data['last_name'] );
        $this->assertSame( 'jane@example.com', $data['email'] );
        $this->assertSame( 'Lead Engineer', $data['job_title'] );
    }

    public function test_get_member_data_returns_grouped_address(): void {
        $this->mock_get_member_data();

        $data = Template_Tags::get_member_data( 1 );

        $this->assertIsArray( $data['address'] );
        $this->assertSame( '123 Main St', $data['address']['street'] );
        $this->assertSame( 'Toronto', $data['address']['city'] );
        $this->assertSame( 'ON', $data['address']['state'] );
        $this->assertSame( 'M5V 1A1', $data['address']['zip'] );
        $this->assertSame( 'Canada', $data['address']['country'] );
    }

    public function test_get_member_data_returns_grouped_social(): void {
        $this->mock_get_member_data();

        $data = Template_Tags::get_member_data( 1 );

        $this->assertIsArray( $data['social'] );
        $this->assertSame( 'https://linkedin.com/in/janedoe', $data['social']['linkedin'] );
        $this->assertSame( 'https://twitter.com/janedoe', $data['social']['twitter'] );
    }

    // -------------------------------------------------------------------------
    // get_member_data() — has_* flags
    // -------------------------------------------------------------------------

    public function test_get_member_data_returns_has_flags(): void {
        $this->mock_get_member_data();

        $data = Template_Tags::get_member_data( 1 );

        $this->assertTrue( $data['has_photo'] );
        $this->assertTrue( $data['has_designation'] );
        $this->assertTrue( $data['has_job_title'] );
        $this->assertTrue( $data['has_phone'] );
        $this->assertTrue( $data['has_cell'] );
        $this->assertTrue( $data['has_company'] );
        $this->assertTrue( $data['has_department'] );
        $this->assertTrue( $data['has_website'] );
        $this->assertTrue( $data['has_linkedin'] );
        $this->assertTrue( $data['has_twitter'] );
        $this->assertTrue( $data['has_address'] );
        $this->assertTrue( $data['has_social'] );
    }

    public function test_get_member_data_has_flags_false_when_empty(): void {
        $empty_overrides = [
            'pikari_team_designation'     => '',
            'pikari_team_job_title'       => '',
            'pikari_team_phone'           => '',
            'pikari_team_cell'            => '',
            'pikari_team_company'         => '',
            'pikari_team_department'      => '',
            'pikari_team_website'         => '',
            'pikari_team_address_street'  => '',
            'pikari_team_address_city'    => '',
            'pikari_team_address_state'   => '',
            'pikari_team_address_zip'     => '',
            'pikari_team_address_country' => '',
            'pikari_team_linkedin'        => '',
            'pikari_team_twitter'         => '',
        ];

        $this->mock_get_member_data( 1, $empty_overrides, 'jane-doe', 'card', '' );

        $data = Template_Tags::get_member_data( 1 );

        $this->assertFalse( $data['has_photo'] );
        $this->assertFalse( $data['has_designation'] );
        $this->assertFalse( $data['has_job_title'] );
        $this->assertFalse( $data['has_phone'] );
        $this->assertFalse( $data['has_cell'] );
        $this->assertFalse( $data['has_company'] );
        $this->assertFalse( $data['has_department'] );
        $this->assertFalse( $data['has_website'] );
        $this->assertFalse( $data['has_linkedin'] );
        $this->assertFalse( $data['has_twitter'] );
        $this->assertFalse( $data['has_address'] );
        $this->assertFalse( $data['has_social'] );
    }

    // -------------------------------------------------------------------------
    // get_member_data() — computed URLs
    // -------------------------------------------------------------------------

    public function test_get_member_data_returns_computed_urls(): void {
        $this->mock_get_member_data( 1, [], 'jane-doe', 'card' );

        $data = Template_Tags::get_member_data( 1 );

        $this->assertSame( 'https://example.com/card/jane-doe/', $data['card_url'] );
        $this->assertSame( 'https://example.com/card/jane-doe/download.vcf', $data['vcard_url'] );
    }

    // -------------------------------------------------------------------------
    // get_formatted_address()
    // -------------------------------------------------------------------------

    public function test_get_formatted_address_returns_string(): void {
        $this->mock_get_member_data();

        $address = Template_Tags::get_formatted_address( 1 );

        $this->assertStringContainsString( '123 Main St', $address );
        $this->assertStringContainsString( 'Toronto', $address );
        $this->assertStringContainsString( 'ON', $address );
        $this->assertStringContainsString( 'M5V 1A1', $address );
        $this->assertStringContainsString( 'Canada', $address );
    }

    public function test_get_formatted_address_returns_empty_when_no_address(): void {
        $empty_overrides = [
            'pikari_team_address_street'  => '',
            'pikari_team_address_city'    => '',
            'pikari_team_address_state'   => '',
            'pikari_team_address_zip'     => '',
            'pikari_team_address_country' => '',
        ];

        $this->mock_get_member_data( 1, $empty_overrides );

        $address = Template_Tags::get_formatted_address( 1 );

        $this->assertSame( '', $address );
    }

    // -------------------------------------------------------------------------
    // get_social_links()
    // -------------------------------------------------------------------------

    public function test_get_social_links_returns_array_with_platform_metadata(): void {
        $this->mock_get_member_data();

        $links = Template_Tags::get_social_links( 1 );

        $this->assertCount( 2, $links );

        $platforms = array_column( $links, 'platform' );
        $this->assertContains( 'linkedin', $platforms );
        $this->assertContains( 'twitter', $platforms );

        $linkedin = array_values(
            array_filter( $links, fn( $l ) => $l['platform'] === 'linkedin' )
        )[0];

        $this->assertSame( 'https://linkedin.com/in/janedoe', $linkedin['url'] );
        $this->assertSame( 'LinkedIn', $linkedin['label'] );
    }

    public function test_get_social_links_omits_empty_platforms(): void {
        $overrides = [
            'pikari_team_linkedin' => '',
            'pikari_team_twitter'  => 'https://twitter.com/janedoe',
        ];

        $this->mock_get_member_data( 1, $overrides );

        $links = Template_Tags::get_social_links( 1 );

        $this->assertCount( 1, $links );
        $this->assertSame( 'twitter', $links[0]['platform'] );
        $this->assertSame( 'Twitter/X', $links[0]['label'] );
    }
}
