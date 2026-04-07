<?php
/**
 * Validation utilities for team member meta fields.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Validation {

    /**
     * Required field keys.
     */
    private const REQUIRED_FIELDS = [
        'pikari_team_first_name',
        'pikari_team_last_name',
        'pikari_team_email',
    ];

    /**
     * Human-readable labels for all known meta fields.
     */
    private const FIELD_LABELS = [
        'pikari_team_first_name'    => 'First Name',
        'pikari_team_last_name'     => 'Last Name',
        'pikari_team_designation'   => 'Designation',
        'pikari_team_job_title'     => 'Job Title',
        'pikari_team_email'         => 'Email',
        'pikari_team_phone'         => 'Phone',
        'pikari_team_cell'          => 'Cell',
        'pikari_team_company'       => 'Company',
        'pikari_team_department'    => 'Department',
        'pikari_team_website'       => 'Website',
        'pikari_team_address_street' => 'Street',
        'pikari_team_address_city'  => 'City',
        'pikari_team_address_state' => 'State/Province',
        'pikari_team_address_zip'   => 'ZIP/Postal Code',
        'pikari_team_address_country' => 'Country',
        'pikari_team_linkedin'      => 'LinkedIn URL',
        'pikari_team_twitter'       => 'Twitter/X URL',
    ];

    /**
     * Minimum number of digits for a valid phone number.
     */
    private const PHONE_MIN_DIGITS = 7;

    /**
     * Returns the list of required field keys.
     *
     * @return string[]
     */
    public static function get_required_fields(): array {
        return self::REQUIRED_FIELDS;
    }

    /**
     * Checks whether a given field key is required.
     *
     * @param string $field_key The meta field key.
     * @return bool
     */
    public static function is_required( string $field_key ): bool {
        return in_array( $field_key, self::REQUIRED_FIELDS, true );
    }

    /**
     * Returns a human-readable label for a field key.
     *
     * @param string $field_key The meta field key.
     * @return string The label, or the raw key if not found.
     */
    public static function get_field_label( string $field_key ): string {
        return self::FIELD_LABELS[ $field_key ] ?? $field_key;
    }

    /**
     * Validates a phone number string.
     *
     * Empty strings are considered valid (phone is optional).
     * Otherwise, strips non-digit characters and checks for minimum length.
     *
     * @param string $phone The phone number to validate.
     * @return bool
     */
    public static function validate_phone( string $phone ): bool {
        if ( '' === $phone ) {
            return true;
        }

        $digits = preg_replace( '/\D/', '', $phone );

        return strlen( $digits ) >= self::PHONE_MIN_DIGITS;
    }

    /**
     * Returns the keys of required fields that are missing or empty in the given meta array.
     *
     * @param array $meta Associative array of field key => value.
     * @return string[] Missing field keys.
     */
    public static function get_missing_required_fields( array $meta ): array {
        $missing = [];

        foreach ( self::REQUIRED_FIELDS as $field ) {
            if ( empty( $meta[ $field ] ) ) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Checks required fields for a post and adds an admin notice if any are missing.
     *
     * Skips posts that are not of the `pikari_team_member` post type.
     *
     * @param int $post_id The post ID to check.
     * @return void
     */
    public static function maybe_add_admin_notice( int $post_id ): void {
        if ( 'pikari_team_member' !== get_post_type( $post_id ) ) {
            return;
        }

        $meta = [];
        foreach ( self::REQUIRED_FIELDS as $field ) {
            $meta[ $field ] = (string) get_post_meta( $post_id, $field, true );
        }

        $missing = self::get_missing_required_fields( $meta );

        if ( empty( $missing ) ) {
            return;
        }

        $labels = array_map( [ self::class, 'get_field_label' ], $missing );

        add_action(
            'admin_notices',
            function () use ( $labels ) {
                printf(
                    '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                    esc_html(
                        sprintf(
                            /* translators: %s: comma-separated list of missing field labels */
                            __( 'Team member saved, but the following required fields are missing: %s', 'pikari-team' ),
                            implode( ', ', $labels )
                        )
                    )
                );
            }
        );
    }
}
