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

// --- Cek Duplikasi (AJAX) ---
if (isset($_GET['cek_duplikasi'])) {
    header('Content-Type: application/json');
    $seen = $dups = [];
    if (!empty($_SESSION['uploaded_files'])) {
        foreach ($_SESSION['uploaded_files'] as $f) {
            $rows = $f['data'];
            if (count($rows) < 2) continue;
            $hdr = $rows[0];
            for ($i = 1; $i < count($rows); $i++) {
                if (count($rows[$i]) !== count($hdr)) continue;
                $assoc = array_combine($hdr, $rows[$i]);
                $key = json_encode($assoc);
                if (isset($seen[$key])) $dups[] = $assoc;
                else $seen[$key] = 1;
            }
        }
    }
    echo json_encode($dups);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanify</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .duplicate-row {
            background-color: #ffe5e5 !important; /* merah muda lembut */
            color: #800000 !important;           /* teks merah tua */
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
    </style>
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
                <a href="cekduplikasi.php" class="text-blue-600 font-semibold">Cek Duplikasi</a>
                <a href="history.php" class="text-gray-600 hover:text-blue-600">Riwayat</a>
                <a href="?logout=true" class="text-red-600 hover:text-red-800 font-semibold">Logout</a>
            </div>

        </div>
    </div>
</nav>
</div>

<div>
    <div class="max-w-5xl mx-auto bg-blue-50 p-6 rounded-lg shadow-md mt-12">
        <h2 class="text-2xl font-bold">Cek Duplikasi Data</h2>
        <hr class="my-4">

        <!-- Tools -->
        <div class="flex space-x-2 mb-4">
            <button onclick="cekDuplikasi()" class="bg-blue-600 text-white p-2 rounded">Cek Duplikasi</button>
        </div>

        <!-- Hasil -->
        <div id="searchResults" class="mt-4"></div>

        <footer style="text-align: center;">
            <p>Copyright Kelompok 1B - © 2025</p>
        </footer>

    </div>
</div>

<script>
function cekDuplikasi() {
    fetch('?cek_duplikasi=true')
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                alert("Tidak ditemukan duplikasi.");
            } else {
                renderTable(data, 'Data Duplikat', true);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Terjadi kesalahan saat memeriksa duplikasi.");
        });
}

function renderTable(data, title, highlight = false) {
    const resultDiv = document.getElementById('searchResults');
    if (data.length === 0) {
        resultDiv.innerHTML = '<p>Tidak ada hasil.</p>';
        return;
    }

    let html = `<h3>${title}</h3><table class="min-w-full border-collapse border border-gray-200"><thead><tr>`;
    const headers = Object.keys(data[0]);
    headers.forEach(h => html += `<th class="border border-gray-200 p-2">${h}</th>`);
    html += '</tr></thead><tbody>';

    data.forEach(row => {
        html += `<tr${highlight ? ' class="duplicate-row"' : ''}>`;
        headers.forEach(h => html += `<td class="border border-gray-200 p-2">${row[h]}</td>`);
        html += '</tr>';
    });

    html += '</tbody></table>';
    resultDiv.innerHTML = html;
}
</script>

</body>
</html>