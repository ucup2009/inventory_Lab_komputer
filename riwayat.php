<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get riwayat with barang info
$query = "SELECT r.*, b.nama_barang, b.jenis 
          FROM riwayat_barang r 
          INNER JOIN barang b ON r.id_barang = b.id_barang 
          ORDER BY r.tanggal DESC, r.id_riwayat DESC";
$stmt = $db->query($query);
$riwayat_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Barang - Inventory Lab Komputer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Riwayat Barang</h2>
            <p class="text-gray-600 mt-2">Daftar semua perubahan dan aktivitas barang</p>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($riwayat_list)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada riwayat</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($riwayat_list as $riwayat): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo date('d/m/Y H:i', strtotime($riwayat['tanggal'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($riwayat['nama_barang']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($riwayat['jenis']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-700"><?php echo htmlspecialchars($riwayat['keterangan']); ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>




