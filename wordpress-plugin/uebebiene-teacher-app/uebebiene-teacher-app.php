<?php
/**
 * Plugin Name: ÜbeBiene Teacher App
 * Plugin URI: https://marsrakete.de/
 * Description: Liefert die ÜbeBiene Lehrkräfte-App als eigenständige PWA innerhalb von WordPress aus.
 * Version: 0.1.27
 * Author: Marsrakete
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: uebebiene-teacher-app
 */

if (!defined('ABSPATH')) {
  exit;
}

define('UEBEBIENE_TEACHER_APP_VERSION', '0.1.27');
define('UEBEBIENE_TEACHER_APP_FILE', __FILE__);
define('UEBEBIENE_TEACHER_APP_PATH', plugin_dir_path(__FILE__));
define('UEBEBIENE_TEACHER_APP_URL', plugin_dir_url(__FILE__));

require_once UEBEBIENE_TEACHER_APP_PATH . 'includes/class-uebebiene-teacher-app.php';
require_once UEBEBIENE_TEACHER_APP_PATH . 'includes/class-uebebiene-teacher-app-routes.php';
require_once UEBEBIENE_TEACHER_APP_PATH . 'includes/class-uebebiene-teacher-app-admin.php';

register_activation_hook(UEBEBIENE_TEACHER_APP_FILE, ['Uebebiene_Teacher_App', 'activate']);
register_deactivation_hook(UEBEBIENE_TEACHER_APP_FILE, ['Uebebiene_Teacher_App', 'deactivate']);

Uebebiene_Teacher_App::instance();




























