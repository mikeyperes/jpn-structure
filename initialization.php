<?php
/**
 * Plugin Name: Hexa JPN Tools
 * Description: JPN event management, host tools, WordPress integration, and Code.Hexa event receipts.
 * Version: 2.0.0
 * Requires PHP: 8.1
 * Author: Michael Peres
 * Plugin URI: https://github.com/mikeyperes/jpn-structure
 * Author URI: https://michaelperes.com
 * GitHub Plugin URI: https://github.com/mikeyperes/jpn-structure
 * GitHub Branch: main
 * Text Domain: hexa-jpn-tools
 */

defined('ABSPATH') || exit;

define('HEXA_JPN_VERSION', '2.0.0');
define('HEXA_JPN_PLUGIN_FILE', __FILE__);
define('HEXA_JPN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HEXA_JPN_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HEXA_JPN_PLUGIN_DIR . 'src/Autoloader.php';

\Hexa\Jpn\Autoloader::register(HEXA_JPN_PLUGIN_DIR . 'src');

register_activation_hook(HEXA_JPN_PLUGIN_FILE, [\Hexa\Jpn\Migrations\Migration::class, 'activate']);
register_deactivation_hook(HEXA_JPN_PLUGIN_FILE, [\Hexa\Jpn\Migrations\Migration::class, 'deactivate']);

\Hexa\Jpn\Plugin::boot();
