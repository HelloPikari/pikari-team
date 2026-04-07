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

$post_id     = get_the_ID();
$data        = \Pikari\Team\Template_Tags::get_member_data( $post_id );
$settings    = get_option( 'pikari_team_settings', [] );
$brand_color = $settings['brand_color'] ?? '#0073aa';
$url_base    = $settings['url_base'] ?? 'card';
$slug        = get_post_field( 'post_name', $post_id );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $data['full_name'] ); ?> — <?php echo esc_html( $data['company'] ); ?></title>

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
        <?php
        /**
         * Filters additional CSS to inline in the standalone card template.
         *
         * @param string $css  Additional CSS string (empty by default).
         * @param array  $data Structured member data.
         */
        $custom_css = apply_filters( 'pikari_team_card_css', '', $data );
        if ( $custom_css ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS from filter.
            echo $custom_css;
        }
        ?>
    </style>

    <?php
    /**
     * Fires inside the <head> of the standalone card template.
     *
     * Allows injecting custom meta tags, links, or styles.
     *
     * @param array $data Structured member data.
     */
    do_action( 'pikari_team_card_head', $data );
    ?>
</head>
<body class="pikari-team-card-page">
    <div class="pikari-team-card">
        <?php
        $sections = [ 'header', 'contact', 'address', 'social', 'qr', 'footer' ];
        foreach ( $sections as $section ) {
            /**
             * Fires to render a card section in the standalone PWA template.
             *
             * @param array  $data    Structured member data.
             * @param string $context Always 'standalone' for this template.
             */
            do_action( 'pikari_team_card_' . $section, $data, 'standalone' );
        }
        ?>
    </div>

    <script>
        <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static JS path components from plugin settings.
        echo 'var pikariSwUrl = "/' . esc_js( $url_base ) . '/' . esc_js( $slug ) . '/sw.js";';
        ?>

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
