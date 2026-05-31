param(
    [string]$AppPath = (Get-Location).Path
)

$ErrorActionPreference = 'Stop'

if (!(Test-Path $AppPath)) {
    throw "Folder aplikasi tidak ditemukan: $AppPath"
}

Push-Location $AppPath

try {
    if (!(Test-Path '.env')) {
        Copy-Item '.env.example' '.env'
    }

    composer install --no-dev --optimize-autoloader
    npm ci
    npm run build

    php artisan key:generate
    php artisan storage:link
    php artisan migrate --force
    php artisan db:seed --force
    php artisan optimize

    Write-Output 'Setup production selesai. Lanjutkan konfigurasi APP_URL, DB, queue, dan cloudflared.'
}
finally {
    Pop-Location
}
