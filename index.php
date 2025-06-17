<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

if (isset($_GET['clear'])) {
    unset($_SESSION['uploaded_files']);
    unset($_SESSION['history']);
    header('Location: index.php');
    exit();
}

$users = [];
if (file_exists('users.json')) {
    $users = json_decode(file_get_contents('users.json'), true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (isset($users[$username]) && $users[$username] === $password) {
        $_SESSION['user'] = $username;
        header('Location: index.php');
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}

$is_logged_in = isset($_SESSION['user']);

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

// --- Download CSV Bersih ---
if (isset($_GET['download_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=cleaned_data.csv');

    if (!empty($_SESSION['uploaded_files'])) {
        $rows = $_SESSION['uploaded_files'][0]['data']; // Ambil dari file pertama (contoh)
        $output = fopen('php://output', 'w');
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }
    exit;
}

// --- Generate Filter Options (AJAX) ---
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

// --- Upload File ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'], $_FILES['file'])) {
    $_SESSION['uploaded_files'] = $_SESSION['uploaded_files'] ?? [];
    $_SESSION['history'] = $_SESSION['history'] ?? [];

    foreach ($_FILES['file']['tmp_name'] as $i => $tmp) {
        $name = $_FILES['file']['name'][$i];
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'csv') continue;
        if (!is_dir('uploads')) mkdir('uploads');
        $target = 'uploads/' . time() . '-' . $name;
        if (move_uploaded_file($tmp, $target)) {
            $rows = [];
            $h = fopen($target, 'r');
            while (($d = fgetcsv($h, 1000, ',')) !== false) $rows[] = $d;
            fclose($h);
            $_SESSION['uploaded_files'][] = ['filename' => $name, 'data' => $rows];
            $_SESSION['history'][] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'total' => count($rows) - 1,
                'duplikat' => 0,
                'file' => ''
            ];
        }
    }
    header('Location: index.php');
    exit;
}

// --- Siapkan JSON untuk JS ---
$historyJson = !empty($_SESSION['history']) ? json_encode($_SESSION['history']) : '[]';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cleanify</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="text-gray-800 font-sans overflow-hidden">

<?php if (!$is_logged_in): ?>
  <div class="flex flex-col md:flex-row h-screen">
    
    <!-- KIRI: GAMBAR -->
    <div class="hidden md:flex w-1/2 items-center justify-center bg-blue-100">
      <div class="flex flex-col items-center text-center px-6">
        <img src="uploads/logo cleanify (1).png" alt="Logo" class="max-w-xs mb-4">
        <h1 class="text-3xl font-bold text-blue-900 mb-2">CLEANIFY</h1>
        <p class="text-gray-600 italic">"Bersihkan data, tingkatkan akurasi."</p>
      </div>
    </div>

    <!-- KANAN: FORM LOGIN -->
    <div class="w-full md:w-1/2 flex flex-col items-center justify-center ">
      <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md bg-gray-100 ">
        <h1 class="text-2xl font-bold text-center">Selamat Datang di Cleanify!</h1>
        <p class="text-center text-gray-600 mb-4">Sistem Deteksi Duplikasi Data Registrasi Kanker</p>
        <h2 class="text-center text-xl mb-2">Login</h2>

        <?php if (isset($error)): ?>
          <p class="text-red-500"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST">
          <label class="block mt-4">Username:</label>
          <input type="text" name="username" required class="border rounded w-full p-2">
          <label class="block mt-4">Password:</label>
          <input type="password" name="password" required class="border rounded w-full p-2">
          <button type="submit" name="login" class="mt-4 w-full bg-blue-600 text-white p-2 rounded">Login</button>
        </form>
      </div>

      <!-- FOOTER di dalam container agar tidak keluar h-screen -->
    <footer class="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-gray-500 text-sm ">
    <p>Copyright Kelompok 1B - © 2025</p>
    </footer>
    </div>
  </div>

<?php else: ?>
  <div class="container mx-auto p-4">

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
                <a href="index.php" class="text-blue-600 font-semibold">Dashboard</a>
                <a href="filter.php" class="text-gray-600 hover:text-blue-600">Filter</a>
                <a href="cekduplikasi.php" class="text-gray-600 hover:text-blue-600">Cek Duplikasi</a>
                <a href="history.php" class="text-gray-600 hover:text-blue-600">Riwayat</a>
                <a href="?logout=true" class="text-red-600 hover:text-red-800 font-semibold">Logout</a>
            </div>

        </div>
    </div>
</nav>
</div>

<div>
        <div class="max-w-5xl mx-auto bg-blue-50 p-6 rounded-lg shadow-md mt-12">
            <h2 class="text-2xl font-bold">Halo, <?= $_SESSION['user']; ?>!</h2>
            <hr class="my-4">

            <!-- Upload File -->
            <form action="" method="POST" enctype="multipart/form-data" class="mb-4">
                <label class="block">Upload File </label>
                <input type="file" name="file[]" multiple accept=".csv" class="border rounded w-full p-2">
                <button type="submit" name="upload" class="mt-4 w-full bg-blue-600 text-white p-2 rounded">Tambah File</button>
               <a href="?clear=true" class="inline-block mt-2 text-red-600 hover:text-red-800">🧹 Hapus Data</a>
            </form>

            <!-- Tools -->
            <div class="flex space-x-2 mb-4">
                <form id="searchForm" onsubmit="return searchData(event)" class="flex-1">
                    <input type="text" id="searchInput" placeholder="Cari data" class="border rounded b-full p-2 bg-gray-100"">
                    <button type="submit" class="bg-blue-600 text-white p-2 rounded">🔍 Cari</button>
            </div>

            <!-- Modal Filter -->
            <div id="filterModal" class="filter-modal hidden border border-gray-300 p-4 mt-4">
                <button class="close-btn" onclick="closeFilter()" class="bg-red-600 text-white p-2 rounded">Tutup</button>
                <select id="filterColumn" onchange="loadFilterOptions()" class="border rounded w-full p-2 mt-2">
                    <option value="">-- Pilih Kolom --</option>
                </select>
                <div id="filterFields" class="filter-fields mt-2">Pilih kolom dulu.</div>
            </div>

            <!-- Hasil -->
            <div id="searchResults" class="mt-4"></div>

            <!-- Tampilkan Data -->
            <?php if (!empty($_SESSION['uploaded_files'])): ?>
                <h3 class="text-xl mt-4">File yang sudah diupload:</h3>
                <ul class="list-disc pl-5">
                    <?php foreach ($_SESSION['uploaded_files'] as $f): ?>
                        <li><strong><?= htmlspecialchars(basename($f['filename'])) ?></strong></li>
                        <table class="min-w-full border-collapse border border-gray-200 mt-2">
                            <thead>
                                <tr>
                                    <?php foreach ($f['data'][0] as $head): ?>
                                        <th class="border border-gray-200 p-2"><?= htmlspecialchars($head) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($j = 1; $j < count($f['data']); $j++): ?>
                                    <tr>
                                        <?php foreach ($f['data'][$j] as $cell): ?>
                                            <td class="border border-gray-200 p-2"><?= htmlspecialchars($cell) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <footer style="text-align: center;">
                <p>Copyright Kelompok 1B - © 2025</p>
            </footer>

        </div>
    <?php endif; ?>
</div>

<!-- Script -->
<script>
function searchData(event) {
    event.preventDefault();
    const keyword = document.getElementById('searchInput').value;
    if (!keyword) return;

    fetch('cari.php?q=' + encodeURIComponent(keyword))
        .then(res => res.json())
        .then(data => renderTable(data, 'Hasil Pencarian'))
        .catch(err => {
            console.error(err);
            document.getElementById('searchResults').innerHTML = '<p>Error saat pencarian.</p>';
        });
}

let allOptions = {};

function openFilter() {
    document.getElementById('filterModal').classList.remove('hidden');
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
            document.getElementById('filterFields').innerText = 'Pilih kolom.';
        });
}

function closeFilter() {
    document.getElementById('filterModal').classList.add('hidden');
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

function tampilkanHistory() {
    const container = document.getElementById('searchResults');
    container.innerHTML = ''; 

    // Sembunyikan bagian upload
    const uploadedSection = document.getElementById('uploadedSection');
    if (uploadedSection) {
        uploadedSection.style.display = 'none';
    }

    fetch('history.php')
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<p>Gagal memuat riwayat.</p>';
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
