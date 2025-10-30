<?php
session_start();
include 'config.php';

// Check if customer is logged in
if (!isset($_SESSION['pelanggan_logged_in']) || $_SESSION['pelanggan_logged_in'] !== true) {
    header('Location: login_pelanggan.php');
    exit;
}

 $id_pelanggan = $_SESSION['id_pelanggan'];

// Get customer's complaints
 $query = "SELECT * FROM pengaduan WHERE id_pelanggan = $id_pelanggan ORDER BY tanggal_pengaduan DESC";
 $result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pengaduan - PDAM Tirta Musi</title>
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
                        <li><a href="riwayat_pengaduan.php" class="active">Riwayat Pengaduan</a></li>
                        <li><a href="info_tunggakan.php">Info Tunggakan</a></li>
                    </ul>
                </aside>
                
                <div class="main-content">
                    <div class="content-header">
                        <h2>Riwayat Pengaduan</h2>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Isi Pengaduan</th>
                                    <th>Status</th>
                                    <th>Tanggapan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $row['id_pengaduan']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_pengaduan'])); ?></td>
                                            <td><?php echo $row['isi_pengaduan']; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['tanggapan_admin'])): ?>
                                                    <?php echo $row['tanggapan_admin']; ?>
                                                    <br><small><?php echo date('d/m/Y H:i', strtotime($row['tanggal_tanggapan'])); ?></small>
                                                <?php else: ?>
                                                    <em>Belum ada tanggapan</em>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">Belum ada pengaduan</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>