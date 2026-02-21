<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireAdmin();

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

$lab_column = $has_lokasi_lab ? 'lokasi_lab' : ($has_id_lab ? 'id_lab' : null);
$has_lab_column = $has_lokasi_lab || $has_id_lab;

// Get filter parameters
$filter_lab = $_GET['filter_lab'] ?? '';
$filter_kondisi = $_GET['filter_kondisi'] ?? '';

// Get laboratorium for filter
$laboratorium = [];
try {
    $stmt = $db->query("SELECT id_lab, nama_lab FROM laboratorium ORDER BY nama_lab");
    $laboratorium = $stmt->fetchAll();
} catch (Exception $e) {
    // Table might not exist
}

// Build query
if ($has_lab_column) {
    $query = "SELECT b.*, l.nama_lab FROM barang b 
              LEFT JOIN laboratorium l ON b.$lab_column = l.id_lab 
              WHERE 1=1";
} else {
    $query = "SELECT b.*, NULL as nama_lab FROM barang b WHERE 1=1";
}

$params = [];

if (!empty($filter_lab) && $has_lab_column) {
    $query .= " AND b.$lab_column = ?";
    $params[] = $filter_lab;
}

if (!empty($filter_kondisi)) {
    $query .= " AND b.kondisi = ?";
    $params[] = $filter_kondisi;
}

if ($has_lab_column) {
    $query .= " ORDER BY l.nama_lab, b.nama_barang";
} else {
    $query .= " ORDER BY b.nama_barang";
}

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $barang_list = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback
    $query = "SELECT b.*, NULL as nama_lab FROM barang b ORDER BY b.nama_barang";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $barang_list = $stmt->fetchAll();
}

// Statistics
$stats = [
    'total' => count($barang_list),
    'baik' => 0,
    'rusak' => 0,
    'perbaikan' => 0
];

foreach ($barang_list as $barang) {
    if (isset($stats[$barang['kondisi']])) {
        $stats[$barang['kondisi']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Inventory - Inventory Lab Komputer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Laporan Inventory</h2>
            <div class="space-x-2">
                <button onclick="window.print()" 
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition duration-200">
                    Cetak
                </button>
                <button onclick="exportToExcel()" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition duration-200">
                    Export Excel
                </button>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter Laboratorium</label>
                    <select name="filter_lab" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                        <option value="">Semua Lab</option>
                        <?php foreach ($laboratorium as $lab): ?>
                        <option value="<?php echo $lab['id_lab']; ?>" <?php echo $filter_lab == $lab['id_lab'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lab['nama_lab']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter Kondisi</label>
                    <select name="filter_kondisi" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                        <option value="">Semua Kondisi</option>
                        <option value="baik" <?php echo $filter_kondisi === 'baik' ? 'selected' : ''; ?>>Baik</option>
                        <option value="rusak" <?php echo $filter_kondisi === 'rusak' ? 'selected' : ''; ?>>Rusak</option>
                        <option value="perbaikan" <?php echo $filter_kondisi === 'perbaikan' ? 'selected' : ''; ?>>Perbaikan</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-600">Total Barang</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-600">Barang Baik</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $stats['baik']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-600">Barang Rusak</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $stats['rusak']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-600">Barang Perbaikan</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['perbaikan']; ?></p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="reportTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Merk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kondisi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi Lab</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($barang_list)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada data</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($barang_list as $barang): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($barang['nama_barang']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($barang['jenis']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($barang['merk'] ?? '-'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php 
                                    echo $barang['kondisi'] === 'baik' ? 'bg-green-100 text-green-800' : 
                                        ($barang['kondisi'] === 'rusak' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); 
                                ?>">
                                    <?php echo ucfirst($barang['kondisi']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $barang['jumlah']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($barang['nama_lab'] ?? '-'); ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function exportToExcel() {
            const table = document.getElementById('reportTable');
            let html = table.outerHTML;
            
            // Create blob and download
            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'laporan_inventory_' + new Date().toISOString().split('T')[0] + '.xls';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>

    <style media="print">
        @media print {
            nav, button, .no-print {
                display: none !important;
            }
            body {
                background: white;
            }
            .bg-gray-100 {
                background: white;
            }
        }
    </style>
</body>
</html>

