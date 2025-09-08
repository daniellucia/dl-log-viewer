<?php

/**
 * Plugin Name:       Log viewer
 * Description:       Easily view log files
 * Version:           0.0.5
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Daniel Lucia
 * Author URI:        http://www.daniellucia.es/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        http://www.daniellucia.es/
 * Text Domain:       dl-log-viewer
 * Domain Path:       /languages
 */



use DL\LogViewer\Plugin;

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

define('DL_LOG_VIEWER_VERSION', '0.0.5');
define('DL_LOG_VIEWER_FILE', __FILE__);

add_action('plugins_loaded', function () {

    load_plugin_textdomain('dl-log-viewer', false, dirname(plugin_basename(DL_LOG_VIEWER_FILE)) . '/languages');

    $plugin = new Plugin();
});
