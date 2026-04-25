/**
 * Fungsi untuk membuka Modal (Jendela Pop-up)
 * @param {string} action - Menentukan aksi ('add' untuk tambah atau 'edit' untuk ubah)
 * @param {object} data - Data laboratorium dari database (hanya untuk mode edit)
 */
function openModal(action, data = null) {
    // Menampilkan modal dengan menghapus class 'hidden' (menggunakan Tailwind CSS atau CSS murni)
    document.getElementById('modal').classList.remove('hidden');

    // Mengeset nilai input tersembunyi 'formAction' agar backend tahu ini proses tambah atau edit
    document.getElementById('formAction').value = action;
    
    // Logika jika tombol yang diklik adalah 'Edit'
    if (action === 'edit' && data) {
        // Mengubah judul modal menjadi 'Edit Laboratorium'
        document.getElementById('modalTitle').textContent = 'Edit Laboratorium';
        
        // Mengisi kolom input dengan data yang sudah ada di database
        document.getElementById('id_lab').value = data.id_lab;
        document.getElementById('nama_lab').value = data.nama_lab;
        
        // Mengisi kolom penanggung jawab, jika kosong diisi string kosong agar tidak muncul 'null'
        document.getElementById('penanggung_jawab').value = data.penanggung_jawab || '';
    } else {
        // Logika jika tombol yang diklik adalah 'Tambah' (action bukan edit)
        document.getElementById('modalTitle').textContent = 'Tambah Laboratorium';
        
        // Mengosongkan/membersihkan semua inputan di dalam form agar bersih
        document.getElementById('labForm').reset();
        
        // Memastikan ID lab kosong karena ini adalah data baru
        document.getElementById('id_lab').value = '';
    }
}