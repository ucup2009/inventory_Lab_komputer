<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

$db = (new Database())->getConnection();

// 1. Helper: Cek Kolom & Ambil Info Tabel
function getColInfo($db, $table) {
    $stmt = $db->query("SHOW COLUMNS FROM $table");
    $info = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $info[$col['Field']] = $col;
    }
    return $info;
}

$barang_cols = getColInfo($db, 'barang');
$lab_column = isset($barang_cols['lokasi_lab']) ? 'lokasi_lab' : (isset($barang_cols['id_lab']) ? 'id_lab' : null);

// 2. Helper: Truncate string sesuai limit DB
$truncate = function($val, $col) use ($barang_cols) {
    if (!isset($barang_cols[$col]['Type']) || !is_string($val)) return $val;
    if (preg_match('/\((\d+)\)/', $barang_cols[$col]['Type'], $m)) return mb_substr($val, 0, (int)$m[1], 'UTF-8');
    return $val;
};

$action = $_POST['action'] ?? '';
$id_barang = $_POST['id_barang'] ?? '';

if ($action === 'add' || $action === 'edit') {
    $data = [
        'nama_barang' => $_POST['nama_barang'] ?? '',
        'jenis'       => $_POST['jenis'] ?? '',
        'merk'        => $_POST['merk'] ?? '',
        'kondisi'     => $_POST['kondisi'] ?? 'baik',
        'jumlah'      => $_POST['jumlah'] ?? 0
    ];

    if (empty($data['nama_barang']) || empty($data['jenis'])) {
        $_SESSION['error'] = "Nama dan jenis wajib diisi!";
        header("Location: ../barang.php"); exit();
    }

    if ($lab_column) $data[$lab_column] = $_POST['lokasi_lab'] ?? '';

    try {
        $db->beginTransaction();
        
        if ($action === 'add') {
            $fields = []; $placeholders = []; $params = [];
            foreach ($data as $key => $val) {
                if (isset($barang_cols[$key])) {
                    $fields[] = $key;
                    $placeholders[] = "?";
                    $params[] = $truncate($val, $key);
                }
            }
            $stmt = $db->prepare("INSERT INTO barang (".implode(',', $fields).") VALUES (".implode(',', $placeholders).")");
            $stmt->execute($params);
            $target_id = $db->lastInsertId();
            $log_msg = "Barang masuk: " . $data['nama_barang'];
        } else {
            $stmt = $db->prepare("SELECT kondisi, jumlah FROM barang WHERE id_barang = ?");
            $stmt->execute([$id_barang]);
            $old = $stmt->fetch();
            
            $sets = []; $params = [];
            foreach ($data as $key => $val) {
                if (isset($barang_cols[$key])) {
                    $sets[] = "$key = ?";
                    $params[] = $truncate($val, $key);
                }
            }
            $params[] = $id_barang;
            $db->prepare("UPDATE barang SET ".implode(',', $sets)." WHERE id_barang = ?")->execute($params);
            
            $target_id = $id_barang;
            $log_msg = ($old['kondisi'] !== $data['kondisi']) ? "Kondisi berubah ke ".$data['kondisi'] : "Data diupdate";
        }

        // Simpan Riwayat
        $riwayat_cols = getColInfo($db, 'riwayat_barang');
        if (!empty($riwayat_cols)) {
            $r_fields = ['id_barang', 'keterangan'];
            $r_params = [$target_id, mb_substr($log_msg, 0, 250)];
            if (isset($riwayat_cols['tanggal'])) {
                $r_fields[] = 'tanggal';
                $r_fields_val = "NOW()";
            }
            $db->prepare("INSERT INTO riwayat_barang (".implode(',', $r_fields).") VALUES (?, ?, ".(isset($r_fields_val) ? "NOW()" : "").")")
               ->execute($r_params);
        }

        $db->commit();
        $_SESSION['success'] = "Data berhasil disimpan!";
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Gagal: " . $e->getMessage();
    }
} elseif ($action === 'delete' && isAdmin()) {
    try {
        $db->prepare("DELETE FROM barang WHERE id_barang = ?")->execute([$id_barang]);
        $_SESSION['success'] = "Barang dihapus!";
    } catch (Exception $e) { $_SESSION['error'] = $e->getMessage(); }
}

header("Location: ../barang.php");
exit();