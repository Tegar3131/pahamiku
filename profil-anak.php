<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'inc/config.php';

$is_logged_in = isset($_SESSION['user_id']);
$logged_in_id = $is_logged_in ? $_SESSION['user_id'] : 0;

$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($target_id === 0) {
    redirect('komunitas.php');
}

// ── AMBIL DATA PROFIL ANAK & PENDAMPINGNYA ──
$stmt = $conn->prepare("
    SELECT pr.*, u.nama as nama_pendamping, u.foto_profil as foto_pendamping 
    FROM profil_abk pr 
    JOIN users u ON pr.user_id = u.id 
    WHERE pr.id = ?
");
$stmt->bind_param('i', $target_id);
$stmt->execute();
$anak = $stmt->get_result()->fetch_assoc();

if (!$anak) {
    setFlash('error', 'Profil anak tidak ditemukan.');
    redirect('komunitas.php');
}

// ── LOGIKA PRIVASI ──
// Jika profil ini PRIVATE dan yang mengakses BUKAN orang tuanya/pembuatnya
if ($anak['is_public'] == 0 && $anak['user_id'] != $logged_in_id) {
    setFlash('error', 'Akses Ditolak: Profil anak ini bersifat Privat dan hanya bisa diakses oleh pendampingnya.');
    redirect('komunitas.php');
}

// ── AMBIL PAPAN AAC YANG DIBUAT UNTUK ANAK INI ──
// Jika yang melihat adalah pembuatnya, tampilkan semua (publik & privat).
// Jika yang melihat orang lain, tampilkan hanya yang publik.
$sql_b = "SELECT p.id, p.nama_papan, p.ikon_papan, p.grid, p.kategori, p.deskripsi, p.access_type 
          FROM papan p 
          WHERE p.profil_id = ?";
if ($anak['user_id'] != $logged_in_id) {
    $sql_b .= " AND p.access_type = 'public'";
}
$sql_b .= " ORDER BY p.id DESC";

$stmt_b = $conn->prepare($sql_b);
$stmt_b->bind_param('i', $target_id);
$stmt_b->execute();
$boards = $stmt_b->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Anak: <?= htmlspecialchars($anak['nama']) ?> - PAHAMIKU</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --biru:       #4ECDC4;
            --putih:      #F8FAFC;
            --gelap:      #0F172A;
            --abu:        #64748B;
            --abu-muda:   #E2E8F0;
            --radius-lg:  20px;
        }
        body { font-family: 'Nunito', sans-serif; background-color: var(--putih); color: var(--gelap); min-height: 100vh; display: flex; flex-direction: column; }
        
        .layout-container { max-width: 900px; margin: 40px auto; padding: 0 20px; width: 100%; flex: 1; }
        
        /* HEADER ANAK */
        .child-header { background: white; border-radius: 24px; padding: 30px; border: 1px solid var(--abu-muda); box-shadow: 0 10px 30px rgba(0,0,0,0.03); display: flex; gap: 30px; margin-bottom: 30px; position: relative; overflow: hidden;}
        .child-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 8px; background: linear-gradient(90deg, #FDE68A, #FBCFE8, #A7F3D0); }
        
        @media (max-width: 700px) { .child-header { flex-direction: column; align-items: center; text-align: center; } }

        .child-avatar { width: 140px; height: 140px; border-radius: 24px; background: #FFFBEB; display: flex; align-items: center; justify-content: center; font-size: 60px; font-family: 'Baloo 2', cursive; color: #D97706; box-shadow: 0 4px 10px rgba(0,0,0,0.05); object-fit: cover; flex-shrink: 0;}
        .child-info { flex: 1; }
        
        .child-name { font-size: 32px; font-family: 'Baloo 2', cursive; font-weight: 800; margin-bottom: 4px; display:flex; align-items:center; gap:8px;}
        @media (max-width: 700px) { .child-name { justify-content: center; } }
        
        .badge-status { font-size: 11px; padding: 4px 10px; border-radius: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;}
        .bg-public { background: #D1FAE5; color: #065F46; }
        .bg-private { background: #FEE2E2; color: #991B1B; }

        .child-caregiver { display: inline-flex; align-items: center; gap: 8px; background: #F8FAFC; padding: 6px 12px; border-radius: 50px; font-size: 14px; font-weight: 700; color: var(--abu); margin-bottom: 20px; border: 1px solid var(--abu-muda); text-decoration: none; transition: 0.2s;}
        .child-caregiver:hover { background: #F1F5F9; border-color: #CBD5E1; }
        .caregiver-ava { width: 24px; height: 24px; border-radius: 50%; background: #E0F2FE; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #0369A1; object-fit: cover;}

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;}
        @media (max-width: 500px) { .info-grid { grid-template-columns: 1fr; } }
        .info-item { background: #F8FAFC; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--abu-muda); }
        .info-label { font-size: 12px; color: var(--abu); font-weight: 800; text-transform: uppercase; margin-bottom: 4px; display: block;}
        .info-val { font-size: 16px; color: var(--gelap); font-weight: 700; }
        
        .child-desc { font-size: 15px; line-height: 1.6; color: #334155; margin: 0; background: #F0FDF4; padding: 16px; border-radius: 12px; border-left: 4px solid #4ADE80;}

        /* BOARD GRID */
        .section-title { font-family: 'Baloo 2', cursive; font-size: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;}
        
        .board-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .board-card { background: white; border: 1px solid var(--abu-muda); border-radius: var(--radius-lg); overflow: hidden; transition: 0.2s; display: flex; flex-direction: column;}
        .board-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-color: var(--biru); }
        .board-ikon-area { background: #F8FAFC; height: 140px; display: flex; align-items: center; justify-content: center; font-size: 60px; border-bottom: 1px solid var(--abu-muda); position:relative;}
        .board-badge { position:absolute; top:12px; right:12px; font-size:10px; padding:4px 8px; border-radius:8px; font-weight:800; text-transform:uppercase; background:white; box-shadow:0 2px 5px rgba(0,0,0,0.1);}
        
        .board-info { padding: 20px; flex: 1; display: flex; flex-direction: column;}
        .board-title { font-size: 18px; font-weight: 800; margin-bottom: 8px; font-family: 'Baloo 2', cursive; color: var(--gelap);}
        .board-desc { font-size: 14px; color: var(--abu); margin-bottom: 16px; line-height: 1.5; flex: 1;}
        .board-meta { font-size: 12px; font-weight: 700; color: #94A3B8; display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;}
        .board-meta span { background: #F1F5F9; padding: 4px 10px; border-radius: 8px; }
        
        .btn-view-board { background: var(--gelap); color: white; text-align: center; padding: 12px; border-radius: 12px; text-decoration: none; font-weight: 800; transition: 0.2s; display:block;}
        .btn-view-board:hover { background: #1E293B; }
        
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 20px; border: 1px solid var(--abu-muda); grid-column: 1/-1;}
    </style>
</head>
<body>

<?php include 'inc/navbar.php'; ?>

<div class="layout-container">
    
    <?php tampilFlash(); ?>

    <!-- HEADER ANAK -->
    <div class="child-header">
        <?php if (!empty($anak['foto_profil'])): ?>
            <img src="uploads/anak/<?= htmlspecialchars($anak['foto_profil']) ?>" class="child-avatar">
        <?php else: ?>
            <div class="child-avatar"><?= strtoupper(substr($anak['nama'], 0, 1)) ?></div>
        <?php endif; ?>
        
        <div class="child-info">
            <h1 class="child-name">
                <?= htmlspecialchars($anak['nama']) ?>
                <span class="badge-status <?= $anak['is_public'] ? 'bg-public' : 'bg-private' ?>">
                    <?= $anak['is_public'] ? '🌐 Profil Publik' : '🔒 Profil Privat' ?>
                </span>
            </h1>
            
            <a href="profil-kreator.php?id=<?= $anak['user_id'] ?>" class="child-caregiver" title="Lihat Profil Pendamping">
                <span>Didampingi oleh:</span>
                <?php if (!empty($anak['foto_pendamping'])): ?>
                    <img src="uploads/profil/<?= htmlspecialchars($anak['foto_pendamping']) ?>" class="caregiver-ava">
                <?php else: ?>
                    <div class="caregiver-ava"><?= strtoupper(substr($anak['nama_pendamping'], 0, 1)) ?></div>
                <?php endif; ?>
                <span style="color:var(--gelap);"><?= htmlspecialchars($anak['nama_pendamping']) ?></span>
            </a>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Kategori Usia</span>
                    <span class="info-val"><?= htmlspecialchars($anak['kategori_usia'] ?? 'Belum diatur') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kebutuhan Utama</span>
                    <span class="info-val"><?= htmlspecialchars($anak['kebutuhan_komunikasi'] ?? 'Belum diatur') ?></span>
                </div>
            </div>

            <?php if (!empty($anak['deskripsi_singkat'])): ?>
                <p class="child-desc">"<?= nl2br(htmlspecialchars($anak['deskripsi_singkat'])) ?>"</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- DAFTAR PAPAN -->
    <h2 class="section-title">📋 Koleksi Papan AAC (<?= count($boards) ?>)</h2>
    
    <div class="board-grid">
        <?php if (empty($boards)): ?>
            <div class="empty-state">
                <span style="font-size:50px;">📦</span>
                <h3 style="font-family:'Baloo 2', cursive; font-size:24px; margin-top:10px;">Belum ada papan yang dibagikan</h3>
                <p style="color:var(--abu); font-weight:600;">Papan AAC untuk profil ini yang di-set Publik akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <?php foreach($boards as $b): ?>
            <div class="board-card">
                <div class="board-ikon-area">
                    <?= !empty($b['ikon_papan']) ? htmlspecialchars($b['ikon_papan']) : '📋' ?>
                    <?php if($anak['user_id'] == $logged_in_id): ?>
                        <span class="board-badge <?= $b['access_type'] == 'public' ? 'bg-public' : 'bg-private' ?>">
                            <?= $b['access_type'] == 'public' ? 'Publik' : 'Privat' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="board-info">
                    <h4 class="board-title"><?= htmlspecialchars($b['nama_papan']) ?></h4>
                    <p class="board-desc"><?= !empty($b['deskripsi']) ? htmlspecialchars(substr($b['deskripsi'], 0, 80)).'...' : 'Tidak ada deskripsi.' ?></p>
                    
                    <div class="board-meta">
                        <span>Grid <?= htmlspecialchars($b['grid']) ?></span>
                        <span><?= htmlspecialchars($b['kategori'] ?? 'Umum') ?></span>
                    </div>
                    
                    <a href="papan/index.php?papan_id=<?= $b['id'] ?>" class="btn-view-board">Lihat Papan</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php include 'inc/footer.php'; ?>
</body>
</html>
