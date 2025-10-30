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

// Handle customer status toggle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'toggle_status') {
    $id_pelanggan = mysqli_real_escape_string($conn, $_POST['id_pelanggan']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    $query = "UPDATE pelanggan SET status = '$new_status' WHERE id_pelanggan = $id_pelanggan";
    if (mysqli_query($conn, $query)) {
        $success_message = "Status pelanggan berhasil diperbarui.";
    } else {
        $error_message = "Gagal memperbarui status: " . mysqli_error($conn);
    }
}

// Handle admin password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    
    if ($new_password === $confirm_password && !empty($new_password)) {
        $hashed_password = md5($new_password);
        $id_admin = $_SESSION['id_admin'];
        $query = "UPDATE admin SET password = '$hashed_password' WHERE id_admin = $id_admin";
        if (mysqli_query($conn, $query)) {
            $success_message = "Password berhasil diubah.";
        } else {
            $error_message = "Gagal mengubah password: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Password baru dan konfirmasi tidak cocok atau kosong.";
    }
}

// Get all customers
 $customers_query = "SELECT * FROM pelanggan ORDER BY nama_pelanggan";
 $customers_result = mysqli_query($conn, $customers_query);

// Get current admin data
 $admin_query = "SELECT username, nama_admin FROM admin WHERE id_admin = " . $_SESSION['id_admin'];
 $admin_result = mysqli_query($conn, $admin_query);
 $admin_data = mysqli_fetch_assoc($admin_result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - PDAM Tirta Musi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .tab-container { display: flex; margin-bottom: 20px; border-bottom: 2px solid var(--light-color); }
        .tab-button { background: none; border: none; padding: 10px 20px; font-size: 16px; cursor: pointer; color: #777; transition: all 0.3s; border-bottom: 3px solid transparent; }
        .tab-button.active { color: var(--primary-color); font-weight: 600; border-bottom-color: var(--primary-color); }
        .form-panel { display: none; }
        .form-panel.active { display: block; animation: fadeIn 0.5s ease-out; }
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
                        <li><a href="data_tunggakan.php">Data Tunggakan</a></li>
                        <li><a href="laporan.php">Laporan</a></li>
                        <li><a href="pengaturan.php" class="active">Pengaturan</a></li>
                    </ul>
                </aside>
                
                <div class="main-content">
                    <div class="content-header">
                        <h2>Pengaturan</h2>
                    </div>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <div class="tab-container">
                        <button class="tab-button active" onclick="showTab('customers')">Kelola Pelanggan</button>
                        <button class="tab-button" onclick="showTab('profile')">Profil Admin</button>
                    </div>

                    <!-- Tab Kelola Pelanggan -->
                    <div id="customers-tab" class="form-panel active">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No. Pelanggan</th>
                                        <th>Nama</th>
                                        <th>Alamat</th>
                                        <th>No. HP</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($customers_result) > 0): ?>
                                        <?php while ($customer = mysqli_fetch_assoc($customers_result)): ?>
                                            <tr>
                                                <td><?php echo $customer['no_pelanggan']; ?></td>
                                                <td><?php echo $customer['nama_pelanggan']; ?></td>
                                                <td><?php echo $customer['alamat']; ?></td>
                                                <td><?php echo $customer['no_hp']; ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo $customer['status'] == 'aktif' ? 'status-selesai' : 'status-menunggu'; ?>">
                                                        <?php echo ucfirst($customer['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <form action="pengaturan.php" method="post" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status?');">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="id_pelanggan" value="<?php echo $customer['id_pelanggan']; ?>">
                                                        <input type="hidden" name="new_status" value="<?php echo $customer['status'] == 'aktif' ? 'nonaktif' : 'aktif'; ?>">
                                                        <button type="submit" class="btn btn-sm <?php echo $customer['status'] == 'aktif' ? 'btn-danger' : 'btn-success'; ?>">
                                                            <?php echo $customer['status'] == 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" style="text-align: center;">Tidak ada data pelanggan.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Profil Admin -->
                    <div id="profile-tab" class="form-panel">
                        <div class="form-container">
                            <h3>Ubah Password</h3>
                            <form action="pengaturan.php" method="post">
                                <input type="hidden" name="action" value="change_password">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_data['username']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Nama</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_data['nama_admin']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="new_password">Password Baru</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Konfirmasi Password Baru</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn">Simpan Perubahan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function showTab(tabName) {
            document.getElementById('customers-tab').classList.remove('active');
            document.getElementById('profile-tab').classList.remove('active');
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));

            if (tabName === 'customers') {
                document.getElementById('customers-tab').classList.add('active');
                document.querySelectorAll('.tab-button')[0].classList.add('active');
            } else {
                document.getElementById('profile-tab').classList.add('active');
                document.querySelectorAll('.tab-button')[1].classList.add('active');
            }
        }
    </script>
    <script src="assets/js/script.js"></script>
</body>
</html>