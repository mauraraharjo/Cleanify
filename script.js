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

function openFilter() {
    document.getElementById('filterModal').style.display = 'block';
    document.getElementById('filterFields').innerHTML = 'Silakan pilih kolom untuk menampilkan opsi.';
}

function loadFilterOptions() {
    const column = document.getElementById('filterColumn').value;
    const filterDiv = document.getElementById('filterFields');

    if (!column) {
        filterDiv.innerHTML = 'Silakan pilih kolom.';
        return;
    }

fetch('generate_filter_option.php')
    .then(res => res.json())
    .then(data => {
        const column = document.getElementById('filterColumn').value;
        const filterDiv = document.getElementById('filterFields');

        if (!data[column] || data[column].length === 0) {
            filterDiv.innerHTML = 'Tidak ada opsi tersedia.';
            return;
        }

        let html = '';
        data[column].forEach(val => {
            html += `<label><input type="checkbox" name="filter_values[]" value="${val}"> ${val}</label><br>`;
        });
        html += `<input type="hidden" name="filter_column" value="${column}">`;
        filterDiv.innerHTML = html;
    })

        .catch(err => {
            console.error('Gagal memuat opsi filter:', err);
            filterDiv.innerHTML = 'Terjadi kesalahan saat memuat opsi.';
        });
}

function applyFilter(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const selectedColumn = formData.get('filter_column'); // Ambil kolom terpilih

    fetch('filter.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        renderTable(data, 'Hasil Filter', [selectedColumn]); // Hanya tampilkan kolom terpilih
        document.getElementById('filterModal').style.display = 'none';
    })
    .catch(err => {
        console.error('Gagal menerapkan filter:', err);
        document.getElementById('searchResults').innerHTML = '<p>Gagal menerapkan filter.</p>';
    });
}

function tampilkanHistory() {
    document.getElementById('searchResults').innerHTML = '';

    fetch('history.php')
        .then(res => res.text())
        .then(html => {
            document.getElementById('searchResults').innerHTML = html;
        })
        .catch(err => {
            console.error('Gagal memuat history:', err);
            document.getElementById('searchResults').innerHTML = '<p>Gagal memuat riwayat.</p>';
        });
}

function renderTable(data, title, selectedColumns = null) {
    const resultDiv = document.getElementById('searchResults');

    if (!data || data.length === 0) {
        resultDiv.innerHTML = `<p>${title} kosong.</p>`;
        return;
    }

    let html = `<h3>${title}</h3><table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr>`;
    html += `<th>No</th>`; // Tambahkan kolom No

    const headers = selectedColumns || Object.keys(data[0]);
    headers.forEach(h => html += `<th>${h}</th>`);
    html += '</tr></thead><tbody>';

    const isDuplicate = title.toLowerCase().includes("duplikat");

    data.forEach((row, index) => {
        html += `<tr${isDuplicate ? ' class="duplicate-row"' : ''}>`;
        html += `<td>${index + 1}</td>`; // Nomor urut
        headers.forEach(h => html += `<td>${row[h]}</td>`);
        html += '</tr>';
    });

    html += '</tbody></table>';
    resultDiv.innerHTML = html;
}

console.log("Script aktif");



