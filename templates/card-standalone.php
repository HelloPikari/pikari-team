<?php
/**
 * Standalone PWA card page template.
 *
 * Complete HTML document independent of the active theme.
 * Renders a mobile-optimized digital business card.
 *
 * @package pikari-team
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_id   = get_the_ID();
$meta      = get_post_meta( $post_id );
$settings  = get_option( 'pikari_team_settings', [] );

$first_name  = $meta['pikari_team_first_name'][0] ?? '';
$last_name   = $meta['pikari_team_last_name'][0] ?? '';
$full_name   = trim( $first_name . ' ' . $last_name );
$job_title   = $meta['pikari_team_job_title'][0] ?? '';
$email       = $meta['pikari_team_email'][0] ?? '';
$phone       = $meta['pikari_team_phone'][0] ?? '';
$cell        = $meta['pikari_team_cell'][0] ?? '';
$company     = $meta['pikari_team_company'][0] ?? '';
$department  = $meta['pikari_team_department'][0] ?? '';
$website     = $meta['pikari_team_website'][0] ?? '';
$linkedin    = $meta['pikari_team_linkedin'][0] ?? '';
$twitter     = $meta['pikari_team_twitter'][0] ?? '';
$designation = $meta['pikari_team_designation'][0] ?? '';

$brand_color = $settings['brand_color'] ?? '#0073aa';
$url_base    = $settings['url_base'] ?? 'card';
$slug        = get_post_field( 'post_name', $post_id );
$headshot    = get_the_post_thumbnail_url( $post_id, 'medium' );

// Generate QR code if the class is available.
$qr_svg = '';
if ( class_exists( '\Pikari\Team\QR_Code' ) ) {
    $qr = new \Pikari\Team\QR_Code();
    if ( method_exists( $qr, 'generate_qr_svg' ) ) {
        $qr_svg = $qr->generate_qr_svg( $post_id );
    }
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $full_name ); ?> — <?php echo esc_html( $company ); ?></title>

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="<?php echo esc_attr( $brand_color ); ?>">
    <?php
    $logo_id = $settings['company_logo'] ?? 0;
    if ( $logo_id ) {
        $logo_192 = wp_get_attachment_image_url( $logo_id, 'medium' );
        if ( $logo_192 ) :
            ?>
    <link rel="apple-touch-icon" href="<?php echo esc_url( $logo_192 ); ?>">
            <?php
        endif;
    }
    ?>
    <link rel="manifest" href="/<?php echo esc_attr( $url_base ); ?>/<?php echo esc_attr( $slug ); ?>/manifest.json">

    <style>
        <?php
        $css_file = PIKARI_TEAM_DIR . 'assets/css/card.css';
        if ( file_exists( $css_file ) ) {
            // Local trusted CSS file — safe to inline without escaping.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.EscapeOutput.OutputNotEscaped
            echo file_get_contents( $css_file );
        }
        ?>
        :root {
            --pikari-brand-color: <?php echo esc_attr( $brand_color ); ?>;
        }
    </style>
</head>
<body class="pikari-team-card-page">
    <div class="pikari-team-card">
        <header class="pikari-team-card__header" style="background-color: <?php echo esc_attr( $brand_color ); ?>">
            <?php if ( $headshot ) : ?>
                <img
                    class="pikari-team-card__headshot"
                    src="<?php echo esc_url( $headshot ); ?>"
                    alt="<?php echo esc_attr( $full_name ); ?>"
                    width="120"
                    height="120"
                >
            <?php endif; ?>
            <h1 class="pikari-team-card__name"><?php echo esc_html( $full_name ); ?></h1>
            <?php if ( $designation ) : ?>
                <p class="pikari-team-card__designation"><?php echo esc_html( $designation ); ?></p>
            <?php endif; ?>
            <?php if ( $job_title ) : ?>
                <p class="pikari-team-card__title"><?php echo esc_html( $job_title ); ?></p>
            <?php endif; ?>
            <?php if ( $company ) : ?>
                <p class="pikari-team-card__company"><?php echo esc_html( $company ); ?></p>
            <?php endif; ?>
        </header>

        <div class="pikari-team-card__contact">
            <?php if ( $phone ) : ?>
                <a href="tel:<?php echo esc_attr( $phone ); ?>" class="pikari-team-card__link">
                    <span class="pikari-team-card__label"><?php esc_html_e( 'Phone', 'pikari-team' ); ?></span>
                    <span class="pikari-team-card__value"><?php echo esc_html( $phone ); ?></span>
                </a>
            <?php endif; ?>

            <?php if ( $cell ) : ?>
                <a href="tel:<?php echo esc_attr( $cell ); ?>" class="pikari-team-card__link">
                    <span class="pikari-team-card__label"><?php esc_html_e( 'Cell', 'pikari-team' ); ?></span>
                    <span class="pikari-team-card__value"><?php echo esc_html( $cell ); ?></span>
                </a>
            <?php endif; ?>

            <?php if ( $email ) : ?>
                <a href="mailto:<?php echo esc_attr( $email ); ?>" class="pikari-team-card__link">
                    <span class="pikari-team-card__label"><?php esc_html_e( 'Email', 'pikari-team' ); ?></span>
                    <span class="pikari-team-card__value"><?php echo esc_html( $email ); ?></span>
                </a>
            <?php endif; ?>

            <?php if ( $website ) : ?>
                <a href="<?php echo esc_url( $website ); ?>" class="pikari-team-card__link" target="_blank" rel="noopener noreferrer">
                    <span class="pikari-team-card__label"><?php esc_html_e( 'Website', 'pikari-team' ); ?></span>
                    <span class="pikari-team-card__value"><?php echo esc_html( $website ); ?></span>
                </a>
            <?php endif; ?>
        </div>

        <?php if ( $linkedin || $twitter ) : ?>
            <div class="pikari-team-card__social">
            <?php if ( $linkedin ) : ?>
                    <a href="<?php echo esc_url( $linkedin ); ?>" class="pikari-team-card__social-link" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e( 'LinkedIn', 'pikari-team' ); ?>
                    </a>
                <?php endif; ?>
            <?php if ( $twitter ) : ?>
                    <a href="<?php echo esc_url( $twitter ); ?>" class="pikari-team-card__social-link" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e( 'Twitter/X', 'pikari-team' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ( $qr_svg ) : ?>
            <div class="pikari-team-card__qr">
            <?php
            // QR SVG is generated by the plugin, safe to output.
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $qr_svg;
            ?>
            </div>
        <?php endif; ?>

        <div class="pikari-team-card__actions">
            <a href="/<?php echo esc_attr( $url_base ); ?>/<?php echo esc_attr( $slug ); ?>/download.vcf" class="pikari-team-card__save-btn">
                <?php esc_html_e( 'Save Contact', 'pikari-team' ); ?>
            </a>
        </div>
    </div>

    <script>
        <?php
        $sw_file = PIKARI_TEAM_DIR . 'assets/js/sw-register.js';
        if ( file_exists( $sw_file ) ) {
            // Local trusted JS file — safe to inline without escaping.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.EscapeOutput.OutputNotEscaped
            echo file_get_contents( $sw_file );
        }
        ?>
    </script>
</body>
</html>
