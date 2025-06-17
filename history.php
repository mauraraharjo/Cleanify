<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanify</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-white-100 text-gray-800 font-sans">

<div class="container mx-auto p-4">
</div>

<!-- Navbar -->
<div>
<nav class="bg-white shadow fixed top-0 left-0 w-full z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            
            <!-- Logo + Judul + Subjudul -->
            <div class="flex flex-col items-start">
                <div class="flex items-center space-x-2">
                    <img src="uploads/logo cleanify2.png" alt="Logo" class="rounded" style="width: 32px; height: 32px;">
                    <div class="text-xl font-bold text-blue-700">Cleanify</div>
                </div>
                <h1 class="text-sm text-gray-600">Deteksi Duplikasi Data Kanker</h1>
            </div>

            <!-- Navigasi -->
            <div class="space-x-4 hidden md:block">
                <a href="index.php" class="text-gray-600 hover:text-blue-600">Dashboard</a>
                <a href="filter.php" class="text-gray-600 hover:text-blue-600">Filter</a>
                <a href="cekduplikasi.php" class="text-gray-600 hover:text-blue-600">Cek Duplikasi</a>
                <a href="history.php" class="text-blue-600 font-semibold">Riwayat</a>
                <a href="?logout=true" class="text-red-600 hover:text-red-800 font-semibold">Logout</a>
            </div>

        </div>
    </div>
</nav>
</div>

<div>
    <div class="max-w-5xl mx-auto bg-blue-50 p-6 rounded-lg shadow-md mt-12">
        <h2 class="text-2xl font-bold">Riwayat Data</h2>
        <hr class="my-4">

        <!-- Tampilkan History -->
        <?php if (!empty($_SESSION['history'])): ?>
            <div class="mt-4">
                <table class="min-w-full border-collapse border border-blue-200 mt-2">
                    <thead>
                        <tr>
                            <th class="border border-blue-200 p-2 bg-blue-200">Waktu</th>
                            <th class="border border-blue-200 p-2 bg-blue-200">Jumlah Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['history'] as $log): ?>
                            <tr>
                                <td class="border border-blue-200 p-2"><?= isset($log['timestamp']) ? $log['timestamp'] : '-' ?></td>
                                <td class="border border-blue-200 p-2"><?= isset($log['total']) ? $log['total'] : (isset($log['data']) ? $log['data'] : '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="mt-4">Belum ada riwayat.</p>
        <?php endif; ?>

        <footer style="text-align: center;">
            <p>Copyright Kelompok 1B - © 2025</p>
        </footer>

    </div>
</div>

</body>
</html>