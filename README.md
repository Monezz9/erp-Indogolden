# Indogolden ERP Internal

ERP internal berbasis Laravel + Filament untuk alur logistik, produksi, transfer cabang, gudang, dan keuangan dengan model stok ledger (bukan flat stock).

## Tech Stack

- PHP `^8.2`
- Laravel `^12`
- Filament `^5.5`
- PostgreSQL / MySQL
- Spatie Laravel Permission

## Scope Modul

- Master data: cabang, gudang, supplier, unit, kategori item, stage item, item, resep produksi
- Inventory ledger: stock movement, stock balance, stock batch
- Produksi: production order (consumption + output movement)
- Transfer: antar gudang / cabang (submit -> approve -> ship -> receive)
- Branch sales: input nota cabang cepat + posting + print thermal/A4
- Finance: pemasukan, pengeluaran, kategori finance
- Branch daily stock report: carry-over saldo harian (`stok_awal + masuk - keluar = sisa`)
- Import/export Excel: tersedia di seluruh list resource (dengan pembatasan role)
- Audit: activity log
- Access control: role + policy per modul

## Alur Stage Stok

Stage yang disediakan:

1. `raw_dirty`
2. `raw_clean`
3. `wip`
4. `finished_goods`
5. `branch_stock`
6. `mro`
7. `analysis`

Keputusan bisnis yang sudah diterapkan:

- Premix masuk `wip`
- Bumbu jadi masuk `finished_goods`
- Cabang menerima `finished_goods` + `mro`
- Minus stock di-block keras (throw `InsufficientStockException`)
- Costing inventory pakai weighted average

## Role Default

Role yang tersedia:

- `admin`
- `gudang`
- `cabang`
- `owner`
- `finance`
- `head_logistics`
- `logistics_admin`
- `branch`

Catatan kompatibilitas role:

- `admin` diperlakukan setara akses owner-level
- `gudang` diperlakukan setara akses warehouse-level (`head_logistics` + `logistics_admin`)
- `cabang` diperlakukan setara akses branch-level

Panduan operasional per role (login sampai flow fitur):

- `docs/OPERASIONAL_PER_ROLE.md`

Dummy user hasil seeder (`password` untuk semua):

- `owner@erp.local`
- `finance@erp.local`
- `headlogistik@erp.local`
- `adminlogistik@erp.local`
- `cabang.jakarta@erp.local`
- `cabang.bekasi@erp.local`
- `admin@erp.local`
- `gudang@erp.local`
- `cabang@erp.local`

Login panel mendukung `email` atau `username`.

Username default seeder:

- `owner`
- `finance`
- `headlogistik`
- `adminlogistik`
- `cabang.jakarta`
- `cabang.bekasi`

## Struktur Data Utama

### Core

- `users` (+ relasi ke `branches`)
- `branches`
- `warehouses`
- `suppliers`
- `units`

### Item & Produksi

- `item_categories`
- `item_stages`
- `items`
- `production_recipes`
- `production_recipe_items`
- `production_orders`
- `production_order_inputs`
- `production_order_outputs`

### Inventory Ledger

- `stock_movements`
- `stock_movement_items`
- `stock_balances`
- `stock_batches`

### Transfer

- `transfers`
- `transfer_items`

### Branch Sales

- `branch_sales`
- `branch_sale_items`

### Finance & Audit

- `finance_categories`
- `finance_incomes`
- `finance_expenses`
- `activity_logs`

### Support Framework

- `cache`
- `jobs`
- tabel permission dari Spatie (`roles`, `permissions`, dst)

## Relasi Ringkas

- `branches` 1..* `warehouses`, `users`, `finance_incomes`, `finance_expenses`
- `items` -> `item_categories`, `units`, `item_stages`
- `stock_movements` 1..* `stock_movement_items`
- `stock_balances` unik per kombinasi item + stage + warehouse/branch + batch (via `balance_key`)
- `production_recipes` 1..* `production_recipe_items`
- `production_orders` 1..* `production_order_inputs` + 1..* `production_order_outputs`
- `transfers` 1..* `transfer_items`

## Setup Lokal

### 1) Install dependency

```bash
composer install
npm install
```

### 2) Konfigurasi environment

```bash
copy .env.example .env
php artisan key:generate
```

Edit `.env` untuk PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=erp_internal
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 3) Migrasi + seeding

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

### 4) Jalankan aplikasi

```bash
php artisan serve
npm run dev
```

Filament admin panel:

- `http://127.0.0.1:8000/admin`

### 5) Build production asset (opsional)

```bash
npm run build
```

## Resource Filament

Resource yang sudah tersedia di panel admin:

- Branches
- Warehouses
- Suppliers
- Units
- Item Categories
- Item Stages
- Items
- Production Recipes
- Stock Movements
- Production Orders
- Transfers
- Branch Sales
- Finance Incomes
- Finance Expenses
- Stock Balances
- Activity Logs
- Users

Page custom tambahan:

- Branch Daily Stock Report

## Catatan Implementasi

- Service utama:
  - `StockBalanceService` (weighted average + saldo)
  - `StockMovementService` (draft/submit/approve/reject)
  - `ProductionService` (create + complete order)
  - `TransferService` (submit/approve/ship/receive)
  - `BranchSaleService` (sync total, post nota, hitung COGS/gross profit, auto finance income)
  - `BranchDailyStockReportService` (opening/in/out/closing harian per item cabang)
  - `FinanceSummaryService`
- Policy per modul sudah diaktifkan berbasis role.
- Query list resource dan dashboard widget sudah dibatasi per-role/per-branch (terutama untuk user `branch`).
- Dashboard custom Filament tersedia dengan KPI + chart + pending approval + low stock + activity.
- Posting nota cabang otomatis membuat `FinanceIncome` kategori `REV-SALES`.
- Print nota tersedia untuk format thermal dan A4.
- Import Excel untuk role `branch` dibatasi hanya untuk transaksi cabang (`BranchSale`, `StockMovement`, `Transfer`).

## Aksi Workflow di UI

Di halaman list dan edit untuk modul terkait, sudah tersedia action cepat sesuai flow:

- Stock Movement: `submit` -> `approve` / `reject`
- Transfer: `submit` -> `approve` / `reject` -> `ship` -> `receive`
- Production Order: `submit` -> `approve` -> `complete`
- Branch Sale: `draft` -> `post` (stok keluar + jurnal income otomatis)

Action dibatasi oleh policy role + status dokumen, dan menampilkan notifikasi sukses/gagal langsung di panel.

## Branch Daily Stock Report

Laporan stok cabang harian dihitung dari ledger movement approved, dengan rumus:

- `stok_awal + masuk - keluar = sisa`
- `sisa` hari ini menjadi `stok_awal` hari berikutnya (carry-over)

Report dapat diakses dari menu admin dan dapat diexport ke XLSX.

## Validasi Cepat

```bash
php artisan about
php artisan filament:about
php artisan test
```

Jika command database gagal, cek kredensial database di `.env` (PostgreSQL/MySQL).

## Deploy Windows Client

Panduan deploy cepat untuk komputer client Windows (tanpa VPS):

- `docs/DEPLOY_WINDOWS_CLIENT.md`
- `docs/WINDOWS_NO_DOMAIN_QUICK_START.md` (tanpa domain, pakai quick tunnel)

Script bantu:

- `scripts/windows/setup-production.ps1`
- `scripts/windows/register-laravel-tasks.ps1`
- `scripts/windows/cloudflared/config.example.yml`
- `scripts/windows/start-quick-tunnel.ps1` (akses publik tanpa domain)
- `scripts/windows/register-quick-tunnel-task.ps1` (auto-start quick tunnel saat boot)
