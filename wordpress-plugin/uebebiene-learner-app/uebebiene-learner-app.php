<?php
/**
 * Plugin Name: ÜbeBiene Learner App
 * Plugin URI: https://marsrakete.de/
 * Description: Liefert die ÜbeBiene Lernenden-App als eigenständige PWA innerhalb von WordPress aus.
 * Version: 0.1.10
 * Author: Marsrakete
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: uebebiene-learner-app
 */

if (!defined('ABSPATH')) {
  exit;
}

define('UEBEBIENE_LEARNER_APP_VERSION', '0.1.10');
define('UEBEBIENE_LEARNER_APP_FILE', __FILE__);
define('UEBEBIENE_LEARNER_APP_PATH', plugin_dir_path(__FILE__));
define('UEBEBIENE_LEARNER_APP_URL', plugin_dir_url(__FILE__));

require_once UEBEBIENE_LEARNER_APP_PATH . 'includes/class-uebebiene-learner-app.php';
require_once UEBEBIENE_LEARNER_APP_PATH . 'includes/class-uebebiene-learner-app-routes.php';
require_once UEBEBIENE_LEARNER_APP_PATH . 'includes/class-uebebiene-learner-app-admin.php';

register_activation_hook(UEBEBIENE_LEARNER_APP_FILE, ['Uebebiene_Learner_App', 'activate']);
register_deactivation_hook(UEBEBIENE_LEARNER_APP_FILE, ['Uebebiene_Learner_App', 'deactivate']);

Uebebiene_Learner_App::instance();












