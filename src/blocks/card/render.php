<?php
/**
 * Server-side rendering for the pikari-team/card block.
 *
 * @package pikari-team
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 */

$post_id = \Pikari\Team\Shortcode::resolve_post_id( $attributes, $block->context );

if ( 'full' === ( $attributes['view'] ?? 'embed' ) ) {
    // Password-protected members keep their contact details behind the form.
    if ( $post_id && ! post_password_required( $post_id ) ) {
        // The full card is escaped inside the Card_Renderer section callbacks.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo \Pikari\Team\Card_Renderer::render( $post_id, 'single' );
    }
    return;
}

// Card HTML is built with proper escaping inside render_card().
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo \Pikari\Team\Shortcode::render_card( $post_id );
