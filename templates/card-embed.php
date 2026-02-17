<?php
/**
 * Embeddable card partial template.
 *
 * Used by the shortcode and block render callback.
 * Expects $post_id to be defined in the calling scope.
 *
 * @package pikari-team
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$first_name = get_post_meta( $post_id, 'pikari_team_first_name', true );
$last_name  = get_post_meta( $post_id, 'pikari_team_last_name', true );
$full_name  = trim( $first_name . ' ' . $last_name );
$job_title  = get_post_meta( $post_id, 'pikari_team_job_title', true );
$email      = get_post_meta( $post_id, 'pikari_team_email', true );
$phone      = get_post_meta( $post_id, 'pikari_team_phone', true );
$company    = get_post_meta( $post_id, 'pikari_team_company', true );
$headshot   = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
?>
<div class="pikari-team-card-embed">
    <?php if ( $headshot ) : ?>
        <img
            class="pikari-team-card-embed__headshot"
            src="<?php echo esc_url( $headshot ); ?>"
            alt="<?php echo esc_attr( $full_name ); ?>"
            width="80"
            height="80"
        >
    <?php endif; ?>
    <div class="pikari-team-card-embed__info">
        <strong class="pikari-team-card-embed__name"><?php echo esc_html( $full_name ); ?></strong>
        <?php if ( $job_title ) : ?>
            <span class="pikari-team-card-embed__title"><?php echo esc_html( $job_title ); ?></span>
        <?php endif; ?>
        <?php if ( $company ) : ?>
            <span class="pikari-team-card-embed__company"><?php echo esc_html( $company ); ?></span>
        <?php endif; ?>
        <?php if ( $phone ) : ?>
            <a href="tel:<?php echo esc_attr( $phone ); ?>" class="pikari-team-card-embed__phone">
            <?php echo esc_html( $phone ); ?>
            </a>
        <?php endif; ?>
        <?php if ( $email ) : ?>
            <a href="mailto:<?php echo esc_attr( $email ); ?>" class="pikari-team-card-embed__email">
            <?php echo esc_html( $email ); ?>
            </a>
        <?php endif; ?>
    </div>
</div>
