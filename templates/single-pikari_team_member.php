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

<main id="main" class="site-main pikari-team-single">
    <?php
    while ( have_posts() ) :
        the_post();

        // Card HTML is escaped in the Card_Renderer callbacks.
        $pikari_team_card = \Pikari\Team\Card_Renderer::render( get_the_ID(), 'single' );
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <div class="entry-content">
        <?php echo $pikari_team_card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <?php if ( get_the_content() ) : ?>
                    <div class="pikari-team-single__bio">
            <?php the_content(); ?>
                    </div>
        <?php endif; ?>
            </div>
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php
get_footer();
