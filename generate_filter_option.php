<?php
session_start();
header('Content-Type: application/json');

// Cek apakah ada file yang di-upload
if (!isset($_SESSION['uploaded_files']) || empty($_SESSION['uploaded_files'])) {
    echo json_encode([]);
    exit;
}

$allData = [];

// Gabungkan semua data dari semua file
foreach ($_SESSION['uploaded_files'] as $file) {
    $rows = $file['data'];
    if (count($rows) < 2) continue; // Skip kalau tidak ada data

    $headers = $rows[0];

    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        // Validasi baris: jumlah kolom harus sesuai
        if (count($row) !== count($headers)) continue;

        $assocRow = array_combine($headers, $row);
        $allData[] = $assocRow;
    }
}

// Ambil nilai unik untuk tiap kolom
$options = [];
foreach ($allData as $row) {
    foreach ($row as $key => $value) {
        $value = trim($value);
        if (!isset($options[$key])) {
            $options[$key] = [];
        }

        if (!in_array($value, $options[$key])) {
            $options[$key][] = $value;
        }
    }
}

// Kirim hasil sebagai JSON
echo json_encode($options);
