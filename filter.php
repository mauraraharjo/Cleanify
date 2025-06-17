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

// Tambah ini: untuk log riwayat multilayer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_history'])) {
    $jumlahDuplikat = intval($_POST['jumlah_duplikat'] ?? 0);
    $timestamp = date('Y-m-d H:i:s');

    $_SESSION['history'] = $_SESSION['history'] ?? [];
    $_SESSION['history'][] = [
        'timestamp' => $timestamp,
        'total' => $jumlahDuplikat
    ];

    echo json_encode(['status' => 'ok']);
    exit;
}

// Sudah ada: generate_filter_option
if (isset($_GET['generate_filter_option'])) {
    header('Content-Type: application/json');
    $allData = [];
    if (!empty($_SESSION['uploaded_files'])) {
        foreach ($_SESSION['uploaded_files'] as $f) {
            $rows = $f['data'];
            if (count($rows) < 2) continue;
            $hdr = $rows[0];
            for ($i = 1; $i < count($rows); $i++) {
                if (count($rows[$i]) !== count($hdr)) continue;
                $allData[] = array_combine($hdr, $rows[$i]);
            }
        }
    }
    $opts = [];
    foreach ($allData as $r) {
        foreach ($r as $k => $v) {
            $v = trim($v);
            $opts[$k] = $opts[$k] ?? [];
            if (!in_array($v, $opts[$k])) $opts[$k][] = $v;
        }
    }
    echo json_encode($opts);
    exit;
}

// Ambil semua data
$allData = [];
if (!empty($_SESSION['uploaded_files'])) {
    foreach ($_SESSION['uploaded_files'] as $file) {
        $rows = $file['data'];
        if (count($rows) < 2) continue;
        $header = $rows[0];
        for ($i = 1; $i < count($rows); $i++) {
            if (count($rows[$i]) !== count($header)) continue;
            $allData[] = array_combine($header, $rows[$i]);
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter - Cleanify</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-white-100 text-gray-800 font-sans">

<!-- Navbar -->
<nav class="bg-white shadow fixed top-0 left-0 w-full z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex flex-col items-start">
                <div class="flex items-center space-x-2">
                    <img src="uploads/logo cleanify2.png" alt="Logo" class="rounded" style="width: 32px; height: 32px;">
                    <div class="text-xl font-bold text-blue-700">Cleanify</div>
                </div>
                <h1 class="text-sm text-gray-600">Deteksi Duplikasi Data Kanker</h1>
            </div>
            <div class="space-x-4 hidden md:block">
                <a href="index.php" class="text-gray-600 hover:text-blue-600">Dashboard</a>
                <a href="filter.php" class="text-blue-600 font-semibold">Filter</a>
                <a href="cekduplikasi.php" class="text-gray-600 hover:text-blue-600">Cek Duplikasi</a>
                <a href="history.php" class="text-gray-600 hover:text-blue-600">Riwayat</a>
                <a href="?logout=true" class="text-red-600 hover:text-red-800 font-semibold">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container mx-auto p-4 mt-20">
    <div class="max-w-5xl mx-auto bg-blue-50 p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold">Filter Data</h2>
        <hr class="my-4">

        <div class="border border-gray-300 p-4 mt-4">
            <select id="filterColumn" onchange="loadFilterOptions()" class="border rounded w-full p-2 mt-2">
                <option value="">-- Pilih Kolom --</option>
            </select>
            <div id="filterFields" class="filter-fields mt-2">Pilih kolom dulu.</div>

           <button onclick="stepByStepFilter()" class="bg-blue-600 text-white px-4 py-1 rounded mt-2">
                Multilayering-Filter
            </button>
            <div id="layeredResults" class="mt-4"></div>
        </div>

        <div id="searchResults" class="mt-4"></div>

        <?php if (!empty($_SESSION['uploaded_files'])): ?>
            <!-- Data will be handled in JS below -->
        <?php else: ?>
            <div class="mt-4 p-4 bg-yellow-100 border border-yellow-300 rounded">
                <p>Belum ada file yang diupload. <a href="index.php" class="text-blue-600 hover:underline">Kembali ke dashboard</a> untuk upload file.</p>
            </div>
        <?php endif; ?>

        <footer class="mt-4 text-center text-gray-500 text-sm">
            <p>Copyright Kelompok 1B - © 2025</p>
        </footer>
    </div>
</div>

<?php
$allData = [];
if (!empty($_SESSION['uploaded_files'])) {
    foreach ($_SESSION['uploaded_files'] as $file) {
        $rows = $file['data'];
        if (count($rows) < 2) continue;
        $header = $rows[0];
        for ($i = 1; $i < count($rows); $i++) {
            if (count($rows[$i]) !== count($header)) continue;
            $allData[] = array_combine($header, $rows[$i]);
        }
    }
}
?>

<script>
let allOptions = {};
let rawData = <?php echo json_encode($allData); ?>;
let filterSteps = ["Nama", "Tanggal Lahir", "Alamat", "NIK"];
let currentStep = 0;
let currentPairs = [];

document.addEventListener('DOMContentLoaded', function() {
    openFilter();
});

function openFilter() {
    document.getElementById('filterFields').innerText = 'Memuat…';
    fetch('?generate_filter_option=1')
        .then(r => r.json())
        .then(data => {
            allOptions = data;
            const sel = document.getElementById('filterColumn');
            sel.innerHTML = '<option value="">-- Pilih Kolom --</option>';
            Object.keys(data).forEach(c => {
                sel.innerHTML += `<option value="${c}">${c}</option>`;
            });
            document.getElementById('filterFields').innerText = '';
        });
}

function loadFilterOptions() {
    const col = document.getElementById('filterColumn').value;
    const div = document.getElementById('filterFields');
    if (!col) {
        div.innerText = 'Pilih kolom.';
        return;
    }
    const opts = allOptions[col] || [];
    if (!opts.length) {
        div.innerText = 'Tidak ada opsi.';
        return;
    }
    div.innerHTML = opts.map(v => `<div>${v}</div>`).join('');
}

function stepByStepFilter() {
    currentStep = 0;
    currentPairs = [];

    for (let i = 0; i < rawData.length; i++) {
        for (let j = i + 1; j < rawData.length; j++) {
            currentPairs.push([rawData[i], rawData[j]]);
        }
    }

    document.getElementById("layeredResults").innerHTML = "";
    showNextStep();
}

function showNextStep() {
    if (currentStep >= filterSteps.length) {
        document.getElementById("layeredResults").innerHTML += `
            <div class="mt-4 p-4 bg-green-100 border border-green-300 rounded">
                <p class="text-green-800 font-semibold">Semua tahap selesai.</p>
            </div>`;

        // Kirim ke server buat disimpan ke riwayat
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'log_history=1&jumlah_duplikat=' + currentPairs.length
        });

        return;
    }

    const col = filterSteps[currentStep];
    const matchedPairs = [];

    currentPairs.forEach(([a, b]) => {
        const valA = (a[col] || '').trim().toLowerCase();
        const valB = (b[col] || '').trim().toLowerCase();
        if (valA && valB && valA === valB) {
            matchedPairs.push([a, b]);
        }
    });

    let resultHtml = `<div class="mt-4 bg-blue-50 p-4 rounded">
        <h3 class="font-bold text-lg text-blue-700">Tahap ${currentStep + 1}: Filter berdasarkan <u>${col}</u></h3>
        <p class="text-sm text-gray-600 mb-2">Jumlah pasangan duplikat: ${matchedPairs.length}</p>`;

    if (matchedPairs.length === 0) {
        resultHtml += `<p class="text-red-600">Tidak ditemukan pasangan duplikat berdasarkan ${col}.</p>`;
    } else {
        matchedPairs.forEach(([a, b], idx) => {
            const headers = Object.keys(a);
            resultHtml += `<div class="mb-2">
                <p class="font-semibold text-gray-700">Pasangan ${idx + 1}:</p>
                <table class="border border-red-600 w-full text-sm mb-2">
                    <thead>
                        <tr>
                            ${headers.map(h => `<th class="border border-red-800 bg-red-500 text-white p-1">${h}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        <tr>${headers.map(h => `<td class="border border-red-800 p-1 bg-red-100 text-red-800">${a[h]}</td>`).join('')}</tr>
                        <tr>${headers.map(h => `<td class="border border-red-800 p-1 bg-red-100 text-red-800">${b[h]}</td>`).join('')}</tr>
                    </tbody>
                </table>
            </div>`;
        });
    }

    resultHtml += `</div>`;
    document.getElementById("layeredResults").innerHTML += resultHtml;

    currentPairs = matchedPairs;
    currentStep++;

    if (currentStep < filterSteps.length && matchedPairs.length > 0) {
        document.getElementById("layeredResults").innerHTML += `
            <button onclick="showNextStep()" class="mt-2 bg-green-600 text-white px-4 py-1 rounded">
                Lanjut ke Tahap ${currentStep + 1}
            </button>`;
    } else if (matchedPairs.length === 0) {
        document.getElementById("layeredResults").innerHTML += `
            <div class="mt-2 text-yellow-700 bg-yellow-100 border border-yellow-300 p-2 rounded">
                Tidak ada data yang bisa dilanjutkan ke tahap berikutnya.
            </div>`;
    }
}
</script>
</body>
</html>
