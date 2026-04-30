<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'inc/config.php';

$is_logged_in = isset($_SESSION['user_id']);

// Konfigurasi Paginasi
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search_query = isset($_GET['q']) ? bersihkan($_GET['q']) : '';

// 1. Hitung total data untuk Paginasi
$sql_count = "SELECT COUNT(*) as total FROM papan p 
              JOIN profil_abk pr ON p.profil_id = pr.id 
              WHERE p.access_type = 'public'";

$params = [];
$types = "";

if ($search_query) {
    $sql_count .= " AND (p.nama_papan LIKE ? OR p.kategori LIKE ? OR p.deskripsi LIKE ?)";
    $q = "%$search_query%";
    $params = [$q, $q, $q];
    $types = "sss";
}

$stmt_count = $conn->prepare($sql_count);
if ($types) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_data = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// 2. Ambil data papan sesuai halaman
$sql_data = "SELECT p.id, p.nama_papan, p.ikon_papan, p.grid, p.kategori, p.deskripsi, 
                    u.id as kreator_id, u.nama as nama_pembuat, u.foto_profil,
                    pr.id as anak_id, pr.nama as nama_anak, pr.is_public as anak_is_public
             FROM papan p
             JOIN profil_abk pr ON p.profil_id = pr.id
             JOIN users u ON pr.user_id = u.id
             WHERE p.access_type = 'public'";

if ($search_query) {
    $sql_data .= " AND (p.nama_papan LIKE ? OR p.kategori LIKE ? OR p.deskripsi LIKE ?)";
}

$sql_data .= " ORDER BY p.id DESC LIMIT ? OFFSET ?";
$params_data = $params;
$params_data[] = $limit;
$params_data[] = $offset;
$types_data = $types . "ii";

$stmt_data = $conn->prepare($sql_data);
if ($types_data) {
    $stmt_data->bind_param($types_data, ...$params_data);
}
$stmt_data->execute();
$boards = $stmt_data->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri Papan Publik - PAHAMIKU</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --biru:       #4ECDC4;
            --biru-tua:   #2BB5AC;
            --putih:      #F8FAFC;
            --gelap:      #0F172A;
            --abu:        #64748B;
            --abu-muda:   #E2E8F0;
            --radius-lg:  20px;
            --shadow:     0 4px 12px rgba(0,0,0,0.05);
        }
        body { font-family: 'Nunito', sans-serif; background-color: var(--putih); color: var(--gelap); min-height: 100vh; display: flex; flex-direction: column; }
        
        /* HEADER HERO */
        .hero-gallery {
            background: linear-gradient(135deg, #E0F2FE 0%, #BAE6FD 100%);
            padding: 60px 20px;
            text-align: center;
            border-bottom: 1px solid #BAE6FD;
        }
        .hero-gallery h1 {
            font-family: 'Baloo 2', cursive;
            font-size: 36px;
            font-weight: 800;
            color: #0369A1;
            margin-bottom: 12px;
        }
        .hero-gallery p {
            font-size: 18px;
            color: #0284C7;
            font-weight: 600;
            max-width: 600px;
            margin: 0 auto 30px auto;
        }

        /* SEARCH BAR */
        .search-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 16px 24px;
            padding-right: 120px;
            border-radius: 99px;
            border: 2px solid white;
            font-family: 'Nunito';
            font-size: 16px;
            font-weight: 700;
            outline: none;
            box-shadow: 0 10px 25px rgba(3, 105, 161, 0.1);
            transition: 0.3s;
        }
        .search-input:focus {
            border-color: var(--biru);
            box-shadow: 0 10px 30px rgba(78, 205, 196, 0.2);
        }
        .search-btn {
            position: absolute;
            right: 8px;
            top: 8px;
            bottom: 8px;
            background: var(--biru);
            color: white;
            border: none;
            border-radius: 99px;
            padding: 0 24px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s;
        }
        .search-btn:hover { background: var(--biru-tua); }

        /* GRID KONTEN */
        .gallery-layout { max-width: 1200px; margin: 40px auto; padding: 0 20px; flex: 1; width: 100%; }
        
        .board-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
        .board-card { background: white; border: 1px solid var(--abu-muda); border-radius: var(--radius-lg); overflow: hidden; transition: 0.2s; display: flex; flex-direction: column; box-shadow: var(--shadow);}
        .board-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.08); border-color: var(--biru); }
        .board-ikon-area { background: #F8FAFC; height: 160px; display: flex; align-items: center; justify-content: center; font-size: 70px; border-bottom: 1px solid var(--abu-muda);}
        .board-info { padding: 20px; flex: 1; display: flex; flex-direction: column;}
        .board-title { font-size: 18px; font-weight: 800; margin-bottom: 8px; font-family: 'Baloo 2', cursive; color: var(--gelap);}
        .board-desc { font-size: 14px; color: var(--abu); margin-bottom: 16px; line-height: 1.5; flex: 1;}
        
        .board-meta { font-size: 12px; font-weight: 700; color: #94A3B8; display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;}
        .board-meta span { background: #F1F5F9; padding: 4px 10px; border-radius: 8px; }
        
        .creator-link { display:flex; align-items:center; gap:8px; margin-bottom:12px; font-size:13px; font-weight:800; color:var(--abu); text-decoration:none; transition:0.2s;}
        .creator-link:hover { color:var(--biru); }
        .creator-ava { width:24px; height:24px; border-radius:50%; background:#E0F2FE; color:#0369A1; display:flex; align-items:center; justify-content:center; font-size:10px; flex-shrink:0;}
        
        .child-link { display:block; margin-bottom:16px; font-size:13px; font-weight:800; color:var(--biru); text-decoration:none; padding:8px 12px; background:#F0FDF4; border-radius:10px; text-align:center;}
        .child-link:hover { background:#DCFCE7; }

        .btn-view-board { background: var(--gelap); color: white; text-align: center; padding: 12px; border-radius: 12px; text-decoration: none; font-weight: 800; transition: 0.2s; display:block; width:100%; border:none; cursor:pointer;}
        .btn-view-board:hover { background: #1E293B; }

        /* PAGINATION */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 50px; }
        .page-link { padding: 10px 18px; border-radius: 12px; background: white; border: 1px solid var(--abu-muda); color: var(--gelap); text-decoration: none; font-weight: 800; transition: 0.2s; }
        .page-link:hover { background: #F1F5F9; }
        .page-link.active { background: var(--biru); color: white; border-color: var(--biru); }
        .page-link.disabled { opacity: 0.5; pointer-events: none; }
        
        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 20px; border: 1px solid var(--abu-muda); grid-column: 1/-1;}
    </style>
</head>
<body>

<?php include 'inc/navbar.php'; ?>

<div class="hero-gallery">
    <h1>Koleksi Papan Publik</h1>
    <p>Jelajahi ratusan papan AAC yang dibagikan oleh komunitas pendamping. Anda dapat melihat, menyalin, dan memodifikasinya sesuai kebutuhan.</p>
    
    <div class="search-container">
        <form method="GET" action="galeri-papan.php">
            <input type="text" name="q" class="search-input" placeholder="Cari judul papan, kategori, atau topik..." value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit" class="search-btn">Cari</button>
        </form>
    </div>
</div>

<div class="gallery-layout">
    <div class="board-grid">
        <?php if (empty($boards)): ?>
            <div class="empty-state">
                <span style="font-size:60px;">🔍</span>
                <h3 style="font-family:'Baloo 2', cursive; font-size:24px; margin-top:10px;">Tidak ada hasil</h3>
                <p style="color:var(--abu); font-weight:600;">Coba gunakan kata kunci lain untuk mencari papan.</p>
            </div>
        <?php else: ?>
            <?php foreach ($boards as $b): ?>
                <div class="board-card">
                    <div class="board-ikon-area"><?= !empty($b['ikon_papan']) ? htmlspecialchars($b['ikon_papan']) : '📋' ?></div>
                    <div class="board-info">
                        <h4 class="board-title"><?= htmlspecialchars($b['nama_papan']) ?></h4>
                        <p class="board-desc"><?= !empty($b['deskripsi']) ? htmlspecialchars(substr($b['deskripsi'], 0, 80)).'...' : 'Tidak ada deskripsi.' ?></p>
                        
                        <div class="board-meta">
                            <span>Grid <?= htmlspecialchars($b['grid']) ?></span>
                            <span><?= htmlspecialchars($b['kategori'] ?? 'Umum') ?></span>
                        </div>
                        
                        <!-- Kreator Info -->
                        <a href="profil-kreator.php?id=<?= $b['kreator_id'] ?>" class="creator-link">
                            <?php if(!empty($b['foto_profil'])): ?>
                                <img src="uploads/profil/<?= $b['foto_profil'] ?>" class="creator-ava" style="object-fit:cover;">
                            <?php else: ?>
                                <div class="creator-ava"><?= strtoupper(substr($b['nama_pembuat'], 0, 1)) ?></div>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($b['nama_pembuat']) ?></span>
                        </a>
                        
                        <?php if($b['anak_is_public']): ?>
                            <a href="profil-anak.php?id=<?= $b['anak_id'] ?>" class="child-link">🧒 Digunakan oleh: <?= htmlspecialchars($b['nama_anak']) ?></a>
                        <?php endif; ?>

                        <div style="display:flex; gap:8px;">
                            <a href="papan/index.php?papan_id=<?= $b['id'] ?>" class="btn-view-board" style="flex:1;">👀 Lihat</a>
                            <?php if ($is_logged_in): ?>
                                <a href="dashboard/papan-duplikat.php?papan_id=<?= $b['id'] ?>" class="btn-view-board" style="flex:1; background:var(--biru); color:white;">⬇️ Salin</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- KONTROL PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <!-- Prev Button -->
        <a href="?page=<?= $page - 1 ?><?= $search_query ? '&q='.urlencode($search_query) : '' ?>" class="page-link <?= ($page <= 1) ? 'disabled' : '' ?>">« Prev</a>
        
        <!-- Page Numbers -->
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <?php 
            // Hanya tampilkan halaman yang berdekatan untuk menghindari daftar terlalu panjang
            if($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): 
            ?>
                <a href="?page=<?= $i ?><?= $search_query ? '&q='.urlencode($search_query) : '' ?>" class="page-link <?= ($i == $page) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php elseif($i == $page - 3 || $i == $page + 3): ?>
                <span style="color:var(--abu); font-weight:800;">...</span>
            <?php endif; ?>
        <?php endfor; ?>

        <!-- Next Button -->
        <a href="?page=<?= $page + 1 ?><?= $search_query ? '&q='.urlencode($search_query) : '' ?>" class="page-link <?= ($page >= $total_pages) ? 'disabled' : '' ?>">Next »</a>
    </div>
    <?php endif; ?>
</div>

<?php include 'inc/footer.php'; ?>

</body>
</html>
