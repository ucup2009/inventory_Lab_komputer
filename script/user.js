//  Menambah data baru dan Mengubah data lama (Edit).
 
 function openModal(action, data = null) {
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('formAction').value = action;
            
            if (action === 'edit' && data) {
                document.getElementById('modalTitle').textContent = 'Edit User';
                document.getElementById('id_user').value = data.id_user;
                document.getElementById('nama').value = data.nama;
                document.getElementById('username').value = data.username;
                document.getElementById('password').required = false;
                document.getElementById('role').value = data.role;
            } else {
                document.getElementById('modalTitle').textContent = 'Tambah User';
                document.getElementById('userForm').reset();
                document.getElementById('id_user').value = '';
                document.getElementById('password').required = true;
            }
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
        }

        function deleteUser(id) {
            if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'actions/users_action.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id_user';
                idInput.value = id;
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }