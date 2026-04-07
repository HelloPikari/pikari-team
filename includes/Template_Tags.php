<?php
/**
 * Public helper API for team member data.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

/**
 * Static utility class providing structured access to team member meta.
 *
 * All methods are stateless and accept a post ID, making them safe to call
 * from any template context without instantiation.
 */
class Template_Tags {

    /**
     * Raw meta keys (without prefix) that map to single string values.
     */
    private const RAW_META_KEYS = [
        'first_name',
        'last_name',
        'designation',
        'job_title',
        'email',
        'phone',
        'cell',
        'company',
        'department',
        'website',
        'address_street',
        'address_city',
        'address_state',
        'address_zip',
        'address_country',
        'linkedin',
        'twitter',
    ];

    /**
     * Social platform definitions used for get_social_links().
     */
    private const SOCIAL_PLATFORMS = [
        'linkedin' => [
            'key' => 'pikari_team_linkedin',
            'label' => 'LinkedIn',
        ],
        'twitter'  => [
            'key' => 'pikari_team_twitter',
            'label' => 'Twitter/X',
        ],
    ];

    /**
     * Returns a fully structured data array for a team member.
     *
     * Includes raw meta, computed values, grouped sub-arrays, and has_* flags.
     *
     * @param int $post_id The team member post ID.
     * @return array<string, mixed>
     */
    public static function get_member_data( int $post_id ): array {
        // Fetch all raw meta fields.
        $raw = [];
        foreach ( self::RAW_META_KEYS as $short_key ) {
            $raw[ $short_key ] = (string) get_post_meta( $post_id, 'pikari_team_' . $short_key, true );
        }

        // Computed: full name.
        $full_name = trim( $raw['first_name'] . ' ' . $raw['last_name'] );

        // Computed: photo URL.
        $photo_url = (string) get_the_post_thumbnail_url( $post_id, 'medium' );

        // Computed: card and vCard URLs.
        $settings = get_option( 'pikari_team_settings', [] );
        $base     = $settings['url_base'] ?? 'card';
        $slug     = (string) get_post_field( 'post_name', $post_id );
        $card_url  = home_url( '/' . $base . '/' . $slug . '/' );
        $vcard_url = home_url( '/' . $base . '/' . $slug . '/download.vcf' );

        // Grouped sub-arrays.
        $address = [
            'street'  => $raw['address_street'],
            'city'    => $raw['address_city'],
            'state'   => $raw['address_state'],
            'zip'     => $raw['address_zip'],
            'country' => $raw['address_country'],
        ];

        $social = [
            'linkedin' => $raw['linkedin'],
            'twitter'  => $raw['twitter'],
        ];

        // has_* boolean flags.
        $has_address = array_reduce(
            $address,
            fn( $carry, $val ) => $carry || $val !== '',
            false
        );

        $has_social = array_reduce(
            $social,
            fn( $carry, $val ) => $carry || $val !== '',
            false
        );

        return array_merge(
            $raw,
            [
                'post_id'        => $post_id,
                'full_name'      => $full_name,
                'photo_url'      => $photo_url,
                'card_url'       => $card_url,
                'vcard_url'      => $vcard_url,
                'address'        => $address,
                'social'         => $social,
                'has_photo'      => $photo_url !== '',
                'has_designation' => $raw['designation'] !== '',
                'has_job_title'  => $raw['job_title'] !== '',
                'has_phone'      => $raw['phone'] !== '',
                'has_cell'       => $raw['cell'] !== '',
                'has_company'    => $raw['company'] !== '',
                'has_department' => $raw['department'] !== '',
                'has_website'    => $raw['website'] !== '',
                'has_linkedin'   => $raw['linkedin'] !== '',
                'has_twitter'    => $raw['twitter'] !== '',
                'has_address'    => $has_address,
                'has_social'     => $has_social,
            ]
        );
    }

    /**
     * Returns a formatted address string for a team member.
     *
     * Format: "Street, City, State ZIP, Country" with empty parts omitted.
     * Returns an empty string if no address fields are populated.
     *
     * @param int $post_id The team member post ID.
     * @return string
     */
    public static function get_formatted_address( int $post_id ): string {
        $data = self::get_member_data( $post_id );
        return self::get_formatted_address_from_data( $data );
    }

    /**
     * Returns a formatted address string from an existing member data array.
     *
     * @param array<string, mixed> $data Member data array from get_member_data().
     * @return string
     */
    public static function get_formatted_address_from_data( array $data ): string {
        $address = $data['address'] ?? [];

        $street  = $address['street'] ?? '';
        $city    = $address['city'] ?? '';
        $state   = $address['state'] ?? '';
        $zip     = $address['zip'] ?? '';
        $country = $address['country'] ?? '';

        if ( '' === $street && '' === $city && '' === $state && '' === $zip && '' === $country ) {
            return '';
        }

        $parts = [];

        if ( '' !== $street ) {
            $parts[] = $street;
        }

        // Combine city, state, and ZIP into one segment.
        $city_state_zip = '';
        if ( '' !== $city ) {
            $city_state_zip .= $city;
        }
        if ( '' !== $state ) {
            $city_state_zip .= ( '' !== $city_state_zip ? ', ' : '' ) . $state;
        }
        if ( '' !== $zip ) {
            $city_state_zip .= ( '' !== $city_state_zip ? ' ' : '' ) . $zip;
        }
        if ( '' !== $city_state_zip ) {
            $parts[] = $city_state_zip;
        }

        if ( '' !== $country ) {
            $parts[] = $country;
        }

        return implode( ', ', $parts );
    }

    /**
     * Returns an array of populated social link entries for a team member.
     *
     * Empty platforms are omitted. Each entry contains 'platform', 'url', 'label'.
     *
     * @param int $post_id The team member post ID.
     * @return array<int, array{platform: string, url: string, label: string}>
     */
    public static function get_social_links( int $post_id ): array {
        $links = [];

        foreach ( self::SOCIAL_PLATFORMS as $platform => $config ) {
            $url = (string) get_post_meta( $post_id, $config['key'], true );
            if ( '' === $url ) {
                continue;
            }
            $links[] = [
                'platform' => $platform,
                'url'      => $url,
                'label'    => $config['label'],
            ];
        }

        return $links;
    }
}
