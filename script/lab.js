function openModal(action, data = null) {
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('formAction').value = action;
            
            if (action === 'edit' && data) {
                document.getElementById('modalTitle').textContent = 'Edit Laboratorium';
                document.getElementById('id_lab').value = data.id_lab;
                document.getElementById('nama_lab').value = data.nama_lab;
                document.getElementById('penanggung_jawab').value = data.penanggung_jawab || '';
            } else {
                document.getElementById('modalTitle').textContent = 'Tambah Laboratorium';
                document.getElementById('labForm').reset();
                document.getElementById('id_lab').value = '';
            }
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
        }

        function deleteLab(id) {
            if (confirm('Apakah Anda yakin ingin menghapus laboratorium ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'actions/laboratorium_action.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id_lab';
                idInput.value = id;
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }