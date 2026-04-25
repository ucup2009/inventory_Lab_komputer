/**
 * Fungsi untuk membuka jendela Modal (Tambah atau Edit User)
 * @param {string} action - Menentukan tindakan ('add' atau 'edit')
 * @param {object} data - Data user yang akan diedit (null jika tambah baru)
 */
function openModal(action, data = null) {
    // Menampilkan modal dengan menghapus class 'hidden' (menggunakan Tailwind CSS/CSS)
    document.getElementById('modal').classList.remove('hidden');
    
    // Mengatur nilai pada input hidden 'formAction' agar backend tahu ini proses simpan atau update
    document.getElementById('formAction').value = action;
    
    // Mengecek apakah tindakan adalah 'edit' dan data user tersedia
    if (action === 'edit' && data) {
        // Mengubah judul modal menjadi 'Edit User'
        document.getElementById('modalTitle').textContent = 'Edit User';
        
        // Mengisi kolom input form dengan data user yang sudah ada
        document.getElementById('id_user').value = data.id_user; // ID User untuk referensi update
        document.getElementById('nama').value = data.nama;
        document.getElementById('username').value = data.username;
        
        // Saat edit, password tidak wajib diisi (boleh dikosongkan jika tidak ingin ganti)
        document.getElementById('password').required = false;
        
        // Mengatur dropdown/input role sesuai data user
        document.getElementById('role').value = data.role;
    } else {
        // Jika tindakan adalah 'add' (tambah user baru)
        document.getElementById('modalTitle').textContent = 'Tambah User';
        
        // Mereset atau mengosongkan semua kolom input di dalam form
        document.getElementById('userForm').reset();
        
        // Memastikan ID user kosong karena ini adalah record baru
        document.getElementById('id_user').value = '';
        
        // Saat tambah user baru, password hukumnya wajib diisi (required)
        document.getElementById('password').required = true;
    }
}

/**
 * Fungsi untuk menutup jendela Modal
 */
function closeModal() {
    // Menyembunyikan kembali modal dengan menambahkan class 'hidden'
    document.getElementById('modal').classList.add('hidden');
}

/**
 * Fungsi untuk menghapus user berdasarkan ID
 * @param {number|string} id - ID user yang akan dihapus
 */
function deleteUser(id) {
    // Menampilkan dialog konfirmasi ke user sebelum benar-benar menghapus
    if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
        // Membuat elemen form secara dinamis (bayangan) untuk mengirim data via POST
        const form = document.createElement('form');
        form.method = 'POST'; // Menggunakan metode POST agar lebih aman
        form.action = 'actions/users_action.php'; // Tujuan pengiriman data
        
        // Membuat input tersembunyi untuk instruksi aksi 'delete'
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        // Membuat input tersembunyi untuk mengirimkan ID user yang akan dihapus
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id_user';
        idInput.value = id;
        form.appendChild(idInput);
        
        // Memasukkan form bayangan ke dalam dokumen agar bisa dikirim
        document.body.appendChild(form);
        
        // Menjalankan submit form secara otomatis
        form.submit();
    }
}