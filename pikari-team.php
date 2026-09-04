<?php
/**
 * Plugin Name: Pikari Team
 * Plugin URI:  https://pikari.io
 * Description: Team member CPT with digital business cards, PWA-enabled card pages, vCard QR codes, and customizable card templates
 * Version:     1.0.1
 * Author:      Pikari Inc.
 * Author URI:  https://pikari.io
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pikari-team
 * Domain Path: /languages
 * Requires at least: 6.8
 * Tested up to:      7.1
 * Requires PHP: 8.4
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
define( 'PIKARI_TEAM_VERSION', '1.0.1' );

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

    \Pikari\Team\Card_Renderer::register_defaults();

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
 * Check for plugin updates via GitHub releases.
 */
$pikari_team_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/HelloPikari/pikari-team/',
    __FILE__,
    'pikari-team'
);
$pikari_team_vcs_api = $pikari_team_update_checker->getVcsApi();
$pikari_team_vcs_api->enableReleaseAssets(
    '/pikari-team.*\.zip/',
    // The VCS API's minor-version namespace (e.g. v5p6) isn't guaranteed by
    // a `use` alias, so REQUIRE_RELEASE_ASSETS is read off the concrete
    // instance rather than hardcoded, to survive a plugin-update-checker
    // point release without silently falling back to PREFER_RELEASE_ASSETS.
    constant( get_class( $pikari_team_vcs_api ) . '::REQUIRE_RELEASE_ASSETS' )
);

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
