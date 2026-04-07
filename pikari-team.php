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

// Composer autoloader (loads third-party libraries such as chillerlan/php-qrcode).
require_once PIKARI_TEAM_DIR . 'vendor/autoload.php';

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

/**
 * Initialize the plugin.
 */
function pikari_team_init() {
    load_plugin_textdomain( 'pikari-team', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    require_once PIKARI_TEAM_DIR . 'includes/template-tag-functions.php';

    new \Pikari\Team\Settings();
    new \Pikari\Team\Post_Type();
    new \Pikari\Team\Meta_Box();
    new \Pikari\Team\Block_Bindings();
    new \Pikari\Team\Template();
    new \Pikari\Team\Template_Parts();
    new \Pikari\Team\VCard();
    new \Pikari\Team\QR_Code();
    new \Pikari\Team\PWA();
    new \Pikari\Team\Shortcode();

    // Register the card embed block on init (register_block_type requires init).
    add_action(
        'init',
        function () {
            $block_dir = PIKARI_TEAM_DIR . 'build/blocks/card';
            if ( file_exists( $block_dir ) ) {
                register_block_type( $block_dir );
            }
        }
    );
}
add_action( 'plugins_loaded', 'pikari_team_init' );

/**
 * Flush rewrite rules on activation.
 */
function pikari_team_activate() {
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'pikari_team_activate' );

/**
 * Flush rewrite rules on deactivation.
 */
function pikari_team_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pikari_team_deactivate' );
