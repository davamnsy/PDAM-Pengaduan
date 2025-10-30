<?php
include 'config.php';

header('Content-Type: application/json');

 $response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_status') {
            $id_pengaduan = mysqli_real_escape_string($conn, $_POST['id_pengaduan']);
            $status = mysqli_real_escape_string($conn, $_POST['status']);
            
            $query = "UPDATE pengaduan SET status = '$status' WHERE id_pengaduan = $id_pengaduan";
            
            if (mysqli_query($conn, $query)) {
                $response['success'] = true;
                $response['message'] = 'Status berhasil diperbarui';
            } else {
                $response['message'] = 'Gagal memperbarui status: ' . mysqli_error($conn);
            }
        } elseif ($_POST['action'] == 'submit_response') {
            $id_pengaduan = mysqli_real_escape_string($conn, $_POST['id_pengaduan']);
            $tanggapan_admin = mysqli_real_escape_string($conn, $_POST['tanggapan_admin']);
            
            $query = "UPDATE pengaduan SET tanggapan_admin = '$tanggapan_admin', tanggal_tanggapan = CURRENT_TIMESTAMP WHERE id_pengaduan = $id_pengaduan";
            
            if (mysqli_query($conn, $query)) {
                $response['success'] = true;
                $response['message'] = 'Tanggapan berhasil dikirim';
            } else {
                $response['message'] = 'Gagal mengirim tanggapan: ' . mysqli_error($conn);
            }
        }
    }
}

echo json_encode($response);
?>