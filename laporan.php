<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login_admin.php');
    exit;
}

// Initialize variables
 $where_clauses = [];
 $params = [];
 $query = "SELECT p.*, pl.nama_pelanggan, pl.no_pelanggan 
          FROM pengaduan p 
          JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan";

// Handle filtering
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['filter'])) {
    if (!empty($_POST['start_date'])) {
        $where_clauses[] = "p.tanggal_pengaduan >= ?";
        $params[] = $_POST['start_date'];
    }
    if (!empty($_POST['end_date'])) {
        $where_clauses[] = "p.tanggal_pengaduan <= ?";
        $params[] = $_POST['end_date'];
    }
    if (!empty($_POST['status']) && $_POST['status'] !== 'all') {
        $where_clauses[] = "p.status = ?";
        $params[] = $_POST['status'];
    }
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}
 $query .= " ORDER BY p.tanggal_pengaduan DESC";

// Prepare and execute the statement
 $stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);

// Handle CSV Export
if (isset($_POST['export']) && $_POST['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laporan_pengaduan_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header CSV
    fputcsv($output, ['ID Pengaduan', 'No. Pelanggan', 'Nama Pelanggan', 'Tanggal', 'Isi Pengaduan', 'Status', 'Tanggapan Admin']);
    
    // Data CSV
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id_pengaduan'],
            $row['no_pelanggan'],
            $row['nama_pelanggan'],
            $row['tanggal_pengaduan'],
            $row['isi_pengaduan'],
            $row['status'],
            $row['tanggapan_admin']
        ]);
    }
    
    fclose($output);
    exit();
}

// Re-execute query for display if it was used for export
if (isset($_POST['export'])) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - PDAM Tirta Musi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="assets/img/logo.png" alt="PDAM Tirta Musi Logo">
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
                        <li><a href="data_tunggakan.php">Data Tunggakan</a></li>
                        <li><a href="laporan.php" class="active">Laporan</a></li>
                        <li><a href="pengaturan.php">Pengaturan</a></li>
                    </ul>
                </aside>
                
                <div class="main-content">
                    <div class="content-header">
                        <h2>Laporan Pengaduan</h2>
                    </div>
                    
                    <div class="form-container">
                        <form action="laporan.php" method="post">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="start_date">Tanggal Mulai</label>
                                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="end_date">Tanggal Selesai</label>
                                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select id="status" name="status" class="form-control">
                                            <option value="all" <?php echo (isset($_POST['status']) && $_POST['status'] == 'all') ? 'selected' : ''; ?>>Semua</option>
                                            <option value="menunggu" <?php echo (isset($_POST['status']) && $_POST['status'] == 'menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                            <option value="diproses" <?php echo (isset($_POST['status']) && $_POST['status'] == 'diproses') ? 'selected' : ''; ?>>Diproses</option>
                                            <option value="selesai" <?php echo (isset($_POST['status']) && $_POST['status'] == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="filter" class="btn">Terapkan Filter</button>
                            <button type="submit" name="export" value="csv" class="btn btn-success" style="margin-left: 10px;">Export ke CSV</button>
                        </form>
                    </div>

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
                                    <th>Aksi</th> <!-- KOLOM AKSI DITAMBAHKAN -->
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
                                                <!-- TOMBOL TINDAK LANJUT DITAMBAHKAN -->
                                                <button class="btn btn-sm" onclick="showComplaintDetails(<?php echo $row['id_pengaduan']; ?>)">Tindak Lanjut</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">Tidak ada data pengaduan yang ditemukan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal untuk Tindak Lanjut -->
    <div id="complaintModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; border-radius: 8px;">
            <span class="close" onclick="closeModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <div id="modalContent">
                <!-- Konten akan dimuat di sini oleh JavaScript -->
                <div class="spinner"></div>
            </div>
        </div>
    </div>

    <script>
        function showComplaintDetails(id) {
            // Tampilkan loading spinner
            document.getElementById('modalContent').innerHTML = '<div class="spinner"></div>';
            document.getElementById('complaintModal').style.display = 'block';

            // Fetch detail pengaduan
            fetch(`get_complaint_details.php?id=${id}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalContent').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('modalContent').innerHTML = '<div class="alert alert-danger">Terjadi kesalahan saat memuat detail pengaduan.</div>';
                });
        }
        
        function closeModal() {
            document.getElementById('complaintModal').style.display = 'none';
        }
        function submitModalForm(event, formId) {
    event.preventDefault(); // Mencegah form submit secara default

    const form = document.getElementById(formId);
    const formData = new FormData(form);

    // Tampilkan loading pada tombol
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Memproses...';

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Tampilkan notifikasi sukses
            showAlert(data.message, 'success');
            // Tutup modal
            closeModal();
            // Refresh halaman untuk memperbarui data di tabel utama
            setTimeout(() => {
                location.reload();
            }, 1500); // Tunggu 1.5 detik agar notifikasi terlihat
        } else {
            // Tampilkan notifikasi error
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
    })
    .finally(() => {
        // Kembalikan tombol ke keadaan semula
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    });

    return false;
}
        // Tutup modal saat mengklik di luar area modal
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