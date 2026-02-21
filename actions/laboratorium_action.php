<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $nama_lab = $_POST['nama_lab'] ?? '';
    $penanggung_jawab = $_POST['penanggung_jawab'] ?? '';
    
    if (empty($nama_lab)) {
        $_SESSION['error'] = "Nama lab harus diisi!";
        header("Location: ../laboratorium.php");
        exit();
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO laboratorium (nama_lab, penanggung_jawab) VALUES (?, ?)");
        $stmt->execute([$nama_lab, $penanggung_jawab]);
        $_SESSION['success'] = "Laboratorium berhasil ditambahkan!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal menambahkan laboratorium: " . $e->getMessage();
    }
    
    header("Location: ../laboratorium.php");
    exit();
    
} elseif ($action === 'edit') {
    $id_lab = $_POST['id_lab'] ?? '';
    $nama_lab = $_POST['nama_lab'] ?? '';
    $penanggung_jawab = $_POST['penanggung_jawab'] ?? '';
    
    if (empty($id_lab) || empty($nama_lab)) {
        $_SESSION['error'] = "Data wajib harus diisi!";
        header("Location: ../laboratorium.php");
        exit();
    }
    
    try {
        $stmt = $db->prepare("UPDATE laboratorium SET nama_lab = ?, penanggung_jawab = ? WHERE id_lab = ?");
        $stmt->execute([$nama_lab, $penanggung_jawab, $id_lab]);
        $_SESSION['success'] = "Laboratorium berhasil diupdate!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal mengupdate laboratorium: " . $e->getMessage();
    }
    
    header("Location: ../laboratorium.php");
    exit();
    
} elseif ($action === 'delete') {
    $id_lab = $_POST['id_lab'] ?? '';
    
    if (empty($id_lab)) {
        $_SESSION['error'] = "ID lab tidak valid!";
        header("Location: ../laboratorium.php");
        exit();
    }
    
    try {
        // Check which lab column exists
        $has_lokasi_lab = false;
        $has_id_lab = false;
        try {
            $check_stmt = $db->query("SHOW COLUMNS FROM barang LIKE 'lokasi_lab'");
            $has_lokasi_lab = $check_stmt->rowCount() > 0;
            
            $check_stmt = $db->query("SHOW COLUMNS FROM barang LIKE 'id_lab'");
            $has_id_lab = $check_stmt->rowCount() > 0;
        } catch (Exception $e) {
            // Ignore
        }
        
        $lab_column = $has_lokasi_lab ? 'lokasi_lab' : ($has_id_lab ? 'id_lab' : null);
        
        // Check if lab is used in barang (only if column exists)
        if ($lab_column) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM barang WHERE $lab_column = ?");
            $stmt->execute([$id_lab]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $_SESSION['error'] = "Laboratorium tidak dapat dihapus karena masih digunakan oleh data barang!";
            } else {
                $stmt = $db->prepare("DELETE FROM laboratorium WHERE id_lab = ?");
                $stmt->execute([$id_lab]);
                $_SESSION['success'] = "Laboratorium berhasil dihapus!";
            }
        } else {
            // Column doesn't exist, just delete
            $stmt = $db->prepare("DELETE FROM laboratorium WHERE id_lab = ?");
            $stmt->execute([$id_lab]);
            $_SESSION['success'] = "Laboratorium berhasil dihapus!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal menghapus laboratorium: " . $e->getMessage();
    }
    
    header("Location: ../laboratorium.php");
    exit();
} else {
    $_SESSION['error'] = "Aksi tidak valid!";
    header("Location: ../laboratorium.php");
    exit();
}
?>

