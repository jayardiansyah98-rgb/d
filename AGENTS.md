# AGENTS.md

Panduan kerja untuk agen/AI yang melanjutkan proyek **Sistem Informasi Pengelolaan Donasi Yayasan**.

## Urutan membaca sebelum bekerja

1. `AGENTS.md` (file ini)
2. `PROJECT_STATUS.md`
3. `README.md`
4. Periksa kode, migration, seeder, dan test yang benar-benar ada. Jangan mengandalkan ringkasan percakapan saja.

## Aturan utama

- Kerjakan **hanya tahap aktif** yang tercatat di `PROJECT_STATUS.md`.
- Jangan memulai tahap berikutnya tanpa persetujuan eksplisit pengguna.
- Jangan membuat ulang file yang sudah ada tanpa memeriksa isinya terlebih dahulu.
- Jangan menandai fitur "selesai" tanpa dijalankan/diuji.
- Laporkan hasil nyata: perintah yang dijalankan, jumlah test, dan kegagalan yang tersisa.
- Jangan menghapus atau menimpa pekerjaan pengguna yang tidak terkait.

## Stack dan gaya proyek

- PHP 8.2+, CodeIgniter 4.7, MySQL/MariaDB 10.4+.
- Bootstrap 5 + Bootstrap Icons, tampilan berbahasa Indonesia.
- Pola MVC CI4: Controller tipis, logika bisnis di `app/Libraries`, validasi di `app/Validation` dan `app/Config/Validation.php`, query di model/service.
- Gunakan `snake_case` untuk kolom database, `camelCase` untuk method/variabel PHP.
- Format tanggal aplikasi: `Asia/Jakarta`, locale `id`.
- Jangan mengubah file di `vendor/`.

## Aturan keamanan wajib

- Semua form POST memakai CSRF global (sudah aktif di `app/Config/Filters.php`).
- Semua output dinamis di view wajib `esc()` atau `esc(..., 'attr')`.
- Validasi selalu di server; validasi HTML hanya pelengkap.
- Otorisasi memakai permission RBAC (`can()` / filter `permission:nama.permission`), bukan hanya pengecekan role di view.
- Password hanya disimpan sebagai hash (`password_hash`), tidak pernah plaintext.
- Rahasia, kredensial DB, dan password seeder disimpan di `.env`, bukan di kode.
- Penulisan data finansial wajib memakai transaksi database.
- Data finansial tidak boleh dihapus permanen; gunakan pembatalan/arsip yang dapat diaudit.
- Audit log bersifat append-only. Jangan membuat endpoint update/delete untuk `audit_logs`.
- Jangan menampilkan `password_hash`, token, atau secret di audit log.

## Role awal

| Role | Slug | Cakupan |
|---|---|---|
| Admin | `admin` | Seluruh modul + pengguna/role/audit. Role sistem, tidak dapat dihapus. |
| Keuangan | `keuangan` | Donatur, penerimaan donasi, laporan. |
| Distribusi | `distribusi` | Melihat donasi dan mengelola distribusi. |
| Pimpinan | `pimpinan` | Read-only: dashboard, donasi, distribusi, laporan, audit. |

- Role lama (`administrator`, `pengurus`, `petugas`, `auditor`) diganti nama in-place oleh `RoleSeeder`.
- Hanya `admin` yang merupakan role sistem; administrator aktif terakhir tidak boleh dinonaktifkan, diarsipkan, atau diturunkan role-nya.
- `distributions.*` sudah dipakai modul distribusi; `reports.view` sudah di-seed, tetapi modul laporan belum dibuat.

## Struktur penting

- `app/Config/Routes.php` — route dan filter per modul.
- `app/Config/Filters.php` — CSRF global, alias `auth`, `guest`, `permission`.
- `app/Config/Auth.php` — batas percobaan login dan panjang password.
- `app/Config/Services.php` — service `auth`, `auditLogger`, `userService`, `roleService`, `donaturService`, `donasiService`, `distribusiService`.
- `app/Libraries/AuthService.php` — login, sesi, permission.
- `app/Libraries/UserService.php` — create/update/status/archive user + guard admin aktif terakhir.
- `app/Libraries/RoleService.php` — CRUD role + permission dalam transaksi.
- `app/Libraries/DonaturService.php` — CRUD donatur, kode otomatis, pencegahan duplikasi email/nama+telepon, audit, soft delete.
- `app/Libraries/DonasiService.php` — pencatatan donasi, nomor transaksi, verifikasi, pembatalan, audit, transaksi database.
- `app/Libraries/DistribusiService.php` — pencatatan distribusi, aturan saldo dana/stok barang, kunci saldo `distribution_balance_locks`, nomor distribusi, persetujuan/penyaluran/pembatalan, audit, transaksi database.
- `app/Libraries/AuditLogger.php` — penulisan audit dan sanitasi nilai sensitif.
- `app/Controllers/` — `Auth`, `Dashboard`, `User`, `Role`, `AuditLog`, `Profile`, `Template`, `Donatur`, `Donasi`, `Distribusi`.
- `app/Views/partials/` — `sidebar`, `topbar`, `alerts`, `footer`.
- `app/Views/donatur/` — daftar, formulir, dan detail donatur.
- `app/Views/donasi/` — daftar, formulir, detail, dan bukti penerimaan cetak.
- `app/Views/distribusi/` — daftar, formulir, detail, dan bukti penyaluran cetak.
- `app/Views/templates/report.php` — kerangka UI laporan (tanpa data bisnis).
- `app/Database/Migrations/` — 5 migration fondasi + 5 migration tahap 2 (program, donatur, donasi, distribusi, detail distribusi) + 2 migration distribusi (`notes`, `distribution_balance_locks`).
- `app/Database/Seeds/` — permission, role (Admin/Keuangan/Distribusi/Pimpinan + rename role lama in-place), mapping role-permission, user (password dari `.env`, tanpa fallback hardcoded).
- `app/Models/` — model fondasi + `BaseModel`, `ProgramDonasiModel`, `DonaturModel`, `DonasiModel`, `DistribusiModel`, `DetailDistribusiModel`.
- `app/Validation/DistribusiRules.php` — aturan validasi store dan pembatalan distribusi.
- `tests/feature/` — test HTTP/auth/RBAC/CSRF/profile/user CRUD/donatur CRUD/donasi CRUD + bukti penerimaan/distribusi CRUD + bukti penyaluran/matriks akses role.
- `tests/database/` — test service, transaksi role, matriks permission role, validasi, skema donasi/distribusi, service donatur, service donasi + penomoran konkuren, service distribusi + saldo/stok/kunci.
- `tests/unit/` — test scaffold CI4.
- `PROJECT_STATUS.md` — status tahap, hasil test, dan langkah berikutnya.

## Database dan seeder

Perintah dari root proyek:

```powershell
C:\xampp\php\php.exe spark migrate
C:\xampp\php\php.exe spark db:seed DatabaseSeeder
C:\xampp\php\php.exe spark serve --host 127.0.0.1 --port 8080
```

- Database aplikasi: `donasi_yayasan`.
- Database test: `donasi_yayasan_test` (dikonfigurasi di `.env`).
- Seeder bersifat idempoten; aman dijalankan ulang.
- Password tidak boleh ditulis di source code. Administrator awal memakai `seed.adminPassword` atau `seed.adminPasswordHash`; tanpa keduanya, akun Admin dilewati.
- Deployment lama harus menjalankan ulang `spark db:seed DatabaseSeeder` agar rename role lama dan mapping permission baru diterapkan tanpa menghapus pengguna.
- Seeder demo hanya aktif jika `seed.demoUsers = true` dan password demo diisi di `.env`. Jangan aktifkan demo user di produksi.
- Jangan commit `.env`.

## Pengujian

```powershell
C:\xampp\php\php.exe vendor\bin\phpunit --no-coverage
```

- Test database memakai group `tests` dari `.env`; jangan arahkan ke database aplikasi/produksi.
- Jalankan `php -l` untuk file yang diubah.
- Untuk perubahan route/auth, verifikasi juga lewat HTTP nyata (login, CSRF, redirect, RBAC).
- Status terakhir: **193 test, 1102 assertion, semuanya lulus** (2026-11-01).
- Test penuh memerlukan MySQL berjalan dan memakan waktu sekitar 11–25 menit karena setiap test me-refresh seluruh skema database test (12 tabel).

## Roadmap tahap

- **Tahap 1 (selesai):** fondasi, autentikasi, dashboard, user, role/permission, audit log, profil.
- **Tahap 2 (selesai):** skema database program donasi, donatur, donasi, distribusi, detail distribusi; model + ERD + test skema.
- **Tahap 3a (selesai):** modul data donatur (CRUD, kode otomatis, RBAC, audit, soft delete, view daftar/form/detail, test).
- **Tahap 3b (selesai):** pencatatan donasi uang/barang, verifikasi, pembatalan, nomor transaksi otomatis, transaksi database, audit, view daftar/form/detail, bukti penerimaan cetak, ringkasan aktif, dan test.
- **Penyelarasan akses (selesai):** role Admin/Keuangan/Distribusi/Pimpinan, matriks permission, test matriks akses, dokumentasi akun bootstrap.
- **Penyempurnaan donatur (selesai):** permintaan "Tahap 4" pengguna; pencegahan duplikasi dan test pencarian/pagination/otorisasi.
- **Penyempurnaan penerimaan donasi (selesai):** permintaan "Tahap 5" pengguna; bukti penerimaan cetak, ringkasan aktif, filter, dan test penomoran konkuren.
- **Tahap 4 internal / permintaan Tahap 6 pengguna (selesai):** distribusi donasi uang/barang, aturan saldo dana & stok barang, kunci saldo, alur draf→disetujui→tersalurkan, pembatalan, bukti penyaluran, filter, RBAC, dan test.
- **Tahap 5 internal / permintaan Tahap 7 pengguna (belum dikerjakan):** laporan dan penyempurnaan.

> Catatan penomoran: permintaan pengguna pada 2026-10-28 menyebut **modul Manajemen Data Donatur** sebagai "Tahap 4" dan **modul Penerimaan Donasi** sebagai "Tahap 5". Keduanya sudah ada sejak Tahap 3a/3b dan telah disempurnakan. Permintaan **modul Distribusi Donasi** disebut "Tahap 6" pengguna dan setara dengan **Tahap 4 internal**; modul ini **sudah selesai**. "Tahap 5 internal" (laporan) tetap **belum dikerjakan**.

Permission `donations.*` sudah dipakai modul donasi, termasuk bukti penerimaan cetak dengan `donations.view`. Permission `distributions.*` sudah dipakai modul distribusi, termasuk bukti penyaluran dengan `distributions.view`. Permission `reports.view` sudah di-seed untuk tahap laporan, tetapi **modul laporan belum dibuat** — jangan menganggapnya sudah berfungsi.
