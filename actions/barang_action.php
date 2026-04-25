<?php
/**
 * FILE: actions/barang_action.php
 * FUNGSI: Menangani Logika Backend (Create, Update, Delete) untuk data Barang
 */

require_once '../config/database.php';
require_once '../config/session.php';

// Proteksi halaman: Hanya user yang sudah login yang bisa mengakses file ini
requireLogin();

// Inisialisasi Koneksi Database
$db = (new Database())->getConnection();

// --- 1. HELPER FUNCTIONS ---

/**
 * Fungsi untuk mengambil informasi struktur kolom tabel dari database.
 * Digunakan agar proses INSERT/UPDATE bisa berjalan otomatis (dinamis).
 */
function getColInfo($db, $table) {
    $stmt = $db->query("SHOW COLUMNS FROM $table");
    $info = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $info[$col['Field']] = $col;
    }
    return $info;
}

// Ambil info kolom tabel barang dan cek apakah menggunakan nama 'lokasi_lab' atau 'id_lab'
$barang_cols = getColInfo($db, 'barang');
$lab_column = isset($barang_cols['lokasi_lab']) ? 'lokasi_lab' : (isset($barang_cols['id_lab']) ? 'id_lab' : null);

/**
 * Fungsi Truncate: Memastikan data yang diinput tidak melebihi panjang karakter di DB.
 * Mencegah error "Data too long for column".
 */
$truncate = function($val, $col) use ($barang_cols) {
    if (!isset($barang_cols[$col]['Type']) || !is_string($val)) return $val;
    if (preg_match('/\((\d+)\)/', $barang_cols[$col]['Type'], $m)) {
        return mb_substr($val, 0, (int)$m[1], 'UTF-8');
    }
    return $val;
};

// --- 2. TANGKAP DATA DARI FORM ---

$action    = $_POST['action'] ?? '';    // 'add', 'edit', atau 'delete'
$id_barang = $_POST['id_barang'] ?? ''; // Diperlukan untuk edit & delete

// --- 3. LOGIKA TAMBAH & EDIT ---

if ($action === 'add' || $action === 'edit') {
    // Susun data dari array $_POST
    $data = [
        'nama_barang' => $_POST['nama_barang'] ?? '',
        'jenis'       => $_POST['jenis'] ?? '',
        'merk'        => $_POST['merk'] ?? '',
        'kondisi'     => $_POST['kondisi'] ?? 'baik',
        'jumlah'      => $_POST['jumlah'] ?? 0
    ];

    // Validasi dasar: Field wajib jangan sampai kosong
    if (empty($data['nama_barang']) || empty($data['jenis'])) {
        $_SESSION['error'] = "Nama barang dan jenis wajib diisi!";
        header("Location: ../barang.php"); 
        exit();
    }

    // Masukkan info lab jika kolomnya tersedia di database
    if ($lab_column) {
        $data[$lab_column] = $_POST['lokasi_lab'] ?? '';
    }

    try {
        // Mulai transaksi database (agar jika log gagal, data barang juga batal disimpan)
        $db->beginTransaction();
        
        if ($action === 'add') {
            // --- BAGIAN: TAMBAH BARANG ---
            $fields = []; $placeholders = []; $params = [];
            
            foreach ($data as $key => $val) {
                if (isset($barang_cols[$key])) {
                    $fields[] = $key;
                    $placeholders[] = "?";
                    $params[] = $truncate($val, $key);
                }
            }
            
            $sql = "INSERT INTO barang (".implode(',', $fields).") VALUES (".implode(',', $placeholders).")";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $target_id = $db->lastInsertId(); // ID barang baru
            $log_msg = "Barang baru ditambahkan: " . $data['nama_barang'];

        } else {
            // --- BAGIAN: EDIT BARANG ---
            // Ambil data lama untuk keperluan log riwayat
            $stmt = $db->prepare("SELECT kondisi FROM barang WHERE id_barang = ?");
            $stmt->execute([$id_barang]);
            $old = $stmt->fetch();
            
            $sets = []; $params = [];
            foreach ($data as $key => $val) {
                if (isset($barang_cols[$key])) {
                    $sets[] = "$key = ?";
                    $params[] = $truncate($val, $key);
                }
            }
            $params[] = $id_barang; // Untuk WHERE id_barang = ?
            
            $sql = "UPDATE barang SET ".implode(',', $sets)." WHERE id_barang = ?";
            $db->prepare($sql)->execute($params);
            
            $target_id = $id_barang;
            // Jika kondisi berubah, catat di log
            $log_msg = ($old['kondisi'] !== $data['kondisi']) 
                       ? "Update: Kondisi berubah menjadi ".$data['kondisi'] 
                       : "Update: Perubahan data barang";
        }

        // --- 4. SIMPAN KE RIWAYAT (AUDIT TRAIL) ---
        $riwayat_cols = getColInfo($db, 'riwayat_barang');
        if (!empty($riwayat_cols)) {
            $r_fields = ['id_barang', 'keterangan'];
            $r_params = [$target_id, mb_substr($log_msg, 0, 250)];
            
            // Cek apakah tabel riwayat punya kolom tanggal otomatis
            $tanggal_sql = isset($riwayat_cols['tanggal']) ? ", NOW()" : "";
            $col_tanggal = isset($riwayat_cols['tanggal']) ? ", tanggal" : "";

            $sql_riwayat = "INSERT INTO riwayat_barang (id_barang, keterangan $col_tanggal) VALUES (?, ? $tanggal_sql)";
            $db->prepare($sql_riwayat)->execute($r_params);
        }

        $db->commit(); // Simpan semua perubahan secara permanen
        $_SESSION['success'] = "Data berhasil disimpan!";

    } catch (Exception $e) {
        $db->rollBack(); // Batalkan semua jika ada error
        $_SESSION['error'] = "Gagal memproses data: " . $e->getMessage();
    }

// --- 5. LOGIKA HAPUS BARANG ---

} elseif ($action === 'delete' && isAdmin()) {
    try {
        $stmt = $db->prepare("DELETE FROM barang WHERE id_barang = ?");
        $stmt->execute([$id_barang]);
        $_SESSION['success'] = "Barang berhasil dihapus!";
    } catch (Exception $e) { 
        $_SESSION['error'] = "Gagal menghapus: " . $e->getMessage(); 
    }
}

// Kembali ke halaman utama barang
header("Location: ../barang.php");
exit();