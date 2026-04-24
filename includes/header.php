<?php
// Cek session (sesuaikan dengan struktur folder Anda)
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../config/session.php';
}
requireLogin();

$current_page = basename($_SERVER['PHP_SELF']);
$role = getUserRole();
?>
<!-- memanggil Alpine.js framework JavaScript-->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- selesai -->

<div x-data="{ isOpen: false }">
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="isOpen = false" 
         class="fixed inset-0 bg-black/60 z-40 lg:hidden" x-cloak></div>

    <div class="lg:hidden fixed top-0 left-0 w-full bg-white shadow-md p-4 flex justify-between items-center z-40">
        <h1 class="font-bold text-gray-800">Inventory Lab</h1>
        <button @click="isOpen = !isOpen" class="p-2 text-gray-600 focus:outline-none">
            <i class="fas fa-bars text-2xl"></i>
        </button>
    </div>

    <aside 
        :class="isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 left-0 w-72 bg-slate-900 text-slate-300 flex flex-col transition-transform duration-300 ease-in-out z-50 shadow-2xl">
        
        <div class="h-20 flex items-center px-8 border-b border-slate-800 bg-slate-950/30">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-900/20">
                    <i class="fas fa-microchip text-white text-xl"></i>
                </div>
                <span class="text-xl font-bold text-white tracking-tight">INV-MANAGER</span>
            </div>
        </div>

        <nav class="flex-grow py-6 px-4 space-y-1 overflow-y-auto custom-scrollbar">
            <p class="text-[11px] font-bold text-slate-500 uppercase px-4 mb-4 tracking-widest">Main Menu</p>
            
            <a href="dashboard.php" 
               class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?php echo $current_page === 'dashboard.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'hover:bg-slate-800 hover:text-white'; ?>">
                <i class="fas fa-chart-pie w-6 text-lg"></i>
                <span class="ml-3 font-medium">Dashboard</span>
            </a>

            <div x-data="{ open: <?php echo in_array($current_page, ['barang.php', 'laboratorium.php']) ? 'true' : 'false' ?> }">
                <button @click="open = !open" 
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 hover:bg-slate-800 hover:text-white group">
                    <div class="flex items-center">
                        <i class="fas fa-layer-group w-6 text-lg"></i>
                        <span class="ml-3 font-medium">Data Master</span>
                    </div>
                    <i class="fas fa-chevron-right text-xs transition-transform duration-300" :class="open ? 'rotate-90' : ''"></i>
                </button>
                
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" 
                     x-transition:enter-start="opacity-0 -translate-y-2" 
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mt-2 ml-10 space-y-1 border-l-2 border-slate-800">
                    <a href="barang.php" class="block py-2 px-4 text-sm rounded-lg <?php echo $current_page === 'barang.php' ? 'text-blue-400 font-bold' : 'hover:text-white'; ?>">Daftar Barang</a>
                    <a href="laboratorium.php" class="block py-2 px-4 text-sm rounded-lg <?php echo $current_page === 'laboratorium.php' ? 'text-blue-400 font-bold' : 'hover:text-white'; ?>">Data Lab</a>
                </div>
            </div>

            <a href="riwayat.php" class="flex items-center px-4 py-3 rounded-xl transition-all hover:bg-slate-800 hover:text-white group <?php echo $current_page === 'riwayat.php' ? 'bg-blue-600 text-white' : ''; ?>">
                <i class="fas fa-clock-rotate-left w-6 text-lg"></i>
                <span class="ml-3 font-medium">Riwayat Stok</span>
            </a>
            <!-- kode php supaya admin saja yang dapat mengakses menu ini -->
            <?php if (isAdmin()): ?>
            <div class="pt-8">
                <p class="text-[11px] font-bold text-slate-500 uppercase px-4 mb-4 tracking-widest">Administrator</p>
                
                <a href="users.php" class="flex items-center px-4 py-3 rounded-xl transition-all hover:bg-slate-800 hover:text-white group <?php echo $current_page === 'users.php' ? 'bg-blue-600 text-white' : ''; ?>">
                    <i class="fas fa-user-gear w-6 text-lg"></i>
                    <span class="ml-3 font-medium">Manajemen User</span>
                </a>

                <div x-data="{ open: false }">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all hover:bg-slate-800 hover:text-white group">
                        <div class="flex items-center">
                            <i class="fas fa-file-contract w-6 text-lg"></i>
                            <span class="ml-3 font-medium">Laporan</span>
                        </div>
                        <i class="fas fa-chevron-right text-xs transition-transform" :class="open ? 'rotate-90' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition class="mt-2 ml-10 space-y-1 border-l-2 border-slate-800">
                        <a href="laporan.php" class="block py-2 px-4 text-sm hover:text-white">Lap. Inventaris</a>
                        <a href="laporan_aktivitas.php" class="block py-2 px-4 text-sm hover:text-white">Lap. Aktivitas</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <!-- selesai -->
        </nav>

        <div class="p-4 bg-slate-950/40 border-t border-slate-800">
            <div class="flex items-center p-3 mb-4 bg-slate-800/50 rounded-2xl">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-inner">
                    <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                </div>
                <div class="ml-3 overflow-hidden">
                    <p class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($_SESSION['nama']); ?></p>
                    <p class="text-[11px] text-blue-400 font-medium uppercase tracking-tighter"><?php echo $role; ?></p>
                </div>
            </div>
            <a href="logout.php" class="flex items-center justify-center w-full py-3 px-4 text-sm font-bold text-red-400 border border-red-500/20 rounded-xl hover:bg-red-500 hover:text-white transition-all duration-200">
                <i class="fas fa-power-off mr-2"></i> KELUAR SISTEM
            </a>
        </div>
    </aside>
</div>

<style>
    [x-cloak] { display: none !important; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #334155; }
</style>