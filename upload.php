<?php 
session_start();

if (!isset($_SESSION['uploaded_files'])) {
    $_SESSION['uploaded_files'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    foreach ($_FILES['file']['tmp_name'] as $key => $tmp) {
        $name = $_FILES['file']['name'][$key];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            echo "File $name tidak didukung! Hanya file CSV yang diperbolehkan.";
            continue;
        }

        if (!is_dir('uploads')) mkdir('uploads');

        $target = 'uploads/' . time() . '-' . basename($name);
        if (move_uploaded_file($tmp, $target)) {
            $data = [];
            if (($handle = fopen($target, 'r')) !== false) {
                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    $data[] = $row;
                }
                fclose($handle);
            }

            // Simpan ke session
            $_SESSION['uploaded_files'][] = [
                'filename' => $name,
                'path' => $target,
                'data' => $data
            ];
        } else {
            echo "Upload file $name gagal.";
        }
    }

    header('Location: index.php');
    exit();
} else {
    echo "Tidak ada file yang dikirim atau terjadi error.";
}


