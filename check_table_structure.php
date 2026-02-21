<?php
/**
 * Script untuk mengecek struktur tabel barang
 */

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Struktur Tabel Barang</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
</style>";

try {
    // Check structure of barang table
    $stmt = $db->query("DESCRIBE barang");
    $columns = $stmt->fetchAll();
    
    echo "<h3>Kolom di Tabel 'barang':</h3>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $has_lokasi_lab = false;
    $lokasi_lab_column = null;
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
        
        // Check if lokasi_lab exists or similar
        if (stripos($col['Field'], 'lokasi') !== false || stripos($col['Field'], 'lab') !== false) {
            $has_lokasi_lab = true;
            $lokasi_lab_column = $col['Field'];
        }
    }
    echo "</table>";
    
    if (!$has_lokasi_lab) {
        echo "<div style='color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0;'>";
        echo "<strong>⚠ PENTING:</strong> Kolom 'lokasi_lab' tidak ditemukan di tabel barang!<br>";
        echo "Kemungkinan kolomnya bernama berbeda atau belum dibuat.<br>";
        echo "Kolom yang mengandung 'lokasi' atau 'lab': ";
        if ($lokasi_lab_column) {
            echo "<strong>" . htmlspecialchars($lokasi_lab_column) . "</strong>";
        } else {
            echo "Tidak ada";
        }
        echo "</div>";
        
        echo "<h3>Solusi:</h3>";
        echo "<p>1. Jika kolom belum ada, tambahkan kolom dengan SQL berikut:</p>";
        echo "<pre style='background: #f4f4f4; padding: 10px; border: 1px solid #ddd;'>";
        echo "ALTER TABLE barang ADD COLUMN lokasi_lab INT NULL AFTER jumlah;\n";
        echo "ALTER TABLE barang ADD FOREIGN KEY (lokasi_lab) REFERENCES laboratorium(id_lab) ON DELETE SET NULL;";
        echo "</pre>";
        
        echo "<p>2. Atau jika kolom sudah ada dengan nama berbeda, beri tahu nama kolom yang benar.</p>";
    } else {
        echo "<div style='color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0;'>";
        echo "✓ Kolom lokasi ditemukan: <strong>" . htmlspecialchars($lokasi_lab_column) . "</strong>";
        echo "</div>";
    }
    
    // Check laboratorium table
    echo "<hr>";
    echo "<h3>Struktur Tabel 'laboratorium':</h3>";
    $stmt = $db->query("DESCRIBE laboratorium");
    $lab_columns = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($lab_columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}
?>




