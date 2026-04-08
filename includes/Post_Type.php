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
        'pikari_team_card_template',
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

        $slug = $settings['post_type_slug'] ?? 'team';

        $args = [
            'labels'       => $labels,
            'public'       => true,
            'has_archive'  => false,
            'show_in_rest' => true,
            'supports'     => [ 'title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes' ],
            'menu_icon'    => 'dashicons-id-alt',
            'rewrite'      => [
                'slug'       => $slug,
                'with_front' => false,
            ],
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
        ];

        /**
         * Filters the arguments passed to register_post_type() for the pikari_team_member CPT.
         *
         * Allows theme and plugin developers to modify CPT configuration — e.g. to add
         * taxonomy support, change capabilities, or alter rewrite rules — without
         * needing to deregister and re-register the post type.
         *
         * @param array $args The post type registration arguments.
         */
        $args = apply_filters( 'pikari_team_post_type_args', $args );

        register_post_type( 'pikari_team_member', $args );

        $this->register_meta_fields();
    }

    private function register_meta_fields(): void {
        /**
         * Filters the list of meta fields registered for team members.
         *
         * Allows themes and plugins to add or remove meta fields from
         * both REST API registration and the admin meta box.
         *
         * @param string[] $fields Array of meta field keys (prefixed with pikari_team_).
         */
        $fields = apply_filters( 'pikari_team_meta_fields', self::META_FIELDS );

        foreach ( $fields as $field ) {
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
        return apply_filters( 'pikari_team_meta_fields', self::META_FIELDS );
    }
}
