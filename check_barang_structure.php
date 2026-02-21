<?php
/**
 * Script untuk mengecek struktur tabel barang secara detail
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
    .required { color: red; font-weight: bold; }
    .optional { color: green; }
</style>";

try {
    // Check structure of barang table
    $stmt = $db->query("DESCRIBE barang");
    $columns = $stmt->fetchAll();
    
    echo "<h3>Kolom di Tabel 'barang':</h3>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th><th>Status</th></tr>";
    
    foreach ($columns as $col) {
        $is_required = $col['Null'] === 'NO' && $col['Default'] === null && $col['Extra'] !== 'auto_increment';
        $status = $is_required ? '<span class="required">REQUIRED</span>' : '<span class="optional">Optional</span>';
        
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample data
    echo "<hr>";
    echo "<h3>Sample Data (jika ada):</h3>";
    try {
        $stmt = $db->query("SELECT * FROM barang LIMIT 1");
        $sample = $stmt->fetch();
        if ($sample) {
            echo "<pre style='background: #f4f4f4; padding: 10px; border: 1px solid #ddd;'>";
            print_r($sample);
            echo "</pre>";
        } else {
            echo "<p>Tidak ada data di tabel barang.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}
?>




