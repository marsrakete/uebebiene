<?php

if (!defined('ABSPATH')) {
  exit;
}

class Uebebiene_Sync_Bridge_Rest {
  private Uebebiene_Sync_Bridge_Repository $repository;

  public function __construct(Uebebiene_Sync_Bridge_Repository $repository) {
    $this->repository = $repository;
    add_filter('rest_pre_serve_request', [$this, 'send_cors_headers'], 10, 4);
  }

  public function register_routes(): void {
    register_rest_route('uebebiene-sync/v1', '/report', [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'receive_report'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/student-sync', [
      'methods' => WP_REST_Server::READABLE,
      'callback' => [$this, 'student_sync'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/student-backup/save', [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'save_student_backup'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/student-backup/latest', [
      'methods' => WP_REST_Server::READABLE,
      'callback' => [$this, 'latest_student_backup'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/push/config', [
      'methods' => WP_REST_Server::READABLE,
      'callback' => [$this, 'push_config'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/push/subscribe', [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'push_subscribe'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/push/unsubscribe', [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'push_unsubscribe'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/push/test', [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'push_test'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/push/timer/schedule', [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'push_timer_schedule'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/push/timer/cancel', [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'push_timer_cancel'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/push/dispatch', [
      'methods' => [WP_REST_Server::READABLE, WP_REST_Server::CREATABLE],
      'callback' => [$this, 'push_dispatch'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/connect-profile', [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'connect_profile'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/teacher-sync', [
      'methods' => WP_REST_Server::READABLE,
      'callback' => [$this, 'teacher_sync'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/teacher-profile-package', [
      'methods' => WP_REST_Server::READABLE,
      'callback' => [$this, 'teacher_profile_package'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/profile-package', [
      'methods' => WP_REST_Server::READABLE,
      'callback' => [$this, 'public_profile_package'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/teacher-cards', [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'save_teacher_cards'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/teacher-card-awards', [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'save_teacher_card_award'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/teacher-roster', [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'save_teacher_roster'],
      'permission_callback' => '__return_true',
    ]);

    register_rest_route('uebebiene-sync/v1', '/feedback-response', [
      'methods' => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'submit_feedback_response'],
      'permission_callback' => '__return_true',
    ]);
  }

  private function with_no_cache_headers(WP_REST_Response $response): WP_REST_Response {
    $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    $response->header('Pragma', 'no-cache');
    $response->header('Expires', '0');
    return $response;
  }

  public function receive_report(WP_REST_Request $request): WP_REST_Response {
    $content_type = (string) $request->get_header('content-type');
    if (stripos($content_type, 'application/json') === false) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Nur application/json ist erlaubt.',
      ], 415);
    }

    $upload_token = sanitize_text_field((string) $request->get_header('x-uebebiene-upload-token'));
    if (!$upload_token) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Upload-Token fehlt.',
      ], 401);
    }

    $profile = $this->repository->get_profile_by_upload_token($upload_token);
    if (!$profile) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Ungültiges Upload-Token.',
      ], 403);
    }

    $payload = $request->get_json_params();
    if (!is_array($payload) || ($payload['kind'] ?? '') !== 'uebebiene-berichtspaket') {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Ungültiges Berichtspaket.',
      ], 400);
    }

    $checksum = sanitize_text_field((string) ($payload['checksum'] ?? ''));
    $expected_checksum_payload = $payload;
    unset($expected_checksum_payload['checksum']);
    $expected_checksum = $this->repository->create_checksum($expected_checksum_payload);
    if (!$checksum || !hash_equals($expected_checksum, $checksum)) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Prüfsumme ungültig.',
      ], 400);
    }

    if (($payload['student']['studentId'] ?? '') !== $profile['app_student_id']) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Bericht gehört nicht zu diesem Profil.',
      ], 409);
    }

    $stored = $this->repository->store_report($payload, $profile);
    return new WP_REST_Response([
      'ok' => true,
      'status' => $stored['duplicate'] ? 'duplicate_ignored' : 'created',
      'reportUuid' => $stored['report_uuid'],
      'studentId' => $profile['app_student_id'],
      'receivedAt' => gmdate('c'),
    ], $stored['duplicate'] ? 200 : 201);
  }

  public function student_sync(WP_REST_Request $request): WP_REST_Response {
    $upload_token = sanitize_text_field((string) $request->get_header('x-uebebiene-upload-token'));
    if (!$upload_token) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Upload-Token fehlt.',
      ], 401);
    }

    $profile = $this->repository->get_profile_by_upload_token($upload_token);
    if (!$profile) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Ungültiges Upload-Token.',
      ], 403);
    }

    return $this->with_no_cache_headers(new WP_REST_Response([
      'ok' => true,
      'snapshot' => $this->repository->get_student_sync_snapshot($profile),
    ], 200));
  }

  public function save_student_backup(WP_REST_Request $request): WP_REST_Response {
    $content_type = (string) $request->get_header('content-type');
    if (stripos($content_type, 'application/json') === false) {
      return $this->error_response('Nur application/json ist erlaubt.', 415);
    }

    $upload_token = sanitize_text_field((string) $request->get_header('x-uebebiene-upload-token'));
    if (!$upload_token) {
      return $this->error_response('Upload-Token fehlt.', 401);
    }

    $profile = $this->repository->get_profile_by_upload_token($upload_token);
    if (!$profile) {
      return $this->error_response('Ungültiges Upload-Token.', 403);
    }

    $payload = $request->get_json_params();
    if (!is_array($payload)) {
      return $this->error_response('Ungültiges Backup.', 400);
    }

    try {
      $stored = $this->repository->store_student_backup($payload, $profile);
    } catch (InvalidArgumentException $exception) {
      $message = match ($exception->getMessage()) {
        'ungueltiges-backup' => 'Ungültiges Backup.',
        'ungueltige-pruefsumme' => 'Prüfsumme ungültig.',
        'profil-passt-nicht' => 'Backup gehört nicht zu diesem Profil.',
        default => 'Server-Backup konnte nicht geprüft werden.',
      };

      return $this->error_response($message, $exception->getMessage() === 'profil-passt-nicht' ? 409 : 400);
    } catch (Throwable $exception) {
      return $this->error_response('Server-Backup konnte nicht gespeichert werden.', 500);
    }

    return new WP_REST_Response([
      'ok' => true,
      'status' => $stored['duplicate'] ? 'duplicate_ignored' : 'created',
      'backupUuid' => $stored['backup_uuid'],
      'studentId' => $profile['app_student_id'],
      'savedAt' => $stored['saved_at'],
    ], $stored['duplicate'] ? 200 : 201);
  }

  public function latest_student_backup(WP_REST_Request $request): WP_REST_Response {
    $upload_token = sanitize_text_field((string) $request->get_header('x-uebebiene-upload-token'));
    if (!$upload_token) {
      return $this->error_response('Upload-Token fehlt.', 401);
    }

    $profile = $this->repository->get_profile_by_upload_token($upload_token);
    if (!$profile) {
      return $this->error_response('Ungültiges Upload-Token.', 403);
    }

    $backup = $this->repository->get_latest_student_backup($profile);
    if (!$backup) {
      return $this->with_no_cache_headers($this->error_response('Noch kein Server-Backup vorhanden.', 404));
    }

    return $this->with_no_cache_headers(new WP_REST_Response([
      'ok' => true,
      'backup' => $backup,
    ], 200));
  }

  /**
   * Liefert die öffentliche Push-Konfiguration für die Lernenden-App.
   *
   * @param WP_REST_Request $request REST-Anfrage.
   * @return WP_REST_Response JSON-Antwort mit VAPID Public Key.
   */
  public function push_config(WP_REST_Request $request): WP_REST_Response {
    $config = $this->repository->get_push_public_config();
    if ((string) ($config['publicKey'] ?? '') === '') {
      return $this->error_response('Push ist auf dem Server noch nicht konfiguriert.', 503);
    }

    return $this->with_no_cache_headers(new WP_REST_Response([
      'ok' => true,
      'push' => $config,
    ], 200));
  }

  /**
   * Speichert eine Push-Subscription für das gekoppelte Lernenden-Gerät.
   *
   * @param WP_REST_Request $request REST-Anfrage mit Upload-Token und Subscription.
   * @return WP_REST_Response JSON-Antwort mit Speicherstatus.
   */
  public function push_subscribe(WP_REST_Request $request): WP_REST_Response {
    $profile = $this->profile_from_upload_token($request);
    if (!$profile) {
      return $this->error_response('Ungültiges Upload-Token.', 403);
    }

    $payload = $request->get_json_params();
    if (!is_array($payload)) {
      return $this->error_response('Push-Subscription fehlt.', 400);
    }

    try {
      $stored = $this->repository->store_push_subscription($profile, $payload);
    } catch (InvalidArgumentException $exception) {
      return $this->error_response('Push-Subscription ist unvollständig.', 400);
    } catch (Throwable $exception) {
      return $this->error_response('Push-Subscription konnte nicht gespeichert werden.', 500);
    }

    return new WP_REST_Response([
      'ok' => true,
      'subscription' => $stored,
    ], 200);
  }

  /**
   * Entfernt eine Push-Subscription für das gekoppelte Lernenden-Gerät.
   *
   * @param WP_REST_Request $request REST-Anfrage mit Upload-Token und Endpoint.
   * @return WP_REST_Response JSON-Antwort mit Entfernen-Status.
   */
  public function push_unsubscribe(WP_REST_Request $request): WP_REST_Response {
    $profile = $this->profile_from_upload_token($request);
    if (!$profile) {
      return $this->error_response('Ungültiges Upload-Token.', 403);
    }

    $payload = $request->get_json_params();
    if (!is_array($payload)) {
      return $this->error_response('Push-Endpunkt fehlt.', 400);
    }

    $endpoint = (string) ($payload['endpoint'] ?? '');
    $changed = $this->repository->deactivate_push_subscription($profile, $endpoint);
    return new WP_REST_Response([
      'ok' => true,
      'changed' => $changed,
    ], 200);
  }

  /**
   * Sendet eine sofortige Test-Push-Nachricht an ein Lernenden-Gerät.
   *
   * @param WP_REST_Request $request REST-Anfrage mit Upload-Token und deviceId.
   * @return WP_REST_Response JSON-Antwort mit Versandstatus.
   */
  public function push_test(WP_REST_Request $request): WP_REST_Response {
    $profile = $this->profile_from_upload_token($request);
    if (!$profile) {
      return $this->error_response('Ungültiges Upload-Token.', 403);
    }

    $payload = $request->get_json_params();
    if (!is_array($payload)) {
      return $this->error_response('Push-Testdaten fehlen.', 400);
    }

    $device_id = sanitize_text_field((string) ($payload['deviceId'] ?? ''));
    $reminder = [
      'student_profile_id' => (int) $profile['id'],
      'device_id' => $device_id,
    ];
    $sent_count = $this->send_push_to_reminder_subscriptions($reminder);
    $status = 404;
    if ($sent_count > 0) {
      $status = 200;
    }

    return new WP_REST_Response([
      'ok' => $sent_count > 0,
      'sentCount' => $sent_count,
    ], $status);
  }

  /**
   * Plant eine Timer-Erinnerung als Web Push.
   *
   * @param WP_REST_Request $request REST-Anfrage mit Upload-Token und Fälligkeitszeit.
   * @return WP_REST_Response JSON-Antwort mit Reminder-UUID.
   */
  public function push_timer_schedule(WP_REST_Request $request): WP_REST_Response {
    $profile = $this->profile_from_upload_token($request);
    if (!$profile) {
      return $this->error_response('Ungültiges Upload-Token.', 403);
    }

    $payload = $request->get_json_params();
    if (!is_array($payload)) {
      return $this->error_response('Push-Erinnerung fehlt.', 400);
    }

    try {
      $stored = $this->repository->schedule_push_timer_reminder($profile, $payload);
    } catch (InvalidArgumentException $exception) {
      if ($exception->getMessage() === 'push-subscription-fehlt') {
        return $this->error_response('Für dieses Gerät ist noch keine Push-Subscription gespeichert.', 409);
      }

      return $this->error_response('Push-Erinnerung ist unvollständig.', 400);
    } catch (Throwable $exception) {
      return $this->error_response('Push-Erinnerung konnte nicht geplant werden.', 500);
    }

    return new WP_REST_Response([
      'ok' => true,
      'reminder' => $stored,
    ], 201);
  }

  /**
   * Bricht geplante Timer-Erinnerungen für ein Gerät ab.
   *
   * @param WP_REST_Request $request REST-Anfrage mit Upload-Token und deviceId.
   * @return WP_REST_Response JSON-Antwort mit Abbruchstatus.
   */
  public function push_timer_cancel(WP_REST_Request $request): WP_REST_Response {
    $profile = $this->profile_from_upload_token($request);
    if (!$profile) {
      return $this->error_response('Ungültiges Upload-Token.', 403);
    }

    $payload = $request->get_json_params();
    if (!is_array($payload)) {
      return $this->error_response('Push-Abbruchdaten fehlen.', 400);
    }

    $device_id = sanitize_text_field((string) ($payload['deviceId'] ?? ''));
    $changed = $this->repository->cancel_push_timer_reminders($profile, $device_id);
    return new WP_REST_Response([
      'ok' => true,
      'changed' => $changed,
    ], 200);
  }

  /**
   * Versendet alle fälligen Push-Erinnerungen.
   *
   * @param WP_REST_Request $request REST-Anfrage mit geheimem Dispatch-Key.
   * @return WP_REST_Response JSON-Antwort mit Anzahl versendeter Erinnerungen.
   */
  public function push_dispatch(WP_REST_Request $request): WP_REST_Response {
    $key = sanitize_text_field((string) ($request->get_param('key') ?? ''));
    if (!$this->repository->verify_push_dispatch_key($key)) {
      return $this->error_response('Dispatch-Key ungültig.', 403);
    }

    $sent = 0;
    $failed = 0;
    $reminders = $this->repository->get_due_push_reminders(50);
    foreach ($reminders as $reminder) {
      $sent_count = $this->send_push_to_reminder_subscriptions($reminder);
      if ($sent_count > 0) {
        $sent += $sent_count;
        $this->repository->mark_push_reminder_dispatched($reminder, true, 'sent');
      } else {
        $failed += 1;
        $this->repository->mark_push_reminder_dispatched($reminder, false, 'no-active-subscription');
      }
    }
    $deleted_count = $this->repository->cleanup_old_push_reminders(14);

    return new WP_REST_Response([
      'ok' => true,
      'reminderCount' => count($reminders),
      'sentCount' => $sent,
      'failedCount' => $failed,
      'deletedOldReminderCount' => $deleted_count,
    ], 200);
  }

  public function connect_profile(WP_REST_Request $request): WP_REST_Response {
    $content_type = (string) $request->get_header('content-type');
    if (stripos($content_type, 'application/json') === false) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Nur application/json ist erlaubt.',
      ], 415);
    }

    $payload = $request->get_json_params();
    if (!is_array($payload)) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Kopplungsdaten fehlen.',
      ], 400);
    }

    $app_student_id = sanitize_text_field((string) ($payload['appStudentId'] ?? ''));
    $connect_code = preg_replace('/\D+/', '', (string) ($payload['connectCode'] ?? ''));
    if ($app_student_id === '' || $connect_code === '') {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lernenden-ID oder Verbindungscode fehlt.',
      ], 400);
    }

    $profile = $this->repository->get_profile_by_connect_credentials($app_student_id, $connect_code);
    if (!$profile) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lernenden-ID oder Verbindungscode ungültig.',
      ], 403);
    }

    return $this->with_no_cache_headers(new WP_REST_Response([
      'ok' => true,
      'snapshot' => $this->repository->get_student_sync_snapshot($profile),
    ], 200));
  }

  public function teacher_sync(WP_REST_Request $request): WP_REST_Response {
    $teacher_key = sanitize_text_field((string) $request->get_header('x-uebebiene-teacher-key'));
    if (!$teacher_key) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lehrkräfte-Key fehlt.',
      ], 401);
    }

    $teacher = $this->repository->get_teacher_by_api_key($teacher_key);
    if (!$teacher) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lehrkräfte-Key ungültig.',
      ], 403);
    }

    return $this->with_no_cache_headers(new WP_REST_Response([
      'ok' => true,
      'snapshot' => $this->repository->get_teacher_sync_snapshot($teacher),
    ], 200));
  }

  public function submit_feedback_response(WP_REST_Request $request): WP_REST_Response {
    $content_type = (string) $request->get_header('content-type');
    if (stripos($content_type, 'application/json') === false) {
      return $this->error_response('Nur application/json ist erlaubt.', 415);
    }

    $payload = $request->get_json_params();
    if (!is_array($payload)) {
      return $this->error_response('Rückmeldung fehlt.');
    }

    $validated = $this->validate_feedback_response_payload($payload);
    if (!$validated['ok']) {
      return $this->error_response($validated['message'], 400);
    }

    try {
      $stored = $this->repository->store_feedback_response(
        $validated['ballotToken'],
        $validated['roundId'],
        $validated['answers']
      );
    } catch (InvalidArgumentException $exception) {
      $message = match ($exception->getMessage()) {
        'feedback-bereits-abgegeben' => 'Diese Rückmeldung wurde bereits abgegeben.',
        'feedback-unvollstaendig' => 'Bitte beantworte alle Fragen.',
        default => 'Die Rückmeldung konnte nicht gespeichert werden.',
      };
      return $this->error_response($message, 409);
    } catch (Throwable $exception) {
      return $this->error_response('Die Rückmeldung konnte gerade nicht gespeichert werden.', 500);
    }

    return new WP_REST_Response([
      'ok' => true,
      'status' => 'recorded',
      'roundId' => $stored['roundId'],
      'submittedAt' => $stored['submittedAt'],
    ], 201);
  }

  public function teacher_profile_package(WP_REST_Request $request): WP_REST_Response {
    $teacher_key = sanitize_text_field((string) $request->get_header('x-uebebiene-teacher-key'));
    if (!$teacher_key) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lehrkräfte-Key fehlt.',
      ], 401);
    }

    $teacher = $this->repository->get_teacher_by_api_key($teacher_key);
    if (!$teacher) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lehrkräfte-Key ungültig.',
      ], 403);
    }

    $student_id = sanitize_text_field((string) ($request->get_param('studentId') ?? ''));
    if ($student_id === '') {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lernenden-ID fehlt.',
      ], 400);
    }

    $profile = $this->repository->get_profile_by_app_student_id($student_id);
    if (!$profile) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Profil nicht gefunden.',
      ], 404);
    }

    $snapshot = $this->repository->get_teacher_sync_snapshot($teacher);
    $allowed_ids = array_map(
      static fn(array $item): string => sanitize_text_field((string) ($item['studentId'] ?? '')),
      is_array($snapshot['students'] ?? null) ? $snapshot['students'] : []
    );
    if (!in_array($student_id, $allowed_ids, true)) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Dieses Profil ist der Lehrkraft nicht zugeordnet.',
      ], 403);
    }

    $package = $this->repository->build_profile_package((int) $profile['id']);
    if (!$package) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Profilpaket konnte nicht erzeugt werden.',
      ], 500);
    }

    $download_url = add_query_arg([
      'appStudentId' => $package['appStudentId'],
      'token' => $package['uploadToken'],
      'download' => '1',
    ], rest_url('uebebiene-sync/v1/profile-package'));

    return new WP_REST_Response([
      'ok' => true,
      'package' => $package,
      'fileName' => $this->build_profile_package_filename($package),
      'downloadUrl' => $download_url,
      'shareUrl' => $download_url,
    ], 200);
  }

  public function public_profile_package(WP_REST_Request $request): WP_REST_Response {
    $app_student_id = sanitize_text_field((string) ($request->get_param('appStudentId') ?? ''));
    $token = sanitize_text_field((string) ($request->get_param('token') ?? ''));
    if ($app_student_id === '' || $token === '') {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'App-Lernenden-ID oder Token fehlt.',
      ], 400);
    }

    $profile = $this->repository->get_profile_by_app_student_id($app_student_id);
    if (!$profile || !hash_equals((string) ($profile['upload_token'] ?? ''), $token)) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Profilfreigabe ungültig.',
      ], 403);
    }

    $package = $this->repository->build_profile_package((int) $profile['id']);
    if (!$package) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Profilpaket konnte nicht erzeugt werden.',
      ], 500);
    }

    $response = new WP_REST_Response($package, 200);
    $response->header('Content-Type', 'application/json; charset=utf-8');
    if ((string) ($request->get_param('download') ?? '') === '1') {
      $response->header('Content-Disposition', 'attachment; filename="' . $this->build_profile_package_filename($package) . '"');
    }
    return $response;
  }

  public function save_teacher_cards(WP_REST_Request $request): WP_REST_Response {
    $content_type = (string) $request->get_header('content-type');
    if (stripos($content_type, 'application/json') === false) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Nur application/json ist erlaubt.',
      ], 415);
    }

    $teacher_key = sanitize_text_field((string) $request->get_header('x-uebebiene-teacher-key'));
    if (!$teacher_key) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lehrkräfte-Key fehlt.',
      ], 401);
    }

    $teacher = $this->repository->get_teacher_by_api_key($teacher_key);
    if (!$teacher) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lehrkräfte-Key ungültig.',
      ], 403);
    }

    $payload = $request->get_json_params();
    if (!is_array($payload) || ($payload['kind'] ?? '') !== 'uebebiene-teacher-karten-sync') {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Ungültiges Kartenpaket.',
      ], 400);
    }

    $cards = is_array($payload['cards'] ?? null) ? $payload['cards'] : null;
    if ($cards === null) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Kartenliste fehlt.',
      ], 400);
    }

    $checksum = sanitize_text_field((string) ($payload['checksum'] ?? ''));
    $expected_checksum_payload = $payload;
    unset($expected_checksum_payload['checksum']);
    $expected_checksum = $this->repository->create_checksum($expected_checksum_payload);
    if (!$checksum || !hash_equals($expected_checksum, $checksum)) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Prüfsumme ungültig.',
      ], 400);
    }

    try {
      $result = $this->repository->sync_teacher_cards($teacher, $cards);
    } catch (Throwable $exception) {
      $message = sanitize_text_field($exception->getMessage());
      return $this->error_response(
        $message !== ''
          ? 'Kärtchen konnten nicht mit dem Server synchronisiert werden. ' . $message
          : 'Kärtchen konnten nicht mit dem Server synchronisiert werden.',
        500
      );
    }

    return new WP_REST_Response([
      'ok' => true,
      'status' => 'saved',
      'count' => $result['count'],
      'savedAt' => $result['savedAt'],
    ], 200);
  }

  public function save_teacher_card_award(WP_REST_Request $request): WP_REST_Response {
    $content_type = (string) $request->get_header('content-type');
    if (stripos($content_type, 'application/json') === false) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Nur application/json ist erlaubt.',
      ], 415);
    }

    $teacher_key = sanitize_text_field((string) $request->get_header('x-uebebiene-teacher-key'));
    if (!$teacher_key) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lehrkräfte-Key fehlt.',
      ], 401);
    }

    $teacher = $this->repository->get_teacher_by_api_key($teacher_key);
    if (!$teacher) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lehrkräfte-Key ungültig.',
      ], 403);
    }

    $payload = $request->get_json_params();
    if (!is_array($payload) || ($payload['kind'] ?? '') !== 'uebebiene-teacher-karten-vergabe') {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Ungültige Kärtchen-Vergabe.',
      ], 400);
    }

    $checksum = sanitize_text_field((string) ($payload['checksum'] ?? ''));
    $expected_checksum_payload = $payload;
    unset($expected_checksum_payload['checksum']);
    $expected_checksum = $this->repository->create_checksum($expected_checksum_payload);
    if (!$checksum || !hash_equals($expected_checksum, $checksum)) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Prüfsumme ungültig.',
      ], 400);
    }

    try {
      if (($payload['action'] ?? 'award') === 'revoke') {
        $this->repository->revoke_card_award($teacher, $payload);
        return new WP_REST_Response([
          'ok' => true,
          'status' => 'revoked',
        ], 200);
      }

      $result = $this->repository->award_card_to_profile($teacher, $payload);
      return new WP_REST_Response([
        'ok' => true,
        'status' => 'awarded',
        'awardId' => $result['awardId'],
        'savedAt' => $result['awardedAt'],
      ], 200);
    } catch (InvalidArgumentException $exception) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => $this->map_award_error_message($exception->getMessage()),
      ], 400);
    } catch (Throwable $exception) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Die Kärtchen-Vergabe konnte serverseitig nicht gespeichert werden.',
      ], 500);
    }
  }

  public function save_teacher_roster(WP_REST_Request $request): WP_REST_Response {
    $content_type = (string) $request->get_header('content-type');
    if (stripos($content_type, 'application/json') === false) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Nur application/json ist erlaubt.',
      ], 415);
    }

    $teacher_key = sanitize_text_field((string) $request->get_header('x-uebebiene-teacher-key'));
    if (!$teacher_key) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lehrkräfte-Key fehlt.',
      ], 401);
    }

    $teacher = $this->repository->get_teacher_by_api_key($teacher_key);
    if (!$teacher) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Lehrkräfte-Key ungültig.',
      ], 403);
    }

    $payload = $request->get_json_params();
    if (!is_array($payload) || ($payload['kind'] ?? '') !== 'uebebiene-teacher-roster-sync') {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Ungültiges Stammdatenpaket.',
      ], 400);
    }

    $checksum = sanitize_text_field((string) ($payload['checksum'] ?? ''));
    $expected_checksum_payload = $payload;
    unset($expected_checksum_payload['checksum']);
    $expected_checksum = $this->repository->create_checksum($expected_checksum_payload);
    if (!$checksum || !hash_equals($expected_checksum, $checksum)) {
      return new WP_REST_Response([
        'ok' => false,
        'message' => 'Prüfsumme ungültig.',
      ], 400);
    }

    $result = $this->repository->sync_teacher_roster($teacher, $payload);

    return new WP_REST_Response([
      'ok' => true,
      'status' => 'saved',
      'classCount' => $result['classCount'],
      'studentCount' => $result['studentCount'],
      'savedAt' => $result['savedAt'],
    ], 200);
  }

  private function map_award_error_message(string $code): string {
    return match ($code) {
      'karte-nicht-gefunden' => 'Dieses Kärtchen wurde auf dem Server nicht gefunden.',
      'profil-nicht-zugeordnet' => 'Dieses Profil ist der Lehrkraft nicht zugeordnet.',
      default => 'Die Kärtchen-Vergabe konnte nicht verarbeitet werden.',
    };
  }

  /**
   * Holt ein Lernenden-Profil anhand des Upload-Tokens aus einer REST-Anfrage.
   *
   * @param WP_REST_Request $request REST-Anfrage.
   * @return array|null Profilzeile oder null.
   */
  private function profile_from_upload_token(WP_REST_Request $request): ?array {
    $upload_token = sanitize_text_field((string) $request->get_header('x-uebebiene-upload-token'));
    if ($upload_token === '') {
      return null;
    }

    $profile = $this->repository->get_profile_by_upload_token($upload_token);
    if (!$profile) {
      return null;
    }

    return $profile;
  }

  /**
   * Sendet einen Push an alle aktiven Subscriptions einer Erinnerung.
   *
   * @param array $reminder Reminder-Zeile oder minimale Reminder-Daten.
   * @return int Anzahl erfolgreich versendeter Pushes.
   */
  private function send_push_to_reminder_subscriptions(array $reminder): int {
    $subscriptions = $this->repository->get_active_push_subscriptions_for_reminder($reminder);
    if (!$subscriptions) {
      return 0;
    }

    $sent_count = 0;
    $vapid = $this->repository->get_push_vapid_config();
    foreach ($subscriptions as $subscription) {
      try {
        $result = Uebebiene_Sync_Bridge_Web_Push::send($subscription, $vapid);
      } catch (Throwable $exception) {
        $result = [
          'ok' => false,
          'status' => 0,
          'message' => $exception->getMessage(),
        ];
      }

      if (!empty($result['ok'])) {
        $sent_count += 1;
      } else {
        $this->repository->record_push_subscription_result($subscription, (int) ($result['status'] ?? 0));
      }
    }

    return $sent_count;
  }

  public function send_cors_headers($served, $result, $request, $server) {
    $route = method_exists($request, 'get_route') ? (string) $request->get_route() : '';
    if (strpos($route, '/uebebiene-sync/v1/') !== 0) {
      return $served;
    }

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Uebebiene-Upload-Token, X-Uebebiene-Teacher-Key');
    header('Access-Control-Expose-Headers: Content-Type');

    if ('OPTIONS' === strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) {
      status_header(200);
      exit;
    }

    return $served;
  }

  private function validate_feedback_response_payload(array $payload): array {
    $round_id = (int) ($payload['roundId'] ?? 0);
    $ballot_token = sanitize_text_field((string) ($payload['ballotToken'] ?? ''));
    $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : null;

    if (!$round_id || $ballot_token === '' || !$answers) {
      return [
        'ok' => false,
        'message' => 'Rückmeldung ist unvollständig.',
      ];
    }

    $normalized_answers = [];
    $seen_question_ids = [];
    foreach ($answers as $answer) {
      if (!is_array($answer)) {
        return [
          'ok' => false,
          'message' => 'Rückmeldung ist unvollständig.',
        ];
      }

      $question_id = (int) ($answer['questionId'] ?? 0);
      $value = (int) ($answer['value'] ?? 0);
      if (!$question_id || $value < 1 || $value > 5) {
        return [
          'ok' => false,
          'message' => 'Bitte alle Antworten auf der Skala 1 bis 5 angeben.',
        ];
      }

      if (isset($seen_question_ids[$question_id])) {
        return [
          'ok' => false,
          'message' => 'Jede Frage darf nur einmal beantwortet werden.',
        ];
      }

      $seen_question_ids[$question_id] = true;
      $normalized_answers[] = [
        'questionId' => $question_id,
        'value' => $value,
      ];
    }

    return [
      'ok' => true,
      'roundId' => $round_id,
      'ballotToken' => $ballot_token,
      'answers' => $normalized_answers,
    ];
  }

  private function error_response(string $message, int $status = 400): WP_REST_Response {
    return new WP_REST_Response([
      'ok' => false,
      'message' => $message,
    ], $status);
  }

  private function build_profile_package_filename(array $package): string {
    $display_name = sanitize_file_name((string) ($package['displayName'] ?? 'schueler'));
    $app_student_id = sanitize_file_name((string) ($package['appStudentId'] ?? 'profil'));
    return 'uebebiene-profil-' . ($display_name ?: 'schueler') . '-' . ($app_student_id ?: 'profil') . '.json';
  }
}
