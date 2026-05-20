<!doctype html>
<html lang="de">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="#f26f3d" />
    <meta name="description" content="Lehrkräfte-Oberfläche für ÜbeBiene mit Klassen, Kopplung, Berichtspaket-Import und Übersichten." />
    <meta property="og:title" content="ÜbeBiene Lehrkräfte" />
    <meta property="og:description" content="Lehrkräfte-App für ÜbeBiene mit Klassen, Import, Berichten und Kärtchen." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo esc_url($runtime_config['appUrl']); ?>" />
    <meta property="og:image" content="<?php echo esc_url($runtime_config['icon512Url']); ?>" />
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="ÜbeBiene Lehrkräfte" />
    <meta name="twitter:description" content="Lehrkräfte-App für ÜbeBiene mit Klassen, Import, Berichten und Kärtchen." />
    <meta name="twitter:image" content="<?php echo esc_url($runtime_config['icon512Url']); ?>" />
    <link rel="manifest" href="./manifest.webmanifest" />
    <link rel="stylesheet" href="./teacher.css" />
    <link rel="icon" type="image/png" sizes="32x32" href="./icons/favicon-32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="./icons/favicon-16.png" />
    <link rel="apple-touch-icon" sizes="180x180" href="./icons/apple-touch-icon.png" />
    <title>ÜbeBiene Lehrkräfte</title>
  </head>
  <body>
    <div id="teacher-root"></div>
    <script>
      window.UEBEBIENE_RUNTIME_CONFIG = <?php echo wp_json_encode($runtime_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="./version.js"></script>
    <script src="./vendor-qrcodejs.js"></script>
    <script type="module" src="./teacher.js"></script>
  </body>
</html>


