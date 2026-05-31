param(
    [string]$LocalUrl = 'http://localhost:80'
)

$ErrorActionPreference = 'Stop'

$cloudflared = Get-Command cloudflared -ErrorAction SilentlyContinue

if ($null -eq $cloudflared) {
    throw 'cloudflared belum terpasang atau belum ada di PATH.'
}

Write-Output 'Menjalankan Cloudflare Quick Tunnel tanpa domain...'
Write-Output "Target lokal: $LocalUrl"
Write-Output 'Catatan: URL publik akan berubah jika proses di-restart.'

cloudflared tunnel --url $LocalUrl
