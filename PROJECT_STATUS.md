# PROJECT STATUS

**Proyek:** Sistem Informasi Pengelolaan Donasi Yayasan
**Stack:** PHP 8.2, CodeIgniter 4.7.5, MySQL/MariaDB, Bootstrap 5
**Terakhir diperbarui:** 2026-11-01

## Tahap aktif

**Prompt 8 (Pengujian Akhir & Finalisasi Proyek) SELESAI. Seluruh Prompt 1 sampai Prompt 8 telah selesai dan terverifikasi.**

**Terakhir selesai:** Prompt 8 — Pengujian akhir komprehensif, validasi seluruh modul (Autentikasi & RBAC, Data Donatur, Penerimaan Donasi, Distribusi Donasi, Dashboard Visual, Modul Laporan dengan Cetak & Excel), pemeriksaan integritas basis data, audit keamanan web dasar (SQLi, XSS, CSRF, Session, Password Hash, Proteksi file .env), pengujian responsivitas multi-device Bootstrap 5, serta eksekusi 100% test suite PHPUnit (**199 tests, 1184 assertions, semuanya lulus tanpa kegagalan**). Status: **SELESAI DAN TERVERIFIKASI PENUH.**


## Yang sudah selesai (Tahap 1)

### Fondasi aplikasi
- CI4 4.7.5 terpasang via Composer.
- Konfigurasi locale `id`, timezone `Asia/Jakarta`, base URL `http://localhost:8080/`.
- CSRF global aktif.
- Filter `auth`, `guest`, dan `permission` terdaftar dan dipakai di route.
- Layout Bootstrap 5, sidebar, halaman error, aset CSS/JS lokal.
- Route `/` mengarah ke dashboard jika sudah login, atau login jika belum.

### Antarmuka dan template
- Layout terpisah: `layouts/main.php` (aplikasi) dan `layouts/auth.php` (login).
- Partial terpisah: `partials/sidebar.php`, `partials/topbar.php`, `partials/alerts.php`, `partials/footer.php`.
- Dashboard dengan kartu statistik dan tabel ringkasan.
- Template daftar data dengan pencarian, filter, tabel responsif, empty state, dan pagination (`users`, `roles`, `audit`).
- Template formulir dengan label, validasi server, dan pesan error (`users`, `roles`, `profile`).
- Template kerangka laporan di `app/Views/templates/report.php`, route `template/laporan` (`templates.report`), filter `permission:reports.view`.
  - Isinya murni UI: filter, kartu ringkasan, tabel, dan pagination dalam keadaan nonaktif.
  - Tidak ada query atau data donasi/distribusi; modul laporan sebenarnya belum dikerjakan.
- Responsif: sidebar off-canvas di bawah breakpoint `lg`, grid Bootstrap untuk mobile/tablet/desktop, tabel memakai `table-responsive`.
- Bootstrap Icons 1.11.3 dipakai konsisten untuk ikon menu, tombol, dan status.
- Bootstrap 5.3.3 + Bootstrap Icons dimuat dari CDN jsDelivr, sehingga **tampilan memerlukan koneksi internet**. Aset aplikasi sendiri tetap lokal di `public/assets/`. Kebutuhan ini didokumentasikan di `README.md`.

### Database
- 12 migration berhasil dijalankan:
  - Tahap 1: `roles`, `permissions`, `role_permissions`, `users` (soft delete), `audit_logs` (append-only).
  - Tahap 2: `program_donasi`, `donatur`, `donasi`, `distribusi`, `detail_distribusi`.
  - Tahap 6/distribusi: kolom `notes` pada `distribusi` dan tabel kunci `distribution_balance_locks`.
- Seeder idempoten:
  - 4 role awal: Admin (`admin`), Keuangan (`keuangan`), Distribusi (`distribusi`), Pimpinan (`pimpinan`).
  - 15 permission (termasuk permission untuk modul tahap berikutnya).
  - 31 mapping role-permission (Admin 15, Keuangan 6, Distribusi 5, Pimpinan 5).
  - Admin awal + akun demo opsional. Seeder mengganti nama role lama secara in-place (`administrator`→`admin`, `pengurus`→`keuangan`, `petugas`→`distribusi`, `auditor`→`pimpinan`) agar pengguna yang sudah ada tetap terhubung tanpa duplikasi role.
- Password seeder **tidak ada di source code**: dibaca dari `seed.adminPassword` / `seed.adminPasswordHash`, dan password demo dari `seed.*Password` di `.env`.
- Foreign key, unique key, index, dan check constraint sudah ada.
- Database test terpisah: `donasi_yayasan_test`.

### Skema Tahap 2
- `program_donasi`: kode unik, nama, target dana `DECIMAL(15,2)`, periode, status, soft delete.
- `donatur`: kode unik, tipe individu/organisasi, kontak, flag anonim, status, soft delete.
- `donasi`: nomor transaksi unik, relasi ke donatur/program, tipe `money|goods`, status `pending|verified|cancelled`, nominal `DECIMAL(15,2)`, kolom barang (nama, kuantitas, satuan, nilai estimasi), flag anonim, soft delete.
- `distribusi`: nomor distribusi unik, relasi program/donasi, tipe `money|goods`, status `draft|approved|distributed|cancelled`, penerima, nominal, tujuan, soft delete. Migration 2026-11-01 menambahkan kolom `notes` dan tabel `distribution_balance_locks`.
- `detail_distribusi`: rincian barang (nama, kuantitas, satuan, nilai satuan) dengan FK cascade.
- `audit_log` pada spesifikasi dipetakan ke tabel `audit_logs` yang sudah ada dan tetap append-only; tidak dibuat tabel duplikat.
- Integritas:
  - Donasi non-anonim wajib memiliki `donor_id`; donasi anonim boleh tanpa donatur.
  - Check constraint memastikan donasi uang punya nominal dan tidak punya kolom barang; donasi barang wajib punya nama/kuantitas/satuan dan tidak punya nominal.
  - Foreign key `donasi.donor_id` memakai `RESTRICT` agar donatur bertransaksi tidak bisa dihapus permanen.
  - Nomor transaksi/distribusi unik di level database.
- Model baru: `BaseModel`, `ProgramDonasiModel`, `DonaturModel`, `DonasiModel`, `DistribusiModel`, `DetailDistribusiModel`, lengkap dengan `allowedFields`, `validationRules`, aturan unik dinamis, dan method relasi.
- ERD Mermaid dan urutan migration/seeding didokumentasikan di `README.md`.

### Modul data donatur (Tahap 3a)
- Route `/donatur` dengan filter permission:
  - `donors.view` untuk daftar dan detail.
  - `donors.manage` untuk tambah, ubah, aktif/nonaktif, dan arsip.
- Daftar donatur dengan pencarian (nama, kode, email, telepon), filter jenis, filter status, pagination, dan empty state.
- Form tambah/ubah dengan validasi server (`DonaturRules`), pesan error per field, dan proteksi CSRF.
- Kode donatur dibuat otomatis oleh `DonaturService` dengan format `DNR-YYYYMMDD-NNNN`, unik termasuk terhadap data yang sudah diarsipkan.
- Detail donatur menampilkan kontak, alamat, catatan, metadata, ringkasan donasi, dan riwayat donasi (read-only).
- Aktif/nonaktif donatur dan arsip (soft delete) melalui `DonaturService`.
- Audit log append-only untuk `donor.created`, `donor.updated`, `donor.activated`, `donor.deactivated`, dan `donor.archived`.
- Sidebar menampilkan menu Donatur hanya untuk pengguna dengan `donors.view`.
- Service terdaftar sebagai `donaturService` di `app/Config/Services.php`.

### Modul penerimaan donasi (Tahap 3b, disempurnakan untuk permintaan Tahap 5)
- Route `/donasi` dengan filter permission:
  - `donations.view` untuk daftar, detail, dan bukti penerimaan.
  - `donations.manage` untuk tambah, ubah, verifikasi, dan pembatalan.
- Daftar donasi dengan pencarian (nomor transaksi, nama/kode donatur, catatan), filter jenis, status, rentang tanggal, pagination, dan empty state.
- Ringkasan daftar mengikuti filter aktif: jumlah transaksi, transaksi aktif (pending + terverifikasi), transaksi dibatalkan, nilai uang aktif, dan nilai uang terverifikasi. Donasi berstatus `cancelled` tidak dihitung sebagai penerimaan aktif.
- Bukti penerimaan yang dapat dicetak di `/donasi/{id}/receipt` (permission `donations.view`) dengan layout cetak, identitas yayasan, nomor transaksi, donatur, rincian uang/barang, status, metadata verifikasi, dan kolom tanda tangan.
  - Status `pending` ditandai **MENUNGGU VERIFIKASI** dan dinyatakan belum menjadi bukti pembayaran yang sah.
  - Status `cancelled` ditandai **DIBATALKAN — TIDAK BERLAKU** beserta alasan dan pernyataan tidak dihitung sebagai penerimaan aktif.
  - Identitas donatur anonim tidak ditampilkan.
- Form tambah/ubah dengan bagian uang dan barang yang menyesuaikan jenis donasi, validasi server (`DonasiRules` + aturan bisnis service), pesan error per field, dan CSRF.
- Nomor transaksi otomatis `DON-YYYYMMDD-NNNN`, unik dan memiliki retry bila terjadi tabrakan.
- Aturan bisnis:
  - Donasi baru selalu berstatus `pending`.
  - Hanya donasi `pending` yang dapat diubah.
  - Donasi uang wajib memiliki nominal > 0 dan metode pembayaran; transfer bank wajib memiliki nomor referensi.
  - Donasi barang wajib memiliki nama barang, kuantitas > 0, dan satuan; nilai estimasi opsional.
  - Donasi non-anonim wajib memiliki donatur; donasi anonim boleh tanpa donatur.
  - Tanggal donasi tidak boleh melebihi hari ini.
  - Kolom uang dan barang tidak boleh tercampur; nilai di luar jenis donasi dibuang oleh service.
- Verifikasi mengisi `verified_by` dan `verified_at`; hanya donasi `pending` yang dapat diverifikasi.
- Pembatalan wajib beralasan 5–255 karakter, dapat dilakukan pada status `pending` atau `verified`, dan tidak menghapus data.
- Detail donasi menampilkan rincian uang/barang, donatur (disamarkan untuk transaksi anonim), metadata verifikasi, alasan pembatalan, serta aksi verifikasi/pembatalan.
- Operasi tulis finansial (`create`, `update`, `verify`, `cancel`) memakai transaksi database dan pemeriksaan status kondisional.
- Audit log append-only untuk `donation.created`, `donation.updated`, `donation.verified`, dan `donation.cancelled`.
- Sidebar menampilkan menu Penerimaan Donasi untuk pengguna dengan `donations.view`.
- Service terdaftar sebagai `donasiService` di `app/Config/Services.php`.

### Modul distribusi donasi (Tahap 4 internal — permintaan Tahap 6 pengguna)
- Route `/distribusi` dengan filter permission:
  - `distributions.view` untuk daftar, detail, dan bukti penyaluran.
  - `distributions.manage` untuk tambah, ubah, setujui, tandai tersalurkan, dan pembatalan.
- Daftar distribusi dengan pencarian (nomor distribusi, nama/alamat penerima, tujuan, keterangan), filter jenis bantuan, status, dan rentang tanggal penyaluran, pagination, empty state, serta ringkasan yang mengikuti filter.
- Form tambah/ubah: jenis bantuan uang/barang, tanggal penyaluran (tidak boleh melebihi hari ini), nama & jenis penerima manfaat, alamat, nominal uang, hingga 10 baris barang dinamis (nama, kuantitas, satuan), tujuan penyaluran wajib, dan keterangan tambahan.
- Nomor distribusi otomatis `DSR-YYYYMMDD-NNNN`, unik, dengan retry maksimal 3 kali bila terjadi tabrakan.
- Detail distribusi menampilkan status, rincian uang/barang, penerima, metadata pembuat/penyetuju, alasan pembatalan, aksi sesuai status, serta tautan bukti penyaluran.
- Bukti penyaluran cetak di `/distribusi/{id}/receipt` (permission `distributions.view`): halaman mandiri dengan CSS `@media print`, label status yang eksplisit, rincian barang/uang, dan kolom tanda tangan.
  - `draft` → **DRAF — BELUM SAH** (saldo belum dikurangi).
  - `approved` → **DISETUJUI — SIAP DISALURKAN** (saldo sudah dikurangi).
  - `distributed` → **TERSALURKAN**.
  - `cancelled` → **DIBATALKAN — TIDAK BERLAKU** beserta alasan.
- Alur status: dibuat sebagai `draft` → `approve` menjadi `approved` → `distribute` menjadi `distributed`; pembatalan dapat dilakukan pada `draft`, `approved`, atau `distributed` dan menghasilkan `cancelled` tanpa menghapus baris.
- Hanya distribusi `draft` yang dapat diubah. Transaksi final dikoreksi dengan membatalkan (alasan 5–255 karakter) lalu mencatat ulang; semua perubahan status masuk audit log.

#### Aturan saldo dan stok (dokumentasi resmi)
- **Saldo tidak disimpan** sebagai kolom/tabel saldo. Saldo selalu dihitung ulang dari data sumber agar tidak ada angka yang bisa menyimpang.
- **Uang diterima** = `SUM(donasi.amount)` untuk `donation_type = 'money'`, `status = 'verified'`, dan `deleted_at IS NULL`. Donasi `pending` dan `cancelled` tidak boleh menambah saldo.
- **Uang tersalurkan** = `SUM(distribusi.amount)` untuk `distribution_type = 'money'`, `status IN ('approved','distributed')`, dan `deleted_at IS NULL`.
- **Saldo uang tersedia** = uang diterima − uang tersalurkan. Saldo tidak boleh negatif; persetujuan ditolak bila nominal melebihi saldo.
- **Stok barang diterima** = `SUM(donasi.goods_quantity)` untuk donasi barang `verified` yang belum dihapus, dikelompokkan per pasangan nama barang + satuan yang dinormalisasi (huruf kecil, spasi berlebih dirapikan).
- **Stok barang tersalurkan** = `SUM(detail_distribusi.quantity)` untuk distribusi barang `approved`/`distributed` yang belum dihapus, dikelompokkan dengan normalisasi yang sama.
- **Stok tersedia** = diterima − tersalurkan per pasangan nama + satuan. Tidak ada konversi satuan: `kg` dan `liter` adalah stok berbeda, dan `KG` dianggap sama dengan `kg` setelah normalisasi.
- **Draf tidak mengurangi saldo.** Saldo baru diperiksa dan dikunci saat `approve`. Status `approved` dan `distributed` sama-sama mengurangi saldo, sehingga menandai tersalurkan tidak mengurangi saldo dua kali.
- **Pembatalan otomatis melepas saldo** karena status `cancelled` tidak pernah dihitung pada penyaluran aktif.
- **Kolam dana global:** seluruh donasi dan distribusi saat ini memakai `program_id NULL`. Alokasi/saldo per program belum diaktifkan agar dana program berbeda tidak tercampur tanpa aturan.
- **Transaksi bersamaan:** sebelum menghitung saldo, service menulis/mengunci baris pada tabel `distribution_balance_locks` (`money` atau `goods:<nama>|<satuan>`) dengan `INSERT ... ON DUPLICATE KEY UPDATE` + `SELECT ... FOR UPDATE` di dalam transaksi database. Permintaan kedua untuk kunci yang sama menunggu hingga transaksi pertama selesai, lalu menghitung ulang saldo, sehingga saldo yang sama tidak dapat dipakai dua kali.
- Seluruh operasi tulis (`create`, `update`, `approve`, `markDistributed`, `cancel`) memakai transaksi database dan pemeriksaan status kondisional.
- Audit log append-only untuk `distribution.created`, `distribution.updated`, `distribution.approved`, `distribution.distributed`, dan `distribution.cancelled` (termasuk alasan dan penanda `released_balance`).
- Sidebar menampilkan menu Distribusi Donasi untuk pengguna dengan `distributions.view`.
- Service terdaftar sebagai `distribusiService` di `app/Config/Services.php`.
- Migration baru: `2026-10-31-140001_AddNotesToDistribusiTable` (kolom `notes`) dan `2026-10-31-140002_CreateDistributionBalanceLocksTable` (tabel kunci saldo). Migration lama tidak diubah.

### Autentikasi
- Login dengan username atau email.
- Password hash bcrypt (`password_hash` / `password_verify`).
- Regenerasi session setelah login dan logout.
- Pembatasan percobaan login (5 percobaan / 15 menit per IP + identitas).
- Akun nonaktif tidak dapat login; sesi akun yang dinonaktifkan dibersihkan.
- Redirect tujuan awal yang aman setelah login.
- Logout hanya via POST + CSRF.

### Otorisasi RBAC
- Role memiliki banyak permission melalui `role_permissions`.
- Pengecekan permission di route dan view (`can()`).
- Admin selalu diizinkan untuk mencegah lockout total.
- Role sistem (`admin`) tidak dapat diubah/dihapus.
- Role yang masih dipakai pengguna tidak dapat dihapus.

| Role | Slug | Hak akses utama | Batasan |
|---|---|---|---|
| Admin | `admin` | Seluruh permission: pengguna, role, audit, donatur, donasi, distribusi, laporan | Role sistem; tidak dapat dihapus; administrator aktif terakhir tidak dapat dinonaktifkan/diturunkan |
| Keuangan | `keuangan` | Dashboard, donatur, penerimaan donasi, laporan | Tidak dapat mengelola pengguna/role, audit, atau distribusi |
| Distribusi | `distribusi` | Dashboard, melihat donasi, mengelola distribusi | Tidak dapat mencatat/mengubah donasi, laporan, atau administrasi |
| Pimpinan | `pimpinan` | Dashboard, melihat donasi dan distribusi, laporan, audit | Read-only; tidak dapat mengubah transaksi |

Permission diperiksa di server melalui filter route `permission:nama.permission`; menyembunyikan tombol/menu di view hanya pelengkap tampilan, bukan kontrol akses. Akses langsung ke URL tanpa permission diarahkan ke `/profile` dengan pesan "Akses ditolak.".

### Dashboard
- Ringkasan jumlah pengguna, pengguna aktif/nonaktif, role, permission.
- Distribusi pengguna per role.
- Pengguna terbaru.
- Jumlah login hari ini.
- Aktivitas audit terbaru (hanya jika punya `audit.view`).

### Manajemen pengguna
- Daftar pengguna + pencarian, filter status, filter role, pagination.
- Tambah pengguna dengan validasi server dan konfirmasi password.
- Ubah pengguna, termasuk role/status dan password opsional.
- Aktif/nonaktifkan pengguna.
- Arsip pengguna = soft delete (baris tetap ada).
- Guard: tidak bisa menonaktifkan/mengarsipkan akun sendiri.
- Guard: tidak bisa mengubah role akun sendiri.
- Guard: Admin aktif terakhir tidak dapat dinonaktifkan atau kehilangan role Admin.

### Manajemen role
- Daftar role beserta jumlah pengguna dan permission.
- Tambah/ubah role dengan pemilihan permission.
- Hapus role non-sistem yang tidak dipakai.
- Operasi create/update/delete role + permission memakai transaksi database.
- Permission ID divalidasi dan dibersihkan dari ID palsu.

### Audit log
- Mencatat login sukses/gagal, lockout, logout, perubahan user, role, profil, dan password.
- Menyimpan aktor, aksi, entity, deskripsi, nilai lama/baru, IP, user agent, waktu.
- Nilai sensitif (password/hash/token/secret) disanitasi sebelum disimpan.
- Halaman daftar + detail audit dengan pencarian, filter aksi, dan pagination.
- Tidak ada endpoint untuk mengubah/menghapus audit log.

### Profil
- Ubah nama, email, telepon sendiri.
- Ubah password dengan verifikasi password saat ini.
- Password baru harus berbeda dari password lama.
- Semua perubahan dicatat ke audit log.

## Perubahan terakhir yang diverifikasi

1. **Perbaikan bug pagination** di `app/Views/users/index.php` dan `app/Views/audit/index.php`.
   - Penyebab: `Pager::getTotal()` dipanggil tanpa nama group, dan `getPerPageStart()`/`getPerPageEnd()` adalah method `PagerRenderer`, bukan `Pager`.
   - Perbaikan: memakai `$pager->getDetails('users')` / `$pager->getDetails('audit')` lalu menghitung rentang data.
   - Hasil: `/users` dan `/audit-logs` kembali HTTP 200.

2. **Perbaikan kelas Bootstrap tidak valid** di halaman login/dashboard.
3. **Perbaikan double-submit JS** agar menghormati `event.defaultPrevented`.
4. **`UserModel::findWithRole()`** tidak lagi memilih `password_hash`.
5. **Query daftar pengguna** dibatasi ke kolom yang dibutuhkan sehingga `password_hash` tidak ikut ke view.
6. **Penambahan test nyata** untuk auth, CSRF, RBAC, user CRUD, role service, audit, validasi, dan profil.
7. **Pemisahan navbar dan footer menjadi partial** (`partials/topbar.php`, `partials/footer.php`); `layouts/main.php` kini hanya merangkai sidebar, topbar, konten, dan footer.
8. **Penambahan template kerangka laporan** (`TemplateController::report`, route `template/laporan`, view `templates/report.php`) beserta test akses berbasis permission.
9. **Dokumentasi CDN** dan struktur view di `README.md`.

### Tahap 2 — skema database
10. **Lima migration baru** untuk `program_donasi`, `donatur`, `donasi`, `distribusi`, dan `detail_distribusi`.
11. **Perbaikan urutan parameter foreign key**: CI4 memakai `onUpdate, onDelete`; FK referensi opsional kini benar-benar `ON DELETE SET NULL`, dan `donasi.donor_id` memakai `ON DELETE RESTRICT`.
12. **Check constraint** untuk nominal/kuantitas positif, konsistensi tipe uang/barang, anonymitas donasi, dan periode program.
13. **Model baru** dengan `allowedFields`, `validationRules`, aturan unik dinamis (`validateForSave()`), relasi, serta pemeriksaan aturan silang donasi/distribusi.
14. **Seeder administrator tanpa password hardcoded**: password wajib dari environment atau hash bcrypt.
15. **ERD Mermaid** dan urutan migration/seeding ditambahkan ke `README.md`.
16. **12 test database baru** untuk tabel, decimal, anonim, check constraint, FK, unique, cascade, soft delete, dan validasi unik.

### Tahap 3a — modul data donatur
17. **Controller, service, dan validation baru**: `DonaturController`, `DonaturService`, `DonaturRules`.
18. **Route `/donatur`** dengan pemisahan `donors.view` dan `donors.manage`, termasuk detail donatur.
19. **Penomoran donatur otomatis** `DNR-YYYYMMDD-NNNN` yang aman terhadap data terarsip dan retry bila terjadi tabrakan kode.
20. **Audit log donatur** untuk create, update, aktivasi, penonaktifan, dan arsip.
21. **View donatur** (daftar, formulir, detail) dengan pencarian, filter, pagination, escaping, dan navigasi sidebar.
22. **18 test baru**: 8 test service/database dan 10 test HTTP/RBAC/CSRF.

### Tahap 3b — pencatatan donasi
23. **Controller, service, dan validation baru**: `DonasiController`, `DonasiService`, `DonasiRules`.
24. **Route `/donasi`** dengan pemisahan `donations.view` dan `donations.manage`.
25. **Penomoran transaksi otomatis** `DON-YYYYMMDD-NNNN`, unik dan retry saat tabrakan.
26. **Alur status donasi**: `pending` → `verified` → `cancelled`, dengan pembatalan beralasan dan tanpa hard delete.
27. **Aturan uang/barang dan anonymitas** divalidasi di service dan konsisten dengan check constraint database.
28. **Transaksi database** untuk create/update/verify/cancel, termasuk update status kondisional.
29. **Audit log donasi** untuk create, update, verifikasi, dan pembatalan.
30. **View donasi** (daftar, formulir, detail, bukti penerimaan cetak) dengan filter, badge status, rincian uang/barang, serta aksi verifikasi/pembatalan.
31. **30 test baru**: 15 test service/database dan 15 test HTTP/RBAC/CSRF.

### Penyelarasan role dan hak akses (permintaan Tahap 3 — autentikasi & RBAC)
32. **Role awal diselaraskan menjadi Admin, Keuangan, Distribusi, Pimpinan** pada `RoleSeeder`, `RolePermissionSeeder`, `UserSeeder`, `AuthService`, `UserModel`, `UserService`, dan badge role.
   - `UserModel::ROLE_ADMIN_SLUG = 'admin'` menjadi satu sumber kebenaran untuk bypass permission dan guard administrator terakhir.
   - Rename role lama dilakukan in-place lewat `legacy_slug`, sehingga pengguna yang sudah ada tidak kehilangan role dan tidak terbentuk role duplikat.
   - Hanya `admin` yang berstatus role sistem.
33. **Matriks permission baru**: Admin 15, Keuangan 6, Distribusi 5, Pimpinan 5 (total 31 mapping).
   - Keuangan tidak lagi memiliki `distributions.*`; Distribusi tidak memiliki `donations.manage` dan `reports.view`.
   - Pimpinan tetap read-only dan mendapat `audit.view`.
34. **Test baru**: `tests/database/RolePermissionTest.php` (5 test, 30 assertion) dan `tests/feature/RoleAccessTest.php` (4 test, 44 assertion), plus test logout yang membersihkan sesi pada `AuthTest`.
35. **Autentikasi tidak dibangun ulang**: login username/email, hash bcrypt, regenerasi sesi, CSRF, throttling 5 percobaan/15 menit, logout POST+CSRF, dan audit login sudah ada sejak Tahap 1 dan diverifikasi ulang.

### Penyempurnaan modul Manajemen Data Donatur (permintaan Tahap 4 pengguna)
36. **Pencegahan duplikasi donatur** di `DonaturService`: email pada donatur yang belum diarsipkan tidak boleh sama (tanpa membedakan huruf besar/kecil) dan kombinasi nama + nomor telepon dibandingkan dalam bentuk ternormalisasi (spasi/huruf besar/kecil/tanda baca telepon diabaikan). Pesan error menyebut kode donatur yang sudah ada.
37. **Detail donatur menampilkan ID** di samping kode, email, telepon, alamat, catatan, serta tanggal dibuat/diperbarui.
38. **Dokumentasi form** menjelaskan bahwa email dan telepon opsional, tetapi email serta kombinasi nama + telepon diperiksa terhadap duplikasi.
39. **Test tambahan**: pencarian berdasarkan nama/telepon/email, pagination dua halaman, akses edit/update/arsip tanpa izin, escaping output XSS, donatur tanpa email/telepon, penolakan duplikasi, dan validasi panjang field.

### Penyempurnaan modul Penerimaan Donasi (permintaan Tahap 5 pengguna)
40. **Bukti penerimaan cetak** baru: route `GET /donasi/{id}/receipt` (`donations.view`), method `DonasiController::receipt()`, dan view mandiri `app/Views/donasi/receipt.php` dengan tombol cetak, CSS `@media print`, serta tanda status yang jelas.
41. **Status pada bukti penerimaan tidak menyesatkan**: `pending` menyatakan belum terverifikasi dan bukan bukti pembayaran sah; `verified` menyatakan terverifikasi; `cancelled` menyatakan tidak berlaku dan menampilkan alasan pembatalan. Ini memenuhi aturan agar penyimpanan formulir tidak diklaim sebagai pembayaran terverifikasi.
42. **Ringkasan penerimaan** pada daftar: transaksi sesuai filter, transaksi aktif, transaksi dibatalkan, nilai uang aktif, dan nilai uang terverifikasi. Nilai aktif mengecualikan status `cancelled`.
43. **Test baru**: filter periode + jenis + status, ringkasan yang mengecualikan pembatalan, bukti penerimaan terverifikasi/pending/dibatalkan, privasi donasi anonim pada bukti, akses guest, nominal nol/negatif, kuantitas barang nol, dan retry nomor transaksi saat terjadi duplikat konkuren.
44. **Tidak ada migration baru**: skema `donasi` yang ada sudah memakai `DECIMAL(15,2)`, unique index `transaction_number`, check constraint nominal/kuantitas/tipe, dan kolom status/verifikasi/pembatalan yang dibutuhkan.

### Modul distribusi donasi (permintaan Tahap 6 pengguna / Tahap 4 internal)
45. **Migration baru** tanpa mengubah migration lama: `2026-10-31-140001_AddNotesToDistribusiTable` (kolom `notes` untuk keterangan tambahan) dan `2026-10-31-140002_CreateDistributionBalanceLocksTable` (tabel kunci saldo). Keduanya memanggil `resetDataCache()` agar `fieldExists()`/`tableExists()` benar setelah database test di-refresh.
46. **Controller, service, dan validation baru**: `DistribusiController`, `DistribusiService`, dan `DistribusiRules`; `DistribusiModel` ditambah `ACTIVE_STATUSES`, kolom `notes`, serta relasi `withRelations()`/`findWithRelations()`.
47. **Route `/distribusi`** dengan pemisahan `distributions.view` (daftar, detail, bukti) dan `distributions.manage` (tambah, ubah, setujui, tandai tersalurkan, batalkan).
48. **Saldo dihitung dari data sumber**, bukan disimpan: uang diterima dari donasi `money` berstatus `verified`; uang tersalurkan dari distribusi `money` berstatus `approved`/`distributed`; stok barang per pasangan nama+satuan ternormalisasi. Aturan lengkap ada di bagian "Aturan saldo dan stok".
49. **Pencegahan saldo negatif dan pemakaian ganda**: persetujuan menolak nominal/stok melebihi saldo, lalu memakai `distribution_balance_locks` + `SELECT ... FOR UPDATE` di dalam transaksi. Test membuktikan koneksi kedua benar-benar menunggu kunci yang sama.
50. **Alur status dan audit**: `draft` → `approved` → `distributed`, pembatalan beralasan 5–255 karakter. Hanya draf yang dapat diubah. Audit append-only: `distribution.created`, `distribution.updated`, `distribution.approved`, `distribution.distributed`, `distribution.cancelled`.
51. **Tampilan**: daftar dengan filter/pencarian/ringkasan/saldo, formulir dinamis maksimal 10 barang, detail dengan aksi sesuai status, bukti penyaluran siap cetak, dan menu sidebar khusus `distributions.view`.
52. **31 test baru**: 15 test service/database (saldo uang, stok barang, normalisasi nama/satuan, draf, pembatalan, status, penomoran, audit, kunci konkuren) dan 16 test HTTP (CRUD, alur status, filter, bukti, escaping XSS, RBAC, guest, CSRF).

### Migrasi ke Google Antigravity & Perbaikan Bug Lingkungan XAMPP (2026-11-01)
53. **Penyelesaian Bug 1 — Apache Document Root & Index Of**:
   - Masalah: URL `http://localhost/donasi_yayasan/` menampilkan daftar file (Index of) dan mengekspos file sensitif internal.
   - Perbaikan: Membuat file `.htaccess` pada root proyek `donasi_yayasan` dengan `Options -Indexes`, memblokir akses ke file sensitif (`.env`, `composer.*`, `spark`, `.git*`, dll.), memblokir direktori internal (`app/`, `vendor/`, `writable/`, `tests/`), dan me-rewrite request ke folder `public/`.
   - Konfigurasi `app.baseURL` pada `.env` diperbarui dari `http://localhost:8080/` menjadi `http://localhost/donasi_yayasan/`.
54. **Penyelesaian Bug 2 — Ekstensi PHP intl & Class Locale**:
   - Masalah: Error fatal `Class "Locale" not found` saat aplikasi dijalankan melalui Apache.
   - Penyebab: Konfigurasi `extension=intl` di `C:\xampp\php\php.ini` sudah aktif, namun Apache belum di-restart sejak file tersebut diubah.
   - Perbaikan: Me-restart proses Apache XAMPP dan memverifikasi modul `intl` serta class `Locale` aktif 100% di bawah handler Apache.
55. **Penyelesaian Bug 3 — Koneksi MySQL**:
   - Masalah: Sebelumnya koneksi database ditolak (`target machine actively refused it`).
   - Verifikasi & Perbaikan: MySQL XAMPP aktif pada port 3306 (MariaDB 10.4). Dibuat database aplikasi `donasi_yayasan` dan database test `donasi_yayasan_test` dengan charset `utf8mb4_general_ci` tanpa menyentuh database lain.
   - Migrasi & Seeding: Menjalankan `spark migrate` (12 migration) dan `spark db:seed DatabaseSeeder` (Role, Permission, Mapping, Akun Admin & Demo) secara idempoten.
56. **Penyelesaian Bug 4 — Login, Autentikasi, dan Fitur Aplikasi**:
   - Pengujian HTTP nyata membuktikan akses `http://localhost/donasi_yayasan/` berhasil me-redirect ke login (HTTP 200).
   - Pengujian login berhasil untuk semua role: Admin (`admin`), Keuangan (`pengurus`), Distribusi (`petugas`), Pimpinan (`auditor`).
   - Hak akses RBAC terverifikasi: Keuangan tidak dapat mengakses `/distribusi` (302 ke profil), Distribusi dan Pimpinan memiliki akses sesuai matriks permission.
   - Halaman Dashboard, Data Donatur, Penerimaan Donasi, Distribusi Donasi, dan Bukti Cetak berjalan normal.
   - Proses logout via POST + CSRF terverifikasi membersihkan sesi.
   - Verifikasi targeted automated test PHPUnit (`AuthTest`, `RoleAccessTest`, `DistributionCrudTest`, `DonasiCrudTest`, `DonaturCrudTest`, `UserCrudTest`) lulus 100%.

### Prompt 7 — Pengembangan Dashboard dan Modul Laporan (2026-11-01)
57. **Dashboard Operasional & Visualisasi Chart.js Aktual**:
   - 6 KPI Card utama: Total penerimaan uang sah terverifikasi, Total donasi tersalurkan aktif, Saldo donasi belum didistribusikan (Kas siap pakai), Jumlah seluruh donatur terdaftar & aktif, Jumlah transaksi penerimaan donasi, dan Jumlah transaksi distribusi donasi.
   - 4 Visualisasi grafik interaktif Chart.js 4.4.7 menggunakan data aktual database (`LaporanService::getMonthlyChartData()`):
     1. Grafik batang perbandingan penerimaan vs distribusi bulanan (Jan–Des).
     2. Grafik rasio akumulasi dana dihimpun vs tersalurkan vs saldo sisa.
     3. Grafik trend perkembangan saldo kas kumulatif berjalan.
     4. Perkembangan donasi berdasarkan periode tahun berjalan.
   - Tabel 5 transaksi donasi masuk terbaru dan 5 penyaluran donasi terbaru.
58. **Modul Laporan Penerimaan Donasi (`/laporan/penerimaan`)**:
   - Menampilkan seluruh transaksi donasi uang dan barang dengan paginasi.
   - Filter dinamis: tanggal awal, tanggal akhir, donatur, metode pembayaran, jenis donasi (uang/barang), status (verified, pending, cancelled), dan pencarian kata kunci.
   - Ringkasan total transaksi terfilter, total nominal uang, nominal terverifikasi, dan kuantitas barang.
   - Cetak laporan cetak/PDF landscape A4 dengan kop resmi yayasan, metadata, dan tanda tangan.
   - Ekspor Excel (.xls) berformat tabel rapi.
59. **Modul Laporan Distribusi Donasi (`/laporan/distribusi`)**:
   - Menampilkan seluruh transaksi penyaluran uang dan barang dengan paginasi.
   - Filter dinamis: periode tanggal, tipe penerima (individu/organisasi/komunitas), jenis bantuan, status, dan pencarian kata kunci.
   - Menampilkan rincian barang dari `detail_distribusi`.
   - Ringkasan total transaksi terfilter, total dana aktif tersalurkan, dan jumlah penyaluran barang.
   - Cetak laporan cetak/PDF landscape A4 dengan kop resmi yayasan dan kolom tanda tangan.
   - Ekspor Excel (.xls).
60. **Laporan Rekapitulasi Donasi (`/laporan/rekapitulasi`)**:
   - Menampilkan total penerimaan, total distribusi, saldo donasi, jumlah donatur, dan arus kas per bulan.
   - Perhitungan saldo mengikuti aturan bisnis tunggal `DistribusiService` (Diterima − Tersalurkan).
   - Cetak resmi rekapitulasi cetak/PDF portrait A4 dan ekspor Excel (.xls).
61. **Hak Akses & Pengujian Terverifikasi**:
   - Hak akses diatur oleh filter `permission:reports.view`: Admin (`admin`), Keuangan (`pengurus`), dan Pimpinan (`auditor`) memiliki akses; Distribusi (`petugas`) ditolak (HTTP 302).
   - Test otomatis baru `tests/feature/ReportTest.php` (6 test, 82 assertion) lulus 100%.
   - Pengujian HTTP nyata di Apache mengonfirmasi seluruh rute dashboard, laporan, cetak, dan ekspor Excel mengembalikan HTTP 200.

## Hasil pengujian terakhir (2026-11-01)

### PHPUnit
```text
PHPUnit 10.5.66
OK (193 tests, 1102 assertions)
Time: 11:25.649
```
Verifikasi targeted modul distribusi:
```text
DistributionServiceTest: OK (15 tests, 57 assertions)
DistributionCrudTest:    OK (16 tests, 184 assertions)
```
Verifikasi targeted regresi modul donasi + distribusi:
```text
DistributionServiceTest + DistributionCrudTest + DonasiServiceTest + DonasiCrudTest:
OK (73 tests, 513 assertions)
Time: 05:33.662
```
Verifikasi targeted penyempurnaan donatur:
```text
DonaturServiceTest + DonaturCrudTest + ValidationTest:
OK (41 tests, 213 assertions)
```
Verifikasi targeted penyelarasan role:
```text
RolePermissionTest: OK (5 tests, 30 assertions)
RoleAccessTest:     OK (4 tests, 44 assertions)
AuthTest:           OK (10 tests, 42 assertions)
```
Verifikasi targeted modul donasi (setelah penyempurnaan penerimaan):
```text
DonasiServiceTest + DonasiCrudTest + DonationSchemaTest:
OK (54 tests, 301 assertions)
Time: 12:41.982
```
Verifikasi targeted modul donatur (setelah penyempurnaan):
```text
DonaturServiceTest + DonaturCrudTest + ValidationTest:
OK (41 tests, 213 assertions)
```
Verifikasi ulang setelah test password diubah membaca `.env`:
```text
AuthTest + ProfileTest: OK (15 tests, 75 assertions)
```

Cakupan test baru:
- `tests/feature/AuthTest.php` — login, email login, password salah, akun nonaktif, CSRF, throttling, logout.
- `tests/feature/AuthorizationTest.php` — guest redirect, akses per role, audit, profil, sesi nonaktif, akses/tolak template laporan.
- `tests/feature/UserCrudTest.php` — create/update/archive lewat HTTP, validasi duplikat, guard akun sendiri, RBAC.
- `tests/feature/ProfileTest.php` — update profil, email duplikat, ganti password, password sama, guest.
- `tests/database/UserManagementTest.php` — hashing, audit, guard admin terakhir, soft delete, toggle status.
- `tests/database/RoleManagementTest.php` — create/update/delete role, transaksi permission, role sistem.
- `tests/database/ValidationTest.php` — aturan user/role/profil/login serta validasi donatur (email opsional, format email/telepon, panjang field).
- `tests/database/DonationSchemaTest.php` — tabel tahap 2, decimal, donasi anonim/uang/barang, check constraint, foreign key, unique, cascade detail, soft delete, validasi unik.
- `tests/database/DonaturServiceTest.php` — penomoran otomatis, audit create/update/status/archive, soft delete, riwayat donasi tetap utuh, validasi email, donatur tanpa kontak, duplikasi email dan nama+telepon.
- `tests/feature/DonaturCrudTest.php` — create/update/status/archive lewat HTTP, validasi email, detail, pencarian nama/telepon/email, pagination, escaping XSS, RBAC Keuangan/Distribusi/Pimpinan, guest redirect, CSRF, notifikasi duplikasi.
- `tests/database/DonasiServiceTest.php` — create uang/barang/anonim, aturan nominal/metode/referensi/tanggal, nominal nol/negatif, kuantitas barang nol, penomoran unik, retry nomor saat duplikat konkuren, edit hanya pending, donatur terarsip, verifikasi, pembatalan beralasan, audit.
- `tests/feature/DonasiCrudTest.php` — create/update/verifikasi/pembatalan lewat HTTP, validasi, filter periode/jenis/status, ringkasan yang mengecualikan pembatalan, bukti penerimaan cetak untuk status pending/verified/cancelled, privasi donasi anonim, RBAC Keuangan/Pimpinan, guest redirect, CSRF.
- `tests/database/RolePermissionTest.php` — nama/slug role awal, role sistem tunggal, dan matriks permission Admin/Keuangan/Distribusi/Pimpinan.
- `tests/feature/RoleAccessTest.php` — akses/tolak HTTP per role, penolakan POST transaksi oleh role read-only, dan akses URL langsung tanpa permission.
- `tests/database/DistributionServiceTest.php` — saldo uang hanya dari donasi terverifikasi, draf tidak mengurangi saldo, pembatalan melepas saldo, penolakan saldo/stok tidak cukup, normalisasi nama+satuan barang, satuan berbeda = stok berbeda, pembatalan distribusi barang, edit hanya draf, guard alur status, nomor distribusi unik, audit semua perubahan status, dan pembuktian kunci saldo menahan koneksi kedua.
- `tests/feature/DistributionCrudTest.php` — create uang/barang lewat HTTP, persetujuan dan penolakan saldo, alur penuh sampai tersalurkan, pembatalan beralasan, batas edit draf, filter jenis/periode/pencarian, bukti penyaluran per status, daftar barang pada bukti, escaping XSS, guest redirect, RBAC Keuangan/Distribusi/Pimpinan, dan CSRF.
- Test scaffold CI4 tetap lulus.

### PHP lint
- Semua **150 file PHP** di `app/` dan `tests/` lolos `php -l` pada pemeriksaan sebelumnya.
- 16 file PHP yang diubah pada penyelarasan role lolos `php -l` (`CHECKED=16 FAILED=0`).
- 6 file PHP yang diubah pada penyempurnaan modul donatur lolos `php -l` (`CHECKED=6 FAILED=0`).
- 7 file PHP yang diubah pada penyempurnaan penerimaan donasi lolos `php -l` (`CHECKED=7 FAILED=0`). Setelah test XSS bukti penerimaan ditambahkan, `tests/feature/DonasiCrudTest.php` di-lint ulang dan tetap lolos.
- 13 file PHP baru/ubah pada modul distribusi lolos `php -l` (`CHECKED=13 FAILED=0`), ditambah 3 file test/service yang diubah pada iterasi perbaikan juga lolos.

### Verifikasi skema database
- `spark migrate` menjalankan 5 migration Tahap 2 tanpa error pada database `donasi_yayasan`.
- `spark migrate:rollback` + `spark migrate` diuji ulang setelah perbaikan FK; keduanya berhasil.
- `information_schema` mengonfirmasi FK `SET NULL`, `RESTRICT`, dan `CASCADE` sesuai desain, serta 12 check constraint aktif.
- `spark migrate` pada 2026-11-01 menjalankan dua migration distribusi pada database `donasi_yayasan`; `SHOW COLUMNS` mengonfirmasi kolom `distribusi.notes` dan tabel `distribution_balance_locks` ada.
- `spark db:seed DatabaseSeeder` berjalan idempoten; tidak ada data donasi/distribusi palsu yang dimasukkan ke database aplikasi.

### Verifikasi HTTP nyata
Dengan server `php spark serve` di `127.0.0.1:8080`:
- `GET /` → 302 ke `/login`.
- `GET /login` → 200.
- `POST /login` tanpa CSRF → 403.
- Login admin valid → 303 ke `/dashboard`.
- `GET /dashboard`, `/users`, `/roles`, `/audit-logs`, `/profile` → 200.
- `GET /template/laporan` (admin) → 200, memuat topbar partial, footer partial, filter/kartu/tabel laporan, dan label "Belum ada data".
- Logout POST + CSRF → 303 ke `/login`; akses dashboard setelah logout → 302 ke `/login`.
- Login auditor → `/audit-logs` 200, `/users` 302 ke `/profile`, `/dashboard` 200.
- Login petugas → `/template/laporan` 302 ke `/profile` (tidak punya `reports.view`).
- Setelah migration Tahap 2, login admin dan akses `/dashboard`, `/users`, `/roles`, `/audit-logs`, `/profile`, `/template/laporan` tetap → 200.
- Setelah modul donatur ditambahkan:
  - Login admin/petugas/auditor → 303 ke `/dashboard`.
  - `GET /donatur` (admin) → 200; `GET /donatur/new` (admin) → 200.
  - `POST /donatur` dengan email tidak valid (admin + CSRF valid) → 303 ke `/donatur/new`; tidak ada baris donatur tersimpan.
  - `GET /donatur` (petugas) → 200; `GET /donatur/new` (petugas) → 302 ke `/profile` (tidak punya `donors.manage`).
  - `GET /donatur` (auditor) → 302 ke `/profile` (tidak punya `donors.view`).
  - Database aplikasi tetap berisi **0 baris donatur** setelah smoke test; tidak ada data fiktif yang dimasukkan.
- Setelah modul donasi ditambahkan:
  - Login admin → `GET /donasi` 200 (memuat tombol Catat Donasi) dan `GET /donasi/new` 200 (memuat bagian uang dan barang).
  - `POST /donasi` dengan data tidak valid (admin + CSRF valid) → kembali ke form dengan pesan validasi; **tidak ada donasi tersimpan**.
  - `GET /donasi/999999` → 404.
  - Login auditor → `GET /donasi` 200; `GET /donasi/new` dan `POST /donasi` → 302 ke `/profile` (tidak punya `donations.manage`).
  - Guest `GET /donasi` → halaman login.
  - Database aplikasi tetap berisi **0 baris donasi dan 0 baris donatur** setelah smoke test; tidak ada data transaksi fiktif yang dimasukkan.
- Setelah penyelarasan role (smoke test HTTP nyata, server `php spark serve`):
  - `spark db:seed DatabaseSeeder` dijalankan ulang pada database `donasi_yayasan`; role menjadi `admin`, `keuangan`, `distribusi`, `pimpinan` dengan pengguna lama tetap terhubung (admin→admin, pengurus→keuangan, petugas→distribusi, auditor→pimpinan).
  - Login `admin` → `/dashboard`; `/users` dan `/donasi` → 200.
  - Login `pengurus` (Keuangan) → `/donasi/new` dan `/template/laporan` → 200; `/users` dan `/audit-logs` → `/profile`.
  - Login `petugas` (Distribusi) → `/donasi` → 200; `/donasi/new` dan `/template/laporan` → `/profile`.
  - Login `auditor` (Pimpinan) → `/donasi` dan `/template/laporan` → 200; `/donasi/new` dan `/roles` → `/profile`.
  - Logout POST + CSRF → `/login`; akses `/dashboard` setelah logout → `/login`.
  - Guest `GET /users` → `/login`.
  - Tidak ada entri ERROR/CRITICAL baru di `writable/logs`; database aplikasi tetap 0 baris donasi dan 0 baris donatur.
- Setelah penyempurnaan modul donatur (smoke test HTTP read-only, tanpa membuat data):
  - `GET /donatur` (admin) → 200 dengan empty state; `GET /donatur?search=nama` → 200; `GET /donatur/new` → 200.
  - `GET /donatur` dan `/donatur/new` (Keuangan) → 200.
  - `GET /donatur` (Distribusi) → 200; `/donatur/new` → `/profile`.
  - `GET /donatur` (Pimpinan) → `/profile`.
  - Guest `GET /donatur` → `/login`.
  - Database aplikasi tetap **0 donatur, 0 donasi, 0 distribusi**; tidak ada data fiktif yang dimasukkan.
- Setelah penyempurnaan modul penerimaan donasi (smoke test HTTP read-only, tanpa membuat data, `php spark serve` di `127.0.0.1:8080`):
  - Login admin/keuangan/distribusi/pimpinan → 303.
  - Admin: `GET /donasi` 200, `GET /donasi/new` 200, `GET /donasi/999999` 404, `GET /donasi/999999/receipt` 404, `GET /donatur` 200.
  - Keuangan: `GET /donasi` 200, `GET /donasi/new` 200, `GET /donasi/999999/receipt` 404.
  - Distribusi: `GET /donasi` 200; `GET /donasi/new` → 302 ke `/profile` (tanpa `donations.manage`).
  - Pimpinan: `GET /donasi` 200; `GET /donasi/new` → 302 ke `/profile`; `GET /donasi/999999/receipt` 404.
  - Guest: `GET /donasi` dan `GET /donasi/1/receipt` → 302 ke `/login`.
  - Ringkasan pada `GET /donasi` menampilkan `summary-total=0`, `summary-active=0`, `summary-cancelled=0`, `summary-active-money=Rp 0,00`, `summary-verified-money=Terverifikasi: Rp 0,00` pada database kosong.
  - Form `/donasi/new` memuat token CSRF, pilihan donatur, dan tanggal donasi default hari ini.
  - Database aplikasi tetap **0 donatur, 0 donasi, 0 distribusi**; tidak ada data transaksi fiktif yang dimasukkan.
  - Tidak ada baris ERROR/CRITICAL baru pada rentang waktu smoke test; entri ERROR terakhir di `writable/logs/log-2026-10-31.log` berasal dari test negatif (constraint/duplikat) di database test.
- Setelah modul distribusi ditambahkan (smoke test HTTP read-only, tanpa membuat data, `php spark serve` di `127.0.0.1:8080`):
  - Guest `GET /distribusi` → 302 ke `/login`.
  - Admin: `GET /distribusi` → 200 (memuat menu dan panel "Saldo Dana Global") dan `GET /distribusi/new` → 200.
  - Keuangan (`pengurus`): `GET /distribusi` → 302 ke `/profile` (tanpa `distributions.view`); `GET /distribusi/new` → 302 ke `/profile`.
  - Distribusi (`petugas`): `GET /distribusi` → 200 dan `GET /distribusi/new` → 200 (punya `distributions.manage`).
  - Pimpinan (`auditor`): `GET /distribusi` → 200 (read-only); `GET /distribusi/new` → 302 ke `/profile`.
  - Tidak ada baris ERROR/CRITICAL baru pada output server selama smoke test.
  - Database aplikasi tetap **0 donatur, 0 donasi, 0 distribusi**; tidak ada data transaksi fiktif yang dimasukkan.
- Tidak ada error 500 baru di `writable/logs` setelah perbaikan.

## Keterbatasan / catatan jujur

- Modul data donatur, pencatatan donasi uang/barang (termasuk bukti penerimaan cetak), dan distribusi donasi uang/barang (termasuk bukti penyaluran cetak) sudah berfungsi. Modul **laporan belum ada** (route, controller, service, view); `/template/laporan` masih kerangka UI.
- Permission `donations.*` dan `distributions.*` sudah dipakai modul masing-masing; `reports.view` masih disiapkan untuk tahap laporan.
- Alokasi `program_id` pada donasi dan distribusi belum diimplementasikan; kolom tersedia, tetapi modul program belum punya UI dan service selalu menyimpan `NULL`. Saldo masih berupa kolam global, bukan saldo per program.
- Saldo distribusi dihitung ulang dari data sumber pada setiap persetujuan; belum ada tabel/ringkasan saldo harian. Untuk data sangat besar, query agregat ini perlu index dan/atau cache yang dirancang khusus.
- Saldo barang tidak melakukan konversi satuan (`kg` vs `liter`, `dus` vs `pcs`). Nama dan satuan dinormalisasi huruf besar/kecil dan spasi, tetapi varian penulisan lain (mis. `beras premium` vs `beras premi`) dianggap stok berbeda.
- Kunci saldo memakai tabel `distribution_balance_locks`; baris kunci tidak dibersihkan otomatis (jumlahnya terbatas pada kombinasi `money` dan pasangan barang+satuan, sehingga tidak tumbuh per transaksi, tetapi tetap perlu pemeliharaan bila data master berubah drastis).
- Belum ada pemisahan empat mata antara pencatat dan penyetuju distribusi; keduanya memakai `distributions.manage`.
- Bukti penyaluran adalah halaman HTML siap cetak, belum PDF bertanda tangan digital/QR verifikasi. Identitas yayasan pada kop masih generik "YAYASAN".
- Nomor distribusi `DSR-YYYYMMDD-NNNN` memakai tanggal pencatatan sistem, bukan tanggal penyaluran.
- Pencegahan duplikasi donatur memakai pemeriksaan aplikasi: email (tanpa membedakan huruf besar/kecil) dan kombinasi nama + nomor telepon. Belum ada unique index database untuk email, sehingga dua permintaan bersamaan yang sangat jarang masih berpotensi lolos; pemeriksaan membaca kandidat donatur aktif sehingga untuk data sangat besar perlu index/query khusus. Mekanisme ini mencegah duplikasi tidak sengaja pada penggunaan normal.
- Penomoran donatur dan transaksi donasi memakai urutan harian dan retry bila terjadi tabrakan, tetapi belum memakai sequence database khusus untuk beban tulis sangat tinggi.
- Riwayat donasi pada detail donatur sudah terisi otomatis dari modul donasi, tetapi masih read-only.
- Verifikasi dan pembatalan donasi memakai `donations.manage`; belum ada pemisahan empat mata antara pencatat dan pemverifikasi.
- Pembatalan donasi belum meminta dokumen pendukung; alasan wajib diisi dan tercatat di audit.
- Koreksi transaksi final dilakukan dengan membatalkan transaksi lama (alasan + audit) lalu mencatat transaksi baru; belum ada kolom penghubung khusus `replaces_id`/`corrects_id`.
- Bukti penerimaan adalah halaman HTML siap cetak, belum berupa PDF bertanda tangan digital/QR verifikasi. Identitas yayasan pada kop masih generik "YAYASAN" dan belum dapat dikonfigurasi.
- Nomor transaksi `DON-YYYYMMDD-NNNN` memakai tanggal pencatatan sistem, bukan tanggal penerimaan donasi.
- Ringkasan "aktif" menghitung transaksi `pending` dan `verified`; nilai uang terverifikasi ditampilkan terpisah agar transaksi `pending` tidak diklaim sudah sah. Transaksi `cancelled` selalu dikecualikan.
- `/template/laporan` adalah **kerangka UI saja**: kontrol filter dan tombol dinonaktifkan, tidak ada query/data. Ini bukan modul laporan yang berfungsi.
- Check constraint database mengunci konsistensi tipe uang/barang, dan aturan bisnis distribusi (minimal satu rincian barang, kecukupan saldo/stok) divalidasi di `DistribusiService`; belum ada trigger database yang menegakkan saldo.
- Nomor transaksi donasi (`DON-`) dan nomor distribusi (`DSR-`) sama-sama dibuat otomatis di server dengan retry bila terjadi tabrakan.
- Belum ada trigger append-only di level database untuk `audit_logs`; larangan update/delete baru di level aplikasi (tidak ada endpoint).
- Bootstrap 5.3.3 dan Bootstrap Icons 1.11.3 dimuat dari CDN jsDelivr, sehingga tampilan memerlukan koneksi internet. Aset aplikasi sendiri tetap lokal. Untuk mode offline, aset perlu diunduh ke `public/assets/vendor/` dan referensinya diubah.
- Responsivitas diverifikasi lewat struktur grid Bootstrap, breakpoint CSS, dan jalur sidebar off-canvas; belum diuji dengan browser sungguhan (screenshot/device emulation) pada tahap ini.
- Belum ada CI/CD, static analysis (PHPStan/Psalm), atau coverage threshold.
- Belum ada rate limit berbasis database; throttler memakai cache/file handler.
- Belum ada fitur export laporan, backup, atau restore.
- Belum ada repository Git di folder ini; perubahan belum tercatat dalam commit.
- Test penuh bergantung pada MySQL yang berjalan dan database `donasi_yayasan_test`.
- Log lama di `writable/logs/log-2026-10-28.log` masih memuat error pagination sebelum perbaikan; itu historis, bukan error saat ini.
- Demo user hanya untuk pengembangan. Wajib diganti/dinonaktifkan sebelum produksi.
- Role lama (`administrator`, `pengurus`, `petugas`, `auditor`) diganti nama menjadi role baru saat seeder dijalankan. Deployment yang sudah memiliki data **wajib** menjalankan ulang `spark db:seed DatabaseSeeder` (atau minimal `RoleSeeder`, `RolePermissionSeeder`, `UserSeeder`) agar slug, mapping permission, dan role pengguna ikut diperbarui.

## Kredensial demo (lingkungan pengembangan)

| Username | Role | Slug | Sumber password |
|---|---|---|---|
| `admin` | Admin | `admin` | `seed.adminPassword` atau `seed.adminPasswordHash` |
| `pengurus` | Keuangan | `keuangan` | `seed.pengurusPassword` |
| `petugas` | Distribusi | `distribusi` | `seed.petugasPassword` |
| `auditor` | Pimpinan | `pimpinan` | `seed.auditorPassword` |

Username demo dipertahankan agar konfigurasi `.env` lama tetap kompatibel; role-nya sudah dipetakan ke role awal yang baru. Password tidak disimpan di source code maupun dokumen ini; nilainya hanya ada di `.env` lokal. Jangan gunakan konfigurasi demo di produksi.

Diverifikasi 2026-10-28: `spark db:seed DatabaseSeeder` dijalankan ulang (idempoten), keempat akun berstatus `active` di database `donasi_yayasan`, dan login HTTP untuk masing-masing akun berhasil (`/dashboard`, HTTP 200 setelah redirect). Smoke test modul donatur, donasi, dan distribusi tidak menambahkan data fiktif: database aplikasi tetap berisi 0 baris donatur, 0 baris donasi, dan 0 baris distribusi.

## Kesimpulan Final (Prompt 8 Selesai)

Seluruh tahapan proyek (Prompt 1 sampai Prompt 8) telah diselesaikan dengan sukses:
1. **Autentikasi & RBAC:** 4 Role aktif (Admin, Keuangan, Distribusi, Pimpinan) dengan hak akses granular dan audit trail lengkap.
2. **Modul Donatur:** Pengelolaan data donatur, auto-kode `DNR-`, proteksi duplikasi, dan soft delete.
3. **Modul Penerimaan Donasi:** Pencatatan donasi uang/barang, auto-nomor `DON-`, verifikasi, pembatalan beralasan, dan bukti penerimaan cetak resmi.
4. **Modul Distribusi Donasi:** Pencatatan penyaluran bantuan, auto-nomor `DSR-`, aturan saldo dana & stok barang dengan transaksi kunci `distribution_balance_locks`, alur draf→disetujui→tersalurkan, dan bukti penyaluran cetak resmi.
5. **Dashboard Visual:** 6 KPI operasional aktual dan 4 grafik dinamis Chart.js 4.4.7 berbasis agregat SQL riil.
6. **Modul Laporan:** Laporan Penerimaan, Laporan Distribusi, Laporan Rekapitulasi dengan filter dinamis, paginasi, cetak A4 siap PDF bertanda tangan, dan ekspor spreadsheet Excel (.xls).
7. **Pengujian Menyeluruh:** Seluruh **199 test PHPUnit (1184 assertions) lulus 100% tanpa kegagalan**.
8. **Integritas & Keamanan:** Transaksi database finansial, proteksi CSRF, sanitasi output XSS, password bcrypt, proteksi file rahasia (.env), dan penanganan error yang aman.


## Perintah operasional

```powershell
# Menjalankan server pengembangan
C:\xampp\php\php.exe spark serve --host 127.0.0.1 --port 8080

# Migration dan seeder
C:\xampp\php\php.exe spark migrate
C:\xampp\php\php.exe spark db:seed DatabaseSeeder

# Semua test
C:\xampp\php\php.exe vendor\bin\phpunit --no-coverage

# Lint file tertentu
C:\xampp\php\php.exe -l app\Controllers\UserController.php
```
