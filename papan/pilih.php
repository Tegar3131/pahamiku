<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';

// Harus login sebagai ABK
$profil_id = $_SESSION['profil_id'] ?? 0;
if (!$profil_id) {
    header('Location: ' . BASE_URL . 'login-abk.php');
    exit;
}

$profil_nama = $_SESSION['profil_nama'] ?? 'Kamu';

// Ambil papan milik anak yang AKTIF saja
$stmt1 = $conn->prepare(
    "SELECT id, nama_papan, ikon_papan, grid, deskripsi, is_favorit, urutan_tampil
     FROM papan
     WHERE profil_id = ? AND is_aktif = 1
     ORDER BY urutan_tampil ASC, is_favorit DESC, nama_papan ASC"
);
$stmt1->bind_param('i', $profil_id);
$stmt1->execute();
$papan_milik = $stmt1->get_result()->fetch_all(MYSQLI_ASSOC);

// Ambil papan preset yang AKTIF untuk anak ini
// Jika belum ada entri di profil_papan_preset, default = tampil (COALESCE is_aktif, 1)
$stmt2 = $conn->prepare(
    "SELECT p.id, p.nama_papan, p.ikon_papan, p.grid, p.deskripsi, 0 as is_favorit,
            COALESCE(pp.urutan_tampil, 0) as urutan_tampil
     FROM papan p
     LEFT JOIN profil_papan_preset pp ON pp.papan_id = p.id AND pp.profil_id = ?
     WHERE p.profil_id IS NULL AND COALESCE(pp.is_aktif, 1) = 1
     ORDER BY COALESCE(pp.urutan_tampil, 0) ASC, p.nama_papan ASC"
);
$stmt2->bind_param('i', $profil_id);
$stmt2->execute();
$papan_preset = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// Gabungkan: papan milik anak dulu, lalu preset
$daftar_papan = array_merge($papan_milik, $papan_preset);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pilih Papanmu — PAHAMIKU</title>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@700;800&family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --kuning:     #FFD93D;
            --kuning-tua: #E8B800;
            --biru:       #4ECDC4;
            --biru-tua:   #2BB5AC;
            --hijau:      #6BCB77;
            --hijau-tua:  #4aac5a;
            --gelap:      #1A1A2E;
            --abu:        #64748B;
            --putih:      #FFFDF7;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(160deg, #E0F7F5 0%, #FFFDF7 60%, #FFF9E6 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── TOPBAR ── */
        .topbar {
            background: white;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-kiri {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .topbar-logo {
            font-family: 'Baloo 2', cursive;
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #FF6B6B, #FFD93D, #4ECDC4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .topbar-salam {
            font-size: 15px;
            font-weight: 800;
            color: var(--abu);
        }
        .topbar-salam span {
            color: var(--gelap);
        }
        .btn-keluar-topbar {
            background: #FEF2F2;
            color: #DC2626;
            border: none;
            border-radius: 12px;
            padding: 10px 18px;
            font-family: 'Nunito', sans-serif;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
        }
        .btn-keluar-topbar:hover { background: #FEE2E2; }

        /* ── MAIN ── */
        .main {
            flex: 1;
            padding: 30px 20px 50px;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }

        .judul-section {
            text-align: center;
            margin-bottom: 36px;
            animation: masukAtas 0.5s ease both;
        }
        .judul-ikon { font-size: 56px; margin-bottom: 10px; display: block; }
        .judul-teks {
            font-family: 'Baloo 2', cursive;
            font-size: 32px;
            font-weight: 800;
            color: var(--gelap);
            margin-bottom: 6px;
        }
        .judul-sub {
            font-size: 17px;
            color: var(--abu);
            font-weight: 700;
        }

        /* ── GRID PILIH PAPAN ── */
        .papan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .kartu-papan {
            background: white;
            border: 3px solid #E2E8F0;
            border-radius: 24px;
            padding: 28px 20px 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            animation: masukAtas 0.5s ease both;
            position: relative;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
        }
        .kartu-papan:hover {
            transform: translateY(-8px);
            border-color: var(--biru);
            box-shadow: 0 16px 40px rgba(78, 205, 196, 0.2);
            color: inherit;
            text-decoration: none;
        }
        .kartu-papan:active {
            transform: scale(0.97);
        }

        .kartu-papan.favorit {
            border-color: var(--kuning-tua);
            background: linear-gradient(135deg, #FFFEF0, white);
        }
        .kartu-papan.favorit:hover {
            border-color: var(--kuning-tua);
            box-shadow: 0 16px 40px rgba(232, 184, 0, 0.25);
        }

        .badge-favorit {
            position: absolute;
            top: 14px;
            right: 14px;
            background: var(--kuning);
            color: var(--gelap);
            font-size: 11px;
            font-weight: 900;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .kartu-ikon {
            font-size: 72px;
            line-height: 1;
            display: block;
            transition: transform 0.2s;
        }
        .kartu-papan:hover .kartu-ikon {
            transform: scale(1.1);
        }

        .kartu-nama {
            font-family: 'Baloo 2', cursive;
            font-size: 22px;
            font-weight: 800;
            color: var(--gelap);
            line-height: 1.2;
        }
        .kartu-deskripsi {
            font-size: 13px;
            color: var(--abu);
            font-weight: 700;
            line-height: 1.4;
        }
        .kartu-meta {
            font-size: 12px;
            background: #F1F5F9;
            padding: 4px 12px;
            border-radius: 20px;
            color: #64748B;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kartu-aksi {
            display: block;
            width: 100%;
            background: var(--biru);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-family: 'Baloo 2', cursive;
            font-size: 18px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 6px;
        }
        .kartu-aksi:hover {
            background: var(--biru-tua);
        }
        .kartu-papan.favorit .kartu-aksi {
            background: var(--kuning-tua);
            color: var(--gelap);
        }
        .kartu-papan.favorit .kartu-aksi:hover {
            background: #c49a00;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--abu);
        }
        .empty-state .e { font-size: 60px; display: block; margin-bottom: 16px; }
        .empty-state p { font-size: 18px; font-weight: 700; }

        @keyframes masukAtas {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 500px) {
            .papan-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
            .kartu-ikon { font-size: 54px; }
            .kartu-nama { font-size: 18px; }
            .judul-teks { font-size: 26px; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-kiri">
        <div class="topbar-logo">PAHAMIKU</div>
        <div class="topbar-salam">Halo, <span><?= htmlspecialchars($profil_nama) ?></span>! 👋</div>
    </div>
    <a href="<?= BASE_URL ?>logout.php?jenis=abk" class="btn-keluar-topbar">👋 Keluar</a>
</div>

<!-- MAIN -->
<div class="main">

    <div class="judul-section">
        <span class="judul-ikon">📋</span>
        <div class="judul-teks">Mau pakai papan apa hari ini?</div>
        <p class="judul-sub">Pilih papan komunikasimu di bawah ini</p>
    </div>

    <?php if (empty($daftar_papan)): ?>
        <div class="empty-state">
            <span class="e">😔</span>
            <p>Belum ada papan. Minta pendamping untuk membuatkan papan ya!</p>
        </div>
    <?php else: ?>
        <div class="papan-grid">
            <?php foreach ($daftar_papan as $i => $p): ?>
                <?php $is_favorit = !empty($p['is_favorit']); ?>
                <a href="index.php?papan_id=<?= $p['id'] ?>"
                   class="kartu-papan <?= $is_favorit ? 'favorit' : '' ?>"
                   style="animation-delay: <?= $i * 0.05 ?>s;">

                    <?php if ($is_favorit): ?>
                        <span class="badge-favorit">⭐ Favorit</span>
                    <?php endif; ?>

                    <span class="kartu-ikon"><?= !empty($p['ikon_papan']) ? htmlspecialchars($p['ikon_papan']) : '📋' ?></span>

                    <div class="kartu-nama"><?= htmlspecialchars($p['nama_papan']) ?></div>

                    <?php if (!empty($p['deskripsi'])): ?>
                        <p class="kartu-deskripsi"><?= htmlspecialchars(mb_strimwidth($p['deskripsi'], 0, 60, '...')) ?></p>
                    <?php endif; ?>

                    <div class="kartu-meta">Grid <?= htmlspecialchars($p['grid']) ?></div>

                    <div class="kartu-aksi">✅ Pilih Papan Ini</div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
