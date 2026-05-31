# Quick Start Windows Tanpa Domain

Panduan ini khusus untuk deploy cepat di komputer client Windows tanpa domain, agar aplikasi bisa diakses device lain.

## 1) Jalankan setup aplikasi (sekali saja)

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\windows\setup-production.ps1 -AppPath "C:\laragon\www\erp-internal"
```

## 2) Aktifkan scheduler + queue worker (sekali saja)

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\windows\register-laravel-tasks.ps1 -AppPath "C:\laragon\www\erp-internal" -PhpPath "C:\laragon\bin\php\php-8.3.0\php.exe"
```

## 3) Jalankan tunnel publik tanpa domain

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\windows\start-quick-tunnel.ps1 -LocalUrl "http://localhost:80"
```

Simpan URL yang muncul, contoh:

- `https://abcde.trycloudflare.com`

Akses admin:

- `https://abcde.trycloudflare.com/admin`

## 4) Supaya tunnel otomatis jalan saat PC restart

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\windows\register-quick-tunnel-task.ps1 -LocalUrl "http://localhost:80"
```

## 5) Cek cepat setelah live

- Login admin berhasil
- CRUD master data bisa create/edit/delete
- Import/Export Excel muncul di halaman list resource CRUD
- Workflow branch request berjalan: `draft -> submit -> review -> approve -> packed -> shipped -> received`

## Catatan penting

- URL `trycloudflare.com` bisa berubah ketika proses tunnel restart.
- Komputer server harus menyala, tidak sleep/hibernate.
- Jangan expose MySQL ke internet.
