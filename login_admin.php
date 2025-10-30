<?php
session_start();
include 'config.php';

// Check if admin is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard_admin.php');
    exit;
}

 $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Query to check admin credentials
    $query = "SELECT * FROM admin WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);
        
        // Verify password (using MD5 as specified)
        if (md5($password) == $admin['password']) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['id_admin'] = $admin['id_admin'];
            $_SESSION['nama_admin'] = $admin['nama_admin'];
            
            header('Location: dashboard_admin.php');
            exit;
        } else {
            $error = 'Password salah!';
        }
    } else {
        $error = 'Username tidak ditemukan!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - PDAM Tirta Musi</title>
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
                    <p>Portal Admin</p>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="login-container">
                <div class="login-card fade-in">
                    <h2>Login Admin</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="login_admin.php" method="post">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-block">Login</button>
                    </form>
                    
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

    <script src="assets/js/script.js"></script>
</body>
</html>