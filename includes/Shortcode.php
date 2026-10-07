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
     * The block's own postId wins. The full view falls back to the block
     * context, but only on a team member, so it never renders a card for an
     * unrelated host post. An unconfigured embed block still renders nothing.
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

        $is_full = 'full' === ( $attributes['view'] ?? 'embed' );
        if ( $is_full && Post_Type::CPT_SLUG === ( $context['postType'] ?? '' ) ) {
            return (int) ( $context['postId'] ?? 0 );
        }

        return 0;
    }

    /**
     * Whether a card may be shown for this post.
     *
     * Every card entry point (shortcode, block embed, block full view) runs
     * through this, so a block or shortcode can't expose an unpublished,
     * protected or non-member post. Users who can read the post, such as an
     * editor previewing a draft, still see it. Trash never renders.
     *
     * @param int $post_id Post ID.
     * @return bool True when the card can be rendered.
     */
    public static function can_render( int $post_id ): bool {
        $post = $post_id ? get_post( $post_id ) : null;
        if ( ! $post || Post_Type::CPT_SLUG !== $post->post_type || 'trash' === $post->post_status ) {
            return false;
        }

        if ( ! is_post_publicly_viewable( $post ) && ! current_user_can( 'read_post', $post->ID ) ) {
            return false;
        }

        return ! post_password_required( $post );
    }

    public static function render_card( int $post_id ): string {
        if ( ! self::can_render( $post_id ) ) {
            return '';
        }

        return Card_Renderer::render( $post_id, 'shortcode' );
    }

    /**
     * Render the full single-page card, used by the card block's full view.
     *
     * @param int $post_id Team member post ID.
     * @return string Card HTML, or an empty string.
     */
    public static function render_full_card( int $post_id ): string {
        if ( ! self::can_render( $post_id ) ) {
            return '';
        }

        return Card_Renderer::render( $post_id, 'single' );
    }
}
