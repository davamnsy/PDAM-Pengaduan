<?php
// config.php
 $host = "localhost";
 $user = "root";
 $pass = "";
 $dbname = "pdam_tirta_musi";

 $conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>