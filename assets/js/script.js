// assets/js/script.js
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    
                    // Create error message if it doesn't exist
                    let errorMsg = field.parentNode.querySelector('.invalid-feedback');
                    if (!errorMsg) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'invalid-feedback';
                        errorMsg.textContent = 'Field ini harus diisi';
                        field.parentNode.appendChild(errorMsg);
                    }
                } else {
                    field.classList.remove('is-invalid');
                    const errorMsg = field.parentNode.querySelector('.invalid-feedback');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showAlert('Mohon lengkapi semua field yang diperlukan', 'danger');
            }
        });
    });
    
    // File upload preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const label = this.nextElementSibling;
            
            if (file) {
                label.textContent = file.name;
                
                // If it's an image, show preview
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        let preview = input.parentNode.querySelector('.image-preview');
                        
                        if (!preview) {
                            preview = document.createElement('div');
                            preview.className = 'image-preview';
                            preview.style.marginTop = '10px';
                            input.parentNode.appendChild(preview);
                        }
                        
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px;">`;
                    };
                    
                    reader.readAsDataURL(file);
                }
            } else {
                label.textContent = 'Pilih file...';
                const preview = input.parentNode.querySelector('.image-preview');
                if (preview) {
                    preview.remove();
                }
            }
        });
    });
    
    // Status change confirmation
    const statusSelects = document.querySelectorAll('.status-select');
    
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const status = this.value;
            const complaintId = this.dataset.id;
            
            if (confirm(`Apakah Anda yakin ingin mengubah status pengaduan ke "${status}"?`)) {
                updateStatus(complaintId, status);
            } else {
                // Reset to previous value
                this.value = this.dataset.previousValue;
            }
        });
        
        // Store initial value
        select.dataset.previousValue = select.value;
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

// Show alert message
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} fade-in`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => {
            alertDiv.remove();
        }, 300);
    }, 5000);
}

// Update complaint status via AJAX
function updateStatus(complaintId, status) {
    const formData = new FormData();
    formData.append('id_pengaduan', complaintId);
    formData.append('status', status);
    formData.append('action', 'update_status');
    
    fetch('process_complaint.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Status berhasil diperbarui', 'success');
            
            // Update status badge
            const statusBadge = document.querySelector(`#status-${complaintId}`);
            if (statusBadge) {
                statusBadge.className = `status-badge status-${status}`;
                statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            }
            
            // Update previous value
            const statusSelect = document.querySelector(`[data-id="${complaintId}"]`);
            if (statusSelect) {
                statusSelect.dataset.previousValue = status;
            }
        } else {
            showAlert('Gagal memperbarui status: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
    });
}

// Submit response form via AJAX
function submitResponse(complaintId) {
    const responseText = document.querySelector(`#response-${complaintId}`).value;
    
    if (!responseText.trim()) {
        showAlert('Tanggapan tidak boleh kosong', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('id_pengaduan', complaintId);
    formData.append('tanggapan_admin', responseText);
    formData.append('action', 'submit_response');
    
    fetch('process_complaint.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Tanggapan berhasil dikirim', 'success');
            
            // Update response display
            const responseDisplay = document.querySelector(`#response-display-${complaintId}`);
            if (responseDisplay) {
                responseDisplay.textContent = responseText;
            }
            
            // Clear form
            document.querySelector(`#response-${complaintId}`).value = '';
        } else {
            showAlert('Gagal mengirim tanggapan: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
    });
}