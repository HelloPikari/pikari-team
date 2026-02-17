<?php
/**
 * Custom Post Type registration.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Post_Type {

    private const META_FIELDS = [
        'pikari_team_first_name',
        'pikari_team_last_name',
        'pikari_team_designation',
        'pikari_team_job_title',
        'pikari_team_email',
        'pikari_team_phone',
        'pikari_team_cell',
        'pikari_team_company',
        'pikari_team_department',
        'pikari_team_website',
        'pikari_team_address_street',
        'pikari_team_address_city',
        'pikari_team_address_state',
        'pikari_team_address_zip',
        'pikari_team_address_country',
        'pikari_team_linkedin',
        'pikari_team_twitter',
    ];

    public function __construct() {
        add_action( 'init', [ $this, 'register' ] );
    }

    public function register(): void {
        $settings = get_option( 'pikari_team_settings', [] );
        $label    = $settings['admin_label'] ?? 'Team Members';
        $singular = rtrim( $label, 's' );

        $labels = [
            'name'               => $label,
            'singular_name'      => $singular,
            'menu_name'          => $label,
            'add_new'            => __( 'Add New', 'pikari-team' ),
            'add_new_item'       => sprintf(
                /* translators: %s: singular label */
                __( 'Add New %s', 'pikari-team' ),
                $singular
            ),
            'edit_item'          => sprintf(
                /* translators: %s: singular label */
                __( 'Edit %s', 'pikari-team' ),
                $singular
            ),
            'new_item'           => sprintf(
                /* translators: %s: singular label */
                __( 'New %s', 'pikari-team' ),
                $singular
            ),
            'view_item'          => sprintf(
                /* translators: %s: singular label */
                __( 'View %s', 'pikari-team' ),
                $singular
            ),
            'all_items'          => sprintf(
                /* translators: %s: plural label */
                __( 'All %s', 'pikari-team' ),
                $label
            ),
            'search_items'       => sprintf(
                /* translators: %s: plural label */
                __( 'Search %s', 'pikari-team' ),
                $label
            ),
            'not_found'          => sprintf(
                /* translators: %s: plural label */
                __( 'No %s found.', 'pikari-team' ),
                strtolower( $label )
            ),
            'not_found_in_trash' => sprintf(
                /* translators: %s: plural label */
                __( 'No %s found in Trash.', 'pikari-team' ),
                strtolower( $label )
            ),
        ];

        register_post_type(
            'pikari_team_member',
            [
                'labels'       => $labels,
                'public'       => true,
                'has_archive'  => false,
                'show_in_rest' => true,
                'supports'     => [ 'title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes' ],
                'menu_icon'    => 'dashicons-id-alt',
                'template'     => [
                    [ 'core/post-featured-image' ],
                    [
                        'core/heading',
                        [
                            'metadata' => [
                                'bindings' => [
                                    'content' => [
                                        'source' => 'pikari-team/meta',
                                        'args'   => [ 'key' => 'pikari_team_first_name' ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'core/paragraph',
                        [
                            'metadata' => [
                                'bindings' => [
                                    'content' => [
                                        'source' => 'pikari-team/meta',
                                        'args'   => [ 'key' => 'pikari_team_job_title' ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->register_meta_fields();
    }

    private function register_meta_fields(): void {
        foreach ( self::META_FIELDS as $field ) {
            register_post_meta(
                'pikari_team_member',
                $field,
                [
                    'type'              => 'string',
                    'single'            => true,
                    'show_in_rest'      => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ]
            );
        }
    }

    public static function get_meta_fields(): array {
        return self::META_FIELDS;
    }
}
