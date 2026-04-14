<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';

// Cek apakah login sebagai ABK
$profil_id = $_SESSION['profil_id'] ?? 0;
$papan_id  = (int)($_GET['papan_id'] ?? 0);

// Jika tidak ada papan_id, cari papan favorit milik profil ini
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

// Ambil semua simbol di papan ini
$stmt_s = $conn->prepare("SELECT * FROM papan_simbol WHERE papan_id = ? ORDER BY urutan ASC");
$stmt_s->bind_param('i', $papan_id);
$stmt_s->execute();
$simbols = $stmt_s->get_result()->fetch_all(MYSQLI_ASSOC);

// ==========================================
// FITUR BARU: Ambil semua papan milik ABK ini
// ==========================================
$stmt_all = $conn->prepare("SELECT id, nama_papan, is_favorit FROM papan WHERE profil_id = ? ORDER BY is_favorit DESC, nama_papan ASC");
$stmt_all->bind_param('i', $profil_id);
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
        body { background: #fafafa; font-family: 'Baloo 2', cursive; margin: 0; padding: 0; }
        
        .papan-komunikasi {
            display: grid;
            grid-template-columns: repeat(<?= $grid_cols ?>, 1fr);
            gap: 12px;
            padding: 15px;
            height: 90vh;
            overflow-y: auto;
            align-content: start;
        }

        .kartu-suara {
            background: white;
            border: 4px solid #eee;
            border-radius: 20px;
            aspect-ratio: 1; 
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px;
            cursor: pointer;
            transition: all 0.1s;
            box-shadow: 0 4px 0 #ddd;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .kartu-suara:active { transform: translateY(4px); box-shadow: 0 0 0 #ddd; border-color: #4ECDC4; }
        .kartu-suara img { max-width: 80%; max-height: 60%; object-fit: contain; margin-bottom: 8px; border-radius: 10px; }
        .kartu-suara span { font-size: 1.1rem; font-weight: 800; color: #333; text-align: center; line-height: 1.1; }

        /* Navigasi Bawah */
        .nav-bawah {
            height: 10vh;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            border-top: 2px solid #eee;
            position: fixed;
            bottom: 0;
            width: 100%;
            z-index: 100;
        }
        
        .btn-kembali { background: #FF6B6B; color: white; border-radius: 50px; padding: 8px 20px; text-decoration: none; font-weight: 800; }
        
        /* Tombol Pilih Papan */
        .btn-pilih-papan {
            background: #4ECDC4;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 800;
            font-size: 1rem;
            box-shadow: 0 4px 0 #3b9b94;
            transition: transform 0.1s;
        }
        .btn-pilih-papan:active { transform: translateY(4px); box-shadow: 0 0 0 #3b9b94; }
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

<div class="nav-bawah">
    <a href="../logout.php?jenis=abk" class="btn-kembali">🔙 Keluar</a>
    
    <button class="btn-pilih-papan" data-bs-toggle="modal" data-bs-target="#modalPilihPapan">
        📂 Papan: <?= htmlspecialchars($papan['nama_papan']) ?>
    </button>
</div>

<div class="modal fade" id="modalPilihPapan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 24px; border: none;">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-bold" style="font-family: 'Baloo 2', cursive;">Pilih Papan Lain</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-grid gap-3">
                    <?php foreach($semua_papan as $p): ?>
                        <a href="index.php?papan_id=<?= $p['id'] ?>" 
                           class="btn btn-lg fw-bold py-3 text-start" 
                           style="border-radius: 16px; font-family: 'Baloo 2', cursive; <?= $p['id'] == $papan_id ? 'background: #4ECDC4; color: white;' : 'background: #f8fafc; color: #333; border: 2px solid #e2e8f0;' ?>">
                            
                            <?= $p['is_favorit'] ? '⭐ ' : '📂 ' ?>
                            <?= htmlspecialchars($p['nama_papan']) ?>
                            
                            <?php if($p['id'] == $papan_id): ?>
                                <span class="badge bg-white text-dark float-end mt-1">Aktif</span>
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