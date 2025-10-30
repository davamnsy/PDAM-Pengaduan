<?php
include 'config.php';

if (isset($_GET['id'])) {
    $id_pengaduan = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Query untuk mendapatkan detail pengaduan beserta data pelanggan
    $query = "SELECT p.*, pl.nama_pelanggan, pl.no_pelanggan, pl.alamat 
              FROM pengaduan p 
              JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan 
              WHERE p.id_pengaduan = $id_pengaduan";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $complaint = mysqli_fetch_assoc($result);
        
        // Mulai membuat output HTML untuk modal
        echo '<div class="card">';
        echo '<div class="card-header">';
        echo '<h3>Tindak Lanjut Pengaduan #' . $complaint['id_pengaduan'] . '</h3>';
        echo '</div>';
        echo '<div class="card-body">';
        
        // Info Pelanggan
        echo '<h4>Informasi Pelanggan</h4>';
        echo '<div class="form-row">';
        echo '<div class="form-col"><p><strong>No. Pelanggan:</strong> ' . $complaint['no_pelanggan'] . '</p></div>';
        echo '<div class="form-col"><p><strong>Nama Pelanggan:</strong> ' . $complaint['nama_pelanggan'] . '</p></div>';
        echo '</div>';
        echo '<p><strong>Alamat:</strong> ' . $complaint['alamat'] . '</p>';
        
        echo '<hr style="margin: 20px 0;">';
        
        // Detail Pengaduan
        echo '<h4>Detail Pengaduan</h4>';
        echo '<p><strong>Tanggal:</strong> ' . date('d/m/Y', strtotime($complaint['tanggal_pengaduan'])) . '</p>';
        echo '<p><strong>Isi Pengaduan:</strong></p>';
        echo '<p>' . nl2br(htmlspecialchars($complaint['isi_pengaduan'])) . '</p>';
        
        if (!empty($complaint['foto_bukti'])) {
            echo '<p><strong>Foto Bukti:</strong></p>';
            echo '<img src="uploads/' . $complaint['foto_bukti'] . '" alt="Foto Bukti" style="max-width: 100%; max-height: 300px; border-radius: 4px; border: 1px solid #ddd;">';
        }
        
        echo '<hr style="margin: 20px 0;">';
        
        // Form Update Status
        echo '<h4>Update Status</h4>';
        echo '<form id="updateStatusForm" onsubmit="return submitModalForm(event, \'updateStatusForm\')" action="process_complaint.php" method="post" style="margin-bottom: 20px;">';
        echo '<input type="hidden" name="action" value="update_status">';
        echo '<input type="hidden" name="id_pengaduan" value="' . $complaint['id_pengaduan'] . '">';
        echo '<div class="form-group">';
        echo '<select name="status" class="form-control">';
        echo '<option value="menunggu" ' . ($complaint['status'] == 'menunggu' ? 'selected' : '') . '>Menunggu</option>';
        echo '<option value="diproses" ' . ($complaint['status'] == 'diproses' ? 'selected' : '') . '>Diproses</option>';
        echo '<option value="selesai" ' . ($complaint['status'] == 'selesai' ? 'selected' : '') . '>Selesai</option>';
        echo '</select>';
        echo '</div>';
        echo '<button type="submit" class="btn">Update Status</button>';
        echo '</form>';
        
        // Form Tanggapan Admin
        echo '<h4>Tanggapan Admin</h4>';
        echo '<form id="submitResponseForm" onsubmit="return submitModalForm(event, \'submitResponseForm\')" action="process_complaint.php" method="post">';
        echo '<input type="hidden" name="action" value="submit_response">';
        echo '<input type="hidden" name="id_pengaduan" value="' . $complaint['id_pengaduan'] . '">';
        echo '<div class="form-group">';
        echo '<textarea name="tanggapan_admin" class="form-control" rows="4" placeholder="Berikan tanggapan...">' . (isset($complaint['tanggapan_admin']) ? htmlspecialchars($complaint['tanggapan_admin']) : '') . '</textarea>';
        echo '</div>';
        echo '<button type="submit" class="btn">Kirim Tanggapan</button>';
        echo '</form>';
        
        // Tampilkan tanggapan yang sudah ada
        if (!empty($complaint['tanggapan_admin'])) {
            echo '<div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-left: 3px solid var(--primary-color);">';
            echo '<p><strong>Tanggapan Sebelumnya:</strong></p>';
            echo '<p>' . nl2br(htmlspecialchars($complaint['tanggapan_admin'])) . '</p>';
            echo '<small>Dikirim pada: ' . date('d/m/Y H:i', strtotime($complaint['tanggal_tanggapan'])) . '</small>';
            echo '</div>';
        }
        
        echo '</div>'; // .card-body
        echo '</div>'; // .card
        
    } else {
        echo '<div class="alert alert-danger">Pengaduan tidak ditemukan.</div>';
    }
} else {
    echo '<div class="alert alert-danger">ID pengaduan tidak valid.</div>';
}
?>