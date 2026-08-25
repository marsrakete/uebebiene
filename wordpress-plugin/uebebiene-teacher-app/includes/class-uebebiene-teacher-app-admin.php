<?php

if (!defined('ABSPATH')) {
  exit;
}

class Uebebiene_Teacher_App_Admin {
  private Uebebiene_Teacher_App_Routes $routes;

  public function __construct(Uebebiene_Teacher_App_Routes $routes) {
    $this->routes = $routes;
  }

  public function register_menu(): void {
    add_menu_page(
      'ÜbeBiene Lehrkräfte-App',
      'ÜbeBiene Teacher App',
      'manage_options',
      'uebebiene-teacher-app',
      [$this, 'render_page'],
      UEBEBIENE_TEACHER_APP_URL . 'assets/menu-icon.svg',
      58
    );
  }

  public function register_settings(): void {
    register_setting(
      'uebebiene_teacher_app',
      Uebebiene_Teacher_App::SETTINGS_OPTION,
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

    $learner_app_url = trim((string) ($input['learner_app_url'] ?? ''));
    if ($learner_app_url !== '') {
      $settings['learner_app_url'] = esc_url_raw(untrailingslashit($learner_app_url) . '/');
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

    if (!isset($_POST['uebebiene_teacher_app_action'])) {
      return;
    }

    check_admin_referer('uebebiene_teacher_app_tools');

    $action = sanitize_key((string) wp_unslash($_POST['uebebiene_teacher_app_action']));
    if ($action !== 'flush_rewrite_rules') {
      return;
    }

    flush_rewrite_rules();
    wp_safe_redirect(add_query_arg('uebebiene_teacher_notice', 'rewrites-flushed', menu_page_url('uebebiene-teacher-app', false)));
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
    $learner_app_url = $this->routes->get_learner_app_url();
    $sync_base_url = $this->routes->get_sync_base_url();
    $learner_source = $this->routes->get_learner_app_url_source();
    $sync_source = $this->routes->get_sync_base_url_source();
    $app_version_info = $this->get_app_version_info();
    $notice = sanitize_key((string) ($_GET['uebebiene_teacher_notice'] ?? ''));

    echo '<div class="wrap">';
    echo '<h1>ÜbeBiene Lehrkräfte-App</h1>';
    echo '<p>Dieses Plugin liefert die Lehrkräfte-App ohne Theme-Rahmen direkt als PWA aus und kann den WordPress-Betrieb gezielt absichern.</p>';

    if ($notice === 'rewrites-flushed') {
      echo '<div class="notice notice-success is-dismissible"><p>Die Rewrite-Regeln wurden neu geschrieben. Bitte die App-URL jetzt noch einmal im Browser prüfen.</p></div>';
    }

    echo '<h2>Betriebsstatus</h2>';
    echo '<table class="widefat striped" style="max-width:980px">';
    echo '<tbody>';
    $rows = [
      'Plugin-Version' => UEBEBIENE_TEACHER_APP_VERSION,
      'App-Version' => $app_version_info['appVersion'],
      'Cache-Version' => $app_version_info['cacheVersion'],
      'App-URL' => $app_url,
      'Manifest-URL' => $manifest_url,
      'Service-Worker-URL' => $sw_url,
      'Lernenden-App-URL für Kopplung und QR' => $learner_app_url,
      'Quelle der Lernenden-App-URL' => $learner_source,
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
    settings_fields('uebebiene_teacher_app');
    echo '<table class="form-table" role="presentation">';
    echo '<tbody>';
    echo '<tr>';
    echo '<th scope="row"><label for="uebebiene-teacher-learner-app-url">Lernenden-App-URL überschreiben</label></th>';
    echo '<td>';
    echo '<input id="uebebiene-teacher-learner-app-url" name="' . esc_attr(Uebebiene_Teacher_App::SETTINGS_OPTION) . '[learner_app_url]" type="url" class="regular-text code" value="' . esc_attr((string) ($settings['learner_app_url'] ?? '')) . '" placeholder="https://deinedomain.tld/uebebiene/" />';
    echo '<p class="description">Optional. Wenn gesetzt, nutzt die Lehrkräfte-App diese URL für Kopplung, Teilen und QR anstelle der Sync-Bridge-Vorgabe.</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row"><label for="uebebiene-teacher-sync-base-url">Sync-Basis-URL überschreiben</label></th>';
    echo '<td>';
    echo '<input id="uebebiene-teacher-sync-base-url" name="' . esc_attr(Uebebiene_Teacher_App::SETTINGS_OPTION) . '[sync_base_url]" type="url" class="regular-text code" value="' . esc_attr((string) ($settings['sync_base_url'] ?? '')) . '" placeholder="https://deinedomain.tld/wp-json/uebebiene-sync/v1" />';
    echo '<p class="description">Optional. Nur setzen, wenn die Lehrkräfte-App bewusst gegen eine andere Sync-Bridge-URL sprechen soll.</p>';
    echo '</td>';
    echo '</tr>';
    echo '</tbody>';
    echo '</table>';
    submit_button('Einstellungen speichern');
    echo '</form>';

    echo '<h2 style="margin-top:24px">Werkzeuge</h2>';
    echo '<form method="post" action="' . esc_url(menu_page_url('uebebiene-teacher-app', false)) . '">';
    wp_nonce_field('uebebiene_teacher_app_tools');
    echo '<input type="hidden" name="uebebiene_teacher_app_action" value="flush_rewrite_rules" />';
    submit_button('Rewrite-Regeln neu schreiben', 'secondary', 'submit', false);
    echo ' <span class="description" style="margin-left:8px">Hilft, wenn die Route <code>/' . esc_html(Uebebiene_Teacher_App::APP_SLUG) . '/</code> nach Aktivierung noch nicht sauber greift.</span>';
    echo '</form>';

    echo '<h2 style="margin-top:24px">Schneller Test</h2>';
    echo '<ol style="padding-left:20px">';
    echo '<li>App-URL öffnen und prüfen, ob die Lehrkräfte-App ohne Theme-Rahmen erscheint.</li>';
    echo '<li>Manifest-URL öffnen und prüfen, ob reines JSON/Webmanifest ausgegeben wird.</li>';
    echo '<li>Service-Worker-URL öffnen und prüfen, ob JavaScript ausgegeben wird.</li>';
    echo '<li>In der App den Kopplungsdialog öffnen und prüfen, ob der QR-Code lokal erscheint.</li>';
    echo '<li>Danach Installation, Einstellungen und Update-Prüfung im echten Browser testen.</li>';
    echo '</ol>';

    echo '<h2 style="margin-top:24px">Empfohlene Cache-Ausschlüsse</h2>';
    echo '<ul style="list-style:disc; padding-left:20px">';
    echo '<li><code>/' . esc_html(Uebebiene_Teacher_App::APP_SLUG) . '/</code></li>';
    echo '<li><code>/' . esc_html(Uebebiene_Teacher_App::APP_SLUG) . '/manifest.webmanifest</code></li>';
    echo '<li><code>/' . esc_html(Uebebiene_Teacher_App::APP_SLUG) . '/sw.js</code></li>';
    echo '<li><code>/' . esc_html(Uebebiene_Teacher_App::APP_SLUG) . '/teacher.js</code></li>';
    echo '<li><code>/' . esc_html(Uebebiene_Teacher_App::APP_SLUG) . '/teacher.css</code></li>';
    echo '</ul>';
    echo '<p>Die Kopplungs-QRs in der Lehrkräfte-App werden lokal erzeugt und verwenden die aktuell wirksame Lernenden-App-URL aus den Plugin-Einstellungen, der Sync Bridge oder dem Fallback.</p>';
    echo '</div>';
  }

  /**
   * Liest die ausgelieferte App- und Cache-Version aus assets/version.js.
   *
   * @param void Keine Parameter.
   * @return array Versionsdaten mit appVersion und cacheVersion.
   */
  private function get_app_version_info(): array {
    $version_file = UEBEBIENE_TEACHER_APP_PATH . 'assets/version.js';
    $source = '';
    if (file_exists($version_file)) {
      $source = (string) file_get_contents($version_file);
    }

    $app_version = 'unbekannt';
    $cache_version = 'unbekannt';
    if (preg_match('/appVersion:\s*"([^"]+)"/', $source, $matches)) {
      $app_version = (string) $matches[1];
    }
    if (preg_match('/cacheVersion:\s*"([^"]+)"/', $source, $matches)) {
      $cache_version = (string) $matches[1];
    }

    return [
      'appVersion' => $app_version,
      'cacheVersion' => $cache_version,
    ];
  }
}
