<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total barang
$stmt = $db->query("SELECT COUNT(*) as total FROM barang");
$stats['total_barang'] = $stmt->fetch()['total'];

// Barang berdasarkan kondisi
$stmt = $db->query("SELECT kondisi, COUNT(*) as jumlah FROM barang GROUP BY kondisi");
$kondisi_stats = $stmt->fetchAll();
$stats['kondisi'] = [];
foreach ($kondisi_stats as $row) {
    $stats['kondisi'][$row['kondisi']] = $row['jumlah'];
}

// Total laboratorium
$stmt = $db->query("SELECT COUNT(*) as total FROM laboratorium");
$stats['total_lab'] = $stmt->fetch()['total'];

// Total users (admin only)
$stats['total_user'] = 0;
if (isAdmin()) {
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $stats['total_user'] = $stmt->fetch()['total'];
}

// Recent barang (handle if lab column doesn't exist)
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
    
    if ($lab_column) {
        $stmt = $db->query("SELECT b.*, l.nama_lab FROM barang b 
                            LEFT JOIN laboratorium l ON b.$lab_column = l.id_lab 
                            ORDER BY b.id_barang DESC LIMIT 5");
        $recent_barang = $stmt->fetchAll();
    } else {
        $stmt = $db->query("SELECT b.*, NULL as nama_lab FROM barang b 
                            ORDER BY b.id_barang DESC LIMIT 5");
        $recent_barang = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // Fallback: get barang without join
    $stmt = $db->query("SELECT b.*, NULL as nama_lab FROM barang b 
                        ORDER BY b.id_barang DESC LIMIT 5");
    $recent_barang = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory Lab Komputer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Dashboard</h2>
            <p class="text-gray-600 mt-2">Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Barang</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_barang']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Barang Baik</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['kondisi']['baik'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Barang Rusak</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo ($stats['kondisi']['rusak'] ?? 0) + ($stats['kondisi']['perbaikan'] ?? 0); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Laboratorium</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_lab']; ?></p>
                    </div>
                </div>
            </div>

            <?php if (isAdmin()): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total User</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_user']; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Grafik Kondisi Barang</h3>
                <canvas id="kondisiChart"></canvas>
            </div>

            <!-- Recent Barang -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Barang Terbaru</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Barang</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kondisi</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lokasi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recent_barang as $barang): ?>
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php 
                                        echo $barang['kondisi'] === 'baik' ? 'bg-green-100 text-green-800' : 
                                            ($barang['kondisi'] === 'rusak' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); 
                                    ?>">
                                        <?php echo ucfirst($barang['kondisi']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($barang['nama_lab'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        /**
         * Inisialisasi Chart untuk Kondisi Barang
         */

        // 1. Mengambil konteks gambar (canvas) dari HTML dengan ID 'kondisiChart'
        // .getContext('2d') digunakan agar kita bisa menggambar grafik 2 dimensi
        const ctx = document.getElementById('kondisiChart').getContext('2d');

        // 2. Menyiapkan data kondisi dari PHP ke dalam objek JavaScript
        // Kita menggunakan tag  echo  agar angka dari database bisa dibaca oleh JS
        // Operasi '?? 0' memastikan jika data kosong, grafik akan menampilkan angka 0 (tidak error)
        const kondisiData = {
            baik: <?php echo $stats['kondisi']['baik'] ?? 0; ?>,
            rusak: <?php echo $stats['kondisi']['rusak'] ?? 0; ?>,
            perbaikan: <?php echo $stats['kondisi']['perbaikan'] ?? 0; ?>
        };

        // 3. Membuat instance grafik baru menggunakan library Chart.js
        new Chart(ctx, {
            type: 'doughnut', // Jenis grafik: donat (lingkaran berlubang di tengah)
            data: {
                labels: ['Baik', 'Rusak', 'Perbaikan'], // Nama kategori yang muncul di legenda
                datasets: [{
                    // Memasukkan angka statistik ke dalam urutan data grafik
                    data: [kondisiData.baik, kondisiData.rusak, kondisiData.perbaikan],
                    
                    // Mengatur warna masing-masing kategori:
                    // Hijau (#10b981) untuk Baik, Merah (#ef4444) untuk Rusak, Kuning (#f59e0b) untuk Perbaikan
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                    
                    borderWidth: 2,      // Ketebalan garis pinggir potongan donat
                    borderColor: '#fff'  // Warna garis pinggir (putih agar terlihat terpisah rapi)
                }]
            },
            options: {
                responsive: true,           // Grafik akan otomatis mengecil/membesar mengikuti ukuran layar
                maintainAspectRatio: true,  // Menjaga perbandingan tinggi dan lebar grafik agar tetap proporsional
                plugins: {
                    legend: {
                        position: 'bottom'  // Meletakkan keterangan label (legenda) di bawah grafik
                    }
                }
            }
        });
    </script>
</body>
</html>

