<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'inc/config.php';

$is_logged_in = isset($_SESSION['user_id']);
$logged_in_id = $is_logged_in ? $_SESSION['user_id'] : 0;

$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($target_id === 0) {
    redirect('komunitas.php');
}

// ── AMBIL DATA PROFIL PENDAMPING ──
$stmt = $conn->prepare("
    SELECT u.id, u.nama, u.foto_profil, u.peran, u.bio, u.created_at,
           (SELECT COUNT(*) FROM user_follows WHERE following_id = u.id) as total_followers,
           (SELECT COUNT(*) FROM user_follows WHERE follower_id = u.id) as total_following,
           (SELECT COUNT(*) FROM user_follows WHERE follower_id = ? AND following_id = u.id) as is_followed
    FROM users u 
    WHERE u.id = ?
");
$stmt->bind_param('ii', $logged_in_id, $target_id);
$stmt->execute();
$profil = $stmt->get_result()->fetch_assoc();

if (!$profil) {
    setFlash('error', 'Profil tidak ditemukan.');
    redirect('komunitas.php');
}

// ── AMBIL POSTINGAN DARI USER INI ──
$stmt_p = $conn->prepare("
    SELECT post.id AS post_id, post.isi_teks, post.created_at, 
           u.id as kreator_id, u.nama AS pembuat, u.foto_profil, u.peran,
           p.id AS papan_id, p.nama_papan, p.ikon_papan, p.grid, p.kategori,
           (SELECT COUNT(*) FROM postingan_suka ps WHERE ps.post_id = post.id) as total_likes,
           (SELECT COUNT(*) FROM postingan_suka ps2 WHERE ps2.post_id = post.id AND ps2.user_id = ?) as is_liked,
           (SELECT COUNT(*) FROM postingan_komentar pk WHERE pk.post_id = post.id) as total_komentar
    FROM postingan post
    JOIN users u ON post.user_id = u.id
    LEFT JOIN papan p ON post.papan_id = p.id
    WHERE post.user_id = ?
    ORDER BY post.created_at DESC
");
$stmt_p->bind_param('ii', $logged_in_id, $target_id);
$stmt_p->execute();
$posts = $stmt_p->get_result()->fetch_all(MYSQLI_ASSOC);

// ── AMBIL PAPAN AAC PUBLIK DARI USER INI ──
$stmt_b = $conn->prepare("
    SELECT p.id, p.nama_papan, p.ikon_papan, p.grid, p.kategori, p.deskripsi, 
           pr.id as anak_id, pr.nama as nama_anak, pr.is_public as anak_is_public
    FROM papan p 
    JOIN profil_abk pr ON p.profil_id = pr.id 
    WHERE pr.user_id = ? AND p.access_type = 'public'
    ORDER BY p.id DESC
");
$stmt_b->bind_param('i', $target_id);
$stmt_b->execute();
$boards = $stmt_b->get_result()->fetch_all(MYSQLI_ASSOC);

$usr = $is_logged_in ? getUserLogin($conn) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil <?= htmlspecialchars($profil['nama']) ?> - PAHAMIKU</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --biru:       #4ECDC4;
            --biru-tua:   #2BB5AC;
            --putih:      #F8FAFC;
            --putih-card: #FFFFFF;
            --gelap:      #0F172A;
            --border:     #EFF3F4;
            --abu:        #64748B;
            --abu-muda:   #E2E8F0;
            --radius-lg:  20px;
            --shadow:     0 4px 12px rgba(0,0,0,0.05);
        }
        body { font-family: 'Nunito', sans-serif; background-color: var(--putih); color: var(--gelap); min-height: 100vh; display: flex; flex-direction: column; }
        
        /* HEADER PROFIL */
        .profile-header-container { background: white; border-bottom: 1px solid var(--abu-muda); padding-bottom: 30px; }
        .profile-cover { height: 200px; background: linear-gradient(135deg, #A7F3D0 0%, #BAE6FD 100%); width: 100%; }
        .profile-content-wrap { max-width: 1000px; margin: 0 auto; padding: 0 20px; position: relative; display: flex; gap: 30px; align-items: flex-start; margin-top: -60px; }
        @media (max-width: 768px) {
            .profile-content-wrap { flex-direction: column; align-items: center; text-align: center; margin-top: -80px; gap: 16px;}
        }

        .profile-avatar-box { flex-shrink: 0; }
        .profile-avatar-img { width: 150px; height: 150px; border-radius: 50%; border: 6px solid white; background: #E0F2FE; display: flex; align-items: center; justify-content: center; font-size: 50px; font-weight: 900; color: #0369A1; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.1);}
        
        .profile-info-box { flex: 1; padding-top: 75px; }
        @media (max-width: 768px) { .profile-info-box { padding-top: 10px; } }
        .profile-name { font-size: 28px; font-family: 'Baloo 2', cursive; font-weight: 800; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
        @media (max-width: 768px) { .profile-name { justify-content: center; } }
        .badge-peran { font-size: 13px; padding: 4px 12px; background: #E0F2FE; color: #0284C7; border-radius: 12px; font-weight: 800; text-transform: uppercase; font-family: 'Nunito', sans-serif;}
        
        .profile-bio { font-size: 16px; color: var(--abu); margin-bottom: 16px; font-weight: 600; line-height: 1.5; max-width: 600px;}
        
        .profile-stats { display: flex; gap: 24px; align-items: center; flex-wrap: wrap;}
        @media (max-width: 768px) { .profile-stats { justify-content: center; } }
        .stat-item { display: flex; flex-direction: column; }
        .stat-val { font-size: 20px; font-weight: 900; color: var(--gelap); }
        .stat-label { font-size: 13px; color: var(--abu); font-weight: 700; text-transform: uppercase;}
        
        .profile-action-box { padding-top: 75px; }
        @media (max-width: 768px) { .profile-action-box { padding-top: 10px; width: 100%; } }
        
        .btn-action-main { padding: 12px 30px; border-radius: 12px; font-weight: 800; font-size: 16px; cursor: pointer; border: none; transition: 0.2s; width: 100%; display: block; text-align: center; text-decoration: none;}
        .btn-follow { background: var(--biru); color: white; box-shadow: 0 4px 12px rgba(78,205,196,0.3); }
        .btn-follow:hover { background: var(--biru-tua); transform: translateY(-2px); }
        .btn-follow.following { background: #F1F5F9; color: var(--gelap); box-shadow: none; border: 1px solid var(--abu-muda); }
        .btn-edit { background: #F1F5F9; color: var(--gelap); border: 1px solid var(--abu-muda); }
        .btn-edit:hover { background: #E2E8F0; }

        /* KONTEN BAWAH (TABS & GRID) */
        .content-layout { max-width: 1000px; margin: 30px auto; padding: 0 20px; width: 100%; display: grid; grid-template-columns: 1fr; gap: 24px; flex: 1;}
        
        .tab-container { display: flex; gap: 16px; border-bottom: 2px solid var(--abu-muda); margin-bottom: 20px; }
        .tab-btn { background: none; border: none; padding: 12px 24px; font-family: 'Nunito'; font-size: 18px; font-weight: 800; color: var(--abu); cursor: pointer; border-bottom: 4px solid transparent; margin-bottom: -2px; transition: 0.2s;}
        .tab-btn:hover { color: var(--gelap); }
        .tab-btn.active { color: var(--biru); border-bottom-color: var(--biru); }

        /* POST CARD STYLES */
        .post-card { background: var(--putih-card); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow); border: 1px solid #E2E8F0; margin-bottom: 24px;}
        .post-header { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
        .create-avatar { width: 50px; height: 50px; border-radius: 50%; background: #E0F2FE; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #0369A1; object-fit: cover; font-size:18px; flex-shrink:0;}
        .post-author-name { font-size: 16px; font-weight: 800; color: var(--gelap); margin: 0 0 2px 0; }
        .post-meta { font-size: 13px; color: var(--abu); margin: 0; font-weight:600;}
        .post-body { font-size: 16px; line-height: 1.6; color: #334155; margin-bottom: 16px; white-space: pre-wrap; word-wrap: break-word;}
        
        .post-attachment { display: block; border: 1px solid var(--abu-muda); border-radius: 16px; overflow: hidden; text-decoration: none; color: var(--gelap); margin-bottom: 16px; transition: 0.2s;}
        .post-attachment:hover { background: #F8FAFC; border-color: var(--abu); }
        .attachment-cover { background: #E2E8F0; height: 180px; display: flex; align-items: center; justify-content: center; font-size: 80px; }
        .attachment-footer { padding: 12px 16px; background: white; border-top: 1px solid var(--abu-muda); }
        .attachment-domain { font-size: 12px; color: var(--abu); font-weight: 700; margin-bottom: 4px; display: block; text-transform: uppercase;}
        .attachment-title { font-size: 16px; font-weight: 800; margin: 0 0 4px 0; font-family: 'Nunito', sans-serif;}
        
        .post-actions { display: flex; align-items: center; border-top: 1px solid var(--abu-muda); padding-top: 16px; gap: 12px; }
        .btn-post { flex: 1; padding: 12px; border-radius: 99px; text-align: center; text-decoration: none; font-weight: 800; font-size: 15px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; border:none;}
        .btn-like { background: #FFE4E6; color: #E11D48; }
        .btn-like:hover { background: #FECDD3; }
        .btn-like.liked { background: #F43F5E; color: white; }
        .btn-komen { background: #E0F2FE; color: #0369A1; }
        .btn-action-save { background: #F0FDF4; color: #15803D; }

        /* BOARD GRID STYLES */
        .board-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .board-card { background: white; border: 1px solid var(--abu-muda); border-radius: var(--radius-lg); overflow: hidden; transition: 0.2s; display: flex; flex-direction: column;}
        .board-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-color: var(--biru); }
        .board-ikon-area { background: #F8FAFC; height: 160px; display: flex; align-items: center; justify-content: center; font-size: 70px; border-bottom: 1px solid var(--abu-muda);}
        .board-info { padding: 20px; flex: 1; display: flex; flex-direction: column;}
        .board-title { font-size: 18px; font-weight: 800; margin-bottom: 8px; font-family: 'Baloo 2', cursive; color: var(--gelap);}
        .board-desc { font-size: 14px; color: var(--abu); margin-bottom: 16px; line-height: 1.5; flex: 1;}
        .board-meta { font-size: 12px; font-weight: 700; color: #94A3B8; display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;}
        .board-meta span { background: #F1F5F9; padding: 4px 10px; border-radius: 8px; }
        .btn-view-board { background: var(--gelap); color: white; text-align: center; padding: 12px; border-radius: 12px; text-decoration: none; font-weight: 800; transition: 0.2s;}
        .btn-view-board:hover { background: #1E293B; }
        
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 20px; border: 1px solid var(--abu-muda); }

        /* Modal Komentar Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; display: none; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; }
        .modal-overlay.active { display: flex; opacity: 1; animation: fadeIn 0.2s ease-out;}
        .modal-container { background: white; width: 100%; max-width: 600px; height: 85vh; border-radius: 24px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); transform: translateY(20px); transition: 0.3s;}
        .modal-overlay.active .modal-container { transform: translateY(0); }
        .modal-header { padding: 16px 24px; border-bottom: 1px solid var(--abu-muda); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; font-family: inherit; font-size: 20px; font-weight: 800; }
        .btn-close-modal { background: #F1F5F9; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 16px; font-weight: bold; cursor: pointer; color: var(--gelap); transition: 0.2s;}
        .modal-body { padding: 24px; overflow-y: auto; flex: 1; background: #F8FAFC; position: relative;}
        .modal-footer { padding: 16px 24px; border-top: 1px solid var(--abu-muda); background: white; }
        .komentar-list { display: flex; flex-direction: column; gap: 20px; }
        .komentar-item { display: flex; gap: 12px; }
        .komentar-avatar { width: 44px; height: 44px; border-radius: 50%; background: #E0F2FE; flex-shrink: 0; display:flex; align-items:center; justify-content:center; font-weight:800; color:#0369A1; object-fit:cover;}
        .komentar-bubble { background: white; padding: 12px 16px; border-radius: 4px 16px 16px 16px; border: 1px solid var(--abu-muda); box-shadow: 0 1px 2px rgba(0,0,0,0.02); display: inline-block;}
        .komentar-name { font-size: 14px; font-weight: 800; margin-bottom: 4px; }
        .komentar-text { font-size: 15px; margin: 0; }
        .komentar-time { font-size: 12px; color: var(--abu); margin-top: 6px; display:block; font-weight:600; margin-left: 4px;}
        .komentar-input-area { display: flex; gap: 12px; align-items: flex-end; }
        .komentar-textarea { flex: 1; border: 1px solid var(--abu-muda); border-radius: 20px; padding: 12px 16px; font-family: inherit; font-size: 15px; resize: none; outline: none; background: #F8FAFC; max-height:120px;}
        .btn-send-komentar { background: var(--biru); color: white; border: none; width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; font-size: 18px; flex-shrink:0;}
        
        @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
    </style>
</head>
<body>

<?php include 'inc/navbar.php'; ?>

<!-- HEADER PROFIL -->
<div class="profile-header-container">
    <div class="profile-cover"></div>
    <div class="profile-content-wrap">
        <div class="profile-avatar-box">
            <?php if (!empty($profil['foto_profil'])): ?>
                <img src="uploads/profil/<?= htmlspecialchars($profil['foto_profil']) ?>" class="profile-avatar-img">
            <?php else: ?>
                <div class="profile-avatar-img"><?= strtoupper(substr($profil['nama'], 0, 1)) ?></div>
            <?php endif; ?>
        </div>
        
        <div class="profile-info-box">
            <h1 class="profile-name">
                <?= htmlspecialchars($profil['nama']) ?>
                <?php if(!empty($profil['peran'])): ?>
                    <span class="badge-peran"><?= htmlspecialchars($profil['peran']) ?></span>
                <?php endif; ?>
            </h1>
            <p class="profile-bio"><?= !empty($profil['bio']) ? nl2br(htmlspecialchars($profil['bio'])) : 'Halo! Saya bergabung di Komunitas PAHAMIKU untuk berbagi inspirasi.' ?></p>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-val"><?= $profil['total_followers'] ?></span>
                    <span class="stat-label">Pengikut</span>
                </div>
                <div class="stat-item">
                    <span class="stat-val"><?= $profil['total_following'] ?></span>
                    <span class="stat-label">Mengikuti</span>
                </div>
                <div class="stat-item">
                    <span class="stat-val"><?= count($boards) ?></span>
                    <span class="stat-label">Papan Publik</span>
                </div>
                <div class="stat-item">
                    <span class="stat-val"><?= count($posts) ?></span>
                    <span class="stat-label">Postingan</span>
                </div>
            </div>
        </div>

        <div class="profile-action-box">
            <?php if ($is_logged_in && $profil['id'] == $_SESSION['user_id']): ?>
                <a href="dashboard/pengaturan-akun.php" class="btn-action-main btn-edit">✏️ Edit Profil</a>
            <?php else: ?>
                <button class="btn-action-main btn-follow <?= $profil['is_followed'] ? 'following' : '' ?>" onclick="toggleFollow(<?= $profil['id'] ?>, this)">
                    <?= $profil['is_followed'] ? 'Diikuti' : '+ Ikuti Kreator' ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- KONTEN UTAMA -->
<div class="content-layout">
    
    <div class="tab-container">
        <button class="tab-btn active" onclick="switchTab('tab-postingan', this)">📝 Postingan</button>
        <button class="tab-btn" onclick="switchTab('tab-papan', this)">📋 Koleksi Papan</button>
    </div>

    <!-- TAB POSTINGAN -->
    <div id="tab-postingan">
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <span style="font-size:50px;">📭</span>
                <h3 style="font-family:'Baloo 2', cursive; font-size:24px; margin-top:10px;">Belum ada postingan</h3>
                <p style="color:var(--abu); font-weight:600;">Kreator ini belum membagikan postingan apapun.</p>
            </div>
        <?php else: ?>
            <div style="max-width: 700px; margin: 0 auto;">
            <?php foreach ($posts as $p): 
                $inisial = strtoupper(substr($p['pembuat'], 0, 1));
            ?>
                <div class="post-card" id="post-<?= $p['post_id'] ?>">
                    <div class="post-header">
                        <?php if (!empty($p['foto_profil'])): ?>
                            <img src="uploads/profil/<?= htmlspecialchars($p['foto_profil']) ?>" class="create-avatar">
                        <?php else: ?>
                            <div class="create-avatar"><?= $inisial ?></div>
                        <?php endif; ?>
                        <div>
                            <h4 class="post-author-name">
                                <?= htmlspecialchars($p['pembuat']) ?>
                                <span style="color: #38BDF8; font-size: 14px;">☑️</span>
                            </h4>
                            <p class="post-meta"><?= date('d M Y H:i', strtotime($p['created_at'])) ?></p>
                        </div>
                        <?php if ($is_logged_in && $p['kreator_id'] == $_SESSION['user_id']): ?>
                            <button onclick="hapusPostingan(<?= $p['post_id'] ?>)" style="background:#FEE2E2; border:1px solid #FCA5A5; color:#DC2626; cursor:pointer; font-size:13px; font-weight:800; padding:6px 14px; border-radius: 20px; transition:0.2s; margin-left:auto; display:flex; align-items:center; gap:4px;" onmouseover="this.style.background='#FCA5A5'; this.style.color='#991B1B';" onmouseout="this.style.background='#FEE2E2'; this.style.color='#DC2626';" title="Hapus Postingan">
                                🗑️ Hapus
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if(!empty($p['isi_teks'])): ?>
                        <div class="post-body"><?= htmlspecialchars($p['isi_teks']) ?></div>
                    <?php endif; ?>

                    <?php if ($p['papan_id']): ?>
                    <a href="papan/index.php?papan_id=<?= $p['papan_id'] ?>" class="post-attachment">
                        <div class="attachment-cover"><?= !empty($p['ikon_papan']) ? htmlspecialchars($p['ikon_papan']) : '📋' ?></div>
                        <div class="attachment-footer">
                            <span class="attachment-domain">PAHAMIKU.COM/PAPAN</span>
                            <h4 class="attachment-title"><?= htmlspecialchars($p['nama_papan']) ?></h4>
                            <p style="font-size: 14px; color: var(--abu); margin: 0;">
                                Grid: <?= htmlspecialchars($p['grid']) ?>
                            </p>
                        </div>
                    </a>
                    <?php endif; ?>

                    <div class="post-actions">
                        <button class="btn-post btn-like <?= $p['is_liked'] ? 'liked' : '' ?>" onclick="toggleLike(<?= $p['post_id'] ?>, this)">
                            <?= $p['is_liked'] ? '❤️' : '🤍' ?> <span class="like-count"><?= $p['total_likes'] ?></span>
                        </button>
                        <button class="btn-post btn-komen" onclick="bukaModalKomentar(<?= $p['post_id'] ?>)">
                            💬 <span class="komen-count-<?= $p['post_id'] ?>"><?= $p['total_komentar'] ?></span>
                        </button>
                        <button class="btn-post" style="background:#F1F5F9; color:var(--gelap);" onclick="bagikanPostingan(<?= $p['post_id'] ?>, this)">
                            🔗 Bagikan
                        </button>
                        <?php if ($p['papan_id']): ?>
                        <a href="dashboard/papan-duplikat.php?papan_id=<?= $p['papan_id'] ?>" class="btn-post btn-action-save">⬇️ Simpan</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- TAB KOLEKSI PAPAN -->
    <div id="tab-papan" style="display:none;">
        <?php if (empty($boards)): ?>
            <div class="empty-state">
                <span style="font-size:50px;">📦</span>
                <h3 style="font-family:'Baloo 2', cursive; font-size:24px; margin-top:10px;">Belum ada papan publik</h3>
                <p style="color:var(--abu); font-weight:600;">Papan AAC yang dibuat publik akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <div class="board-grid">
                <?php foreach($boards as $b): ?>
                <div class="board-card">
                    <div class="board-ikon-area"><?= !empty($b['ikon_papan']) ? htmlspecialchars($b['ikon_papan']) : '📋' ?></div>
                    <div class="board-info">
                        <h4 class="board-title"><?= htmlspecialchars($b['nama_papan']) ?></h4>
                        <p class="board-desc"><?= !empty($b['deskripsi']) ? htmlspecialchars(substr($b['deskripsi'], 0, 80)).'...' : 'Tidak ada deskripsi.' ?></p>
                        
                        <div class="board-meta">
                            <span>Grid <?= htmlspecialchars($b['grid']) ?></span>
                            <span><?= htmlspecialchars($b['kategori'] ?? 'Umum') ?></span>
                        </div>
                        
                        <?php if($b['anak_is_public']): ?>
                            <a href="profil-anak.php?id=<?= $b['anak_id'] ?>" style="display:block; text-align:center; margin-bottom:12px; font-size:13px; font-weight:800; color:var(--biru); text-decoration:none;">🧒 Profil ABK: <?= htmlspecialchars($b['nama_anak']) ?></a>
                        <?php endif; ?>

                        <a href="papan/index.php?papan_id=<?= $b['id'] ?>" class="btn-view-board">Lihat Papan</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- MODAL KOMENTAR (Disalin minimalis dari komunitas) -->
<div class="modal-overlay" id="modalKomentar">
    <div class="modal-container">
        <div class="modal-header">
            <h3>💬 Diskusi Postingan</h3>
            <button class="btn-close-modal" onclick="tutupModalKomentar()">✖</button>
        </div>
        <div class="modal-body" id="modal_komentar_body"></div>
        <div class="modal-footer">
            <?php if($is_logged_in): ?>
                <div class="komentar-input-area">
                    <input type="hidden" id="input_komentar_post_id" value="">
                    <textarea id="input_isi_komentar" class="komentar-textarea" rows="1" placeholder="Tulis balasan Anda..."></textarea>
                    <button class="btn-send-komentar" onclick="kirimKomentar()" id="btn_send_komentar">➤</button>
                </div>
            <?php else: ?>
                <div style="text-align:center; color:var(--abu); font-size:14px; padding:10px 0;">Silakan <a href="login-pendamping.php">Login</a> untuk berdiskusi.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>

<script>
function switchTab(tabId, btnElement) {
    document.getElementById('tab-postingan').style.display = 'none';
    document.getElementById('tab-papan').style.display = 'none';
    
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    document.getElementById(tabId).style.display = 'block';
    btnElement.classList.add('active');
}

// Logic Follow
function toggleFollow(targetId, btn) {
    <?php if (!$is_logged_in): ?>
        alert('Silakan login terlebih dahulu untuk mengikuti pendamping.');
        window.location.href = '<?= BASE_URL ?>login-pendamping.php';
        return;
    <?php endif; ?>

    const fd = new FormData();
    fd.append('aksi', 'toggle_follow');
    fd.append('target_id', targetId);

    fetch('komunitas.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'sukses') {
            if (d.followed) {
                btn.classList.add('following');
                btn.textContent = 'Diikuti';
                // Update counter secara visual
                let countEl = document.querySelector('.stat-item:nth-child(1) .stat-val');
                if(countEl) countEl.textContent = parseInt(countEl.textContent) + 1;
            } else {
                btn.classList.remove('following');
                btn.textContent = '+ Ikuti Kreator';
                let countEl = document.querySelector('.stat-item:nth-child(1) .stat-val');
                if(countEl) countEl.textContent = parseInt(countEl.textContent) - 1;
            }
        } else {
            alert(d.pesan);
        }
    });
}

// Logic Like (Sama seperti komunitas)
function toggleLike(postId, btn) {
    <?php if (!$is_logged_in): ?>
        alert('Silakan login untuk menyukai postingan.'); return;
    <?php endif; ?>
    const fd = new FormData();
    fd.append('aksi', 'toggle_like_post'); fd.append('post_id', postId);
    fetch('komunitas.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'sukses') {
            btn.querySelector('.like-count').textContent = d.total;
            if (d.liked) {
                btn.classList.add('liked'); btn.innerHTML = `❤️ <span class="like-count">${d.total}</span>`;
            } else {
                btn.classList.remove('liked'); btn.innerHTML = `🤍 <span class="like-count">${d.total}</span>`;
            }
        }
    });
}

// Modal Komentar logic
const modalKomentar = document.getElementById('modalKomentar');
const modalBody = document.getElementById('modal_komentar_body');
function bukaModalKomentar(postId) {
    modalKomentar.classList.add('active'); document.body.style.overflow = 'hidden';
    document.getElementById('input_komentar_post_id').value = postId;
    modalBody.innerHTML = '<div style="text-align:center; padding:40px; color:var(--abu);">Memuat diskusi... ⏳</div>';
    fetch(`komunitas.php?aksi=ambil_komentar&post_id=${postId}`)
    .then(r => r.json()).then(d => { if(d.status === 'sukses') renderDaftarKomentar(d.data); });
}
function tutupModalKomentar() {
    modalKomentar.classList.remove('active'); document.body.style.overflow = '';
}
function renderDaftarKomentar(data) {
    if(data.length === 0) { modalBody.innerHTML = '<div style="text-align:center; padding:50px;">Belum ada balasan</div>'; return; }
    let html = '<div class="komentar-list">';
    data.forEach(k => {
        let ava = k.foto_profil ? `<img src="uploads/profil/${k.foto_profil}" class="komentar-avatar">` : `<div class="komentar-avatar">${k.nama.charAt(0)}</div>`;
        html += `<div class="komentar-item">${ava}<div class="komentar-content"><div class="komentar-bubble"><div class="komentar-name">${k.nama}</div><p class="komentar-text">${k.isi_komentar}</p></div></div></div>`;
    });
    modalBody.innerHTML = html + '</div>';
    setTimeout(() => { modalBody.scrollTop = modalBody.scrollHeight; }, 100);
}
function kirimKomentar() {
    <?php if(!$is_logged_in): ?> return; <?php endif; ?>
    const isi = document.getElementById('input_isi_komentar').value.trim();
    const postId = document.getElementById('input_komentar_post_id').value;
    if(!isi || !postId) return;
    const fd = new FormData(); fd.append('aksi', 'tambah_komentar'); fd.append('post_id', postId); fd.append('isi_komentar', isi);
    fetch('komunitas.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
        if(d.status === 'sukses') {
            document.getElementById('input_isi_komentar').value = '';
            bukaModalKomentar(postId);
            document.querySelector(`.komen-count-${postId}`).textContent = parseInt(document.querySelector(`.komen-count-${postId}`).textContent) + 1;
        }
    });
}

function bagikanPostingan(postId, btn) {
    const url = '<?= BASE_URL ?>komunitas.php#post-' + postId;
    navigator.clipboard.writeText(url).then(() => {
        const oldText = btn.innerHTML;
        btn.innerHTML = '✅ Tersalin!';
        setTimeout(() => {
            btn.innerHTML = oldText;
        }, 2000);
    }).catch(err => {
        alert('Gagal menyalin tautan: ' + err);
    });
}

function hapusPostingan(postId) {
    if (!confirm('Apakah Anda yakin ingin menghapus postingan ini?')) return;
    
    const fd = new FormData();
    fd.append('aksi', 'hapus_post');
    fd.append('post_id', postId);

    fetch('komunitas.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'sukses') {
            const card = document.getElementById('post-' + postId);
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                }, 300);
            }
        } else {
            alert(d.pesan);
        }
    })
    .catch(() => alert('Gagal menghapus postingan.'));
}
</script>
</body>
</html>
