<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$database = "cleanify";

$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully";

// Cek session
if (!isset($_SESSION['username'])) {
    die("Session username belum diset.");
}

$current_user = $_SESSION['username'];

// Ganti nama tabel jadi username_cleanify
$query = mysqli_query($conn, "SELECT user_id FROM username_cleanify WHERE username = '$current_user'");
$user = mysqli_fetch_assoc($query);

if (!$user) {
    die("Username '$current_user' tidak ditemukan di tabel username_cleanify.");
}

$user_id = $user['user_id'];

// Simpan data upload
if (isset($_FILES['file'])) {
    $filename = $_FILES['file']['name'];
    $now = date("Y-m-d H:i:s");
    mysqli_query($conn, "INSERT INTO data_upload (user_id, username, file_name, uploaded_at) 
                         VALUES ($user_id, '$current_user', '$filename', '$now')");
}
?>
