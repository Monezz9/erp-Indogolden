# SOP Operasional Per Role

Dokumen ini menjelaskan alur kerja dari login sampai akses fitur untuk semua role:

- `admin`
- `gudang`
- `cabang`
- `owner`
- `finance`
- `head_logistics`
- `logistics_admin`
- `branch`

## 1) Login dan Masuk Panel

1. Buka `http://127.0.0.1:8000/admin`.
2. Login dengan akun user sesuai role (bisa pakai `email` atau `username`).
3. Setelah login, sidebar akan otomatis menyesuaikan policy role.

Dummy user seeder (password semua akun: `password`):

- `owner@erp.local`
- `finance@erp.local`
- `headlogistik@erp.local`
- `adminlogistik@erp.local`
- `cabang.jakarta@erp.local`
- `cabang.bekasi@erp.local`
- `admin@erp.local`
- `gudang@erp.local`
- `cabang@erp.local`

## 2) Aturan Akses Umum (Role Matrix Ringkas)

- `owner`: akses penuh semua modul dan aksi.
- `admin`: akses penuh setara owner untuk operasional harian.
- `gudang`: akses operasional gudang setara kombinasi head logistics + logistics admin.
- `cabang`: akses operasional cabang setara role branch.
- `finance`: fokus data keuangan + monitoring data operasional terkait.
- `head_logistics`: monitoring + approval alur logistik/produksi/transfer.
- `logistics_admin`: input dan eksekusi operasional harian.
- `branch`: transaksi cabang dan monitoring stok cabang sendiri.

Catatan:

- Semua list page mendukung export Excel.
- Tombol import muncul jika role punya izin `create` pada model terkait.
- Role `branch` hanya boleh import transaksi cabang (`BranchSale`, `StockMovement`, `Transfer`).
- Role `cabang` mengikuti aturan role `branch`.

Workflow logistik request cabang:

- `draft -> submit -> reviewed -> approved -> packed -> shipped -> received`
- Aksi dijaga policy + validasi service sehingga status tidak bisa loncat.

## 3) SOP Harian Per Role

### Owner

Alur harian:

1. Login -> buka `Dashboard`.
2. Review `Pending Approvals`, KPI, trend finance, dan low stock.
3. Review dokumen submitted:
   - `Stock Movements`
   - `Transfers`
   - `Production Orders`
4. Audit aktivitas di `Activity Logs`.
5. Kelola user di `Users` jika ada perubahan tim.

Akses utama:

- Full `Master Data`, `Inventory`, `Production`, `Transfer`, `Branch Sales`, `Finance`, `Audit`, `User Management`.
- Boleh approve/reject, ship/receive, complete produksi, post branch sale, create/update/delete data.

### Finance

Alur harian:

1. Login -> cek `Dashboard` (KPI + trend finance).
2. Buka `Finance Incomes` untuk verifikasi pemasukan (termasuk auto income dari branch sale).
3. Input/update `Finance Expenses` sesuai pengeluaran real.
4. Rekonsiliasi data cabang vs finance.
5. Export laporan ke Excel.

Akses utama:

- `Finance Incomes`, `Finance Expenses` (create/update).
- View monitoring pada `Branch Sales`, `Stock Movements`, `Production Orders`, `Stock Balances`, `Items`, `Branches`, `Production Recipes`.

### Head Logistics

Alur harian:

1. Login -> cek `Pending Approvals` di dashboard.
2. Proses approval dokumen submitted:
   - approve/reject `Stock Movements`
   - approve/reject `Transfers`
   - approve `Production Orders`
3. Pantau low stock dan koordinasi transfer/produksi.
4. Monitor branch sales dan stok cabang.
5. Export data operasional bila dibutuhkan.

Akses utama:

- Akses luas modul operasional.
- Bisa approve/reject dokumen, ship/receive transfer, complete produksi, post branch sale.

### Logistics Admin

Alur harian:

1. Login -> cek kebutuhan operasional hari ini.
2. Input master operasional yang relevan (item, supplier, unit, recipe, warehouse).
3. Buat transaksi:
   - `Stock Movements`
   - `Transfers`
   - `Production Orders`
   - `Branch Sales` (jika operasional minta bantuan)
4. Submit dokumen yang butuh approval.
5. Lanjut proses pasca approval (ship, receive, complete).

Akses utama:

- Create/update data operasional.
- Eksekusi proses operasional harian.
- Tidak jadi approver utama untuk submit flow (approval di owner/head).

### Branch

Alur harian:

1. Login -> fokus menu `Branch Operations`.
2. Buat nota di `Branch Sales`.
3. `Post` nota:
   - stok cabang keluar,
   - COGS + gross profit dihitung,
   - auto `FinanceIncome` kategori `REV-SALES`.
4. Print nota (`thermal` atau `A4`).
5. Cek `Laporan Stok Harian` untuk carry-over (`stok_awal + masuk - keluar = sisa`).

Akses utama:

- Input transaksi cabang sendiri.
- Melihat data branch-scoped.
- Bisa `receive` transfer jika transfer ditujukan ke cabangnya.

## 4) Workflow Dokumen End-to-End

### Stock Movement

- Flow: `draft -> submit -> approve/reject`.
- Create: `owner`, `head_logistics`, `logistics_admin`, `branch`.
- Approve/Reject: `owner`, `head_logistics`.

### Transfer

- Flow: `draft -> submit -> approve/reject -> ship -> receive`.
- Create: `owner`, `head_logistics`, `logistics_admin`, `branch`.
- Approve/Reject: `owner`, `head_logistics`.
- Ship: `owner`, `head_logistics`, `logistics_admin`.
- Receive: `owner`, `head_logistics`, `logistics_admin`, atau `branch` penerima.

### Production Order

- Flow: `draft -> submit -> approve -> complete`.
- Create: `owner`, `head_logistics`, `logistics_admin`.
- Approve: `owner`, `head_logistics`.
- Complete: `owner`, `head_logistics`, `logistics_admin`.

### Branch Sale

- Flow: `draft -> post`.
- Create: `owner`, `head_logistics`, `logistics_admin`, `branch`.
- Post: role yang diizinkan oleh policy dan sesuai scope cabang.
- Dampak post: stok keluar, COGS/profit terhitung, auto catat pemasukan finance.

## 5) Role yang Bisa Akses Semua Fitur

Jika butuh satu akun untuk akses penuh end-to-end, gunakan role `owner`.

Role lain tetap dibatasi sesuai policy agar alur approval, audit trail, dan keamanan data tetap terjaga.
