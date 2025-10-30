<?php
session_start();
include 'config.php';

// Check if customer is logged in
if (!isset($_SESSION['pelanggan_logged_in']) || $_SESSION['pelanggan_logged_in'] !== true) {
    header('Location: login_pelanggan.php');
    exit;
}

 $id_pelanggan = $_SESSION['id_pelanggan'];
 $success_message = '';
 $error_message = '';

// Handle proof of payment upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_bukti') {
    $id_tunggakan = mysqli_real_escape_string($conn, $_POST['id_tunggakan']);
    
    if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] == 0) {
        $target_dir = "uploads/payments/";
        // Buat folder jika belum ada
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES["bukti_pembayaran"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Validasi file
        $check = getimagesize($_FILES["bukti_pembayaran"]["tmp_name"]);
        if ($check !== false) {
            if ($_FILES["bukti_pembayaran"]["size"] <= 5000000) { // Max 5MB
                if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
                    if (move_uploaded_file($_FILES["bukti_pembayaran"]["tmp_name"], $target_file)) {
                        // Update database
                        $query = "UPDATE tunggakan SET bukti_pembayaran = '$file_name', status = 'menunggu_konfirmasi' WHERE id_tunggakan = $id_tunggakan AND id_pelanggan = $id_pelanggan";
                        if (mysqli_query($conn, $query)) {
                            $success_message = "Bukti pembayaran berhasil diunggah. Menunggu konfirmasi dari admin.";
                        } else {
                            $error_message = "Gagal memperbarui database: " . mysqli_error($conn);
                        }
                    } else {
                        $error_message = "Terjadi kesalahan saat mengunggah file.";
                    }
                } else {
                    $error_message = "Maaf, hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.";
                }
            } else {
                $error_message = "Maaf, file terlalu besar. Maksimal 5MB.";
            }
        } else {
            $error_message = "File bukan gambar.";
        }
    } else {
        $error_message = "Pilih file bukti pembayaran terlebih dahulu.";
    }
}

// Fetch customer's arrears
 $query = "SELECT * FROM tunggakan WHERE id_pelanggan = $id_pelanggan ORDER BY periode_tahun DESC, periode_bulan DESC";
 $result = mysqli_query($conn, $query);

// Calculate total outstanding debt (only unpaid ones)
 $total_outstanding = 0;
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['status'] == 'belum_bayar') {
        $total_outstanding += $row['jumlah_tagihan'];
    }
}
// Reset result pointer to loop again for display
mysqli_data_seek($result, 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Info Tunggakan - PDAM Tirta Musi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .status-badge.status-menunggu_konfirmasi {
            background: #fff3cd;
            color: #856404;
        }
        .proof-img {
            max-width: 100px;
            max-height: 60px;
            cursor: pointer;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="assets/img/logo.jpg" alt="PDAM Tirta Musi Logo">
                    <h1>PDAM Tirta Musi - Seberang Ulu 2</h1>
                </div>
                <div class="header-info">
                    <p>Selamat datang, <?php echo $_SESSION['nama_pelanggan']; ?> | <a href="logout.php" style="color: var(--light-text);">Logout</a></p>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="dashboard-container">
                <aside class="sidebar">
                    <h3>Menu Pelanggan</h3>
                    <ul class="sidebar-menu">
                        <li><a href="form_pengaduan.php">Buat Pengaduan</a></li>
                        <li><a href="riwayat_pengaduan.php">Riwayat Pengaduan</a></li>
                        <li><a href="info_tunggakan.php" class="active">Info Tunggakan</a></li>
                    </ul>
                </aside>
                
                <div class="main-content">
                    <div class="content-header">
                        <h2>Informasi Tunggakan</h2>
                    </div>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <div class="card" style="border-left: 5px solid var(--warning-color);">
                        <div class="card-body">
                            <h3>Total Tunggakan Aktif</h3>
                            <p style="font-size: 24px; font-weight: bold; color: var(--warning-color);">
                                Rp <?php echo number_format($total_outstanding, 0, ',', '.'); ?>
                            </p>
                            <p style="font-size: 14px; color: #666;">Mohon segera melakukan pembayaran jika ada tunggakan.</p>
                        </div>
                    </div>

                    <div class="table-container" style="margin-top: 20px;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Periode</th>
                                    <th>Jumlah Tagihan</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status Pembayaran</th>
                                    <th>Bukti Pembayaran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo date("F Y", mktime(0, 0, 0, $row['periode_bulan'], 1, $row['periode_tahun'])); ?></td>
                                            <td>Rp <?php echo number_format($row['jumlah_tagihan'], 0, ',', '.'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['jatuh_tempo'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo str_replace('_', '-', $row['status']); ?>">
                                                    <?php 
                                                        $status_text = str_replace('_', ' ', $row['status']);
                                                        echo ucfirst($status_text); 
                                                    ?>
                                                </span>
                                                <?php if ($row['status'] == 'sudah_bayar' && $row['tanggal_bayar']): ?>
                                                    <br><small>(Bayar: <?php echo date('d/m/Y', strtotime($row['tanggal_bayar'])); ?>)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['bukti_pembayaran'])): ?>
                                                    <img src="uploads/payments/<?php echo $row['bukti_pembayaran']; ?>" alt="Bukti" class="proof-img" onclick="window.open(this.src, '_blank')">
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] == 'belum_bayar'): ?>
                                                    <!-- Tombol yang diperbaiki -->
                                                    <button class="btn btn-sm" onclick="openUploadModal(<?php echo $row['id_tunggakan']; ?>)">Bayar Sekarang</button>
                                                <?php elseif ($row['status'] == 'menunggu_konfirmasi'): ?>
                                                    <small>Menunggu Konfirmasi</small>
                                                <?php else: ?>
                                                    <small>Lunas</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">Tidak ada riwayat tagihan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal for Uploading Proof -->
    <div id="uploadModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="max-width: 500px; margin: 10% auto; padding: 20px; background: white; border-radius: 8px;">
            <span class="close" onclick="document.getElementById('uploadModal').style.display='none'" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h3>Unggah Bukti Pembayaran</h3>
            <form action="info_tunggakan.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_bukti">
                <input type="hidden" id="modal_id_tunggakan" name="id_tunggakan">
                <div class="form-group">
                    <label for="bukti_pembayaran">Pilih File Bukti (JPG/PNG)</label>
                    <div class="file-upload">
                        <input type="file" id="bukti_pembayaran" name="bukti_pembayaran" accept="image/*" required>
                        <label for="bukti_pembayaran" class="file-upload-label">Pilih file...</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-block">Unggah Bukti</button>
            </form>
        </div>
    </div>

    <script>
        // Fungsi untuk membuka modal unggah bukti
        function openUploadModal(id) {
            // --- DEBUGGING: Tampilkan alert untuk memastikan fungsi dipanggil ---
            // Hapus atau beri komentar pada baris di bawah ini jika tombol sudah berfungsi
            alert('Fungsi dipanggil dengan ID Tunggakan: ' + id);
            // ---------------------------------------------------------------

            // Set nilai ID ke hidden input di dalam form modal
            document.getElementById('modal_id_tunggakan').value = id;
            
            // Tampilkan modal
            document.getElementById('uploadModal').style.display = 'block';
        }

        // Tutup modal jika pengguna mengklik di luar area modal
        window.onclick = function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    <script src="assets/js/script.js"></script>
</body>
</html>