<?php
/**
 * PHPUnit bootstrap file.
 *
 * Loads the Composer autoloader and base TestCase class.
 * Brain\Monkey handles WordPress function mocking — no WordPress installation needed.
 *
 * @package Pikari\Tests
 */

// Load Composer autoloader (provides Brain\Monkey, Mockery, and plugin classes).
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// Define plugin constants normally set by the main plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}
if ( ! defined( 'PIKARI_TEAM_DIR' ) ) {
    define( 'PIKARI_TEAM_DIR', dirname( __DIR__, 2 ) . '/' );
}
if ( ! defined( 'PIKARI_TEAM_VERSION' ) ) {
    define( 'PIKARI_TEAM_VERSION', '0.1.0' );
}
if ( ! defined( 'PIKARI_TEAM_URL' ) ) {
    define( 'PIKARI_TEAM_URL', 'https://example.com/wp-content/plugins/pikari-team/' );
}

// Load the base TestCase class (handles Brain\Monkey setUp/tearDown).
require_once __DIR__ . '/TestCase.php';
