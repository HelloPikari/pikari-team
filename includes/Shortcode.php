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
                    'post_type'      => 'pikari_team_member',
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

    public static function render_card( int $post_id ): string {
        if ( ! $post_id ) {
            return '';
        }

        return Card_Renderer::render( $post_id, 'shortcode' );
    }
}
