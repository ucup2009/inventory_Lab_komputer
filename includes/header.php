<?php
// Include session config
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../config/session.php';
}
requireLogin();

$current_page = basename($_SERVER['PHP_SELF']);
$role = getUserRole();
?>
<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <h1 class="text-xl font-bold text-gray-800">Inventory Lab Komputer</h1>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="dashboard.php" 
                        class="<?php echo $current_page === 'dashboard.php' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="barang.php" 
                        class="<?php echo $current_page === 'barang.php' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Data Barang
                    </a>
                    <a href="laboratorium.php" 
                        class="<?php echo $current_page === 'laboratorium.php' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Laboratorium
                    </a>
                    <a href="riwayat.php" 
                        class="<?php echo $current_page === 'riwayat.php' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Riwayat Barang
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="users.php" 
                        class="<?php echo $current_page === 'users.php' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Data User
                    </a>
                    <a href="laporan.php" 
                        class="<?php echo $current_page === 'laporan.php' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Laporan
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center">
                <span class="text-sm text-gray-700 mr-4">
                    <?php echo htmlspecialchars($_SESSION['nama']); ?> 
                    <span class="text-blue-600 font-semibold">(<?php echo ucfirst($role); ?>)</span>
                </span>
                <a href="logout.php" 
                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-200">
                    Logout
                </a>
            </div>
        </div>
    </div>
</nav>

