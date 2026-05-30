<?php
/**
 * Plugin Name: ÜbeBiene Sync Bridge
 * Plugin URI: https://schwoabamunzee.marsrakete.de/
 * Description: Zentrale Synchronisations- und Verwaltungsbrücke für ÜbeBiene mit Lehrkräften, Lernenden, Unterrichten, Klassen, Kärtchen und Berichten.
 * Version: 0.27.12
 * Author: Marsrakete
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: uebebiene-sync-bridge
 */

if (!defined('ABSPATH')) {
  exit;
}

define('UEBEBIENE_SYNC_BRIDGE_VERSION', '0.27.12');
define('UEBEBIENE_SYNC_BRIDGE_FILE', __FILE__);
define('UEBEBIENE_SYNC_BRIDGE_PATH', plugin_dir_path(__FILE__));
define('UEBEBIENE_SYNC_BRIDGE_URL', plugin_dir_url(__FILE__));

require_once UEBEBIENE_SYNC_BRIDGE_PATH . 'includes/class-uebebiene-sync-bridge-repository.php';
require_once UEBEBIENE_SYNC_BRIDGE_PATH . 'includes/class-uebebiene-sync-bridge-rest.php';
require_once UEBEBIENE_SYNC_BRIDGE_PATH . 'includes/class-uebebiene-sync-bridge-admin.php';
require_once UEBEBIENE_SYNC_BRIDGE_PATH . 'includes/class-uebebiene-sync-bridge.php';

register_activation_hook(UEBEBIENE_SYNC_BRIDGE_FILE, ['Uebebiene_Sync_Bridge', 'activate']);
register_deactivation_hook(UEBEBIENE_SYNC_BRIDGE_FILE, ['Uebebiene_Sync_Bridge', 'deactivate']);

Uebebiene_Sync_Bridge::instance();





