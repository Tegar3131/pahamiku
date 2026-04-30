<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';

$profil_id = $_SESSION['profil_id'] ?? 0;
$papan_id  = (int)($_GET['papan_id'] ?? 0);
$preset    = $_GET['preset'] ?? ''; // Ambil parameter preset

// LOGIKA BARU: Jika ada parameter 'preset', cari ID papan globalnya
if ($preset) {
    $nama_preset = '';
    if ($preset == 'dasar') $nama_preset = 'Kebutuhan Dasar';
    if ($preset == 'perpustakaan') $nama_preset = 'Di Perpustakaan';
    if ($preset == 'perasaan') $nama_preset = 'Perasaanku';
    if ($preset == 'darurat') $nama_preset = 'Darurat';

    $stmt_pre = $conn->prepare("SELECT id FROM papan WHERE nama_papan = ? AND profil_id IS NULL LIMIT 1");
    $stmt_pre->bind_param('s', $nama_preset);
    $stmt_pre->execute();
    $res_pre = $stmt_pre->get_result()->fetch_assoc();
    if ($res_pre) $papan_id = $res_pre['id'];
}

// Jika masih tidak ada papan_id dan user sudah login, cari favorit mereka
if (!$papan_id && $profil_id) {
    $stmt = $conn->prepare("SELECT id FROM papan WHERE profil_id = ? ORDER BY is_favorit DESC, created_at DESC LIMIT 1");
    $stmt->bind_param('i', $profil_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $papan_id = $res['id'] ?? 0;
}

// Ambil info papan yang sedang aktif
$stmt_p = $conn->prepare("SELECT * FROM papan WHERE id = ?");
$stmt_p->bind_param('i', $papan_id);
$stmt_p->execute();
$papan = $stmt_p->get_result()->fetch_assoc();

if (!$papan) {
    if ($papan_id === 1) {
        die("Papan komunikasi belum tersedia. Harap hubungi pendamping untuk membuat papan baru.");
    }
    header('Location: index.php?papan_id=1');
    exit;
}

// ==========================================
// LOGIKA AKSES (Private vs Public)
// ==========================================
$akses = $papan['access_type'] ?? 'private';
if ($akses === 'private') {
    $id_user_login = $_SESSION['user_id'] ?? 0;
    $id_abk_login  = $_SESSION['profil_id'] ?? 0;
    $boleh_akses   = false;

    // 1. Jika pengguna adalah ABK yang memiliki papan ini
    if ($id_abk_login && $id_abk_login == $papan['profil_id']) {
        $boleh_akses = true;
    }
    // 2. Jika pengguna adalah pendamping yang memiliki profil ABK ini
    elseif ($id_user_login && $papan['profil_id']) {
        $stmt_cek = $conn->prepare("SELECT id FROM profil_abk WHERE id = ? AND user_id = ?");
        $stmt_cek->bind_param('ii', $papan['profil_id'], $id_user_login);
        $stmt_cek->execute();
        if ($stmt_cek->get_result()->num_rows > 0) {
            $boleh_akses = true;
        }
    }
    // 3. Papan global (preset) tanpa profil_id
    elseif ($papan['profil_id'] === null) {
        $boleh_akses = true;
    }

    if (!$boleh_akses) {
        echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>
                <h2>🔒 Papan ini bersifat Privat</h2>
                <p>Maaf, Anda tidak memiliki izin untuk melihat papan ini.</p>
                <a href='../index.php'>Kembali ke Beranda</a>
              </div>";
        exit;
    }
}

// Ambil semua simbol di papan ini
$stmt_s = $conn->prepare("SELECT * FROM papan_simbol WHERE papan_id = ? ORDER BY urutan ASC");
$stmt_s->bind_param('i', $papan_id);
$stmt_s->execute();
$simbols = $stmt_s->get_result()->fetch_all(MYSQLI_ASSOC);

// ==========================================
// Ambil semua papan: milik ABK ini + papan global (preset)
// ==========================================
if ($profil_id) {
    // User login: tampilkan papan milik mereka + papan global
    $stmt_all = $conn->prepare("SELECT id, nama_papan, is_favorit, ikon_papan FROM papan WHERE profil_id = ? OR profil_id IS NULL ORDER BY is_favorit DESC, nama_papan ASC");
    $stmt_all->bind_param('i', $profil_id);
} else {
    // Guest/preset: hanya tampilkan papan global
    $stmt_all = $conn->prepare("SELECT id, nama_papan, is_favorit, ikon_papan FROM papan WHERE profil_id IS NULL ORDER BY nama_papan ASC");
}
$stmt_all->execute();
$semua_papan = $stmt_all->get_result()->fetch_all(MYSQLI_ASSOC);

$grid_raw = !empty($papan['grid']) ? $papan['grid'] : '3x3';
$grid_cols = explode('x', $grid_raw)[0];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($papan['nama_papan']) ?> — PAHAMIKU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style> 
    /* ===== RESET & BODY ===== */
    body {
        margin: 0;
        padding: 0;
        background-color: #F0FDF9;
        font-family: 'Baloo 2', cursive;
        user-select: none;
        padding-bottom: 90px;
    }

    /* ===== GRID PAPAN KOMUNIKASI ===== */
    .papan-komunikasi {
        display: grid;
        grid-template-columns: repeat(<?= $grid_cols ?>, 1fr);
        gap: 16px;
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    @media (max-width: 768px) {
        .papan-komunikasi {
            gap: 12px;
            padding: 12px;
        }
    }

    /* ===== KARTU SUARA / SIMBOL ===== */
    .kartu-suara {
        background: #FFFFFF;
        border: 4px solid #D1D5DB;
        border-radius: 20px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        cursor: pointer;
        box-shadow: 0 6px 0 #D1D5DB;
        transition: transform 0.08s ease, box-shadow 0.08s ease, border-color 0.08s ease;
        aspect-ratio: 1 / 1;
        color: #1A1A2E;
    }

    .kartu-suara:active {
        transform: translateY(6px);
        box-shadow: 0 0 0 transparent;
        border-color: #4ECDC4;
    }

    .kartu-suara img {
        max-width: 70%;
        max-height: 55%;
        object-fit: contain;
        margin-bottom: 10px;
        pointer-events: none;
    }

    .kartu-suara span {
        font-weight: 800;
        font-size: clamp(1rem, 3vw, 1.4rem);
        line-height: 1.2;
        word-break: break-word;
        pointer-events: none;
    }

     /* ===== NAVIGASI BAWAH ABK ===== */
.nav-bawah {
    height: 80px;
    background: #FFFDF7;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    border-top: 4px solid #E5E7EB;
    position: fixed;
    bottom: 0;
    width: 100%;
    z-index: 100;
    gap: 12px;
}

/* Tombol generik bergaya 3D */
.btn-nav {
    border: none;
    border-radius: 22px;
    padding: 14px 22px;
    font-weight: 800;
    font-size: 1.1rem;
    font-family: 'Baloo 2', cursive;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 56px;
    transition: transform 0.08s ease, box-shadow 0.08s ease;
    text-decoration: none;
    white-space: nowrap;
}
.btn-nav:active {
    transform: translateY(5px);
    box-shadow: 0 0 0 transparent !important;
}

@media (max-width: 480px) {
    .nav-bawah { padding: 0 12px; gap: 8px; }
    .btn-nav { padding: 8px 12px; font-size: 0.95rem; min-height: 48px; white-space: normal; line-height: 1.2; }
    .btn-keluar { min-width: 100px; }
    .btn-nav .ikon-btn { font-size: 1.2rem; }
}

.btn-keluar {
    background: #FF6B6B;
    color: white;
    box-shadow: 0 5px 0 #C0392B;
    min-width: 130px;
    justify-content: center;
}

.btn-pilih-papan {
    background: #FFD93D;
    color: #1A1A2E;
    box-shadow: 0 5px 0 #C9A300;
    flex: 1;
    justify-content: center;
}

.btn-nav .ikon-btn { font-size: 1.5rem; line-height: 1; }

/* ===== MODAL PILIH PAPAN (Bottom Sheet) ===== */
.modal-abk .modal-dialog {
    margin: 0;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    max-width: 100%;
    width: 100%;
}

.modal-abk .modal-content {
    border-radius: 32px 32px 0 0;
    border: 4px solid #4ECDC4;
    border-bottom: none;
    background: #F0FDF9;
    max-height: 88vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-abk .modal-header {
    border-bottom: 3px dashed #4ECDC4;
    padding: 20px 20px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    background: #F0FDF9;
    position: sticky;
    top: 0;
    z-index: 5;
}

.modal-abk .modal-title {
    font-size: 1.5rem;
    color: #0F5E56;
    display: flex;
    align-items: center;
    gap: 10px;
}
.modal-abk .modal-title .ikon-judul { font-size: 1.8rem; }

.modal-abk .btn-close-custom {
    background: #FF6B6B;
    color: white;
    border: none;
    border-radius: 50%;
    width: 52px;
    height: 52px;
    font-size: 1.6rem;
    font-weight: 800;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 0 #C0392B;
    transition: transform 0.08s ease, box-shadow 0.08s ease;
    flex-shrink: 0;
    line-height: 1;
}
.modal-abk .btn-close-custom:active {
    transform: translateY(4px);
    box-shadow: 0 0 0 #C0392B;
}

.modal-abk .modal-body {
    overflow-y: auto;
    padding: 18px;
    -webkit-overflow-scrolling: touch;
}

/* Grid kartu papan */
.grid-pilihan-papan {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}

.kartu-papan-abk {
    background: white;
    border: 4px solid #D1D5DB;
    border-radius: 24px;
    padding: 20px 14px 16px;
    text-align: center;
    text-decoration: none;
    color: #1A1A2E;
    font-weight: 800;
    font-size: 1.1rem;
    font-family: 'Baloo 2', cursive;
    box-shadow: 0 6px 0 #D1D5DB;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    line-height: 1.2;
    transition: transform 0.08s ease, box-shadow 0.08s ease;
    min-height: 120px;
    justify-content: center;
}
.kartu-papan-abk:hover { color: #1A1A2E; text-decoration: none; }
.kartu-papan-abk:active {
    transform: translateY(6px);
    box-shadow: 0 0 0 #D1D5DB;
    border-color: #4ECDC4;
}
.kartu-papan-abk.aktif {
    background: #4ECDC4;
    color: white;
    border-color: #2A9E96;
    box-shadow: 0 6px 0 #1E7D76;
}
.kartu-papan-abk.aktif:active {
    box-shadow: 0 0 0 #1E7D76;
}

.kartu-papan-abk .ikon-papan { font-size: 2.8rem; line-height: 1; }

.badge-aktif {
    background: rgba(255,255,255,0.9);
    color: #0F5E56;
    font-size: 0.72rem;
    padding: 4px 12px;
    border-radius: 12px;
    font-weight: 800;
}
.kartu-papan-abk.aktif .badge-aktif {
    background: rgba(255,255,255,0.25);
    color: white;
}

/* Responsif untuk HP kecil: 1 kolom */
@media (max-width: 400px) {
    .grid-pilihan-papan { grid-template-columns: 1fr; }
}
    </style>
</head>
<body>

<div class="papan-komunikasi">
    <?php foreach ($simbols as $s): 
        $parts = explode('|', $s['label_custom']);
        $tag_gambar = $parts[0];
        $suara_tts = isset($parts[1]) ? $parts[1] : $tag_gambar;

        $id_simbol = $s['simbol_id'];
        if (strpos($id_simbol, 'emoji-') === 0) {
            $ikon = str_replace('emoji-', '', $id_simbol);
            $elemen_visual = '<div style="font-size: 55px; line-height: 1; margin-bottom: 8px;">' . htmlspecialchars($ikon) . '</div>';
        } elseif (strpos($id_simbol, 'upload-') === 0) {
            $elemen_visual = '<img src="../uploads/' . htmlspecialchars($id_simbol) . '" alt="">';
        } else {
            $elemen_visual = '<img src="https://api.arasaac.org/api/pictograms/' . htmlspecialchars($id_simbol) . '" alt="">';
        }
    ?>
        <div class="kartu-suara" onclick="bicara('<?= addslashes($suara_tts) ?>')">
            <?= $elemen_visual ?>
            <span><?= htmlspecialchars($tag_gambar) ?></span>
        </div>
    <?php endforeach; ?>
    <div style="height: 10vh; width: 100%; grid-column: 1 / -1;"></div>
</div>

<div class="nav-bawah" <?= $profil_id ? 'style="justify-content: center;"' : '' ?>>
    <?php if (!$profil_id): ?>
    <a href="<?= BASE_URL ?>index.php" class="btn-nav btn-keluar" style="background:#4ECDC4; box-shadow:0 5px 0 #2A9E96;">
        <span class="ikon-btn">🏠</span> Beranda
    </a>
    <?php endif; ?>
    
    <?php if (($papan['access_type'] ?? 'private') === 'public' && $papan['profil_id'] !== null && !$profil_id): ?>
        <a href="<?= BASE_URL ?>dashboard/papan-duplikat.php?papan_id=<?= $papan_id ?>" class="btn-nav btn-pilih-papan" style="background:#4ECDC4; color:white; box-shadow:0 5px 0 #2A9E96; text-decoration:none;">
            <span class="ikon-btn">⬇️</span> Salin Papan Ini
        </a>
    <?php elseif ($profil_id): ?>
        <a href="pilih.php" class="btn-nav btn-pilih-papan" aria-label="Ganti papan komunikasi">
            <span class="ikon-btn">📂</span> Ganti Papan
        </a>
    <?php else: ?>
        <button class="btn-nav btn-pilih-papan"
                data-bs-toggle="modal"
                data-bs-target="#modalPilihPapan"
                aria-label="Ganti papan komunikasi">
            <span class="ikon-btn">📂</span> Ganti Papan
        </button>
    <?php endif; ?>
</div>

<div class="modal fade modal-abk" 
     id="modalPilihPapan" 
     tabindex="-1" 
     aria-labelledby="judulModalPapan"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title fw-bold" 
                    id="judulModalPapan"
                    style="font-family: 'Baloo 2', cursive;">
                    <span class="ikon-judul">🗣️</span> Mau bicara apa?
                </h3>
                <button type="button" 
                        class="btn-close-custom" 
                        data-bs-dismiss="modal" 
                        aria-label="Tutup">
                    ✕
                </button>
            </div>
            <div class="modal-body">
                <div class="grid-pilihan-papan">
                    <?php foreach($semua_papan as $p): 
                        $is_active = ($p['id'] == $papan_id);
                        
                        // Jika favorit tampilkan bintang, jika tidak, panggil langsung dari database
                        if ($p['is_favorit']) {
                            $ikon = '⭐';
                        } else {
                            $ikon = !empty($p['ikon_papan']) ? $p['ikon_papan'] : '📋';
                        }
                    ?>
                        <a href="index.php?papan_id=<?= $p['id'] ?>" 
                           class="kartu-papan-abk <?= $is_active ? 'aktif' : '' ?>"
                           aria-label="<?= htmlspecialchars($p['nama_papan']) ?><?= $is_active ? ', sedang dipakai' : '' ?>">
                            <span class="ikon-papan" aria-hidden="true"><?= $ikon ?></span>
                            <span><?= htmlspecialchars($p['nama_papan']) ?></span>
                            <?php if($is_active): ?>
                                <span class="badge-aktif">✔ Sedang Dipakai</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
let ucapanAktif = null;

if ('speechSynthesis' in window) {
    window.speechSynthesis.getVoices();
}

function bicara(teks) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        
        ucapanAktif = new SpeechSynthesisUtterance(teks);
        ucapanAktif.lang = 'id-ID'; 
        ucapanAktif.rate = 0.85; 
        ucapanAktif.pitch = 1.1; 
        
        let voices = window.speechSynthesis.getVoices();
        let voiceIndo = voices.find(v => v.lang === 'id-ID' || v.lang === 'id_ID');
        if (voiceIndo) {
            ucapanAktif.voice = voiceIndo;
        }
        
        window.speechSynthesis.speak(ucapanAktif);
    } else {
        alert("Browser Anda tidak mendukung fitur Suara.");
    }
}
</script>

</body>
</html>