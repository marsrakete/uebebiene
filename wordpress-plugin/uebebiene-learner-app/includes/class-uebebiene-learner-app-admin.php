<?php

if (!defined('ABSPATH')) {
  exit;
}

class Uebebiene_Learner_App_Admin {
  private Uebebiene_Learner_App_Routes $routes;

  public function __construct(Uebebiene_Learner_App_Routes $routes) {
    $this->routes = $routes;
  }

  public function register_menu(): void {
    add_menu_page(
      'ÜbeBiene Lernenden-App',
      'ÜbeBiene App',
      'manage_options',
      'uebebiene-learner-app',
      [$this, 'render_page'],
      UEBEBIENE_LEARNER_APP_URL . 'assets/menu-icon.svg',
      57
    );
  }

  public function register_settings(): void {
    register_setting(
      'uebebiene_learner_app',
      Uebebiene_Learner_App::SETTINGS_OPTION,
      [
        'type' => 'array',
        'sanitize_callback' => [$this, 'sanitize_settings'],
        'default' => [],
      ]
    );
  }

  public function sanitize_settings($input): array {
    $input = is_array($input) ? $input : [];
    $settings = [];

    $teacher_app_url = trim((string) ($input['teacher_app_url'] ?? ''));
    if ($teacher_app_url !== '') {
      $settings['teacher_app_url'] = esc_url_raw(untrailingslashit($teacher_app_url) . '/');
    }

    $sync_base_url = trim((string) ($input['sync_base_url'] ?? ''));
    if ($sync_base_url !== '') {
      $settings['sync_base_url'] = esc_url_raw(untrailingslashit($sync_base_url));
    }

    return $settings;
  }

  public function maybe_handle_actions(): void {
    if (!current_user_can('manage_options')) {
      return;
    }

    if (!isset($_POST['uebebiene_learner_app_action'])) {
      return;
    }

    check_admin_referer('uebebiene_learner_app_tools');

    $action = sanitize_key((string) wp_unslash($_POST['uebebiene_learner_app_action']));
    if ($action !== 'flush_rewrite_rules') {
      return;
    }

    flush_rewrite_rules();
    wp_safe_redirect(add_query_arg('uebebiene_learner_notice', 'rewrites-flushed', menu_page_url('uebebiene-learner-app', false)));
    exit;
  }

  public function render_page(): void {
    if (!current_user_can('manage_options')) {
      return;
    }

    $settings = $this->routes->get_plugin_settings();
    $app_url = $this->routes->get_app_url();
    $manifest_url = $this->routes->get_manifest_url();
    $sw_url = $this->routes->get_service_worker_url();
    $teacher_app_url = $this->routes->get_teacher_app_url();
    $sync_base_url = $this->routes->get_sync_base_url();
    $teacher_source = $this->routes->get_teacher_app_url_source();
    $sync_source = $this->routes->get_sync_base_url_source();
    $notice = sanitize_key((string) ($_GET['uebebiene_learner_notice'] ?? ''));

    echo '<div class="wrap">';
    echo '<h1>ÜbeBiene Lernenden-App</h1>';
    echo '<p>Dieses Plugin liefert die Lernenden-App ohne Theme-Rahmen direkt als PWA aus und hält Route, Manifest und Service Worker auf derselben WordPress-Domain zusammen.</p>';

    if ($notice === 'rewrites-flushed') {
      echo '<div class="notice notice-success is-dismissible"><p>Die Rewrite-Regeln wurden neu geschrieben. Bitte die App-URL jetzt noch einmal im Browser prüfen.</p></div>';
    }

    echo '<h2>Betriebsstatus</h2>';
    echo '<table class="widefat striped" style="max-width:980px">';
    echo '<tbody>';
    $rows = [
      'App-URL' => $app_url,
      'Manifest-URL' => $manifest_url,
      'Service-Worker-URL' => $sw_url,
      'Lehrkräfte-App-URL' => $teacher_app_url,
      'Quelle der Lehrkräfte-App-URL' => $teacher_source,
      'Sync-Basis-URL' => $sync_base_url,
      'Quelle der Sync-Basis-URL' => $sync_source,
    ];

    foreach ($rows as $label => $value) {
      echo '<tr>';
      echo '<th style="width:260px">' . esc_html($label) . '</th>';
      echo '<td>';
      echo '<code>' . esc_html($value) . '</code>';
      if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
        echo ' <a class="button button-small" href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer" style="margin-left:8px">Öffnen</a>';
      }
      echo '</td>';
      echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    echo '<h2 style="margin-top:24px">Eigene Plugin-Einstellungen</h2>';
    echo '<form method="post" action="options.php" style="max-width:980px">';
    settings_fields('uebebiene_learner_app');
    echo '<table class="form-table" role="presentation">';
    echo '<tbody>';
    echo '<tr>';
    echo '<th scope="row"><label for="uebebiene-learner-teacher-app-url">Lehrkräfte-App-URL überschreiben</label></th>';
    echo '<td>';
    echo '<input id="uebebiene-learner-teacher-app-url" name="' . esc_attr(Uebebiene_Learner_App::SETTINGS_OPTION) . '[teacher_app_url]" type="url" class="regular-text code" value="' . esc_attr((string) ($settings['teacher_app_url'] ?? '')) . '" placeholder="https://deinedomain.tld/uebebiene-teacher/" />';
    echo '<p class="description">Optional. Nützlich, wenn Lernenden-App und Lehrkräfte-App nicht unter den Standardpfaden laufen sollen.</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row"><label for="uebebiene-learner-sync-base-url">Sync-Basis-URL überschreiben</label></th>';
    echo '<td>';
    echo '<input id="uebebiene-learner-sync-base-url" name="' . esc_attr(Uebebiene_Learner_App::SETTINGS_OPTION) . '[sync_base_url]" type="url" class="regular-text code" value="' . esc_attr((string) ($settings['sync_base_url'] ?? '')) . '" placeholder="https://deinedomain.tld/wp-json/uebebiene-sync/v1" />';
    echo '<p class="description">Optional. Nur setzen, wenn die Lernenden-App bewusst gegen eine andere Sync-Bridge-URL sprechen soll.</p>';
    echo '</td>';
    echo '</tr>';
    echo '</tbody>';
    echo '</table>';
    submit_button('Einstellungen speichern');
    echo '</form>';

    echo '<h2 style="margin-top:24px">Werkzeuge</h2>';
    echo '<form method="post" action="' . esc_url(menu_page_url('uebebiene-learner-app', false)) . '">';
    wp_nonce_field('uebebiene_learner_app_tools');
    echo '<input type="hidden" name="uebebiene_learner_app_action" value="flush_rewrite_rules" />';
    submit_button('Rewrite-Regeln neu schreiben', 'secondary', 'submit', false);
    echo ' <span class="description" style="margin-left:8px">Hilft, wenn die Route <code>/' . esc_html(Uebebiene_Learner_App::APP_SLUG) . '/</code> nach Aktivierung noch nicht sauber greift.</span>';
    echo '</form>';

    echo '<h2 style="margin-top:24px">Schneller Test</h2>';
    echo '<ol style="padding-left:20px">';
    echo '<li>App-URL öffnen und prüfen, ob die Lernenden-App ohne Theme-Rahmen erscheint.</li>';
    echo '<li>Manifest-URL öffnen und prüfen, ob reines JSON/Webmanifest ausgegeben wird.</li>';
    echo '<li>Service-Worker-URL öffnen und prüfen, ob JavaScript ohne Redirect ausgegeben wird.</li>';
    echo '<li>QR-Scanner, Einstellungen, Timer und Kopplung im echten Browser testen.</li>';
    echo '<li>Danach Installation und Update-Prüfung als PWA testen.</li>';
    echo '</ol>';

    echo '<h2 style="margin-top:24px">Empfohlene Cache-Ausschlüsse</h2>';
    echo '<ul style="list-style:disc; padding-left:20px">';
    echo '<li><code>/' . esc_html(Uebebiene_Learner_App::APP_SLUG) . '/</code></li>';
    echo '<li><code>/' . esc_html(Uebebiene_Learner_App::APP_SLUG) . '/manifest.webmanifest</code></li>';
    echo '<li><code>/' . esc_html(Uebebiene_Learner_App::APP_SLUG) . '/sw.js</code></li>';
    echo '<li><code>/' . esc_html(Uebebiene_Learner_App::APP_SLUG) . '/app.js</code></li>';
    echo '<li><code>/' . esc_html(Uebebiene_Learner_App::APP_SLUG) . '/styles.css</code></li>';
    echo '<li><code>/' . esc_html(Uebebiene_Learner_App::APP_SLUG) . '/version.js</code></li>';
    echo '</ul>';
    echo '</div>';
  }
}
