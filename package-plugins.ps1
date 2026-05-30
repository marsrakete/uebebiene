param(
  [string]$Stamp = (Get-Date -Format "yyyyMMdd-HHmmss"),
  [switch]$NoTimestamp
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$pluginRoot = Join-Path $root "wordpress-plugin"
$plugins = @(
  "uebebiene-sync-bridge",
  "uebebiene-learner-app",
  "uebebiene-teacher-app"
)

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

function New-PluginZip {
  param(
    [Parameter(Mandatory = $true)]
    [string]$PluginSlug,
    [Parameter(Mandatory = $true)]
    [string]$DestinationPath
  )

  $sourcePath = Join-Path $pluginRoot $PluginSlug
  if (-not (Test-Path -LiteralPath $sourcePath -PathType Container)) {
    throw "Plugin-Ordner nicht gefunden: $sourcePath"
  }

  if (Test-Path -LiteralPath $DestinationPath) {
    Remove-Item -LiteralPath $DestinationPath -Force
  }

  $zip = [System.IO.Compression.ZipFile]::Open($DestinationPath, [System.IO.Compression.ZipArchiveMode]::Create)
  try {
    Get-ChildItem -LiteralPath $sourcePath -Recurse -File | ForEach-Object {
      $relativePath = $_.FullName.Substring($sourcePath.Length + 1).Replace("\", "/")
      $entryName = "$PluginSlug/$relativePath"
      [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
        $zip,
        $_.FullName,
        $entryName,
        [System.IO.Compression.CompressionLevel]::Optimal
      ) | Out-Null
    }
  }
  finally {
    $zip.Dispose()
  }
}

$created = foreach ($plugin in $plugins) {
  $zipName = if ($NoTimestamp) {
    "$plugin.zip"
  } else {
    "$plugin-$Stamp.zip"
  }
  $destination = Join-Path $pluginRoot $zipName
  New-PluginZip -PluginSlug $plugin -DestinationPath $destination
  Get-Item -LiteralPath $destination
}

$created | Select-Object Name, Length, LastWriteTime
