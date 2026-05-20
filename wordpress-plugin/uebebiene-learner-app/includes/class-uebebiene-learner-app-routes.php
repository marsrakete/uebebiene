<?php

if (!defined('ABSPATH')) {
  exit;
}

class Uebebiene_Learner_App_Routes {
  private const QUERY_FLAG = 'uebebiene_learner_app';
  private const QUERY_ASSET = 'uebebiene_learner_asset';

  public function register_rewrite_rules(): void {
    add_rewrite_rule('^' . Uebebiene_Learner_App::APP_SLUG . '/?$', 'index.php?' . self::QUERY_FLAG . '=1', 'top');
    add_rewrite_rule('^' . Uebebiene_Learner_App::APP_SLUG . '/(.+)$', 'index.php?' . self::QUERY_FLAG . '=1&' . self::QUERY_ASSET . '=$matches[1]', 'top');
  }

  public function register_query_vars(array $vars): array {
    $vars[] = self::QUERY_FLAG;
    $vars[] = self::QUERY_ASSET;
    return $vars;
  }

  public function disable_canonical_redirect_for_app($redirect_url, $requested_url) {
    if ((string) get_query_var(self::QUERY_FLAG) === '1') {
      return false;
    }

    $request_path = wp_parse_url($requested_url, PHP_URL_PATH);
    $app_path = wp_parse_url($this->get_app_url(), PHP_URL_PATH);
    if (
      is_string($request_path)
      && is_string($app_path)
      && str_starts_with(trailingslashit($request_path), trailingslashit($app_path))
    ) {
      return false;
    }

    return $redirect_url;
  }

  public function maybe_serve_request(): void {
    if ((string) get_query_var(self::QUERY_FLAG) !== '1') {
      return;
    }

    $asset = trim((string) get_query_var(self::QUERY_ASSET));
    if ($asset === '') {
      $this->serve_shell();
      return;
    }

    if ($asset === 'manifest.webmanifest') {
      $this->serve_manifest();
      return;
    }

    if ($asset === 'sw.js') {
      $this->serve_service_worker();
      return;
    }

    $this->serve_static_asset($asset);
  }

  public function get_plugin_settings(): array {
    $settings = get_option(Uebebiene_Learner_App::SETTINGS_OPTION, []);
    return is_array($settings) ? $settings : [];
  }

  public function get_app_url(): string {
    return trailingslashit(home_url('/' . Uebebiene_Learner_App::APP_SLUG . '/'));
  }

  public function get_manifest_url(): string {
    return $this->get_app_url() . 'manifest.webmanifest';
  }

  public function get_service_worker_url(): string {
    return $this->get_app_url() . 'sw.js';
  }

  public function get_sync_base_url(): string {
    $settings = $this->get_plugin_settings();
    $configured = trim((string) ($settings['sync_base_url'] ?? ''));
    if ($configured !== '') {
      return untrailingslashit($configured);
    }

    $bridge_settings = get_option('uebebiene_sync_bridge_settings', []);
    $bridge_configured = is_array($bridge_settings) ? trim((string) ($bridge_settings['sync_base_url'] ?? '')) : '';
    if ($bridge_configured !== '') {
      return untrailingslashit($bridge_configured);
    }

    return untrailingslashit(rest_url('uebebiene-sync/v1'));
  }

  public function get_sync_base_url_source(): string {
    $settings = $this->get_plugin_settings();
    if (trim((string) ($settings['sync_base_url'] ?? '')) !== '') {
      return 'Lernenden-App-Plugin';
    }

    $bridge_settings = get_option('uebebiene_sync_bridge_settings', []);
    if (is_array($bridge_settings) && trim((string) ($bridge_settings['sync_base_url'] ?? '')) !== '') {
      return 'Sync Bridge';
    }

    return 'WordPress-Standard (rest_url)';
  }

  public function get_teacher_app_url(): string {
    $settings = $this->get_plugin_settings();
    $configured = trim((string) ($settings['teacher_app_url'] ?? ''));
    if ($configured !== '') {
      return untrailingslashit($configured) . '/';
    }

    return trailingslashit(home_url('/uebebiene-teacher/'));
  }

  public function get_teacher_app_url_source(): string {
    $settings = $this->get_plugin_settings();
    if (trim((string) ($settings['teacher_app_url'] ?? '')) !== '') {
      return 'Lernenden-App-Plugin';
    }

    return 'WordPress-Standard';
  }

  public function get_runtime_config(): array {
    return [
      'mode' => 'wordpress',
      'appKind' => 'learner',
      'appUrl' => $this->get_app_url(),
      'teacherAppUrl' => $this->get_teacher_app_url(),
      'shareUrl' => $this->get_app_url(),
      'syncBaseUrl' => $this->get_sync_base_url(),
      'manifestUrl' => $this->get_manifest_url(),
      'serviceWorkerUrl' => $this->get_service_worker_url(),
      'assetBaseUrl' => $this->get_app_url(),
      'icon512Url' => $this->get_app_url() . 'icons/icon-512.png',
      'qrShareUrl' => $this->get_app_url(),
      'isCanonicalHost' => true,
    ];
  }

  private function send_common_headers(string $content_type): void {
    status_header(200);
    nocache_headers();
    header('Content-Type: ' . $content_type);
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
  }

  private function serve_shell(): void {
    $this->send_common_headers('text/html; charset=utf-8');
    $runtime_config = $this->get_runtime_config();
    require UEBEBIENE_LEARNER_APP_PATH . 'templates/learner-app-shell.php';
    exit;
  }

  private function serve_manifest(): void {
    $this->send_common_headers('application/manifest+json; charset=utf-8');

    $app_url = $this->get_app_url();
    $manifest = [
      'id' => wp_parse_url($app_url, PHP_URL_PATH) ?: '/' . Uebebiene_Learner_App::APP_SLUG . '/',
      'name' => 'ÜbeBiene',
      'short_name' => 'ÜbeBiene',
      'description' => 'Lernenden-PWA für ÜbeBiene mit Timer, Berichten, Kärtchen und Kopplung.',
      'start_url' => $app_url,
      'scope' => $app_url,
      'display' => 'standalone',
      'background_color' => '#fffaf3',
      'theme_color' => '#f26f3d',
      'lang' => 'de-DE',
      'icons' => [
        [
          'src' => $app_url . 'icons/icon-192.png',
          'sizes' => '192x192',
          'type' => 'image/png',
          'purpose' => 'any',
        ],
        [
          'src' => $app_url . 'icons/icon-512.png',
          'sizes' => '512x512',
          'type' => 'image/png',
          'purpose' => 'any',
        ],
      ],
    ];

    echo wp_json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
  }

  private function serve_service_worker(): void {
    $this->send_common_headers('application/javascript; charset=utf-8');

    $app_path = wp_parse_url($this->get_app_url(), PHP_URL_PATH) ?: '/' . Uebebiene_Learner_App::APP_SLUG . '/';
    $version = UEBEBIENE_LEARNER_APP_VERSION;
    $assets = [
      './',
      './app.js',
      './vendor-qrcodejs.js',
      './vendor-jsQR.js',
      './styles.css',
      './version.js',
      './manifest.webmanifest',
      './icons/favicon-16.png',
      './icons/favicon-32.png',
      './icons/apple-touch-icon.png',
      './icons/icon-192.png',
      './icons/icon-512.png',
    ];

    $network_first = [
      '/',
      '/app.js',
      '/vendor-qrcodejs.js',
      '/vendor-jsQR.js',
      '/styles.css',
      '/version.js',
      '/manifest.webmanifest',
    ];

    $assets_json = wp_json_encode($assets, JSON_UNESCAPED_SLASHES);
    $network_json = wp_json_encode($network_first, JSON_UNESCAPED_SLASHES);
    $app_path_json = wp_json_encode(untrailingslashit($app_path), JSON_UNESCAPED_SLASHES);

    echo "const CACHE_NAME = 'uebebiene-learner-app-{$version}';\n";
    echo "const APP_PATH = {$app_path_json};\n";
    echo "const APP_ASSETS = {$assets_json};\n";
    echo "const NETWORK_FIRST_FILES = {$network_json};\n";
    echo <<<'JS'
function withinScope(url) {
  return url.origin === self.location.origin && url.pathname.startsWith(APP_PATH);
}

function shouldUseNetworkFirst(request) {
  const url = new URL(request.url);
  if (!withinScope(url)) {
    return false;
  }

  if (request.mode === 'navigate') {
    return true;
  }

  const relativePath = url.pathname.slice(APP_PATH.length) || '/';
  return NETWORK_FIRST_FILES.includes(relativePath);
}

async function networkFirst(request) {
  const cache = await caches.open(CACHE_NAME);
  try {
    const networkResponse = await fetch(request, { cache: 'no-store' });
    if (networkResponse && networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    throw error;
  }
}

async function cacheFirst(request) {
  const cachedResponse = await caches.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }

  const networkResponse = await fetch(request);
  const cloned = networkResponse.clone();
  caches.open(CACHE_NAME).then((cache) => cache.put(request, cloned));
  return networkResponse;
}

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)),
      ),
    ).then(() => self.clients.claim()),
  );
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil((async () => {
    const allClients = await clients.matchAll({ type: 'window', includeUncontrolled: true });
    const matchingClient = allClients.find((client) => {
      try {
        return new URL(client.url).pathname.includes(APP_PATH);
      } catch {
        return false;
      }
    });

    if (matchingClient) {
      await matchingClient.focus();
      return;
    }

    if (clients.openWindow) {
      await clients.openWindow('./');
    }
  })());
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(shouldUseNetworkFirst(event.request) ? networkFirst(event.request) : cacheFirst(event.request));
});
JS;
    exit;
  }

  private function serve_static_asset(string $asset): void {
    $normalized = trim(str_replace('\\', '/', $asset), '/');
    $allowed = [
      'app.js' => ['file' => 'assets/app.js', 'type' => 'application/javascript; charset=utf-8'],
      'styles.css' => ['file' => 'assets/styles.css', 'type' => 'text/css; charset=utf-8'],
      'version.js' => ['file' => 'assets/version.js', 'type' => 'application/javascript; charset=utf-8'],
      'vendor-qrcodejs.js' => ['file' => 'assets/vendor-qrcodejs.js', 'type' => 'application/javascript; charset=utf-8'],
      'vendor-jsQR.js' => ['file' => 'assets/vendor-jsQR.js', 'type' => 'application/javascript; charset=utf-8'],
      'icons/favicon-16.png' => ['file' => 'assets/icons/favicon-16.png', 'type' => 'image/png'],
      'icons/favicon-32.png' => ['file' => 'assets/icons/favicon-32.png', 'type' => 'image/png'],
      'icons/apple-touch-icon.png' => ['file' => 'assets/icons/apple-touch-icon.png', 'type' => 'image/png'],
      'icons/icon-192.png' => ['file' => 'assets/icons/icon-192.png', 'type' => 'image/png'],
      'icons/icon-512.png' => ['file' => 'assets/icons/icon-512.png', 'type' => 'image/png'],
    ];

    if (!isset($allowed[$normalized])) {
      status_header(404);
      exit;
    }

    $target = UEBEBIENE_LEARNER_APP_PATH . $allowed[$normalized]['file'];
    if (!file_exists($target)) {
      status_header(404);
      exit;
    }

    $this->send_common_headers($allowed[$normalized]['type']);
    readfile($target);
    exit;
  }
}


