<?php
/**
 * Plugin Name: Pikari Team
 * Plugin URI:  https://pikari.io
 * Description: Team member CPT with digital business cards, PWA-enabled card pages, vCard QR codes, and customizable card templates
 * Version:     0.1.0
 * Author:      Pikari Inc.
 * Author URI:  https://pikari.io
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pikari-team
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.2
 *
 * @package pikari-team
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin version.
 */
define( 'PIKARI_TEAM_VERSION', '0.1.0' );

/**
 * Plugin directory path.
 */
define( 'PIKARI_TEAM_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'PIKARI_TEAM_URL', plugin_dir_url( __FILE__ ) );

// Autoloader for plugin classes.
spl_autoload_register(
    function ( $class ) {
        $prefix   = 'Pikari\Team\\';
        $base_dir = PIKARI_TEAM_DIR . 'includes/';

        $len = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            return;
        }

        $relative_class = substr( $class, $len );
        $file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

        if ( file_exists( $file ) ) {
            require $file;
        }
    }
);
