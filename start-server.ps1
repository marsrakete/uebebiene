param(
  [int]$Port = 5004
)

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$serverScript = Join-Path $root "serve-static.js"

$contentTypes = @{
  ".html" = "text/html; charset=utf-8"
  ".css" = "text/css; charset=utf-8"
  ".js" = "application/javascript; charset=utf-8"
  ".json" = "application/json; charset=utf-8"
  ".webmanifest" = "application/manifest+json; charset=utf-8"
  ".svg" = "image/svg+xml"
  ".png" = "image/png"
  ".pdf" = "application/pdf"
  ".txt" = "text/plain; charset=utf-8"
}

function Start-PowerShellStaticServer {
  $listener = [System.Net.HttpListener]::new()
  $listener.Prefixes.Add("http://localhost:$Port/")
  $listener.Start()

  Write-Host "UebeBiene laeuft auf http://localhost:$Port/"
  Write-Host "Zum Beenden Strg+C druecken."

  try {
    while ($listener.IsListening) {
      $contextTask = $listener.GetContextAsync()
      while (-not $contextTask.AsyncWaitHandle.WaitOne(200)) {
        if (-not $listener.IsListening) {
          break
        }
      }

      if (-not $listener.IsListening) {
        break
      }

      $context = $contextTask.GetAwaiter().GetResult()
      $requestPath = $context.Request.Url.AbsolutePath.TrimStart("/")

      if ([string]::IsNullOrWhiteSpace($requestPath)) {
        $requestPath = "index.html"
      }

      $safePath = $requestPath.Replace("/", "\")
      $filePath = Join-Path $root $safePath
      $resolvedRoot = [System.IO.Path]::GetFullPath($root)
      $resolvedFile = [System.IO.Path]::GetFullPath($filePath)

      if (-not $resolvedFile.StartsWith($resolvedRoot) -or -not (Test-Path $resolvedFile -PathType Leaf)) {
        $context.Response.StatusCode = 404
        $bytes = [System.Text.Encoding]::UTF8.GetBytes("404 - Datei nicht gefunden")
        $context.Response.OutputStream.Write($bytes, 0, $bytes.Length)
        $context.Response.Close()
        continue
      }

      $extension = [System.IO.Path]::GetExtension($resolvedFile).ToLowerInvariant()
      $contentType = $contentTypes[$extension]
      if (-not $contentType) {
        $contentType = "application/octet-stream"
      }

      $bytes = [System.IO.File]::ReadAllBytes($resolvedFile)
      $context.Response.ContentType = $contentType
      $context.Response.ContentLength64 = $bytes.Length
      $context.Response.OutputStream.Write($bytes, 0, $bytes.Length)
      $context.Response.Close()
    }
  }
  finally {
    if ($listener) {
      $listener.Stop()
      $listener.Close()
    }
  }
}

try {
  Start-PowerShellStaticServer
}
catch [System.PlatformNotSupportedException] {
  $node = Get-Command node -ErrorAction SilentlyContinue
  if (-not $node) {
    Write-Error "HttpListener wird in dieser Umgebung nicht unterstuetzt und Node.js wurde nicht gefunden. Bitte Node.js LTS installieren oder das Script in einer normalen Windows-PowerShell starten."
    exit 1
  }

  Write-Host "HttpListener wird hier nicht unterstuetzt. Starte Node-Fallback ohne npm..."
  node $serverScript --port $Port
  exit $LASTEXITCODE
}
