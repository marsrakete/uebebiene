<?php

if (!defined('ABSPATH')) {
  exit;
}

final class Uebebiene_Learner_App {
  public const APP_SLUG = 'uebebiene';
  public const SETTINGS_OPTION = 'uebebiene_learner_app_settings';

  private static ?self $instance = null;
  private Uebebiene_Learner_App_Routes $routes;
  private Uebebiene_Learner_App_Admin $admin;

  public static function instance(): self {
    if (!self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  public static function activate(): void {
    self::instance()->routes->register_rewrite_rules();
    flush_rewrite_rules();
  }

  public static function deactivate(): void {
    flush_rewrite_rules();
  }

  private function __construct() {
    $this->routes = new Uebebiene_Learner_App_Routes();
    $this->admin = new Uebebiene_Learner_App_Admin($this->routes);

    add_action('init', [$this->routes, 'register_rewrite_rules']);
    add_filter('query_vars', [$this->routes, 'register_query_vars']);
    add_filter('redirect_canonical', [$this->routes, 'disable_canonical_redirect_for_app'], 10, 2);
    add_action('template_redirect', [$this->routes, 'maybe_serve_request']);
    add_action('admin_init', [$this->admin, 'register_settings']);
    add_action('admin_init', [$this->admin, 'maybe_handle_actions']);
    add_action('admin_menu', [$this->admin, 'register_menu']);
  }
}
