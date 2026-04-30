<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$profil_id = (int)($_GET['profil_id'] ?? 0);

// 1. Ambil info profil ABK
$stmt = $conn->prepare("SELECT nama, jenis_abk FROM profil_abk WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $profil_id, $_SESSION['user_id']);
$stmt->execute();
$profil = $stmt->get_result()->fetch_assoc();

if (!$profil) redirect('dashboard/index.php');

// 2. Ambil semua papan milik profil ini (urutan_tampil ASC, lalu nama)
$stmt_papan = $conn->prepare("SELECT * FROM papan WHERE profil_id = ? ORDER BY urutan_tampil ASC, nama_papan ASC");
$stmt_papan->bind_param('i', $profil_id);
$stmt_papan->execute();
$papan_list = $stmt_papan->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Ambil semua papan preset (global)
$stmt_preset = $conn->prepare("SELECT p.id, p.nama_papan, p.ikon_papan, p.grid, COALESCE(pp.is_aktif, 1) as is_aktif_preset FROM papan p LEFT JOIN profil_papan_preset pp ON pp.papan_id = p.id AND pp.profil_id = ? WHERE p.profil_id IS NULL ORDER BY p.nama_papan ASC");
$stmt_preset->bind_param('i', $profil_id);
$stmt_preset->execute();
$preset_list = $stmt_preset->get_result()->fetch_all(MYSQLI_ASSOC);

// Avatar map untuk header
$avatar_map = [
    'ASD' => '🧩', 'ADHD' => '⚡', 'Tunagrahita' => '🌟', 
    'Down Syndrome' => '🌈', 'Disleksia' => '📖'
];
$avatar = $avatar_map[$profil['jenis_abk']] ?? '😊';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Komunikasi <?= htmlspecialchars($profil['nama']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        /* Menggunakan variabel warna PAHAMIKU agar konsisten */
        :root {
            --biru: #4ECDC4;
            --biru-tua: #2BB5AC;
            --kuning: #FFD93D;
            --putih: #FFFDF7;
            --gelap: #1A1A2E;
            --abu: #6B7280;
            --merah: #FF6B6B;
            --radius: 20px;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #F3F4F6;
            color: var(--gelap);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-wrapper {
            flex: 1;
            width: 100%;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Header Profil */
        .header-profil {
            background: white;
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            animation: masuk 0.5s ease both;
        }

        .profil-info { display: flex; align-items: center; gap: 20px; }
        .profil-avatar { font-size: 50px; }
        .profil-teks h1 { 
            font-family: 'Baloo 2', cursive; 
            margin: 0; font-size: 28px; 
        }
        .profil-teks p { margin: 0; color: var(--abu); font-weight: 600; }

        /* Grid Papan */
        .grid-papan {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }

        .card-papan {
            background: white;
            border-radius: var(--radius);
            padding: 24px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 3px solid transparent;
            animation: masuk 0.5s ease both;
        }

        .card-papan:hover {
            transform: translateY(-5px);
            border-color: var(--biru);
            box-shadow: 0 12px 30px rgba(78, 205, 196, 0.2);
        }

        .papan-ikon { font-size: 40px; margin-bottom: 15px; display: block; }
        .papan-nama { font-family: 'Baloo 2', cursive; font-size: 20px; margin-bottom: 5px; }
        .papan-grid-size { font-size: 14px; color: var(--abu); margin-bottom: 20px; font-weight: 700; }

        /* Tombol Aksi */
        .aksi-papan { display: flex; gap: 10px; }
        .btn-aksi {
            flex: 1;
            padding: 10px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 800;
            font-size: 13px;
            transition: all 0.2s;
            text-align: center;
            border: none;
        }
        .btn-edit { background: var(--kuning); color: var(--gelap); }
        .btn-hapus { background: #FEF2F2; color: var(--merah); }
        .btn-cetak { background: #E0F2FE; color: #0284C7; }
        .btn-tambah {
            background: var(--gelap);
            color: white;
            padding: 12px 24px;
            border-radius: 15px;
            font-family: 'Baloo 2';
            font-size: 18px;
            display: inline-block;
            text-decoration: none;
        }

        .btn-kembali {
            text-decoration: none;
            color: var(--abu);
            font-weight: 700;
            margin-bottom: 20px;
            display: inline-block;
        }

        @keyframes masuk {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .header-profil {
                flex-direction: column;
                text-align: center;
                gap: 20px;
                padding: 20px;
            }
            .profil-info { flex-direction: column; gap: 10px; }
            .btn-tambah { width: 100%; padding: 12px; }
            .grid-papan { grid-template-columns: 1fr; }
        }

        /* ── Toggle Switch ── */
        .toggle-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 10px;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 26px;
            cursor: pointer;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #D1D5DB;
            border-radius: 26px;
            transition: 0.3s;
        }
        .slider::before {
            content: '';
            position: absolute;
            width: 20px; height: 20px;
            left: 3px; bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        .toggle-switch input:checked + .slider { background: var(--biru); }
        .toggle-switch input:checked + .slider::before { transform: translateX(22px); }

        .urutan-badge {
            background: #F1F5F9;
            color: var(--abu);
            font-size: 12px;
            font-weight: 900;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .badge-preset {
            background: #DBEAFE;
            color: #2563EB;
            font-size: 11px;
            font-weight: 900;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .urutan-kontrol {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .btn-panah {
            background: #F1F5F9;
            border: none;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 11px;
            cursor: pointer;
            color: var(--abu);
            transition: 0.2s;
            font-weight: bold;
        }
        .btn-panah:hover {
            background: var(--biru);
            color: white;
        }

        /* State nonaktif */
        .card-papan.nonaktif {
            opacity: 0.45;
            border-color: #E5E7EB;
        }
        .card-papan.nonaktif:hover {
            border-color: #D1D5DB;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transform: none;
        }
    </style>
</head>
<body>

<?php include '../inc/navbar.php'; ?>

<div class="main-wrapper">
<div class="container">
    <nav aria-label="breadcrumb" style="margin-bottom: 24px;">
        <ol style="list-style: none; display: flex; gap: 10px; align-items: center; font-weight: 800; font-size: 14px; color: var(--abu); margin: 0; padding: 0;">
            <li><a href="index.php" style="color: var(--biru); text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='var(--gelap)'" onmouseout="this.style.color='var(--biru)'">Dashboard</a></li>
            <li style="font-size: 12px; opacity: 0.5;">▶</li>
            <li style="color: var(--gelap);">Papan <?= htmlspecialchars($profil['nama']) ?></li>
        </ol>
    </nav>

    <div class="header-profil">
        <div class="profil-info">
            <span class="profil-avatar"><?= $avatar ?></span>
            <div class="profil-teks">
                <h1>Papan <?= htmlspecialchars($profil['nama']) ?></h1>
                <p>Kelola papan komunikasi untuk si kecil</p>
            </div>
        </div>
        <a href="papan-buat.php?profil_id=<?= $profil_id ?>" class="btn-tambah">+ Papan Baru</a>
    </div>

    <!-- Section: Papan Milik Anak -->
    <h3 style="font-family: 'Baloo 2', cursive; font-size: 18px; color: var(--abu); margin-bottom: 14px;">📁 Papan Milik <?= htmlspecialchars($profil['nama']) ?></h3>
    <div class="grid-papan">
        <?php if (empty($papan_list)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 50px;">
                <p style="color: var(--abu); font-weight: 700;">Belum ada papan komunikasi.<br>Yuk, buatkan satu untuk <?= htmlspecialchars($profil['nama']) ?>!</p>
            </div>
        <?php else: ?>
            <?php foreach ($papan_list as $idx => $p): ?>
                <div class="card-papan <?= empty($p['is_aktif']) ? 'nonaktif' : '' ?>" id="papan-<?= $p['id'] ?>">
                    <div class="toggle-wrapper">
                        <label class="toggle-switch" title="<?= $p['is_aktif'] ? 'Aktif — tampil di layar anak' : 'Nonaktif — disembunyikan' ?>">
                            <input type="checkbox" <?= $p['is_aktif'] ? 'checked' : '' ?> onchange="togglePapanAktif(<?= $p['id'] ?>, this)">
                            <span class="slider"></span>
                        </label>
                        <div class="urutan-kontrol">
                            <button type="button" class="btn-panah" onclick="geserUrutan(this, 'atas')" title="Naikkan urutan">▲</button>
                            <span class="urutan-badge">#<?= $idx + 1 ?></span>
                            <button type="button" class="btn-panah" onclick="geserUrutan(this, 'bawah')" title="Turunkan urutan">▼</button>
                        </div>
                    </div>
                    <span class="papan-ikon"><?= !empty($p['ikon_papan']) ? htmlspecialchars($p['ikon_papan']) : '📋' ?></span>
                    <div class="papan-nama"><?= htmlspecialchars($p['nama_papan']) ?></div>
                    <div class="papan-grid-size">Ukuran Kotak: <?= $p['grid'] ?></div>
                    <div class="aksi-papan">
                        <a href="papan-cetak.php?id=<?= $p['id'] ?>" target="_blank" class="btn-aksi btn-cetak">Cetak</a>
                        <a href="papan-edit.php?id=<?= $p['id'] ?>" class="btn-aksi btn-edit">Atur</a>
                        <a href="papan-hapus.php?id=<?= $p['id'] ?>" class="btn-aksi btn-hapus">Hapus</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Section: Papan Siap Pakai -->
    <?php if (!empty($preset_list)): ?>
    <h3 style="font-family: 'Baloo 2', cursive; font-size: 18px; color: var(--abu); margin: 30px 0 14px;">🌍 Papan Siap Pakai</h3>
    <p style="font-size: 13px; color: var(--abu); font-weight: 600; margin-bottom: 14px;">Aktifkan papan siap pakai yang ingin ditampilkan untuk <?= htmlspecialchars($profil['nama']) ?></p>
    <div class="grid-papan">
        <?php foreach ($preset_list as $idxPr => $pr): ?>
            <div class="card-papan <?= empty($pr['is_aktif_preset']) ? 'nonaktif' : '' ?>" id="preset-<?= $pr['id'] ?>">
                <div class="toggle-wrapper">
                    <label class="toggle-switch" title="<?= $pr['is_aktif_preset'] ? 'Aktif' : 'Nonaktif' ?>">
                        <input type="checkbox" <?= $pr['is_aktif_preset'] ? 'checked' : '' ?> onchange="togglePreset(<?= $pr['id'] ?>, this)">
                        <span class="slider"></span>
                    </label>
                    <div class="urutan-kontrol">
                        <button type="button" class="btn-panah" onclick="geserUrutanPreset(this, 'atas')" title="Naikkan urutan">▲</button>
                        <span class="urutan-badge">#<?= $idxPr + 1 ?></span>
                        <button type="button" class="btn-panah" onclick="geserUrutanPreset(this, 'bawah')" title="Turunkan urutan">▼</button>
                    </div>
                </div>
                <span class="papan-ikon"><?= !empty($pr['ikon_papan']) ? htmlspecialchars($pr['ikon_papan']) : '📋' ?></span>
                <div class="papan-nama"><?= htmlspecialchars($pr['nama_papan']) ?></div>
                <div class="papan-grid-size">Grid: <?= $pr['grid'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</div>

<?php include '../inc/footer.php'; ?>

<script>
const PROFIL_ID = <?= $profil_id ?>;

function togglePapanAktif(papanId, checkbox) {
    const card = document.getElementById('papan-' + papanId);
    const formData = new FormData();
    formData.append('aksi', 'toggle_papan_aktif');
    formData.append('papan_id', papanId);

    fetch('papan-aksi.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'sukses') {
                if (data.is_aktif) {
                    card.classList.remove('nonaktif');
                    checkbox.parentElement.title = 'Aktif — tampil di layar anak';
                } else {
                    card.classList.add('nonaktif');
                    checkbox.parentElement.title = 'Nonaktif — disembunyikan';
                }
            } else {
                checkbox.checked = !checkbox.checked;
                alert('Gagal mengubah status papan.');
            }
        })
        .catch(() => {
            checkbox.checked = !checkbox.checked;
            alert('Terjadi kesalahan koneksi.');
        });
}

function togglePreset(papanId, checkbox) {
    const card = document.getElementById('preset-' + papanId);
    const formData = new FormData();
    formData.append('aksi', 'toggle_preset_abk');
    formData.append('papan_id', papanId);
    formData.append('profil_id', PROFIL_ID);

    fetch('papan-aksi.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'sukses') {
                if (data.is_aktif) {
                    card.classList.remove('nonaktif');
                } else {
                    card.classList.add('nonaktif');
                }
            } else {
                checkbox.checked = !checkbox.checked;
                alert('Gagal mengubah status preset.');
            }
        })
        .catch(() => {
            checkbox.checked = !checkbox.checked;
            alert('Terjadi kesalahan koneksi.');
        });
}

function geserUrutan(button, arah) {
    const card = button.closest('.card-papan');
    const parent = card.parentElement;
    
    if (arah === 'atas' && card.previousElementSibling) {
        parent.insertBefore(card, card.previousElementSibling);
    } else if (arah === 'bawah' && card.nextElementSibling) {
        parent.insertBefore(card.nextElementSibling, card);
    } else {
        return; // Tidak ada perubahan
    }
    
    // Update visual badge numbers
    const cards = parent.querySelectorAll('.card-papan');
    const dataUrutan = [];
    
    cards.forEach((item, index) => {
        const badge = item.querySelector('.urutan-badge');
        if (badge) badge.textContent = '#' + (index + 1);
        const id = item.id.replace('papan-', '');
        dataUrutan.push({ id: id, urutan: index, tipe: 'milik' });
    });
    
    simpanUrutan(dataUrutan);
}

function geserUrutanPreset(button, arah) {
    const card = button.closest('.card-papan');
    const parent = card.parentElement;
    
    if (arah === 'atas' && card.previousElementSibling) {
        parent.insertBefore(card, card.previousElementSibling);
    } else if (arah === 'bawah' && card.nextElementSibling) {
        parent.insertBefore(card.nextElementSibling, card);
    } else {
        return; // Tidak ada perubahan
    }
    
    // Update visual badge numbers
    const cards = parent.querySelectorAll('.card-papan');
    const dataUrutan = [];
    
    cards.forEach((item, index) => {
        const badge = item.querySelector('.urutan-badge');
        if (badge) badge.textContent = '#' + (index + 1);
        const id = item.id.replace('preset-', '');
        dataUrutan.push({ id: id, urutan: index, tipe: 'preset' });
    });
    
    simpanUrutan(dataUrutan);
}

function simpanUrutan(dataUrutan) {
    const formData = new FormData();
    formData.append('aksi', 'update_urutan_papan');
    formData.append('profil_id', PROFIL_ID);
    formData.append('data', JSON.stringify(dataUrutan));
    
    fetch('papan-aksi.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'sukses') alert('Gagal menyimpan urutan.');
        })
        .catch(() => alert('Terjadi kesalahan saat sinkronisasi urutan.'));
}
</script>

</body>
</html>