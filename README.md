# Sistem Inventory Lab Komputer

Sistem manajemen inventory untuk laboratorium komputer dengan fitur lengkap untuk admin dan petugas.

## Teknologi

- **Frontend**: HTML, Tailwind CSS, JavaScript
- **Backend**: PHP Native
- **Database**: MySQL

## Fitur

### Fitur Umum
- ✅ Login & Logout dengan role-based access
- ✅ Dashboard dengan statistik dan grafik
- ✅ Pencarian dan filter barang
- ✅ Riwayat perubahan barang otomatis

### Fitur Admin
- ✅ Manajemen Data Barang (CRUD)
- ✅ Manajemen Laboratorium (CRUD)
- ✅ Manajemen User (CRUD)
- ✅ Laporan dengan export Excel
- ✅ Dashboard lengkap dengan statistik

### Fitur Petugas
- ✅ Melihat Data Barang
- ✅ Menambah Data Barang
- ✅ Mengubah Data Barang
- ✅ Melihat Riwayat Barang
- ✅ Dashboard dengan statistik

## Instalasi

1. Pastikan database sudah dibuat dengan nama `invetori_labkomputer`
2. Pastikan tabel sudah dibuat:
   - `users` (id_user, nama, username, password, role)
   - `barang` (id_barang, nama_barang, jenis, merk, kondisi, jumlah, lokasi_lab)
   - `laboratorium` (id_lab, nama_lab, penanggung_jawab)
   - `riwayat_barang` (id_riwayat, id_barang, tanggal, keterangan)

3. Konfigurasi database di `config/database.php`:
   ```php
   private $host = "localhost";
   private $db_name = "invetori_labkomputer";
   private $username = "root";
   private $password = "";
   ```

4. Buat user admin pertama di database:
   ```sql
   INSERT INTO users (nama, username, password, role) 
   VALUES ('Admin', 'admin', '$2y$10$...', 'admin');
   ```
   (Password harus di-hash dengan password_hash PHP)

## Struktur Folder

```
manjement_sarpras/
├── config/
│   ├── database.php
│   └── session.php
├── includes/
│   └── header.php
├── actions/
│   ├── barang_action.php
│   ├── laboratorium_action.php
│   └── users_action.php
├── index.php (Login)
├── dashboard.php
├── barang.php
├── laboratorium.php
├── users.php
├── riwayat.php
├── laporan.php
└── logout.php
```

## Keamanan

- ✅ Password hashing dengan `password_hash()`
- ✅ Session management
- ✅ Prepared statements (proteksi SQL Injection)
- ✅ Input validation
- ✅ Role-based access control

## Penggunaan

1. Akses `index.php` untuk login
2. Admin dapat mengakses semua fitur
3. Petugas hanya dapat menambah dan mengubah data barang
4. Semua perubahan barang otomatis tercatat di riwayat

## Catatan

- Pastikan session sudah diaktifkan di PHP
- Pastikan ekstensi PDO MySQL sudah aktif
- Untuk production, ubah konfigurasi database sesuai server




