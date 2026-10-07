<?php
/**
 * Shortcode registration and shared card render function.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Shortcode {

    public function __construct() {
        add_shortcode( 'pikari_team_card', [ $this, 'shortcode_handler' ] );
    }

    public function shortcode_handler( array $atts ): string {
        $atts = shortcode_atts(
            [
                'id'   => 0,
                'slug' => '',
            ],
            $atts,
            'pikari_team_card'
        );

        $post_id = absint( $atts['id'] );

        if ( ! $post_id && ! empty( $atts['slug'] ) ) {
            $posts = get_posts(
                [
                    'post_type'      => Post_Type::CPT_SLUG,
                    'name'           => sanitize_title( $atts['slug'] ),
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                    'fields'         => 'ids',
                ]
            );

            if ( ! empty( $posts ) ) {
                $post_id = $posts[0];
            }
        }

        return self::render_card( $post_id );
    }

    /**
     * Resolve which team member a card block shows.
     *
     * The block's own postId wins. Otherwise fall back to the block context,
     * but only on a team member, so an unconfigured block in a regular post
     * renders nothing rather than a card for the host post.
     *
     * @param array $attributes Block attributes.
     * @param array $context    Block context (postId, postType).
     * @return int Team member post ID, or 0.
     */
    public static function resolve_post_id( array $attributes, array $context ): int {
        $post_id = (int) ( $attributes['postId'] ?? 0 );
        if ( $post_id ) {
            return $post_id;
        }

        if ( Post_Type::CPT_SLUG === ( $context['postType'] ?? '' ) ) {
            return (int) ( $context['postId'] ?? 0 );
        }

        return 0;
    }

    public static function render_card( int $post_id ): string {
        if ( ! $post_id ) {
            return '';
        }

        return Card_Renderer::render( $post_id, 'shortcode' );
    }
}
