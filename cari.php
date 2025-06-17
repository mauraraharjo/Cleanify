<?php
session_start();
header('Content-Type: application/json');

$keyword = $_GET['q'] ?? '';
$results = [];

if (!empty($_SESSION['uploaded_files'])) {
    foreach ($_SESSION['uploaded_files'] as $file) {
        $data = $file['data'];

        if (empty($data) || count($data) < 2) continue;

        $headers = $data[0];
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];

            foreach ($row as $cell) {
                if (stripos($cell, $keyword) !== false) {
                    $assoc = array_combine($headers, $row);
                    $results[] = $assoc;
                    break;
                }
            }
        }
    }
}

echo json_encode($results);
