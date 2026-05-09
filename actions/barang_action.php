<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

$db = (new Database())->getConnection();

$action    = $_POST['action'] ?? '';
$id_barang = $_POST['id_barang'] ?? '';

if ($action === 'edit' || $action === 'add') {
    $nama   = $_POST['nama_barang'] ?? '';
    $jenis  = $_POST['jenis'] ?? '';
    $merk   = $_POST['merk'] ?? '';
    $kon    = $_POST['kondisi'] ?? 'baik';
    $jml    = $_POST['jumlah'] ?? 0;
    $lab    = $_POST['lokasi_lab'] ?? $_POST['id_lab'] ?? null;

    try {
        if ($action === 'edit') {
            // Kita update kedua kolom lab (id_lab dan lokasi_lab) agar sinkron
            $sql = "UPDATE barang SET 
                    nama_barang = ?, jenis = ?, merk = ?, 
                    kondisi = ?, jumlah = ?, lokasi_lab = ?, id_lab = ? 
                    WHERE id_barang = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$nama, $jenis, $merk, $kon, $jml, $lab, $lab, $id_barang]);
            
            $_SESSION['success'] = "Data berhasil diupdate!";
        } else {
            // Logika Tambah (Add)
            $sql = "INSERT INTO barang (nama_barang, jenis, merk, kondisi, jumlah, lokasi_lab, id_lab) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $db->prepare($sql)->execute([$nama, $jenis, $merk, $kon, $jml, $lab, $lab]);
            $_SESSION['success'] = "Data berhasil ditambah!";
        }
    } catch (Exception $e) {
        // Jika masih error truncated, pesan ini akan memberitahu kolom mana yang bermasalah
        $_SESSION['error'] = "Gagal: " . $e->getMessage();
    }
}

header("Location: ../barang.php");
exit();