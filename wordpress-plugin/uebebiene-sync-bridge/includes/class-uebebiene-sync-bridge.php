<?php

if (!defined('ABSPATH')) {
  exit;
}

final class Uebebiene_Sync_Bridge {
  private const PLUGIN_VERSION_OPTION = 'uebebiene_sync_bridge_plugin_version';
  private static ?Uebebiene_Sync_Bridge $instance = null;

  private Uebebiene_Sync_Bridge_Repository $repository;
  private Uebebiene_Sync_Bridge_Admin $admin;
  private Uebebiene_Sync_Bridge_Rest $rest;

  public static function instance(): Uebebiene_Sync_Bridge {
    if (!self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  public static function activate(): void {
    $repository = new Uebebiene_Sync_Bridge_Repository();
    $repository->install();
    $repository->ensure_default_settings();
    update_option(self::PLUGIN_VERSION_OPTION, UEBEBIENE_SYNC_BRIDGE_VERSION);

    if (!wp_next_scheduled('uebebiene_sync_bridge_cleanup_reports')) {
      wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'uebebiene_sync_bridge_cleanup_reports');
    }
  }

  public static function deactivate(): void {
    wp_clear_scheduled_hook('uebebiene_sync_bridge_cleanup_reports');
  }

  private function __construct() {
    $this->repository = new Uebebiene_Sync_Bridge_Repository();
    $this->admin = new Uebebiene_Sync_Bridge_Admin($this->repository);
    $this->rest = new Uebebiene_Sync_Bridge_Rest($this->repository);

    add_action('plugins_loaded', [$this, 'load_textdomain']);
    add_action('init', [$this, 'bootstrap']);
    add_action('admin_menu', [$this->admin, 'register_menu']);
    add_action('admin_init', [$this->admin, 'handle_actions']);
    add_action('rest_api_init', [$this->rest, 'register_routes']);
    add_action('uebebiene_sync_bridge_cleanup_reports', [$this, 'cleanup_reports']);
    add_shortcode('uebebiene_app_info', [$this, 'render_public_app_info_shortcode']);
    add_shortcode('uebebiene_teacher_info', [$this, 'render_public_teacher_info_shortcode']);
    add_shortcode('uebebiene_parent_info', [$this, 'render_public_parent_info_shortcode']);
  }

  public function load_textdomain(): void {
    load_plugin_textdomain('uebebiene-sync-bridge', false, dirname(plugin_basename(UEBEBIENE_SYNC_BRIDGE_FILE)) . '/languages');
  }

  public function bootstrap(): void {
    $stored_version = (string) get_option(self::PLUGIN_VERSION_OPTION, '');
    if ($stored_version !== UEBEBIENE_SYNC_BRIDGE_VERSION) {
      $this->repository->install();
      $this->repository->ensure_default_settings();
      update_option(self::PLUGIN_VERSION_OPTION, UEBEBIENE_SYNC_BRIDGE_VERSION);
      return;
    }

    $this->repository->ensure_default_settings();
  }

  public function cleanup_reports(): void {
    $settings = $this->repository->get_settings();
    $retention_days = max(30, (int) ($settings['retention_days'] ?? 180));
    $this->repository->cleanup_old_reports($retention_days);
  }

  private function enqueue_public_info_assets(): void {
    wp_enqueue_style(
      'uebebiene-sync-bridge-public-info',
      plugins_url('assets/public-shortcodes.css', UEBEBIENE_SYNC_BRIDGE_FILE),
      [],
      UEBEBIENE_SYNC_BRIDGE_VERSION
    );
    wp_enqueue_script(
      'uebebiene-sync-bridge-qrcode',
      plugins_url('assets/vendor-qrcodejs.js', UEBEBIENE_SYNC_BRIDGE_FILE),
      [],
      UEBEBIENE_SYNC_BRIDGE_VERSION,
      true
    );
    static $assets_enqueued = false;
    if (!$assets_enqueued) {
      wp_add_inline_script(
        'uebebiene-sync-bridge-qrcode',
        "document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.uebebiene-qr-mount[data-qr-text]').forEach(function(mount){if(mount.dataset.qrReady==='1'){return;}var text=mount.getAttribute('data-qr-text')||'';mount.innerHTML='';if(!text||typeof QRCode==='undefined'){return;}new QRCode(mount,{text:text,width:280,height:280,correctLevel:QRCode.CorrectLevel.M});mount.dataset.qrReady='1';});});",
        'after'
      );
      $assets_enqueued = true;
    }
  }

  public function render_public_app_info_shortcode($atts = []): string {
    $this->enqueue_public_info_assets();

    $settings = $this->repository->get_settings();
    $app_url = esc_url($this->repository->get_resolved_learner_app_url());
    $cards_image_url = esc_url(plugins_url('assets/readme-practice-cards.svg', UEBEBIENE_SYNC_BRIDGE_FILE));
    $site_label = esc_html($settings['site_label'] ?? get_bloginfo('name'));

    ob_start();
    ?>
    <section class="uebebiene-public-info">
      <header class="uebebiene-public-info__hero">
        <div class="uebebiene-public-info__copy">
          <p class="uebebiene-public-info__eyebrow">ÜbeBiene für Lernende</p>
          <h2>Einfach starten. Ruhig üben. Verbunden bleiben.</h2>
          <p>ÜbeBiene hilft Musiklernenden dabei, Übezeit sichtbar zu machen, kleine Fortschritte festzuhalten und mit der Lehrkraft verbunden zu bleiben, ohne dass die App nach Kontrolle aussieht.</p>
          <div class="uebebiene-public-info__actions">
            <a class="uebebiene-public-info__button" href="<?php echo $app_url; ?>">Lernenden-App öffnen</a>
            <span class="uebebiene-public-info__tag"><?php echo $site_label; ?></span>
          </div>
          <ol class="uebebiene-public-info__steps">
            <li><strong>App öffnen oder installieren.</strong> Am schnellsten per QR-Code oder direkt über den Link.</li>
            <li><strong>Mit der Lehrkraft verbinden.</strong> In der App einfach <em>Mit Lehrkraft verbinden</em> wählen.</li>
            <li><strong>Lernenden-ID und Verbindungscode eingeben.</strong> Oder direkt den Kopplungs-QR der Lehrkraft scannen.</li>
          </ol>
        </div>
        <aside class="uebebiene-public-info__qr">
          <div class="uebebiene-public-info__qr-code uebebiene-qr-mount" data-qr-text="<?php echo esc_attr($app_url); ?>" role="img" aria-label="QR-Code zur Lernenden-App"></div>
          <p>QR-Code mit dem Smartphone scannen oder den Link direkt auf dem Gerät öffnen.</p>
          <code><?php echo esc_html($app_url); ?></code>
        </aside>
      </header>

      <div class="uebebiene-public-info__sections">
        <section>
          <p class="uebebiene-public-info__eyebrow">Was Lernende erwartet</p>
          <figure class="uebebiene-public-info__cards-visual">
            <img src="<?php echo $cards_image_url; ?>" alt="Drei beispielhafte ÜbeBiene-Kärtchen mit Fortschritt, Freischaltung und direkter Vergabe" loading="lazy" />
          </figure>
          <div class="uebebiene-public-info__grid">
            <article>
              <h3>Kurze Einträge</h3>
              <p>Minuten, Schwerpunkt und auf Wunsch eine kleine Notiz reichen völlig aus.</p>
            </article>
            <article>
              <h3>Sichtbarer Fortschritt</h3>
              <p>Serien, Rückblicke und kleine Ziel-Kärtchen machen Üben greifbarer.</p>
            </article>
            <article>
              <h3>Ruhige Begleitung</h3>
              <p>ÜbeBiene will motivieren, nicht kontrollieren. Der Ton bleibt bewusst freundlich und unaufgeregt.</p>
            </article>
          </div>
        </section>

        <section>
          <p class="uebebiene-public-info__eyebrow">Ablauf mit der Lehrkraft</p>
          <div class="uebebiene-public-info__grid">
            <article>
              <h3>Vor dem ersten Unterricht</h3>
              <p>Die Lehrkraft richtet das passende Profil ein und teilt Lernenden-ID plus Verbindungscode oder direkt den Kopplungs-QR.</p>
            </article>
            <article>
              <h3>Nach der Verbindung</h3>
              <p>Name, Profil, Instrument, Tagesziel und passende Kärtchen werden aus dem Profil auf das Gerät übernommen.</p>
            </article>
            <article>
              <h3>Im Alltag</h3>
              <p>Lernende üben wie gewohnt, tragen kurz ein und synchronisieren den Stand später mit dem Server.</p>
            </article>
          </div>
        </section>

        <section>
          <p class="uebebiene-public-info__eyebrow">Häufige Fragen</p>
          <div class="uebebiene-public-info__faq">
            <details>
              <summary>Muss ich sofort ein Konto anlegen?</summary>
              <p>Nein. Für den Einstieg reicht die Verbindung mit Lernenden-ID und Verbindungscode aus dem Unterrichtskontext.</p>
            </details>
            <details>
              <summary>Kann ich ÜbeBiene auf mehreren Geräten nutzen?</summary>
              <p>Für einen Gerätewechsel empfiehlt sich immer zuerst ein Backup oder die erneute Verbindung über die Lehrkraft.</p>
            </details>
            <details>
              <summary>Was mache ich, wenn QR-Code oder Code nicht funktionieren?</summary>
              <p>Bitte die Lernenden-ID und den Verbindungscode noch einmal prüfen oder die Lehrkraft um einen neuen Kopplungs-QR bitten.</p>
            </details>
          </div>
        </section>
      </div>
    </section>
    <?php
    return trim((string) ob_get_clean());
  }

  public function render_public_teacher_info_shortcode($atts = []): string {
    $this->enqueue_public_info_assets();

    $settings = $this->repository->get_settings();
    $teacher_url = esc_url($this->repository->get_resolved_teacher_app_url());
    $site_label = esc_html($settings['site_label'] ?? get_bloginfo('name'));

    ob_start();
    ?>
    <section class="uebebiene-public-info uebebiene-public-info--teacher">
      <header class="uebebiene-public-info__hero">
        <div class="uebebiene-public-info__copy">
          <p class="uebebiene-public-info__eyebrow">ÜbeBiene im Unterricht</p>
          <h2>Drei Bausteine. Ein ruhiger Unterrichtsfluss.</h2>
          <p>ÜbeBiene verbindet Lehrkräfte-App, WordPress-Plugin und Lernenden-App so, dass Profile, Ziele, Berichte und Rückmeldungen an einem Ort zusammenlaufen, ohne im Alltag kompliziert zu werden.</p>
          <div class="uebebiene-public-info__actions">
            <a class="uebebiene-public-info__button uebebiene-public-info__button--dark" href="<?php echo $teacher_url; ?>">Lehrkräfte-App öffnen</a>
            <span class="uebebiene-public-info__tag"><?php echo $site_label; ?></span>
          </div>
        </div>
        <aside class="uebebiene-public-info__stack">
          <article>
            <h3>1. Lehr&shy;kräfte-App</h3>
            <p>Klassen, Lernende, Profile, Kärtchen und Kopplungsdaten im Alltag pflegen.</p>
          </article>
          <article>
            <h3>2. WordPress-Plug&shy;in</h3>
            <p>Zentrale Datenhaltung für Zuordnungen, Berichte, Feedback und Verwaltung.</p>
          </article>
          <article>
            <h3>3. Ler&shy;nenden-App</h3>
            <p>Einträge, Fortschritt, Kärtchen und Kopplung aus Sicht der Lernenden.</p>
          </article>
        </aside>
      </header>

      <div class="uebebiene-public-info__sections">
        <section>
          <p class="uebebiene-public-info__eyebrow">Zusammenspiel</p>
          <div class="uebebiene-public-info__grid">
            <article>
              <h3>Profile anlegen</h3>
              <p>In der Lehrkräfte-App entstehen pro Unterrichtskontext eigene Profile, zum Beispiel Klavier, Violine oder Gesang.</p>
            </article>
            <article>
              <h3>Mit dem Plugin synchronisieren</h3>
              <p>Das Plugin speichert Lehrkräfte, Profile, Klassen, Ziele und Rückmeldungen als gemeinsame Wahrheit.</p>
            </article>
            <article>
              <h3>Lernende koppeln</h3>
              <p>Über Lernenden-ID und Verbindungscode oder direkt per QR-Code kommt das richtige Profil aufs Gerät.</p>
            </article>
            <article>
              <h3>Im Unterricht nutzen</h3>
              <p>Berichte, Kärtchen und Feedback lassen sich später wieder in der Lehrkräfte-App oder im Plugin auswerten.</p>
            </article>
          </div>
        </section>

        <section>
          <p class="uebebiene-public-info__eyebrow">Typischer Ablauf</p>
          <ol class="uebebiene-public-info__timeline">
            <li><strong>Schritt 1</strong><span>Lehrkraft legt in der Lehrkräfte-App die lernende Person und das passende Profil an und synchronisiert beides mit dem Plugin.</span></li>
            <li><strong>Schritt 2</strong><span>Die Lernenden-App wird installiert und über Verbindungscode oder QR mit genau diesem Profil gekoppelt.</span></li>
            <li><strong>Schritt 3</strong><span>Lernende tragen ihr Üben ein, Ziele und Kärtchen werden sichtbar und die Daten laufen beim nächsten Sync wieder zurück.</span></li>
            <li><strong>Schritt 4</strong><span>Lehrkräfte nutzen Berichte, letzte Einträge und anonymes Feedback als Gesprächsgrundlage im Unterricht.</span></li>
          </ol>
        </section>

        <section>
          <p class="uebebiene-public-info__eyebrow">Warum dieses Modell?</p>
          <div class="uebebiene-public-info__grid">
            <article>
              <h3>Klare Unter&shy;richts&shy;kontexte</h3>
              <p>Eine Person kann mehrere Profile haben. Dadurch bleiben Instrumente, Lehrkräfte und Ziele sauber getrennt.</p>
            </article>
            <article>
              <h3>Weniger Medien&shy;bruch</h3>
              <p>Nicht alles läuft über Dateien. Alltagssync, Kopplung und Rückmeldungen passieren über denselben Server.</p>
            </article>
            <article>
              <h3>Ausbaufähig</h3>
              <p>Das Plugin kann später weitere Rollen, Rechte, Auswertungen und Organisationslogik aufnehmen, ohne den App-Kern zu verkomplizieren.</p>
            </article>
          </div>
        </section>
      </div>
    </section>
    <?php
    return trim((string) ob_get_clean());
  }

  public function render_public_parent_info_shortcode($atts = []): string {
    $this->enqueue_public_info_assets();

    $settings = $this->repository->get_settings();
    $app_url = esc_url($this->repository->get_resolved_learner_app_url());
    $site_label = esc_html($settings['site_label'] ?? get_bloginfo('name'));

    ob_start();
    ?>
    <section class="uebebiene-public-info uebebiene-public-info--parents">
      <header class="uebebiene-public-info__hero">
        <div class="uebebiene-public-info__copy">
          <p class="uebebiene-public-info__eyebrow">ÜbeBiene für Eltern</p>
          <h2>Üben begleiten, ohne Druck aufzubauen.</h2>
          <p>ÜbeBiene hilft dabei, kleine Übemomente sichtbar zu machen. Die App soll nicht kontrollieren, sondern ein ruhiger Gesprächsanlass zwischen Kind, Elternhaus und Unterricht sein.</p>
          <div class="uebebiene-public-info__actions">
            <a class="uebebiene-public-info__button uebebiene-public-info__button--green" href="<?php echo $app_url; ?>">Lernenden-App öffnen</a>
            <span class="uebebiene-public-info__tag"><?php echo $site_label; ?></span>
          </div>
        </div>
        <aside class="uebebiene-public-info__qr">
          <div class="uebebiene-public-info__qr-code uebebiene-qr-mount" data-qr-text="<?php echo esc_attr($app_url); ?>" role="img" aria-label="QR-Code zur Lernenden-App"></div>
          <p>Die App kann direkt geöffnet oder per QR-Code auf dem Smartphone gestartet werden.</p>
          <code><?php echo esc_html($app_url); ?></code>
        </aside>
      </header>

      <div class="uebebiene-public-info__sections">
        <section>
          <p class="uebebiene-public-info__eyebrow">Was ÜbeBiene im Alltag tut</p>
          <div class="uebebiene-public-info__grid">
            <article>
              <h3>Kleine Einträge statt großer Hürden</h3>
              <p>Nach dem Üben reichen oft schon Minuten, Schwerpunkt und eine kurze Notiz.</p>
            </article>
            <article>
              <h3>Fortschritt wird sichtbarer</h3>
              <p>Serien, Rückblicke und Kärtchen helfen dabei, kleine Schritte wertzuschätzen.</p>
            </article>
            <article>
              <h3>Gespräche werden leichter</h3>
              <p>Die App kann helfen, über Üben konkreter und entspannter zu sprechen.</p>
            </article>
          </div>
        </section>

        <section>
          <p class="uebebiene-public-info__eyebrow">So läuft der Einstieg</p>
          <ol class="uebebiene-public-info__timeline">
            <li><strong>Schritt 1</strong><span>Die Lehrkraft richtet das passende Profil ein und gibt Lernenden-ID plus Verbindungscode oder einen Kopplungs-QR weiter.</span></li>
            <li><strong>Schritt 2</strong><span>Die Lernenden-App wird geöffnet und mit genau diesem Unterrichtsprofil verbunden.</span></li>
            <li><strong>Schritt 3</strong><span>Danach kann das Kind Übemomente eintragen und bei Bedarf mit dem Server synchronisieren.</span></li>
          </ol>
        </section>

        <section>
          <p class="uebebiene-public-info__eyebrow">Häufige Fragen</p>
          <div class="uebebiene-public-info__faq">
            <details>
              <summary>Muss jeden Tag geübt werden?</summary>
              <p>Nein. ÜbeBiene soll Üben sichtbar machen, nicht zusätzlichen Druck aufbauen. Auch kleine Einheiten sind wertvoll.</p>
            </details>
            <details>
              <summary>Kann ein Kind mehrere Profile haben?</summary>
              <p>Ja. Das ist sinnvoll, wenn verschiedene Instrumente oder unterschiedliche Lehrkräfte im Spiel sind.</p>
            </details>
            <details>
              <summary>Ist das eine Kontroll-App?</summary>
              <p>Nein. Die Idee ist eine freundliche Übe-Begleitung mit sichtbaren kleinen Fortschritten und weniger Reibung im Alltag.</p>
            </details>
          </div>
        </section>
      </div>
    </section>
    <?php
    return trim((string) ob_get_clean());
  }
}

