<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$user = getUserLogin($conn);

// ── Ambil semua profil ABK milik pendamping ini ──────────
$stmt = $conn->prepare("
    SELECT p.*, 
           COUNT(DISTINCT pb.id) AS jumlah_papan,
           COUNT(DISTINCT sf.id) AS jumlah_favorit
    FROM profil_abk p
    LEFT JOIN papan pb ON pb.profil_id = p.id
    LEFT JOIN simbol_favorit sf ON sf.profil_id = p.id
    WHERE p.user_id = ?
    GROUP BY p.id
    ORDER BY p.nama ASC
");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$profil_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Ambil papan preset global ─────────────────────────────
$preset_list = $conn->query("
    SELECT * FROM papan WHERE profil_id IS NULL ORDER BY id ASC
")->fetch_all(MYSQLI_ASSOC);

// Avatar emoji per jenis ABK
$avatar_map = [
    'ASD'           => '🧩',
    'ADHD'          => '⚡',
    'Tunagrahita'   => '🌟',
    'Down Syndrome' => '🌈',
    'Disleksia'     => '📖',
    'Diskalkulia'   => '🔢',
    'Tunarungu'     => '👂',
    'Tunadaksa'     => '♿',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAHAMIKU — Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --kuning:     #FFD93D;
            --kuning-tua: #E8B800;
            --biru:       #4ECDC4;
            --biru-tua:   #2BB5AC;
            --merah:      #FF6B6B;
            --hijau:      #6BCB77;
            --ungu:       #A78BFA;
            --putih:      #FFFDF7;
            --gelap:      #1A1A2E;
            --abu:        #6B7280;
            --abu-muda:   #F3F4F6;
            --radius:     16px;
            --shadow:     0 4px 20px rgba(0,0,0,0.08);
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--abu-muda);
            color: var(--gelap);
            min-height: 100vh;
        }

        /* ── Topbar ── */
        .topbar {
            background: white;
            padding: 0 28px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-brand {
            font-family: 'Baloo 2', cursive;
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #FF6B6B, #FFD93D, #4ECDC4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }
        .topbar-kanan {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .topbar-nama {
            font-size: 14px;
            font-weight: 700;
            color: var(--abu);
        }
        .topbar-nama span {
            color: var(--gelap);
        }
        .btn-keluar {
            padding: 8px 16px;
            background: var(--abu-muda);
            border: none;
            border-radius: 10px;
            font-family: 'Nunito', sans-serif;
            font-size: 13px;
            font-weight: 800;
            color: var(--abu);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-keluar:hover {
            background: #FEF2F2;
            color: var(--merah);
        }

        /* ── Main layout ── */
        .main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        /* ── Salam ── */
        .salam {
            margin-bottom: 32px;
            animation: masuk 0.5s ease both;
        }
        .salam h1 {
            font-family: 'Baloo 2', cursive;
            font-size: 28px;
            font-weight: 800;
        }
        .salam p {
            font-size: 15px;
            color: var(--abu);
            margin-top: 4px;
            font-weight: 600;
        }

        /* ── Flash message ── */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 24px;
        }
        .alert-sukses { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }
        .alert-gagal  { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }

        /* ── Section header ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .section-judul {
            font-family: 'Baloo 2', cursive;
            font-size: 20px;
            font-weight: 700;
        }

        /* ── Tombol tambah ── */
        .btn-tambah {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: var(--gelap);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Nunito', sans-serif;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-tambah:hover {
            background: #2D2D44;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        /* ── Grid profil ABK ── */
        .profil-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 40px;
            animation: masuk 0.5s ease 0.1s both;
        }

        /* Kartu profil ABK */
        .kartu-profil {
            background: white;
            border-radius: 20px;
            padding: 24px 20px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: all 0.25s;
            position: relative;
            overflow: hidden;
        }
        .kartu-profil::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--kuning), var(--biru));
        }
        .kartu-profil:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 36px rgba(0,0,0,0.12);
        }
        .profil-avatar {
            font-size: 52px;
            margin-bottom: 12px;
            display: block;
            line-height: 1;
        }
        .profil-foto {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 12px;
            display: block;
            border: 3px solid var(--abu-muda);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .profil-foto:hover { transform: scale(1.05); border-color: var(--kuning); }

        /* ── Lightbox Popup ── */
        .lightbox {
            display: none; 
            position: fixed; 
            z-index: 9999; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .lightbox.tampil {
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
        }
        .lightbox img {
            max-width: 90%;
            max-height: 80vh;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }
        .lightbox.tampil img { transform: scale(1); }
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: #fff;
            font-size: 44px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            transition: color 0.2s;
        }
        .lightbox-close:hover { color: var(--kuning); }
        .profil-nama {
            font-family: 'Baloo 2', cursive;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .profil-jenis {
            display: inline-block;
            padding: 3px 12px;
            background: var(--abu-muda);
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            color: var(--abu);
            margin-bottom: 14px;
        }
        .profil-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 12px 0;
            border-top: 1px solid #F3F4F6;
            margin-bottom: 14px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-angka {
            font-family: 'Baloo 2', cursive;
            font-size: 22px;
            font-weight: 800;
            color: var(--gelap);
            display: block;
        }
        .stat-label {
            font-size: 11px;
            color: var(--abu);
            font-weight: 700;
        }
        .profil-aksi {
            display: flex;
            gap: 8px;
        }
        .btn-kecil {
            flex: 1;
            padding: 9px 8px;
            border-radius: 10px;
            font-family: 'Nunito', sans-serif;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            text-align: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-papan {
            background: var(--kuning);
            color: var(--gelap);
        }
        .btn-papan:hover { background: var(--kuning-tua); }
        .btn-edit {
            background: var(--abu-muda);
            color: var(--abu);
        }
        .btn-edit:hover { background: #E5E7EB; color: var(--gelap); }
        .btn-hapus {
            background: #FEF2F2;
            color: var(--merah);
        }
        .btn-hapus:hover { background: #FEE2E2; }

        /* Kartu tambah profil baru */
        .kartu-tambah {
            background: white;
            border-radius: 20px;
            padding: 24px 20px;
            box-shadow: var(--shadow);
            text-align: center;
            border: 2px dashed #D1D5DB;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            text-decoration: none;
            color: var(--abu);
        }
        .kartu-tambah:hover {
            border-color: var(--biru);
            color: var(--biru-tua);
            transform: translateY(-4px);
            background: #F0FFFE;
        }
        .kartu-tambah .ikon-plus {
            font-size: 36px;
            margin-bottom: 10px;
        }
        .kartu-tambah .teks {
            font-size: 14px;
            font-weight: 800;
        }

        /* ── Preset papan ── */
        .preset-section {
            animation: masuk 0.5s ease 0.2s both;
            margin-bottom: 40px;
        }
        .preset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        .kartu-preset {
            background: white;
            border-radius: 16px;
            padding: 18px 16px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: var(--gelap);
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        .kartu-preset:hover {
            border-color: var(--kuning-tua);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        .preset-ikon {
            font-size: 32px;
            flex-shrink: 0;
        }
        .preset-info .nama {
            font-size: 15px;
            font-weight: 800;
        }
        .preset-info .sub {
            font-size: 12px;
            color: var(--abu);
            font-weight: 600;
            margin-top: 2px;
        }
        .preset-panah {
            margin-left: auto;
            color: #D1D5DB;
            font-size: 18px;
        }

        /* ── Kosong state ── */
        .kosong-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 48px 20px;
            color: var(--abu);
        }
        .kosong-state .e { font-size: 48px; display: block; margin-bottom: 12px; }
        .kosong-state p  { font-size: 15px; font-weight: 600; margin-bottom: 20px; }

        /* ── Animasi ── */
        @keyframes masuk {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Responsif ── */
        @media (max-width: 600px) {
            .topbar { padding: 0 16px; }
            .topbar-nama { display: none; }
            .main { padding: 20px 16px; }
            .profil-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include '../inc/navbar.php'; ?>

<!-- Main -->
<div class="main">

    <!-- Salam -->
    <div class="salam">
        <h1>Dashboard Pendamping 👩‍🏫</h1>
        <p>Kelola profil dan papan komunikasi pengguna Anda.</p>
    </div>

    <!-- Flash message -->
    <?php tampilFlash(); ?>

    <!-- ── SEKSI: Profil ABK ── -->
    <div class="section-header">
        <div class="section-judul">👤 Profil Pengguna</div>
        <a href="dashboard-profil-tambah.php" class="btn-tambah">+ Tambah Profil</a>
    </div>

    <div class="profil-grid">
        <?php if (empty($profil_list)): ?>
            <div class="kosong-state">
                <span class="e">🙈</span>
                <p>Belum ada profil pengguna.<br>Tambahkan profil pertama sekarang!</p>
                <a href="dashboard-profil-tambah.php" class="btn-tambah">+ Tambah Profil Pertama</a>
            </div>
        <?php else: ?>
            <?php foreach ($profil_list as $profil):
                $avatar = $avatar_map[$profil['jenis_abk']] ?? '😊';
            ?>
            <div class="kartu-profil">
                <?php if (!empty($profil['foto_profil'])): ?>
                    <img src="../uploads/profil/<?= htmlspecialchars($profil['foto_profil']) ?>" class="profil-foto" alt="Foto Wajah" onclick="bukaLightbox(this.src)">
                <?php else: ?>
                    <span class="profil-avatar"><?= $avatar ?></span>
                <?php endif; ?>
                <div class="profil-nama"><?= htmlspecialchars($profil['nama']) ?></div>
                <span class="profil-jenis">
                    <?= $profil['jenis_abk'] ? htmlspecialchars($profil['jenis_abk']) : 'Umum' ?>
                </span>
                <div class="profil-stats">
                    <div class="stat-item">
                        <span class="stat-angka"><?= $profil['jumlah_papan'] ?></span>
                        <span class="stat-label">Papan</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-angka"><?= $profil['jumlah_favorit'] ?></span>
                        <span class="stat-label">Favorit</span>
                    </div>
                </div>
                <div class="profil-aksi">
                    <a href="papan-list.php?profil_id=<?= $profil['id'] ?>" 
                       class="btn-kecil btn-papan">📋 Papan</a>
                    <a href="profil-edit.php?id=<?= $profil['id'] ?>" 
                       class="btn-kecil btn-edit">✏️ Edit</a>
                    <a href="dashboard-profil-hapus.php?id=<?= $profil['id'] ?>" 
                       class="btn-kecil btn-hapus"
                       onclick="return confirm('Hapus profil <?= htmlspecialchars($profil['nama']) ?>? Semua papannya juga akan terhapus.')">🗑️ Hapus</a>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Kartu tambah profil baru -->
            <a href="dashboard-profil-tambah.php" class="kartu-tambah">
                <span class="ikon-plus">➕</span>
                <span class="teks">Tambah Profil Baru</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- ── SEKSI: Preset Papan ── -->
    <div class="preset-section">
        <div class="section-header">
            <div class="section-judul">📋 Papan Siap Pakai</div>
        </div>
        <div class="preset-grid">
            <?php
            $preset_ikon = [
                'Kebutuhan Dasar'   => ['🤲', 'Langsung pakai tanpa setup'],
                'Di Perpustakaan'   => ['📚', 'Khusus kunjungan perpustakaan'],
                'Perasaanku'        => ['😊', 'Ekspresi perasaan dasar'],
                'Darurat'           => ['🚨', 'Situasi darurat dan bantuan'],
            ];
            foreach ($preset_list as $preset):
                $info = $preset_ikon[$preset['nama_papan']] ?? ['📋', 'Papan komunikasi'];
            ?>
            <a href="../papan/index.php?papan_id=<?= $preset['id'] ?>" 
               class="kartu-preset">
                <span class="preset-ikon"><?= $info[0] ?></span>
                <div class="preset-info">
                    <div class="nama"><?= htmlspecialchars($preset['nama_papan']) ?></div>
                    <div class="sub"><?= $info[1] ?></div>
                </div>
                <span class="preset-panah">→</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

</div><!-- /main -->

<!-- ── Lightbox Container ── -->
<div id="lightbox-foto" class="lightbox" onclick="tutupLightbox()">
    <span class="lightbox-close">&times;</span>
    <img id="lightbox-img" src="" alt="Perbesaran Foto Wajah" onclick="event.stopPropagation()">
</div>

<?php include '../inc/footer.php'; ?>

<script>
function bukaLightbox(srcUrl) {
    const lightbox = document.getElementById('lightbox-foto');
    document.getElementById('lightbox-img').src = srcUrl;
    lightbox.style.display = 'flex';
    // Gunakan timeout super kecil untuk memancing animasi transisi CSS
    setTimeout(() => { lightbox.classList.add('tampil'); }, 10);
}
function tutupLightbox() {
    const lightbox = document.getElementById('lightbox-foto');
    lightbox.classList.remove('tampil');
    setTimeout(() => { lightbox.style.display = 'none'; }, 300); // 300ms sesuai durasi transisi
}
</script>

</body>
</html>
