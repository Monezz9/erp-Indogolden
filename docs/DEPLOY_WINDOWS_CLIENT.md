# Deploy Cepat di Windows Client (Production Ringan)

Dokumen ini untuk skenario server berada di komputer client Windows (tanpa VPS), dengan akses dari perangkat lain via internet.

Arsitektur yang dipakai:

- Laravel + Filament dijalankan di Windows (Laragon/Nginx/Apache + MySQL)
- Akses publik via Cloudflare Tunnel (HTTPS)

Jika belum punya domain, bisa langsung pakai Cloudflare Quick Tunnel (URL sementara `*.trycloudflare.com`).

Panduan super singkat tanpa domain:

- `docs/WINDOWS_NO_DOMAIN_QUICK_START.md`

## 1) Prasyarat

- Windows 10/11 Pro (disarankan), RAM minimal 8 GB, SSD
- Domain aktif di Cloudflare
- Aplikasi sudah berada di folder server, contoh: `C:\laragon\www\erp-internal`

Pastikan:

- Komputer server tidak sleep/hibernate
- Jam dan timezone Windows benar
- Internet stabil

## 2) Install Komponen

1. Install Laragon (Nginx/Apache + MySQL + PHP)
2. Install Composer
3. Install Node.js LTS
4. Install Cloudflared

## 3) Setup Aplikasi (sekali jalan)

Jalankan di root project:

```powershell
composer install --no-dev --optimize-autoloader
npm ci
npm run build

if (!(Test-Path .env)) { Copy-Item .env.example .env }

php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan db:seed --force
php artisan optimize
```

Set `.env` production minimal:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erp.domainkamu.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erp_internal
DB_USERNAME=root
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
```

Lalu jalankan cache config ulang:

```powershell
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4) Setup Scheduler + Queue Worker di Windows Task Scheduler

Gunakan script:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\windows\register-laravel-tasks.ps1 -AppPath "C:\laragon\www\erp-internal" -PhpPath "C:\laragon\bin\php\php-8.3.0\php.exe"
```

Script ini membuat 2 task:

- `ERP Laravel Scheduler` (tiap 1 menit)
- `ERP Laravel Queue Worker` (saat startup)

## 5) Setup Cloudflare Tunnel

1. Login:

```powershell
cloudflared tunnel login
```

2. Buat tunnel:

```powershell
cloudflared tunnel create erp-client
```

3. Tambah DNS route:

```powershell
cloudflared tunnel route dns erp-client erp.domainkamu.com
```

4. Buat config file cloudflared (copy dari template):

- Template: `scripts/windows/cloudflared/config.example.yml`
- Simpan ke: `%USERPROFILE%\.cloudflared\config.yml`

5. Jalankan test tunnel:

```powershell
cloudflared tunnel run erp-client
```

6. Jika sudah OK, install service:

```powershell
cloudflared service install
```

### Alternatif cepat tanpa domain (Quick Tunnel)

Jika client belum punya domain, jalankan ini:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\windows\start-quick-tunnel.ps1 -LocalUrl "http://localhost:80"
```

Atau langsung command cloudflared:

```powershell
cloudflared tunnel --url http://localhost:80
```

Nanti akan muncul URL publik semacam `https://xxxxx.trycloudflare.com`.

Catatan Quick Tunnel:

- URL publik bisa berubah saat cloudflared restart
- Cocok untuk produksi ringan awal / pilot
- Jika sudah stabil dipakai harian, disarankan pindah ke domain sendiri agar URL tetap

Untuk auto-start Quick Tunnel saat boot Windows:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\windows\register-quick-tunnel-task.ps1 -LocalUrl "http://localhost:80"
```

## 6) Uji Akses

- Internal jaringan: `http://IP_SERVER_LOCAL/admin`
- Internet/public: `https://erp.domainkamu.com/admin`

Jika pakai Quick Tunnel tanpa domain:

- `https://xxxxx.trycloudflare.com/admin`

Pastikan login dan proses berikut berhasil:

- CRUD master data
- Import Excel dari list page resource CRUD
- Workflow logistik branch request: `draft -> submit -> review -> approve -> packed -> shipped -> received`

## 7) Operasional Wajib

- Backup harian database + `storage/app`
- Gunakan password kuat untuk semua akun
- Jangan expose port MySQL ke internet
- Update Windows dan antivirus berkala
