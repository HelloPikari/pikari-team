<?php
/**
 * Classic Editor meta box.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Meta_Box {

    private const NONCE_ACTION = 'pikari_team_meta_save';
    private const NONCE_FIELD  = 'pikari_team_meta_nonce';

    private const INPUT_TYPES = [
        'pikari_team_email'    => 'email',
        'pikari_team_phone'    => 'tel',
        'pikari_team_cell'     => 'tel',
        'pikari_team_website'  => 'url',
        'pikari_team_linkedin' => 'url',
        'pikari_team_twitter'  => 'url',
    ];

    private const FIELD_GROUPS = [
        'Personal' => [
            'pikari_team_first_name'  => 'First Name',
            'pikari_team_last_name'   => 'Last Name',
            'pikari_team_designation' => 'Designation',
            'pikari_team_job_title'   => 'Job Title',
            'pikari_team_email'       => 'Email',
            'pikari_team_phone'       => 'Phone',
            'pikari_team_cell'        => 'Cell',
        ],
        'Company'  => [
            'pikari_team_company'    => 'Company',
            'pikari_team_department' => 'Department',
            'pikari_team_website'    => 'Website',
        ],
        'Address'  => [
            'pikari_team_address_street'  => 'Street',
            'pikari_team_address_city'    => 'City',
            'pikari_team_address_state'   => 'State/Province',
            'pikari_team_address_zip'     => 'ZIP/Postal Code',
            'pikari_team_address_country' => 'Country',
        ],
        'Social'   => [
            'pikari_team_linkedin' => 'LinkedIn URL',
            'pikari_team_twitter'  => 'Twitter/X URL',
        ],
    ];

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
        add_action( 'save_post_pikari_team_member', [ $this, 'save_meta' ] );
    }

    public function register_meta_box(): void {
        if ( function_exists( 'use_block_editor_for_post_type' )
            && use_block_editor_for_post_type( 'pikari_team_member' ) ) {
            return;
        }

        add_meta_box(
            'pikari-team-meta',
            __( 'Team Member Details', 'pikari-team' ),
            [ $this, 'render_meta_box' ],
            'pikari_team_member',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ): void {
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

        foreach ( self::FIELD_GROUPS as $group_label => $fields ) {
            echo '<fieldset class="pikari-team-fieldset"><legend><strong>';
            echo esc_html( $group_label );
            echo '</strong></legend>';

            foreach ( $fields as $key => $label ) {
                $value      = get_post_meta( $post->ID, $key, true );
                $input_type = self::INPUT_TYPES[ $key ] ?? 'text';
                $required   = Validation::is_required( $key );
                echo '<p>';
                echo '<label for="' . esc_attr( $key ) . '">';
                echo esc_html( $label );
                if ( $required ) {
                    echo ' <span class="pikari-team-required" aria-label="' . esc_attr__( 'required', 'pikari-team' ) . '">*</span>';
                }
                echo '</label><br>';
                echo '<input type="' . esc_attr( $input_type ) . '" id="' . esc_attr( $key ) . '" ';
                echo 'name="' . esc_attr( $key ) . '" ';
                echo 'value="' . esc_attr( $value ) . '" class="widefat">';
                echo '</p>';
            }

            echo '</fieldset>';
        }
    }

    public function save_meta( int $post_id ): void {
        if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
            return;
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );

        if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        foreach ( self::FIELD_GROUPS as $fields ) {
            foreach ( $fields as $key => $label ) {
                $value = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
                update_post_meta( $post_id, $key, $value );
            }
        }

        Validation::maybe_add_admin_notice( $post_id );
    }
}
