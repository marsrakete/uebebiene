<?php

if (!defined('ABSPATH')) {
  exit;
}

class Uebebiene_Sync_Bridge_Web_Push {
  /**
   * Erzeugt ein neues VAPID-Schlüsselpaar für Web Push.
   *
   * @param void Keine Parameter.
   * @return array Array mit publicKey und privateKeyPem.
   */
  public static function generate_vapid_keys(): array {
    $resource = openssl_pkey_new([
      'private_key_type' => OPENSSL_KEYTYPE_EC,
      'curve_name' => 'prime256v1',
    ]);

    if (!$resource) {
      throw new RuntimeException('vapid-key-erzeugung-fehlgeschlagen');
    }

    $private_key_pem = '';
    openssl_pkey_export($resource, $private_key_pem);
    $details = openssl_pkey_get_details($resource);
    if (!is_array($details) || !is_array($details['ec'] ?? null)) {
      throw new RuntimeException('vapid-key-details-fehlen');
    }

    $x = (string) ($details['ec']['x'] ?? '');
    $y = (string) ($details['ec']['y'] ?? '');
    if ($x === '' || $y === '') {
      throw new RuntimeException('vapid-public-key-fehlt');
    }

    return [
      'publicKey' => self::base64url_encode("\x04" . $x . $y),
      'privateKeyPem' => $private_key_pem,
    ];
  }

  /**
   * Sendet einen sichtbaren Web-Push-Anstoß an eine Browser-Subscription.
   *
   * @param array $subscription Browser-Subscription mit endpoint.
   * @param array $vapid VAPID-Konfiguration mit publicKey, privateKeyPem und subject.
   * @return array Versandstatus mit ok, status und message.
   */
  public static function send(array $subscription, array $vapid): array {
    $endpoint = esc_url_raw((string) ($subscription['endpoint'] ?? ''));
    if ($endpoint === '') {
      return [
        'ok' => false,
        'status' => 0,
        'message' => 'Push-Endpunkt fehlt.',
      ];
    }

    $audience = self::build_audience($endpoint);
    $token = self::create_vapid_jwt($audience, $vapid);
    $public_key = (string) ($vapid['publicKey'] ?? '');
    $response = wp_remote_post($endpoint, [
      'timeout' => 15,
      'redirection' => 0,
      'headers' => [
        'Authorization' => 'vapid t=' . $token . ', k=' . $public_key,
        'Crypto-Key' => 'p256ecdsa=' . $public_key,
        'TTL' => '300',
        'Urgency' => 'normal',
        'Content-Length' => '0',
      ],
      'body' => '',
    ]);

    if (is_wp_error($response)) {
      return [
        'ok' => false,
        'status' => 0,
        'message' => $response->get_error_message(),
      ];
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    return [
      'ok' => $status >= 200 && $status < 300,
      'status' => $status,
      'message' => (string) wp_remote_retrieve_response_message($response),
    ];
  }

  /**
   * Baut die Push-Service-Origin aus dem Subscription-Endpunkt.
   *
   * @param string $endpoint Vollständiger Browser-Push-Endpunkt.
   * @return string Origin für den VAPID-Audience-Claim.
   */
  private static function build_audience(string $endpoint): string {
    $parts = wp_parse_url($endpoint);
    $scheme = (string) ($parts['scheme'] ?? 'https');
    $host = (string) ($parts['host'] ?? '');
    $port = '';
    if (isset($parts['port'])) {
      $port = ':' . (string) $parts['port'];
    }

    return $scheme . '://' . $host . $port;
  }

  /**
   * Erstellt ein signiertes VAPID-JWT für den Push-Service.
   *
   * @param string $audience Origin des Browser-Push-Dienstes.
   * @param array $vapid VAPID-Konfiguration.
   * @return string Signiertes JWT.
   */
  private static function create_vapid_jwt(string $audience, array $vapid): string {
    $header = self::base64url_encode(wp_json_encode([
      'typ' => 'JWT',
      'alg' => 'ES256',
    ]));
    $payload = self::base64url_encode(wp_json_encode([
      'aud' => $audience,
      'exp' => time() + 12 * HOUR_IN_SECONDS,
      'sub' => (string) ($vapid['subject'] ?? 'mailto:admin@example.invalid'),
    ]));
    $input = $header . '.' . $payload;
    $signature = '';
    $private_key = openssl_pkey_get_private((string) ($vapid['privateKeyPem'] ?? ''));
    if (!$private_key) {
      throw new RuntimeException('vapid-private-key-ungueltig');
    }

    $signed = openssl_sign($input, $signature, $private_key, OPENSSL_ALGO_SHA256);
    if (!$signed) {
      throw new RuntimeException('vapid-signatur-fehlgeschlagen');
    }

    return $input . '.' . self::base64url_encode(self::der_to_jose_signature($signature));
  }

  /**
   * Wandelt eine DER-kodierte ECDSA-Signatur in das JOSE-Format um.
   *
   * @param string $der DER-kodierte Signatur von OpenSSL.
   * @return string 64-Byte-Signatur im JOSE-Format.
   */
  private static function der_to_jose_signature(string $der): string {
    $offset = 0;
    if (ord($der[$offset]) !== 0x30) {
      throw new RuntimeException('ecdsa-signatur-ungueltig');
    }
    $offset += 2;
    $r = self::read_der_integer($der, $offset);
    $s = self::read_der_integer($der, $offset);

    return str_pad($r, 32, "\0", STR_PAD_LEFT) . str_pad($s, 32, "\0", STR_PAD_LEFT);
  }

  /**
   * Liest einen Integer-Wert aus einer DER-Sequenz.
   *
   * @param string $der DER-Daten.
   * @param int $offset Aktuelle Leseposition, wird fortgeschrieben.
   * @return string Integer als Binärstring ohne Vorzeichenfüllbyte.
   */
  private static function read_der_integer(string $der, int &$offset): string {
    if (ord($der[$offset]) !== 0x02) {
      throw new RuntimeException('ecdsa-integer-fehlt');
    }
    $offset += 1;
    $length = ord($der[$offset]);
    $offset += 1;
    $value = substr($der, $offset, $length);
    $offset += $length;

    return ltrim($value, "\0");
  }

  /**
   * Kodiert Binärdaten URL-sicher ohne Padding.
   *
   * @param string $value Rohdaten.
   * @return string Base64url-kodierter Wert.
   */
  private static function base64url_encode(string $value): string {
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
  }
}
