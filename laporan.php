<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireAdmin();

$db = (new Database())->getConnection();

// Cek keberadaan kolom lab secara ringkas
$cols = $db->query("SHOW COLUMNS FROM barang")->fetchAll(PDO::FETCH_COLUMN);
$lab_col = in_array('lokasi_lab', $cols) ? 'lokasi_lab' : (in_array('id_lab', $cols) ? 'id_lab' : null);

// Ambil data filter & daftar lab
$f_lab = $_GET['filter_lab'] ?? '';
$f_kon = $_GET['filter_kondisi'] ?? '';
$laboratorium = $db->query("SELECT id_lab, nama_lab FROM laboratorium ORDER BY nama_lab")->fetchAll();

// Bangun query secara dinamis
$sql = "SELECT b.*, " . ($lab_col ? "l.nama_lab" : "NULL as nama_lab") . " FROM barang b ";
if ($lab_col) $sql .= "LEFT JOIN laboratorium l ON b.$lab_col = l.id_lab ";
$sql .= "WHERE 1=1";

$params = [];
if ($f_lab && $lab_col) { $sql .= " AND b.$lab_col = ?"; $params[] = $f_lab; }
if ($f_kon) { $sql .= " AND b.kondisi = ?"; $params[] = $f_kon; }

$sql .= $lab_col ? " ORDER BY l.nama_lab, b.nama_barang" : " ORDER BY b.nama_barang";

// Eksekusi data & hitung statistik
$stmt = $db->prepare($sql);
$stmt->execute($params);
$barang_list = $stmt->fetchAll();

$stats = ['total' => count($barang_list), 'baik' => 0, 'rusak' => 0, 'perbaikan' => 0];
foreach ($barang_list as $b) { if (isset($stats[$b['kondisi']])) $stats[$b['kondisi']]++; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold">Laporan Inventory</h2>
            <div class="space-x-2">
                <button onclick="window.print()" class="bg-gray-600 text-white px-4 py-2 rounded-lg">Cetak</button>
                <button onclick="exportToExcel()" class="bg-green-600 text-white px-4 py-2 rounded-lg">Excel</button>
            </div>
        </div>

        <form class="bg-white p-4 rounded-lg shadow mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <select name="filter_lab" class="border p-2 rounded">
                <option value="">Semua Lab</option>
                <?php foreach ($laboratorium as $l): ?>
                    <option value="<?= $l['id_lab'] ?>" <?= $f_lab == $l['id_lab'] ? 'selected' : '' ?>><?= htmlspecialchars($l['nama_lab']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="filter_kondisi" class="border p-2 rounded">
                <option value="">Semua Kondisi</option>
                <?php foreach(['baik', 'rusak', 'perbaikan'] as $k): ?>
                    <option value="<?= $k ?>" <?= $f_kon == $k ? 'selected' : '' ?>><?= ucfirst($k) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="bg-blue-600 text-white rounded">Filter</button>
        </form>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <?php foreach ($stats as $key => $val): ?>
                <div class="bg-white p-4 rounded shadow">
                    <p class="text-sm uppercase"><?= $key ?></p>
                    <p class="text-2xl font-bold"><?= $val ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full" id="reportTable">
                <thead class="bg-gray-50 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3 text-left">Barang</th><th class="px-6 py-3 text-left">Jenis</th>
                        <th class="px-6 py-3 text-left">Merk</th><th class="px-6 py-3 text-left">Kondisi</th>
                        <th class="px-6 py-3 text-left">Jumlah</th><th class="px-6 py-3 text-left">Lab</th>
                    </tr>
                </thead>
                <tbody class="divide-y text-sm">
                    <?php if (empty($barang_list)): ?>
                        <tr><td colspan="6" class="p-6 text-center">Data kosong</td></tr>
                    <?php else: foreach ($barang_list as $b): ?>
                        <tr>
                            <td class="px-6 py-4 font-medium"><?= htmlspecialchars($b['nama_barang']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($b['jenis']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($b['merk'] ?? '-') ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs <?= $b['kondisi']=='baik'?'bg-green-100':($b['kondisi']=='rusak'?'bg-red-100':'bg-yellow-100') ?>">
                                    <?= ucfirst($b['kondisi']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4"><?= $b['jumlah'] ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($b['nama_lab'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function exportToExcel() {
            const blob = new Blob([document.getElementById('reportTable').outerHTML], { type: 'application/vnd.ms-excel' });
            const a = document.createElement('a');
            a.href = window.URL.createObjectURL(blob);
            a.download = `laporan_${new Date().toISOString().slice(0,10)}.xls`;
            a.click();
        }
    </script>
</body>
</html>