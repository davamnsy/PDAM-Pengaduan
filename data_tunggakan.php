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

// Handle Add Arrears
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    // ... (kode untuk menambah tunggakan tetap sama) ...
    $id_pelanggan = mysqli_real_escape_string($conn, $_POST['id_pelanggan']);
    $periode_bulan = mysqli_real_escape_string($conn, $_POST['periode_bulan']);
    $periode_tahun = mysqli_real_escape_string($conn, $_POST['periode_tahun']);
    $jumlah_tagihan = mysqli_real_escape_string($conn, $_POST['jumlah_tagihan']);
    $jatuh_tempo = mysqli_real_escape_string($conn, $_POST['jatuh_tempo']);
    $query = "INSERT INTO tunggakan (id_pelanggan, periode_bulan, periode_tahun, jumlah_tagihan, jatuh_tempo) 
              VALUES ('$id_pelanggan', '$periode_bulan', '$periode_tahun', '$jumlah_tagihan', '$jatuh_tempo')";
    if (mysqli_query($conn, $query)) {
        $success_message = "Data tunggakan berhasil ditambahkan.";
    } else {
        $error_message = "Gagal menambahkan data: " . mysqli_error($conn);
    }
}

// Handle Confirm Payment
if (isset($_GET['action']) && $_GET['action'] == 'confirm_payment' && isset($_GET['id'])) {
    $id_tunggakan = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "UPDATE tunggakan SET status = 'sudah_bayar', tanggal_bayar = CURDATE() WHERE id_tunggakan = $id_tunggakan";
    if (mysqli_query($conn, $query)) {
        header("Location: data_tunggakan.php?success=Pembayaran berhasil dikonfirmasi.");
        exit;
    }
}

// Handle Reject Payment
if (isset($_GET['action']) && $_GET['action'] == 'reject_payment' && isset($_GET['id'])) {
    $id_tunggakan = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Hapus file bukti pembayaran
    $query = "SELECT bukti_pembayaran FROM tunggakan WHERE id_tunggakan = $id_tunggakan";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $file_path = 'uploads/payments/' . $row['bukti_pembayaran'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Update database
    $query = "UPDATE tunggakan SET status = 'belum_bayar', bukti_pembayaran = NULL WHERE id_tunggakan = $id_tunggakan";
    if (mysqli_query($conn, $query)) {
        header("Location: data_tunggakan.php?success=Pembayaran ditolak dan status dikembalikan.");
        exit;
    }
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    // ... (kode untuk hapus tetap sama, tapi tambahkan logika hapus file bukti) ...
    $id_tunggakan = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT bukti_pembayaran FROM tunggakan WHERE id_tunggakan = $id_tunggakan";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result) && !empty($row['bukti_pembayaran'])) {
        $file_path = 'uploads/payments/' . $row['bukti_pembayaran'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    $query = "DELETE FROM tunggakan WHERE id_tunggakan = $id_tunggakan";
    if (mysqli_query($conn, $query)) {
        header("Location: data_tunggakan.php?success=Data berhasil dihapus.");
        exit;
    }
}

// Fetch all arrears with customer info
 $query = "SELECT t.*, p.nama_pelanggan, p.no_pelanggan 
          FROM tunggakan t 
          JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan 
          ORDER BY t.status ASC, t.periode_tahun DESC, t.periode_bulan DESC";
 $result = mysqli_query($conn, $query);

// Fetch all customers for the dropdown
 $customers_query = "SELECT id_pelanggan, no_pelanggan, nama_pelanggan FROM pelanggan WHERE status = 'aktif' ORDER BY nama_pelanggan";
 $customers_result = mysqli_query($conn, $customers_query);

// Display messages
if (isset($_GET['success'])) $success_message = $_GET['success'];
if (isset($_GET['error'])) $error_message = $_GET['error'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Tunggakan - PDAM Tirta Musi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .status-badge.status-menunggu-konfirmasi {
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
                    <ul class="sidebar-menu">
                        <li><a href="dashboard_admin.php">Dashboard</a></li>
                        <li><a href="data_tunggakan.php" class="active">Data Tunggakan</a></li>
                        <li><a href="laporan.php">Laporan</a></li>
                        <li><a href="pengaturan.php">Pengaturan</a></li>
                    </ul>
                </aside>
                
                <div class="main-content">
                    <div class="content-header">
                        <h2>Data Tunggakan Pelanggan</h2>
                        <button class="btn" onclick="document.getElementById('addModal').style.display='block'">+ Tambah Tunggakan</button>
                    </div>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No. Pelanggan</th>
                                    <th>Nama Pelanggan</th>
                                    <th>Periode</th>
                                    <th>Jumlah Tagihan</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                    <th>Bukti Pembayaran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $row['no_pelanggan']; ?></td>
                                            <td><?php echo $row['nama_pelanggan']; ?></td>
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
                                                    <a href="?action=mark_paid&id=<?php echo $row['id_tunggakan']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Tandai sebagai lunas?')">Lunas</a>
                                                <?php elseif ($row['status'] == 'menunggu_konfirmasi'): ?>
                                                    <a href="?action=confirm_payment&id=<?php echo $row['id_tunggakan']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Konfirmasi pembayaran ini?')">Konfirmasi</a>
                                                    <a href="?action=reject_payment&id=<?php echo $row['id_tunggakan']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tolak pembayaran ini?')">Tolak</a>
                                                <?php endif; ?>
                                                <a href="?action=delete&id=<?php echo $row['id_tunggakan']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center;">Belum ada data tunggakan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal for Adding Arrears (tetap sama) -->
    <div id="addModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
            <h3>Tambah Data Tunggakan</h3>
            <form action="data_tunggakan.php" method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="id_pelanggan">Pelanggan</label>
                    <select name="id_pelanggan" id="id_pelanggan" class="form-control" required>
                        <option value="">-- Pilih Pelanggan --</option>
                        <?php 
                        mysqli_data_seek($customers_result, 0); // Reset pointer
                        while($cust = mysqli_fetch_assoc($customers_result)): ?>
                            <option value="<?php echo $cust['id_pelanggan']; ?>"><?php echo $cust['no_pelanggan'] . ' - ' . $cust['nama_pelanggan']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="periode_bulan">Bulan</label>
                            <select name="periode_bulan" id="periode_bulan" class="form-control" required>
                                <?php for($m=1; $m<=12; $m++): ?>
                                    <option value="<?php echo $m; ?>"><?php echo date("F", mktime(0, 0, 0, $m, 1)); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="periode_tahun">Tahun</label>
                            <input type="number" name="periode_tahun" id="periode_tahun" class="form-control" value="<?php echo date('Y'); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="jumlah_tagihan">Jumlah Tagihan</label>
                    <input type="number" name="jumlah_tagihan" id="jumlah_tagihan" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="jatuh_tempo">Jatuh Tempo</label>
                    <input type="date" name="jatuh_tempo" id="jatuh_tempo" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-block">Simpan</button>
            </form>
        </div>
    </div>

    <script>
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('addModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    <script src="assets/js/script.js"></script>
</body>
</html>