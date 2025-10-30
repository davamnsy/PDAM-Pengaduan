<?php
session_start();
include 'config.php';

// Check if customer is logged in
if (!isset($_SESSION['pelanggan_logged_in']) || $_SESSION['pelanggan_logged_in'] !== true) {
    header('Location: login_pelanggan.php');
    exit;
}

 $success_message = '';
 $error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $isi_pengaduan = mysqli_real_escape_string($conn, $_POST['isi_pengaduan']);
    $id_pelanggan = $_SESSION['id_pelanggan'];
    
    // Handle file upload
    $foto_bukti = '';
    if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] == 0) {
        $target_dir = "uploads/";
        $file_name = time() . '_' . basename($_FILES["foto_bukti"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["foto_bukti"]["tmp_name"]);
        if ($check !== false) {
            // Check file size (5MB max)
            if ($_FILES["foto_bukti"]["size"] <= 5000000) {
                // Allow certain file formats
                if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
                    if (move_uploaded_file($_FILES["foto_bukti"]["tmp_name"], $target_file)) {
                        $foto_bukti = $file_name;
                    } else {
                        $error_message = "Maaf, terjadi kesalahan saat mengunggah file.";
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
    }
    
    // Insert complaint into database
    if (empty($error_message)) {
        $query = "INSERT INTO pengaduan (id_pelanggan, isi_pengaduan, foto_bukti) 
                  VALUES ('$id_pelanggan', '$isi_pengaduan', '$foto_bukti')";
        
        if (mysqli_query($conn, $query)) {
            $success_message = 'Pengaduan berhasil dikirim! Kami akan segera menindaklanjuti.';
        } else {
            $error_message = 'Gagal mengirim pengaduan: ' . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengaduan - PDAM Tirta Musi</title>
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
                        <li><a href="form_pengaduan.php" class="active">Buat Pengaduan</a></li>
                        <li><a href="info_tunggakan.php">Info Tunggakan</a></li>
                        <li><a href="riwayat_pengaduan.php">Riwayat Pengaduan</a></li>
                    </ul>
                </aside>
                
                <div class="main-content">
                    <div class="content-header">
                        <h2>Buat Pengaduan Baru</h2>
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
                    
                    <div class="form-container">
                        <form action="form_pengaduan.php" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="no_pelanggan">Nomor Pelanggan</label>
                                <input type="text" id="no_pelanggan" class="form-control" value="<?php echo $_SESSION['no_pelanggan']; ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="nama_pelanggan">Nama Pelanggan</label>
                                <input type="text" id="nama_pelanggan" class="form-control" value="<?php echo $_SESSION['nama_pelanggan']; ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="isi_pengaduan">Isi Pengaduan</label>
                                <textarea id="isi_pengaduan" name="isi_pengaduan" class="form-control" rows="5" required placeholder="Jelaskan keluhan atau pengaduan Anda..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="foto_bukti">Foto Bukti (Opsional)</label>
                                <div class="file-upload">
                                    <input type="file" id="foto_bukti" name="foto_bukti" accept="image/*">
                                    <label for="foto_bukti" class="file-upload-label">Pilih file...</label>
                                </div>
                                <small style="color: #666; display: block; margin-top: 5px;">Format: JPG, JPEG, PNG, GIF. Maksimal: 5MB</small>
                            </div>
                            
                            <button type="submit" class="btn">Kirim Pengaduan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>