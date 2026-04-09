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

$post_id = absint( $attributes['postId'] ?? 0 );

// Card HTML is built with proper escaping inside render_card().
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo \Pikari\Team\Shortcode::render_card( $post_id );
