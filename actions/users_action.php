<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $nama = $_POST['nama'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'petugas';
    
    if (empty($nama) || empty($username) || empty($password)) {
        $_SESSION['error'] = "Data wajib harus diisi!";
        header("Location: ../users.php");
        exit();
    }
    
    try {
        // Check if username already exists
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $_SESSION['error'] = "Username sudah digunakan!";
            header("Location: ../users.php");
            exit();
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama, $username, $hashed_password, $role]);
        $_SESSION['success'] = "User berhasil ditambahkan!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal menambahkan user: " . $e->getMessage();
    }
    
    header("Location: ../users.php");
    exit();
    
} elseif ($action === 'edit') {
    $id_user = $_POST['id_user'] ?? '';
    $nama = $_POST['nama'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'petugas';
    
    if (empty($id_user) || empty($nama) || empty($username)) {
        $_SESSION['error'] = "Data wajib harus diisi!";
        header("Location: ../users.php");
        exit();
    }
    
    try {
        // Check if username already exists (except current user)
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? AND id_user != ?");
        $stmt->execute([$username, $id_user]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $_SESSION['error'] = "Username sudah digunakan!";
            header("Location: ../users.php");
            exit();
        }
        
        if (!empty($password)) {
            // Update with new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET nama = ?, username = ?, password = ?, role = ? WHERE id_user = ?");
            $stmt->execute([$nama, $username, $hashed_password, $role, $id_user]);
        } else {
            // Update without password
            $stmt = $db->prepare("UPDATE users SET nama = ?, username = ?, role = ? WHERE id_user = ?");
            $stmt->execute([$nama, $username, $role, $id_user]);
        }
        
        $_SESSION['success'] = "User berhasil diupdate!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal mengupdate user: " . $e->getMessage();
    }
    
    header("Location: ../users.php");
    exit();
    
} elseif ($action === 'delete') {
    $id_user = $_POST['id_user'] ?? '';
    
    if (empty($id_user)) {
        $_SESSION['error'] = "ID user tidak valid!";
        header("Location: ../users.php");
        exit();
    }
    
    // Prevent deleting own account
    if ($id_user == getUserId()) {
        $_SESSION['error'] = "Anda tidak dapat menghapus akun sendiri!";
        header("Location: ../users.php");
        exit();
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM users WHERE id_user = ?");
        $stmt->execute([$id_user]);
        $_SESSION['success'] = "User berhasil dihapus!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal menghapus user: " . $e->getMessage();
    }
    
    header("Location: ../users.php");
    exit();
} else {
    $_SESSION['error'] = "Aksi tidak valid!";
    header("Location: ../users.php");
    exit();
}
?>




