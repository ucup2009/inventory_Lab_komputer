<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Check which lab column exists
$has_lokasi_lab = false;
$has_id_lab = false;
try {
    $stmt = $db->query("SHOW COLUMNS FROM barang LIKE 'lokasi_lab'");
    $has_lokasi_lab = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW COLUMNS FROM barang LIKE 'id_lab'");
    $has_id_lab = $stmt->rowCount() > 0;
} catch (Exception $e) {
    $has_lokasi_lab = false;
    $has_id_lab = false;
}

// Determine which column name to use
$lab_column = $has_lokasi_lab ? 'lokasi_lab' : ($has_id_lab ? 'id_lab' : null);

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $nama_barang = $_POST['nama_barang'] ?? '';
    $jenis = $_POST['jenis'] ?? '';
    $merk = $_POST['merk'] ?? '';
    $kondisi = $_POST['kondisi'] ?? 'baik';
    $jumlah = $_POST['jumlah'] ?? 0;
    $lokasi_lab = $_POST['lokasi_lab'] ?? '';
    
    if (empty($nama_barang) || empty($jenis)) {
        $_SESSION['error'] = "Nama barang dan jenis harus diisi!";
        header("Location: ../barang.php");
        exit();
    }
    
    // Check if lab is required
    if ($lab_column && empty($lokasi_lab)) {
        // Check if column is NOT NULL
        try {
            $stmt = $db->query("SHOW COLUMNS FROM barang WHERE Field = '$lab_column' AND `Null` = 'NO'");
            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = "Laboratorium harus dipilih!";
                header("Location: ../barang.php");
                exit();
            }
        } catch (Exception $e) {
            // Continue if check fails
        }
    }
    
    try {
        $db->beginTransaction();
        
        // Get all columns from barang table to build dynamic INSERT
        $stmt = $db->query("SHOW COLUMNS FROM barang");
        $column_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columns = array_column($column_details, 'Field');
        
        // Helper function to get max length from column type
        $getMaxLength = function($type) {
            if (preg_match('/varchar\((\d+)\)/i', $type, $matches)) {
                return (int)$matches[1];
            } elseif (preg_match('/char\((\d+)\)/i', $type, $matches)) {
                return (int)$matches[1];
            } elseif (stripos($type, 'text') !== false) {
                return 65535; // TEXT can handle long text
            }
            return null; // No limit or other type
        };
        
        // Create column info map
        $column_info = [];
        foreach ($column_details as $col) {
            $column_info[$col['Field']] = [
                'null' => $col['Null'],
                'default' => $col['Default'],
                'extra' => $col['Extra'],
                'type' => $col['Type'],
                'max_length' => $getMaxLength($col['Type'])
            ];
        }
        
        // Helper function to truncate string based on column max length
        $truncateValue = function($value, $col_name) use ($column_info) {
            if (!isset($column_info[$col_name]) || !is_string($value)) return $value;
            $max_len = $column_info[$col_name]['max_length'];
            if ($max_len !== null && mb_strlen($value, 'UTF-8') > $max_len) {
                // Use mb_substr for proper UTF-8 handling and ensure we don't exceed max
                return mb_substr($value, 0, $max_len, 'UTF-8');
            }
            return $value;
        };
        
        // Build INSERT query dynamically based on existing columns
        $insert_fields = [];
        $insert_values = [];
        $insert_params = [];
        
        // Always include these if they exist and are not auto_increment
        if (in_array('nama_barang', $columns) && $column_info['nama_barang']['extra'] !== 'auto_increment') {
            $insert_fields[] = 'nama_barang';
            $insert_values[] = '?';
            $insert_params[] = $truncateValue($nama_barang, 'nama_barang');
        }
        if (in_array('jenis', $columns) && $column_info['jenis']['extra'] !== 'auto_increment') {
            $insert_fields[] = 'jenis';
            $insert_values[] = '?';
            $insert_params[] = $truncateValue($jenis, 'jenis');
        }
        if (in_array('merk', $columns) && $column_info['merk']['extra'] !== 'auto_increment') {
            $insert_fields[] = 'merk';
            $insert_values[] = '?';
            $insert_params[] = !empty($merk) ? $truncateValue($merk, 'merk') : null;
        }
        if (in_array('kondisi', $columns) && $column_info['kondisi']['extra'] !== 'auto_increment') {
            $insert_fields[] = 'kondisi';
            $insert_values[] = '?';
            $insert_params[] = $truncateValue($kondisi, 'kondisi');
        }
        if (in_array('jumlah', $columns) && $column_info['jumlah']['extra'] !== 'auto_increment') {
            $insert_fields[] = 'jumlah';
            $insert_values[] = '?';
            $insert_params[] = $jumlah;
        }
        
        // Add lab column if exists and value provided (and not auto_increment)
        if ($lab_column && in_array($lab_column, $columns) && 
            $column_info[$lab_column]['extra'] !== 'auto_increment' && !empty($lokasi_lab)) {
            $insert_fields[] = $lab_column;
            $insert_values[] = '?';
            $insert_params[] = $lokasi_lab;
        }
        
        // Handle required columns that might have defaults
        foreach ($column_info as $col_name => $info) {
            if ($info['extra'] === 'auto_increment') continue;
            if (in_array($col_name, $insert_fields)) continue; // Already handled
            
            // If column is NOT NULL and has no default, we need to provide a value
            if ($info['null'] === 'NO' && $info['default'] === null) {
                // Skip id_barang (auto increment)
                if ($col_name === 'id_barang') continue;
                
                // Provide default values for common columns
                if ($col_name === 'keterangan') {
                    $insert_fields[] = $col_name;
                    $insert_values[] = '?';
                    $insert_params[] = ''; // Empty string for keterangan
                } elseif (strpos($col_name, 'tanggal') !== false || strpos($col_name, 'date') !== false) {
                    $insert_fields[] = $col_name;
                    $insert_values[] = '?';
                    $insert_params[] = date('Y-m-d');
                } else {
                    // For other required columns, use empty string or 0
                    $insert_fields[] = $col_name;
                    $insert_values[] = '?';
                    $insert_params[] = '';
                }
            }
        }
        
        // Double-check all string values are within limits before insert
        foreach ($insert_params as $index => $param) {
            if (is_string($param)) {
                $field_name = $insert_fields[$index];
                if (isset($column_info[$field_name]) && $column_info[$field_name]['max_length'] !== null) {
                    $max_len = $column_info[$field_name]['max_length'];
                    if (mb_strlen($param, 'UTF-8') > $max_len) {
                        $insert_params[$index] = mb_substr($param, 0, $max_len, 'UTF-8');
                    }
                }
            }
        }
        
        // Build and execute INSERT query
        $fields_str = implode(', ', $insert_fields);
        $values_str = implode(', ', $insert_values);
        $query = "INSERT INTO barang ($fields_str) VALUES ($values_str)";
        
        $stmt = $db->prepare($query);
        $stmt->execute($insert_params);
        $id_barang = $db->lastInsertId();
        
        // Insert riwayat (only if table exists)
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'riwayat_barang'");
            if ($stmt->rowCount() > 0) {
                // Check structure of riwayat_barang table to get column type
                $stmt = $db->query("SHOW COLUMNS FROM riwayat_barang WHERE Field = 'keterangan'");
                $keterangan_col = $stmt->fetch();
                
                // Determine max length based on column type
                $max_length = 255; // Default
                if ($keterangan_col) {
                    $type = $keterangan_col['Type'];
                    if (preg_match('/varchar\((\d+)\)/i', $type, $matches)) {
                        $max_length = (int)$matches[1];
                    } elseif (preg_match('/char\((\d+)\)/i', $type, $matches)) {
                        $max_length = (int)$matches[1];
                    } elseif (stripos($type, 'text') !== false) {
                        $max_length = 65535; // TEXT can handle long text
                    }
                }
                
                $keterangan = "Barang masuk: " . $nama_barang;
                $keterangan = substr($keterangan, 0, $max_length - 1); // Leave 1 char buffer
                
                // Check structure of riwayat_barang table
                $stmt = $db->query("SHOW COLUMNS FROM riwayat_barang");
                $riwayat_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Build query based on actual columns
                if (in_array('tanggal', $riwayat_columns)) {
                    // If tanggal column exists, use NOW() or current date
                    $stmt = $db->prepare("INSERT INTO riwayat_barang (id_barang, tanggal, keterangan) 
                                         VALUES (?, NOW(), ?)");
                    $stmt->execute([$id_barang, $keterangan]);
                } else {
                    // If no tanggal column, just insert id_barang and keterangan
                    $stmt = $db->prepare("INSERT INTO riwayat_barang (id_barang, keterangan) 
                                         VALUES (?, ?)");
                    $stmt->execute([$id_barang, $keterangan]);
                }
            }
        } catch (Exception $e) {
            // Log error but don't fail the transaction
            error_log("Error inserting riwayat: " . $e->getMessage());
            // Store error message for debugging
            $riwayat_error = $e->getMessage();
        }
        
        $db->commit();
        if (isset($riwayat_error)) {
            $_SESSION['success'] = "Barang berhasil ditambahkan, tapi ada masalah dengan riwayat: " . $riwayat_error;
        } else {
            $_SESSION['success'] = "Barang berhasil ditambahkan!";
        }
    } catch (Exception $e) {
        $db->rollBack();
        $error_msg = $e->getMessage();
        // Provide more helpful error message
        if (strpos($error_msg, 'keterangan') !== false) {
            $_SESSION['error'] = "Gagal menambahkan barang: Kolom 'keterangan' mungkin memiliki tipe data yang tidak sesuai. Silakan cek struktur tabel dengan check_barang_structure.php";
        } else {
            $_SESSION['error'] = "Gagal menambahkan barang: " . $error_msg;
        }
    }
    
    header("Location: ../barang.php");
    exit();
    
} elseif ($action === 'edit') {
    $id_barang = $_POST['id_barang'] ?? '';
    $nama_barang = $_POST['nama_barang'] ?? '';
    $jenis = $_POST['jenis'] ?? '';
    $merk = $_POST['merk'] ?? '';
    $kondisi = $_POST['kondisi'] ?? 'baik';
    $jumlah = $_POST['jumlah'] ?? 0;
    $lokasi_lab = $_POST['lokasi_lab'] ?? '';
    
    if (empty($id_barang) || empty($nama_barang) || empty($jenis)) {
        $_SESSION['error'] = "Data wajib harus diisi!";
        header("Location: ../barang.php");
        exit();
    }
    
    // Check if lab is required
    if ($lab_column && empty($lokasi_lab)) {
        // Check if column is NOT NULL
        try {
            $stmt = $db->query("SHOW COLUMNS FROM barang WHERE Field = '$lab_column' AND `Null` = 'NO'");
            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = "Laboratorium harus dipilih!";
                header("Location: ../barang.php");
                exit();
            }
        } catch (Exception $e) {
            // Continue if check fails
        }
    }
    
    try {
        $db->beginTransaction();
        
        // Get old data
        $stmt = $db->prepare("SELECT kondisi, jumlah FROM barang WHERE id_barang = ?");
        $stmt->execute([$id_barang]);
        $old_data = $stmt->fetch();
        
        // Get all columns from barang table to build dynamic UPDATE
        $stmt = $db->query("SHOW COLUMNS FROM barang");
        $column_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columns = array_column($column_details, 'Field');
        
        // Helper function to get max length from column type
        $getMaxLength = function($type) {
            if (preg_match('/varchar\((\d+)\)/i', $type, $matches)) {
                return (int)$matches[1];
            } elseif (preg_match('/char\((\d+)\)/i', $type, $matches)) {
                return (int)$matches[1];
            } elseif (stripos($type, 'text') !== false) {
                return 65535; // TEXT can handle long text
            }
            return null; // No limit or other type
        };
        
        // Create column info map
        $column_info = [];
        foreach ($column_details as $col) {
            $column_info[$col['Field']] = [
                'null' => $col['Null'],
                'default' => $col['Default'],
                'extra' => $col['Extra'],
                'type' => $col['Type'],
                'max_length' => $getMaxLength($col['Type'])
            ];
        }
        
        // Helper function to truncate string based on column max length
        $truncateValue = function($value, $col_name) use ($column_info) {
            if (!isset($column_info[$col_name]) || !is_string($value)) return $value;
            $max_len = $column_info[$col_name]['max_length'];
            if ($max_len !== null && mb_strlen($value, 'UTF-8') > $max_len) {
                // Use mb_substr for proper UTF-8 handling and ensure we don't exceed max
                return mb_substr($value, 0, $max_len, 'UTF-8');
            }
            return $value;
        };
        
        // Build UPDATE query dynamically based on existing columns
        $update_fields = [];
        $update_params = [];
        
        // Always include these if they exist
        if (in_array('nama_barang', $columns)) {
            $update_fields[] = 'nama_barang = ?';
            $update_params[] = $truncateValue($nama_barang, 'nama_barang');
        }
        if (in_array('jenis', $columns)) {
            $update_fields[] = 'jenis = ?';
            $update_params[] = $truncateValue($jenis, 'jenis');
        }
        if (in_array('merk', $columns)) {
            $update_fields[] = 'merk = ?';
            $update_params[] = $truncateValue($merk, 'merk');
        }
        if (in_array('kondisi', $columns)) {
            $update_fields[] = 'kondisi = ?';
            $update_params[] = $truncateValue($kondisi, 'kondisi');
        }
        if (in_array('jumlah', $columns)) {
            $update_fields[] = 'jumlah = ?';
            $update_params[] = $jumlah;
        }
        
        // Add lab column if exists and value provided
        if ($lab_column && in_array($lab_column, $columns) && !empty($lokasi_lab)) {
            $update_fields[] = "$lab_column = ?";
            $update_params[] = $lokasi_lab;
        }
        
        // Add id_barang for WHERE clause
        $update_params[] = $id_barang;
        
        // Build and execute UPDATE query
        $fields_str = implode(', ', $update_fields);
        $query = "UPDATE barang SET $fields_str WHERE id_barang = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute($update_params);
        
        // Insert riwayat if kondisi or jumlah changed (only if table exists)
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'riwayat_barang'");
            if ($stmt->rowCount() > 0) {
                $keterangan = "";
                if ($old_data['kondisi'] !== $kondisi) {
                    $keterangan = "Kondisi berubah dari " . $old_data['kondisi'] . " menjadi " . $kondisi;
                } elseif ($old_data['jumlah'] != $jumlah) {
                    $keterangan = "Jumlah berubah dari " . $old_data['jumlah'] . " menjadi " . $jumlah;
                } else {
                    $keterangan = "Data barang diupdate: " . substr($nama_barang, 0, 200);
                }
                
                if (!empty($keterangan)) {
                    // Check structure of riwayat_barang table to get column type
                    $check_stmt = $db->query("SHOW COLUMNS FROM riwayat_barang WHERE Field = 'keterangan'");
                    $keterangan_col = $check_stmt->fetch();
                    
                    // Determine max length based on column type
                    $max_length = 255; // Default
                    if ($keterangan_col) {
                        $type = $keterangan_col['Type'];
                        if (preg_match('/varchar\((\d+)\)/i', $type, $matches)) {
                            $max_length = (int)$matches[1];
                        } elseif (preg_match('/char\((\d+)\)/i', $type, $matches)) {
                            $max_length = (int)$matches[1];
                        } elseif (stripos($type, 'text') !== false) {
                            $max_length = 65535; // TEXT can handle long text
                        }
                    }
                    
                    // Limit keterangan length
                    $keterangan = substr($keterangan, 0, $max_length - 1); // Leave 1 char buffer
                    
                    // Check structure of riwayat_barang table
                    $check_stmt = $db->query("SHOW COLUMNS FROM riwayat_barang");
                    $riwayat_columns = $check_stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Build query based on actual columns
                    if (in_array('tanggal', $riwayat_columns)) {
                        $stmt = $db->prepare("INSERT INTO riwayat_barang (id_barang, tanggal, keterangan) 
                                             VALUES (?, NOW(), ?)");
                        $stmt->execute([$id_barang, $keterangan]);
                    } else {
                        $stmt = $db->prepare("INSERT INTO riwayat_barang (id_barang, keterangan) 
                                             VALUES (?, ?)");
                        $stmt->execute([$id_barang, $keterangan]);
                    }
                }
            }
        } catch (Exception $e) {
            // Log error but don't fail the transaction
            error_log("Error inserting riwayat: " . $e->getMessage());
            // Store error message for debugging
            $riwayat_error = $e->getMessage();
        }
        
        $db->commit();
        if (isset($riwayat_error)) {
            $_SESSION['success'] = "Barang berhasil diupdate, tapi ada masalah dengan riwayat: " . $riwayat_error;
        } else {
            $_SESSION['success'] = "Barang berhasil diupdate!";
        }
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Gagal mengupdate barang: " . $e->getMessage();
    }
    
    header("Location: ../barang.php");
    exit();
    
} elseif ($action === 'delete') {
    if (!isAdmin()) {
        $_SESSION['error'] = "Anda tidak memiliki akses untuk menghapus!";
        header("Location: ../barang.php");
        exit();
    }
    
    $id_barang = $_POST['id_barang'] ?? '';
    
    if (empty($id_barang)) {
        $_SESSION['error'] = "ID barang tidak valid!";
        header("Location: ../barang.php");
        exit();
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM barang WHERE id_barang = ?");
        $stmt->execute([$id_barang]);
        $_SESSION['success'] = "Barang berhasil dihapus!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal menghapus barang: " . $e->getMessage();
    }
    
    header("Location: ../barang.php");
    exit();
} else {
    $_SESSION['error'] = "Aksi tidak valid!";
    header("Location: ../barang.php");
    exit();
}
?>

