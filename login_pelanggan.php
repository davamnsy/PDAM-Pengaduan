<?php
session_start();
include 'config.php';

// Check if customer is already logged in
if (isset($_SESSION['pelanggan_logged_in']) && $_SESSION['pelanggan_logged_in'] === true) {
    header('Location: form_pengaduan.php');
    exit;
}

 $login_error = '';
 $register_success = '';
 $register_error = '';
 $auto_fill_no = '';

// --- Handle Login ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $no_pelanggan = mysqli_real_escape_string($conn, $_POST['no_pelanggan']);
    
    $query = "SELECT * FROM pelanggan WHERE no_pelanggan = '$no_pelanggan' AND status = 'aktif'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $pelanggan = mysqli_fetch_assoc($result);
        
        $_SESSION['pelanggan_logged_in'] = true;
        $_SESSION['id_pelanggan'] = $pelanggan['id_pelanggan'];
        $_SESSION['no_pelanggan'] = $pelanggan['no_pelanggan'];
        $_SESSION['nama_pelanggan'] = $pelanggan['nama_pelanggan'];
        
        header('Location: form_pengaduan.php');
        exit;
    } else {
        $login_error = 'Nomor pelanggan tidak ditemukan atau tidak aktif!';
    }
}

// --- Handle Registration ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Generate new customer number
    $query = "SELECT no_pelanggan FROM pelanggan ORDER BY id_pelanggan DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    $new_no_pelanggan = 'PLG001';
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_no = $row['no_pelanggan'];
        $num = (int) substr($last_no, 3) + 1;
        $new_no_pelanggan = 'PLG' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    // Insert new customer
    $query = "INSERT INTO pelanggan (no_pelanggan, nama_pelanggan, alamat, no_hp, email) 
              VALUES ('$new_no_pelanggan', '$nama_pelanggan', '$alamat', '$no_hp', '$email')";
    
    if (mysqli_query($conn, $query)) {
        // --- PERUBAHAN PENTING DI SINI ---
        // Simpan nomor pelanggan di session untuk diisi otomatis
        $_SESSION['new_customer_no'] = $new_no_pelanggan;
        // Redirect ke halaman yang sama dengan parameter sukses
        header('Location: login_pelanggan.php?status=registered');
        exit;
    } else {
        $register_error = "Gagal mendaftar. Pastikan email belum terdaftar. Error: " . mysqli_error($conn);
    }
}

// --- Logika untuk menangani redirect dari pendaftaran yang sukses ---
if (isset($_GET['status']) && $_GET['status'] == 'registered' && isset($_SESSION['new_customer_no'])) {
    $register_success = "Pendaftaran berhasil! Silakan login dengan nomor pelanggan Anda yang sudah terisi di bawah.";
    $auto_fill_no = $_SESSION['new_customer_no'];
    // Hapus session agar tidak muncul lagi saat refresh
    unset($_SESSION['new_customer_no']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pelanggan - PDAM Tirta Musi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <style>
        /* Tambahan CSS untuk tab */
        .tab-container {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--light-color);
        }
        .tab-button {
            background: none;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            color: #777;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        .tab-button.active {
            color: var(--primary-color);
            font-weight: 600;
            border-bottom-color: var(--primary-color);
        }
        .form-panel {
            display: none;
        }
        .form-panel.active {
            display: block;
            animation: fadeIn 0.5s ease-out;
        }
    </style>
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
                    <p>Portal Pelanggan</p>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="login-container">
                <div class="login-card fade-in">
                    <h2>Portal Pelanggan</h2>
                    
                    <!-- Tab Buttons -->
                    <div class="tab-container">
                        <button class="tab-button" id="loginTabButton" onclick="showForm('login')">Login</button>
                        <button class="tab-button" id="registerTabButton" onclick="showForm('register')">Daftar</button>
                    </div>

                    <!-- Login Form Panel -->
                    <div id="loginPanel" class="form-panel">
                        <?php if (!empty($login_error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $login_error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="login_pelanggan.php" method="post">
                            <input type="hidden" name="action" value="login">
                            <div class="form-group">
                                <label for="no_pelanggan">Nomor Pelanggan</label>
                                <!-- INPUT SUDAH DITAMBAHKAN VALUE UNTUK ISI OTOMATIS -->
                                <input type="text" id="no_pelanggan" name="no_pelanggan" class="form-control" placeholder="Contoh: PLG001" value="<?php echo htmlspecialchars($auto_fill_no); ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-block">Login</button>
                        </form>
                    </div>

                    <!-- Registration Form Panel -->
                    <div id="registerPanel" class="form-panel">
                        <?php if (!empty($register_success)): ?>
                            <div class="alert alert-success">
                                <?php echo $register_success; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($register_error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $register_error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="login_pelanggan.php" method="post">
                            <input type="hidden" name="action" value="register">
                            <div class="form-group">
                                <label for="reg_nama">Nama Lengkap</label>
                                <input type="text" id="reg_nama" name="nama_pelanggan" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="reg_alamat">Alamat Lengkap</label>
                                <textarea id="reg_alamat" name="alamat" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="reg_hp">Nomor HP</label>
                                <input type="tel" id="reg_hp" name="no_hp" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="reg_email">Email</label>
                                <input type="email" id="reg_email" name="email" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-block">Daftar Sekarang</button>
                        </form>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="index.html" style="color: var(--primary-color);">Kembali ke Halaman Utama</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer style="background: var(--dark-color); color: var(--light-text); padding: 20px 0; margin-top: 40px; position: fixed; bottom: 0; width: 100%;">
        <div class="container">
            <div style="text-align: center;">
                <p>&copy; 2023 PDAM Tirta Musi - Seberang Ulu 2. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>

    <script>
        // Fungsi untuk mengganti tampilan tab
        function showForm(formName) {
            // Sembunyikan semua panel
            document.getElementById('loginPanel').classList.remove('active');
            document.getElementById('registerPanel').classList.remove('active');
            
            // Hapus kelas aktif dari semua tombol
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(btn => btn.classList.remove('active'));

            // Tampilkan panel yang dipilih dan beri kelas aktif pada tombolnya
            if (formName === 'login') {
                document.getElementById('loginPanel').classList.add('active');
                document.getElementById('loginTabButton').classList.add('active');
            } else {
                document.getElementById('registerPanel').classList.add('active');
                document.getElementById('registerTabButton').classList.add('active');
            }
        }

        // --- JAVASCRIPT BARU UNTUK AUTO-SWITCH TAB ---
        // Periksa URL parameter saat halaman dimuat
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status') === 'registered') {
                // Jika statusnya 'registered', tampilkan tab login
                showForm('login');
            } else {
                // Jika tidak, tampilkan tab login secara default
                showForm('login');
            }
        }
    </script>

    <script src="assets/js/script.js"></script>
</body>
</html>