param(
    [string]$LocalUrl = 'http://localhost:80'
)

$ErrorActionPreference = 'Stop'

$cloudflared = Get-Command cloudflared -ErrorAction SilentlyContinue

if ($null -eq $cloudflared) {
    throw 'cloudflared belum terpasang atau belum ada di PATH.'
}

$taskName = 'ERP Cloudflare Quick Tunnel'
$taskCommand = "cloudflared tunnel --url $LocalUrl"

schtasks /Create /F /TN $taskName /SC ONSTART /TR $taskCommand

Write-Output "Task startup berhasil dibuat: $taskName"
Write-Output "Target URL lokal: $LocalUrl"
Write-Output 'Catatan: URL trycloudflare.com dapat berubah setelah restart proses.'
