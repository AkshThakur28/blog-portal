$ErrorActionPreference = 'Stop'

# Ensure we run inside the project directory
Set-Location $PSScriptRoot

# Download and extract CodeIgniter 3.1.13
$zip = 'ci3.zip'
Write-Host 'Downloading CodeIgniter 3.1.13...'
Invoke-WebRequest -Uri 'https://github.com/bcit-ci/CodeIgniter/archive/refs/tags/3.1.13.zip' -OutFile $zip

Write-Host 'Expanding archive...'
Expand-Archive -Path $zip -DestinationPath '.' -Force

$src = Join-Path (Get-Location) 'CodeIgniter-3.1.13'
if (-not (Test-Path $src)) {
  throw "Expected folder not found: $src"
}

Write-Host 'Moving CodeIgniter skeleton into project root...'
Get-ChildItem -Path $src -Force | ForEach-Object {
  Move-Item -Path $_.FullName -Destination (Get-Location) -Force
}

# Cleanup
Remove-Item -Recurse -Force $src
Remove-Item -Force $zip

# Create assets and JWT library dirs
Write-Host 'Creating assets and JWT directories...'
New-Item -ItemType Directory -Path 'assets' -Force | Out-Null
New-Item -ItemType Directory -Path 'assets\js' -Force | Out-Null
New-Item -ItemType Directory -Path 'assets\css' -Force | Out-Null
New-Item -ItemType Directory -Path 'application\third_party\JWT' -Force | Out-Null

# Download firebase/php-jwt files (no Composer)
Write-Host 'Fetching JWT library files...'
Invoke-WebRequest -Uri 'https://raw.githubusercontent.com/firebase/php-jwt/v6.9.0/src/JWT.php' -OutFile 'application/third_party/JWT/JWT.php'
Invoke-WebRequest -Uri 'https://raw.githubusercontent.com/firebase/php-jwt/v6.9.0/src/Key.php' -OutFile 'application/third_party/JWT/Key.php'
Invoke-WebRequest -Uri 'https://raw.githubusercontent.com/firebase/php-jwt/v6.9.0/src/BeforeValidException.php' -OutFile 'application/third_party/JWT/BeforeValidException.php'
Invoke-WebRequest -Uri 'https://raw.githubusercontent.com/firebase/php-jwt/v6.9.0/src/ExpiredException.php' -OutFile 'application/third_party/JWT/ExpiredException.php'
Invoke-WebRequest -Uri 'https://raw.githubusercontent.com/firebase/php-jwt/v6.9.0/src/SignatureInvalidException.php' -OutFile 'application/third_party/JWT/SignatureInvalidException.php'

Write-Host 'Scaffold complete.'
Get-ChildItem -Name
