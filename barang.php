<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireLogin();

$db = (new Database())->getConnection();

// 1. Cek Kolom (Lebih ringkas)
$cols = $db->query("SHOW COLUMNS FROM barang")->fetchAll(PDO::FETCH_COLUMN);
$lab_column = in_array('lokasi_lab', $cols) ? 'lokasi_lab' : (in_array('id_lab', $cols) ? 'id_lab' : null);
$has_lab_column = !is_null($lab_column);

// 2. Ambil data Lab untuk Dropdown
$laboratorium = [];
try {
    $laboratorium = $db->query("SELECT id_lab, nama_lab FROM laboratorium ORDER BY nama_lab")->fetchAll();
} catch (Exception $e) {}

// 3. Filter & Search
$search = $_GET['search'] ?? '';
$filter_lab = $_GET['filter_lab'] ?? '';
$filter_kondisi = $_GET['filter_kondisi'] ?? '';

$query = "SELECT b.*, " . ($has_lab_column ? "l.nama_lab" : "NULL as nama_lab") . " FROM barang b ";
if ($has_lab_column) $query .= "LEFT JOIN laboratorium l ON b.$lab_column = l.id_lab ";
$query .= "WHERE 1=1";

$params = [];
if ($search) {
    $query .= " AND (b.nama_barang LIKE ? OR b.jenis LIKE ? OR b.merk LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($filter_lab && $has_lab_column) {
    $query .= " AND b.$lab_column = ?";
    $params[] = $filter_lab;
}
if ($filter_kondisi) {
    $query .= " AND b.kondisi = ?";
    $params[] = $filter_kondisi;
}
$query .= " ORDER BY b.id_barang DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$barang_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Barang - Inventory Lab</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php foreach(['success', 'error'] as $type): if(isset($_SESSION[$type])): ?>
            <div class="mb-4 p-4 rounded <?= $type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= $_SESSION[$type]; unset($_SESSION[$type]); ?>
            </div>
        <?php endif; endforeach; ?>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold">Data Barang</h2>
            <button onclick="openModal('add')" class="bg-blue-600 text-white px-4 py-2 rounded-lg">+ Tambah Barang</button>
        </div>

        <form class="bg-white p-4 rounded-lg shadow mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari barang..." class="border p-2 rounded">
            <?php if($has_lab_column): ?>
            <select name="filter_lab" class="border p-2 rounded">
                <option value="">Semua Lab</option>
                <?php foreach($laboratorium as $lab): ?>
                    <option value="<?= $lab['id_lab'] ?>" <?= $filter_lab == $lab['id_lab'] ? 'selected' : '' ?>><?= htmlspecialchars($lab['nama_lab']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <select name="filter_kondisi" class="border p-2 rounded">
                <option value="">Semua Kondisi</option>
                <option value="baik" <?= $filter_kondisi == 'baik' ? 'selected' : '' ?>>Baik</option>
                <option value="rusak" <?= $filter_kondisi == 'rusak' ? 'selected' : '' ?>>Rusak</option>
                <option value="perbaikan" <?= $filter_kondisi == 'perbaikan' ? 'selected' : '' ?>>Perbaikan</option>
            </select>
            <button type="submit" class="bg-gray-600 text-white rounded">Filter</button>
        </form>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase">Nama Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase">Jenis</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase">Kondisi</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase">Jumlah</th>
                        <?php if($has_lab_column): ?> <th class="px-6 py-3 text-left text-xs font-bold uppercase">Lab</th> <?php endif; ?>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach($barang_list as $b): ?>
                    <tr class="hover:bg-gray-50 text-sm">
                        <td class="px-6 py-4 font-medium"><?= htmlspecialchars($b['nama_barang']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($b['jenis']) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs <?= $b['kondisi']=='baik'?'bg-green-100 text-green-800':($b['kondisi']=='rusak'?'bg-red-100 text-red-800':'bg-yellow-100 text-yellow-800') ?>">
                                <?= ucfirst($b['kondisi']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4"><?= $b['jumlah'] ?></td>
                        <?php if($has_lab_column): ?> <td class="px-6 py-4"><?= htmlspecialchars($b['nama_lab'] ?? '-') ?></td> <?php endif; ?>
                        <td class="px-6 py-4">
                            <button onclick='openModal("edit", <?= json_encode($b) ?>)' class="text-blue-600 mr-3">Edit</button>
                            <?php if(isAdmin()): ?>
                                <button onclick="deleteBarang(<?= $b['id_barang'] ?>)" class="text-red-600">Hapus</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-bold mb-4" id="modalTitle">Tambah Barang</h3>
            <form id="barangForm" method="POST" action="actions/barang_action.php" class="space-y-4">
                <input type="hidden" name="id_barang" id="id_barang">
                <input type="hidden" name="action" id="formAction" value="add">
                
                <div>
                    <label class="block text-sm font-medium">Nama Barang *</label>
                    <input type="text" name="nama_barang" id="nama_barang" required class="w-full border p-2 rounded">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Jenis</label>
                        <input type="text" name="jenis" id="jenis" class="w-full border p-2 rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Merk</label>
                        <input type="text" name="merk" id="merk" class="w-full border p-2 rounded">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Kondisi</label>
                        <select name="kondisi" id="kondisi" class="w-full border p-2 rounded">
                            <option value="baik">Baik</option>
                            <option value="rusak">Rusak</option>
                            <option value="perbaikan">Perbaikan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Jumlah</label>
                        <input type="number" name="jumlah" id="jumlah" required class="w-full border p-2 rounded">
                    </div>
                </div>
                
                <?php if($has_lab_column): ?>
                <div>
                    <label class="block text-sm font-medium">Laboratorium</label>
                    <select name="lokasi_lab" id="lokasi_lab" required class="w-full border p-2 rounded">
                        <option value="">Pilih Lab</option>
                        <?php foreach($laboratorium as $lab): ?>
                            <option value="<?= $lab['id_lab'] ?>"><?= htmlspecialchars($lab['nama_lab']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" rows="2" class="w-full border p-2 rounded"></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(action, data = null) {
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('barangForm').reset();
            document.getElementById('formAction').value = action;
            document.getElementById('modalTitle').textContent = action === 'edit' ? 'Edit Barang' : 'Tambah Barang';

            if (action === 'edit' && data) {
                document.getElementById('id_barang').value = data.id_barang;
                document.getElementById('nama_barang').value = data.nama_barang;
                document.getElementById('jenis').value = data.jenis;
                document.getElementById('merk').value = data.merk || '';
                document.getElementById('kondisi').value = data.kondisi;
                document.getElementById('jumlah').value = data.jumlah;
                document.getElementById('keterangan').value = data.keterangan || '';
                <?php if($has_lab_column): ?>
                document.getElementById('lokasi_lab').value = data.lokasi_lab || data.id_lab || '';
                <?php endif; ?>
            }
        }

        function closeModal() { document.getElementById('modal').classList.add('hidden'); }

        function deleteBarang(id) {
            if (confirm('Hapus barang ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'actions/barang_action.php';
                form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id_barang" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>