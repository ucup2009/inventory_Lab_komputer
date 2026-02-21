<?php
/**
 * Script untuk test insert riwayat
 */

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Test Insert Riwayat</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; }
    .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0; }
    .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; }
    .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; margin: 10px 0; }
</style>";

try {
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'riwayat_barang'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='error'>✗ Tabel 'riwayat_barang' tidak ada!</div>";
        echo "<p>Buat tabel dengan SQL berikut:</p>";
        echo "<pre>CREATE TABLE riwayat_barang (
    id_riwayat INT AUTO_INCREMENT PRIMARY KEY,
    id_barang INT NOT NULL,
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    keterangan TEXT,
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang)
);</pre>";
        exit;
    }
    
    echo "<div class='success'>✓ Tabel 'riwayat_barang' ada</div>";
    
    // Check structure
    echo "<h3>Struktur Tabel:</h3>";
    $stmt = $db->query("DESCRIBE riwayat_barang");
    $columns = $stmt->fetchAll();
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
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
    
    // Get sample barang
    $stmt = $db->query("SELECT id_barang, nama_barang FROM barang LIMIT 1");
    $barang = $stmt->fetch();
    
    if ($barang) {
        echo "<div class='info'>Mencoba insert riwayat untuk barang: " . htmlspecialchars($barang['nama_barang']) . " (ID: " . $barang['id_barang'] . ")</div>";
        
        // Try to insert
        try {
            // Check keterangan column type
            $stmt = $db->query("SHOW COLUMNS FROM riwayat_barang WHERE Field = 'keterangan'");
            $keterangan_col = $stmt->fetch();
            
            // Determine max length based on column type
            $max_length = 255; // Default
            if ($keterangan_col) {
                $type = $keterangan_col['Type'];
                echo "<div class='info'>Tipe kolom keterangan: " . htmlspecialchars($type) . "</div>";
                
                if (preg_match('/varchar\((\d+)\)/i', $type, $matches)) {
                    $max_length = (int)$matches[1];
                } elseif (preg_match('/char\((\d+)\)/i', $type, $matches)) {
                    $max_length = (int)$matches[1];
                } elseif (stripos($type, 'text') !== false) {
                    $max_length = 65535; // TEXT can handle long text
                }
                echo "<div class='info'>Max length untuk keterangan: $max_length</div>";
            }
            
            $stmt = $db->query("SHOW COLUMNS FROM riwayat_barang");
            $riwayat_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $keterangan = "Test: Barang masuk - " . $barang['nama_barang'];
            $keterangan = substr($keterangan, 0, $max_length - 1); // Limit to max length
            
            if (in_array('tanggal', $riwayat_columns)) {
                $stmt = $db->prepare("INSERT INTO riwayat_barang (id_barang, tanggal, keterangan) VALUES (?, NOW(), ?)");
                $stmt->execute([$barang['id_barang'], $keterangan]);
            } else {
                $stmt = $db->prepare("INSERT INTO riwayat_barang (id_barang, keterangan) VALUES (?, ?)");
                $stmt->execute([$barang['id_barang'], $keterangan]);
            }
            
            echo "<div class='success'>✓ Insert riwayat berhasil!</div>";
            
            // Show latest riwayat
            $stmt = $db->query("SELECT * FROM riwayat_barang ORDER BY id_riwayat DESC LIMIT 5");
            $riwayat = $stmt->fetchAll();
            
            echo "<h3>5 Riwayat Terbaru:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>ID</th><th>ID Barang</th><th>Tanggal</th><th>Keterangan</th></tr>";
            foreach ($riwayat as $r) {
                echo "<tr>";
                echo "<td>" . $r['id_riwayat'] . "</td>";
                echo "<td>" . $r['id_barang'] . "</td>";
                echo "<td>" . ($r['tanggal'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($r['keterangan'] ?? '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "<div class='error'>✗ Error insert: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='info'>Tidak ada data barang untuk test. Tambahkan barang terlebih dahulu.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}
?>

