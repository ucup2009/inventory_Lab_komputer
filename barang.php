<?php
require_once 'config/database.php';
require_once 'config/session.php';
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
$has_lab_column = $has_lokasi_lab || $has_id_lab;

// Get laboratorium for dropdown
$laboratorium = [];
try {
    $stmt = $db->query("SELECT id_lab, nama_lab FROM laboratorium ORDER BY nama_lab");
    $laboratorium = $stmt->fetchAll();
} catch (Exception $e) {
    // Table might not exist yet
}

// Search and filter
$search = $_GET['search'] ?? '';
$filter_lab = $_GET['filter_lab'] ?? '';
$filter_kondisi = $_GET['filter_kondisi'] ?? '';

// Build query based on whether lab column exists
if ($has_lab_column) {
    $query = "SELECT b.*, l.nama_lab FROM barang b 
              LEFT JOIN laboratorium l ON b.$lab_column = l.id_lab 
              WHERE 1=1";
} else {
    $query = "SELECT b.*, NULL as nama_lab FROM barang b WHERE 1=1";
}

$params = [];

if (!empty($search)) {
    $query .= " AND (b.nama_barang LIKE ? OR b.jenis LIKE ? OR b.merk LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($filter_lab) && $has_lab_column) {
    $query .= " AND b.$lab_column = ?";
    $params[] = $filter_lab;
}

if (!empty($filter_kondisi)) {
    $query .= " AND b.kondisi = ?";
    $params[] = $filter_kondisi;
}

$query .= " ORDER BY b.id_barang DESC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $barang_list = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback: get barang without join
    $query = "SELECT b.*, NULL as nama_lab FROM barang b ORDER BY b.id_barang DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $barang_list = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Inventory Lab Komputer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
        </div>
        <?php endif; ?>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Data Barang</h2>
            <button onclick="openModal('add')" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition duration-200">
                + Tambah Barang
            </button>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                        placeholder="Cari barang..." 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                </div>
                <?php if ($has_lab_column): ?>
                <div>
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
                <?php endif; ?>
                <div>
                    <select name="filter_kondisi" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                        <option value="">Semua Kondisi</option>
                        <option value="baik" <?php echo $filter_kondisi === 'baik' ? 'selected' : ''; ?>>Baik</option>
                        <option value="rusak" <?php echo $filter_kondisi === 'rusak' ? 'selected' : ''; ?>>Rusak</option>
                        <option value="perbaikan" <?php echo $filter_kondisi === 'perbaikan' ? 'selected' : ''; ?>>Perbaikan</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Merk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kondisi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                            <?php if ($has_lab_column): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi Lab</th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($barang_list)): ?>
                        <tr>
                            <td colspan="<?php echo $has_lab_column ? '7' : '6'; ?>" class="px-6 py-4 text-center text-gray-500">Tidak ada data barang</td>
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
                            <?php if ($has_lab_column): ?>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($barang['nama_lab'] ?? '-'); ?></div>
                            </td>
                            <?php endif; ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($barang)); ?>)" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <?php if (isAdmin()): ?>
                                <button onclick="deleteBarang(<?php echo $barang['id_barang']; ?>)" 
                                    class="text-red-600 hover:text-red-900">Hapus</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Tambah Barang</h3>
                <form id="barangForm" method="POST" action="actions/barang_action.php">
                    <input type="hidden" name="id_barang" id="id_barang">
                    <input type="hidden" name="action" id="formAction" value="add">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Barang *</label>
                            <input type="text" name="nama_barang" id="nama_barang" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis *</label>
                            <select name="jenis" id="jenis" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                                <option value="">Pilih Jenis</option>
                                <option value="PC">PC</option>
                                <option value="Monitor">Monitor</option>
                                <option value="Keyboard">Keyboard</option>
                                <option value="Mouse">Mouse</option>
                                <option value="Printer">Printer</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Merk</label>
                            <input type="text" name="merk" id="merk"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi *</label>
                            <select name="kondisi" id="kondisi" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                                <option value="baik">Baik</option>
                                <option value="rusak">Rusak</option>
                                <option value="perbaikan">Perbaikan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah *</label>
                            <input type="number" name="jumlah" id="jumlah" required min="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                        </div>
                        <?php if ($has_lab_column): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Laboratorium *</label>
                            <select name="lokasi_lab" id="lokasi_lab" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                                <option value="">Pilih Lab</option>
                                <?php foreach ($laboratorium as $lab): ?>
                                <option value="<?php echo $lab['id_lab']; ?>"><?php echo htmlspecialchars($lab['nama_lab']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                            <p class="text-sm text-yellow-800">
                                <strong>Info:</strong> Kolom lokasi_lab/id_lab belum ada di database. 
                                Tambahkan kolom dengan menjalankan: 
                                <code class="bg-yellow-100 px-2 py-1 rounded">ALTER TABLE barang ADD COLUMN lokasi_lab INT NULL;</code>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition duration-200">
                            Batal
                        </button>
                        <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal(action, data = null) {
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('formAction').value = action;
            
            if (action === 'edit' && data) {
                document.getElementById('modalTitle').textContent = 'Edit Barang';
                document.getElementById('id_barang').value = data.id_barang;
                document.getElementById('nama_barang').value = data.nama_barang;
                document.getElementById('jenis').value = data.jenis;
                document.getElementById('merk').value = data.merk || '';
                document.getElementById('kondisi').value = data.kondisi;
                document.getElementById('jumlah').value = data.jumlah;
                <?php if ($has_lab_column): ?>
                document.getElementById('lokasi_lab').value = data.lokasi_lab || data.id_lab || '';
                <?php endif; ?>
            } else {
                document.getElementById('modalTitle').textContent = 'Tambah Barang';
                document.getElementById('barangForm').reset();
                document.getElementById('id_barang').value = '';
            }
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
        }

        function deleteBarang(id) {
            if (confirm('Apakah Anda yakin ingin menghapus barang ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'actions/barang_action.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id_barang';
                idInput.value = id;
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

