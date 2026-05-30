param(
  [int]$Port = 5004
)

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$serverScript = Join-Path $root "serve-static.js"

if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
  Write-Error "Node.js wurde nicht gefunden. Bitte Node.js LTS installieren oder npm run serve in einer Umgebung mit Node starten."
  exit 1
}

node $serverScript --port $Port
exit $LASTEXITCODE
