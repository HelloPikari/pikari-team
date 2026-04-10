<?php
/**
 * Single team member template.
 *
 * Renders within the active theme using get_header()/get_footer().
 * Uses the hookable Card_Renderer for the card content.
 *
 * Theme developers can override this by creating
 * single-pikari_team_member.php in their theme.
 *
 * @package pikari-team
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="main" class="pikari-team-single">
    <?php
    while ( have_posts() ) :
        the_post();

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Card_Renderer callbacks.
        echo \Pikari\Team\Card_Renderer::render( get_the_ID(), 'single' );

        // Display post content (biography) if present.
        $content = get_the_content();
        if ( $content ) :
            ?>
            <div class="pikari-team-single__bio">
            <?php the_content(); ?>
            </div>
            <?php
        endif;

    endwhile;
    ?>
</main>

<?php
get_footer();
