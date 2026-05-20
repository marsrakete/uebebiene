<!doctype html>
<html lang="de">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="#f26f3d" />
    <meta name="description" content="ÜbeBiene motiviert Musiklernende, ihre tägliche Übezeit zu protokollieren und Fleiß-Kärtchen zu sammeln." />
    <meta property="og:title" content="ÜbeBiene" />
    <meta property="og:description" content="Motivierende Übe-App für Musiklernende mit Kärtchen, Berichten und Lehrkräfte-Begleitung." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo esc_url($runtime_config['appUrl']); ?>" />
    <meta property="og:image" content="<?php echo esc_url($runtime_config['icon512Url']); ?>" />
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="ÜbeBiene" />
    <meta name="twitter:description" content="Motivierende Übe-App für Musiklernende mit Kärtchen, Berichten und Lehrkräfte-Begleitung." />
    <meta name="twitter:image" content="<?php echo esc_url($runtime_config['icon512Url']); ?>" />
    <link rel="manifest" href="./manifest.webmanifest" />
    <link rel="stylesheet" href="./styles.css" />
    <link rel="icon" type="image/png" sizes="32x32" href="./icons/favicon-32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="./icons/favicon-16.png" />
    <link rel="apple-touch-icon" sizes="180x180" href="./icons/apple-touch-icon.png" />
    <title>ÜbeBiene</title>
  </head>
  <body>
    <div id="root"></div>
    <script>
      window.UEBEBIENE_RUNTIME_CONFIG = <?php echo wp_json_encode($runtime_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="./version.js"></script>
    <script src="./vendor-qrcodejs.js"></script>
    <script src="./vendor-jsQR.js"></script>
    <script type="module" src="./app.js"></script>
  </body>
</html>


