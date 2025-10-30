<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login_admin.php');
    exit;
}

 $success_message = '';
 $error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_status') {
            $id_pengaduan = mysqli_real_escape_string($conn, $_POST['id_pengaduan']);
            $status = mysqli_real_escape_string($conn, $_POST['status']);
            
            $query = "UPDATE pengaduan SET status = '$status' WHERE id_pengaduan = $id_pengaduan";
            
            if (mysqli_query($conn, $query)) {
                $success_message = 'Status pengaduan berhasil diperbarui!';
            } else {
                $error_message = 'Gagal memperbarui status: ' . mysqli_error($conn);
            }
        } elseif ($_POST['action'] == 'submit_response') {
            $id_pengaduan = mysqli_real_escape_string($conn, $_POST['id_pengaduan']);
            $tanggapan_admin = mysqli_real_escape_string($conn, $_POST['tanggapan_admin']);
            
            $query = "UPDATE pengaduan SET tanggapan_admin = '$tanggapan_admin', tanggal_tanggapan = CURRENT_TIMESTAMP WHERE id_pengaduan = $id_pengaduan";
            
            if (mysqli_query($conn, $query)) {
                $success_message = 'Tanggapan berhasil dikirim!';
            } else {
                $error_message = 'Gagal mengirim tanggapan: ' . mysqli_error($conn);
            }
        }
    }
}

// Get all complaints with customer information
 $query = "SELECT p.*, pl.nama_pelanggan, pl.no_pelanggan 
          FROM pengaduan p 
          JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan 
          ORDER BY p.tanggal_pengaduan DESC";
 $result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PDAM Tirta Musi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
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
                    <p>Selamat datang, <?php echo $_SESSION['nama_admin']; ?> | <a href="logout.php" style="color: var(--light-text);">Logout</a></p>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="dashboard-container">
                <aside class="sidebar">
                    <h3>Menu Admin</h3>
                    <!-- Hasil perubahan -->
<ul class="sidebar-menu">
    <li><a href="dashboard_admin.php">Dashboard</a></li>
    <li><a href="data_tunggakan.php">Data Tunggakan</a></li>
    <li><a href="laporan.php">Laporan</a></li>
    <li><a href="pengaturan.php">Pengaturan</a></li>
</ul>
                </aside>
                
                <div class="main-content">
                    <div class="content-header">
                        <h2>Daftar Pengaduan Pelanggan</h2>
                    </div>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>No. Pelanggan</th>
                                    <th>Nama Pelanggan</th>
                                    <th>Tanggal</th>
                                    <th>Isi Pengaduan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $row['id_pengaduan']; ?></td>
                                            <td><?php echo $row['no_pelanggan']; ?></td>
                                            <td><?php echo $row['nama_pelanggan']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_pengaduan'])); ?></td>
                                            <td><?php echo substr($row['isi_pengaduan'], 0, 50) . '...'; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm" onclick="showDetails(<?php echo $row['id_pengaduan']; ?>)">Detail</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">Tidak ada data pengaduan</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal for complaint details -->
    <div id="complaintModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; border-radius: 8px;">
            <span class="close" onclick="closeModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <div id="modalContent">
                <!-- Content will be loaded here via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function showDetails(id) {
            // Fetch complaint details via AJAX
            fetch(`get_complaint_details.php?id=${id}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalContent').innerHTML = data;
                    document.getElementById('complaintModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat detail pengaduan');
                });
        }
        
        function closeModal() {
            document.getElementById('complaintModal').style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('complaintModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

    <script src="assets/js/script.js"></script>
</body>
</html>