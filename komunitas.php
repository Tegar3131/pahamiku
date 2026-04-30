<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'inc/config.php';

$is_logged_in = isset($_SESSION['user_id']);

// Seeding dihentikan karena fitur posting sudah stabil.

// ── PROSES: BUAT POSTINGAN BARU ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'buat_post') {
    cekLoginPendamping();
    $user_id = $_SESSION['user_id'];
    $isi_teks = bersihkan($_POST['isi_teks'] ?? '');
    $papan_id = !empty($_POST['papan_id']) ? (int)$_POST['papan_id'] : null;

    if ($isi_teks || $papan_id) {
        $stmt = $conn->prepare("INSERT INTO postingan (user_id, isi_teks, papan_id) VALUES (?, ?, ?)");
        $stmt->bind_param('isi', $user_id, $isi_teks, $papan_id);
        if ($stmt->execute()) {
            if ($papan_id) {
                $conn->query("UPDATE papan SET access_type = 'public' WHERE id = $papan_id");
            }
            setFlash('sukses', 'Postingan Anda berhasil diterbitkan di komunitas!');
            redirect('komunitas.php');
        }
    }
}

// ── PROSES: AJAX HAPUS POSTINGAN ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'hapus_post') {
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'pesan' => 'Belum login']); exit;
    }
    $post_id = (int)$_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT 1 FROM postingan WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $post_id, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['status' => 'error', 'pesan' => 'Akses ditolak atau tidak ditemukan']); exit;
    }

    $stmt_del = $conn->prepare("DELETE FROM postingan WHERE id = ?");
    $stmt_del->bind_param('i', $post_id);
    if ($stmt_del->execute()) {
        echo json_encode(['status' => 'sukses']);
    } else {
        echo json_encode(['status' => 'error', 'pesan' => 'Gagal menghapus postingan']);
    }
    exit;
}

// ── PROSES: AJAX LIKE POSTINGAN ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'toggle_like_post') {
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'pesan' => 'Belum login']); exit;
    }
    $post_id = (int)$_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT 1 FROM postingan_suka WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $post_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $conn->query("DELETE FROM postingan_suka WHERE post_id = $post_id AND user_id = $user_id");
        $liked = false;
    } else {
        $conn->query("INSERT INTO postingan_suka (post_id, user_id) VALUES ($post_id, $user_id)");
        $liked = true;
    }

    $c = $conn->query("SELECT COUNT(*) as tot FROM postingan_suka WHERE post_id = $post_id")->fetch_assoc();
    echo json_encode(['status' => 'sukses', 'liked' => $liked, 'total' => $c['tot']]);
    exit;
}

// ── PROSES: AJAX TOGGLE FOLLOW ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'toggle_follow') {
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'pesan' => 'Belum login']); exit;
    }
    $target_id = (int)$_POST['target_id'];
    $follower_id = $_SESSION['user_id'];

    if ($target_id === $follower_id) {
        echo json_encode(['status' => 'error', 'pesan' => 'Tidak bisa follow diri sendiri']); exit;
    }

    $stmt = $conn->prepare("SELECT 1 FROM user_follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param('ii', $follower_id, $target_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $conn->query("DELETE FROM user_follows WHERE follower_id = $follower_id AND following_id = $target_id");
        $followed = false;
    } else {
        $conn->query("INSERT INTO user_follows (follower_id, following_id) VALUES ($follower_id, $target_id)");
        $followed = true;
    }
    
    echo json_encode(['status' => 'sukses', 'followed' => $followed]);
    exit;
}

// ── PROSES: AJAX TAMBAH KOMENTAR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah_komentar') {
    if (!$is_logged_in) {
        echo json_encode(['status' => 'error', 'pesan' => 'Belum login']); exit;
    }
    $post_id = (int)$_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    $isi_komentar = bersihkan($_POST['isi_komentar'] ?? '');

    if (!empty($isi_komentar)) {
        $stmt = $conn->prepare("INSERT INTO postingan_komentar (post_id, user_id, isi_komentar) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $post_id, $user_id, $isi_komentar);
        $stmt->execute();
    }
    echo json_encode(['status' => 'sukses']);
    exit;
}

// ── PROSES: AJAX AMBIL KOMENTAR ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['aksi']) && $_GET['aksi'] === 'ambil_komentar') {
    $post_id = (int)$_GET['post_id'];
    $stmt = $conn->prepare("SELECT k.id, k.isi_komentar, k.created_at, u.nama, u.foto_profil, u.peran 
                            FROM postingan_komentar k JOIN users u ON k.user_id = u.id 
                            WHERE k.post_id = ? ORDER BY k.created_at ASC");
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $komentar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['status' => 'sukses', 'data' => $komentar]);
    exit;
}


// ── FILTER PENCARIAN ──
$search_query = isset($_GET['q']) ? bersihkan($_GET['q']) : '';
$search_filter = isset($_GET['filter']) ? bersihkan($_GET['filter']) : 'postingan'; // 'semua', 'postingan', 'pengguna', 'papan'

$user_id_like = $is_logged_in ? (int)$_SESSION['user_id'] : 0;
$following_ids = [];

// Fetch Following IDs first
if ($is_logged_in) {
    $stmt_f = $conn->prepare("SELECT following_id FROM user_follows WHERE follower_id = ?");
    $stmt_f->bind_param('i', $_SESSION['user_id']);
    $stmt_f->execute();
    $res_f = $stmt_f->get_result();
    while($row = $res_f->fetch_assoc()) {
        $following_ids[] = $row['following_id'];
    }
}

// ── AMBIL DATA FEED POSTINGAN ──
$posts = [];
if ($search_filter === 'semua' || $search_filter === 'postingan') {
            $sql = "SELECT post.id AS post_id, post.isi_teks, post.created_at, 
                u.id as kreator_id, u.nama AS pembuat, u.foto_profil, u.peran,
                p.id AS papan_id, p.nama_papan, p.ikon_papan, p.grid, p.kategori,
                pr.id AS anak_id, pr.nama AS nama_anak, pr.is_public AS anak_is_public,
                (SELECT COUNT(*) FROM postingan_suka ps WHERE ps.post_id = post.id) as total_likes,
                (SELECT COUNT(*) FROM postingan_suka ps2 WHERE ps2.post_id = post.id AND ps2.user_id = ?) as is_liked,
                (SELECT COUNT(*) FROM postingan_komentar pk WHERE pk.post_id = post.id) as total_komentar
            FROM postingan post
            JOIN users u ON post.user_id = u.id
            LEFT JOIN papan p ON post.papan_id = p.id
            LEFT JOIN profil_abk pr ON p.profil_id = pr.id
            WHERE 1=1";

    $params = [$user_id_like];
    $types = "i";

    if ($search_query) {
        $sql .= " AND (post.isi_teks LIKE ? OR p.nama_papan LIKE ? OR u.nama LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }

    $sql .= " ORDER BY post.created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ── AMBIL DATA PENGGUNA (Untuk Filter Pengguna) ──
$users_list = [];
if (($search_filter === 'semua' && !empty($search_query)) || $search_filter === 'pengguna') {
    // Hanya tampilkan jika user mencari spesifik, atau memfilter pengguna, agar tidak meload seluruh database tanpa alasan.
    // Jika tidak ada search query tapi filter = pengguna, kita bisa memunculkan beberapa (explore mode).
    
    $sql_users = "SELECT u.id, u.nama, u.foto_profil, u.peran, 
                         (SELECT COUNT(*) FROM user_follows WHERE following_id = u.id) as total_followers
                  FROM users u 
                  WHERE 1=1";
    
    $params_u = [];
    $types_u = "";

    if ($search_query) {
        $sql_users .= " AND (u.nama LIKE ? OR u.peran LIKE ?)";
        $search_param = "%$search_query%";
        $params_u[] = $search_param;
        $params_u[] = $search_param;
        $types_u .= "ss";
    }

    $sql_users .= " ORDER BY total_followers DESC, u.id DESC LIMIT 50";
    
    $stmt_u = $conn->prepare($sql_users);
    if ($types_u) {
        $stmt_u->bind_param($types_u, ...$params_u);
    }
    $stmt_u->execute();
    $users_list = $stmt_u->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ── AMBIL DATA PAPAN AAC (Untuk Filter Papan) ──
$boards_list = [];
if (($search_filter === 'semua' && !empty($search_query)) || $search_filter === 'papan') {
    $sql_boards = "SELECT p.id, p.nama_papan, p.ikon_papan, p.grid, p.kategori, p.deskripsi, 
                          u.id as kreator_id, u.nama as nama_pembuat,
                          pr.id as anak_id, pr.nama as nama_anak, pr.is_public as anak_is_public
                   FROM papan p
                   JOIN profil_abk pr ON p.profil_id = pr.id
                   JOIN users u ON pr.user_id = u.id
                   WHERE p.access_type = 'public'";
                   
    $params_b = [];
    $types_b = "";

    if ($search_query) {
        $sql_boards .= " AND (p.nama_papan LIKE ? OR p.kategori LIKE ? OR p.deskripsi LIKE ?)";
        $search_param = "%$search_query%";
        $params_b[] = $search_param;
        $params_b[] = $search_param;
        $params_b[] = $search_param;
        $types_b .= "sss";
    }

    $sql_boards .= " ORDER BY p.id DESC LIMIT 50";
    
    $stmt_b = $conn->prepare($sql_boards);
    if ($types_b) {
        $stmt_b->bind_param($types_b, ...$params_b);
    }
    $stmt_b->execute();
    $boards_list = $stmt_b->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ── AMBIL PAPAN MILIK USER (Untuk Form Post JS) ──
$my_boards = [];
if ($is_logged_in) {
    $stmt_b = $conn->prepare("SELECT p.id, p.nama_papan, p.ikon_papan, p.grid, p.kategori, pr.nama as nama_anak FROM papan p JOIN profil_abk pr ON p.profil_id = pr.id WHERE pr.user_id = ? ORDER BY p.id DESC");
    $stmt_b->bind_param('i', $_SESSION['user_id']);
    $stmt_b->execute();
    $my_boards = $stmt_b->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Ambil info user yang sedang login
$usr = $is_logged_in ? getUserLogin($conn) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komunitas PAHAMIKU</title>
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
            --merah:      #EF4444;
            --radius-lg:  20px;
            --radius:     12px;
            --shadow:     0 4px 12px rgba(0,0,0,0.05);
        }
        body { font-family: 'Nunito', sans-serif; background-color: var(--putih); color: var(--gelap); min-height: 100vh; display: flex; flex-direction: column; }
        .social-layout { max-width: 1000px; margin: 30px auto; padding: 0 20px; display: grid; grid-template-columns: 2.3fr 1fr; gap: 24px; flex: 1; width:100%;}
        @media (max-width: 850px) { .social-layout { grid-template-columns: 1fr; display: flex; flex-direction: column-reverse; } }

        .feed-area { display: flex; flex-direction: column; gap: 24px; }
        
        /* Form Buat Postingan */
        .create-post-card { background: var(--putih-card); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow); border: 1px solid #E2E8F0; }
        .create-header { display: flex; align-items: center; gap: 16px; margin-bottom: 0; }
        .create-avatar { width: 48px; height: 48px; border-radius: 50%; background: #E0F2FE; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #0369A1; object-fit: cover; font-size:18px; flex-shrink:0;}
        .create-input-btn { flex: 1; background: #F8FAFC; border: 1px solid var(--abu-muda); padding: 14px 20px; border-radius: 50px; color: var(--abu); cursor: pointer; text-align: left; font-family: 'Nunito'; font-size: 16px; font-weight: 600; transition: 0.2s;}
        .create-input-btn:hover { background: #F1F5F9; border-color:#CBD5E1;}
        
        /* Form Expandable */
        #form-post-expand { display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--abu-muda); animation: fadeIn 0.3s ease; }
        .form-post-textarea { width: 100%; border: 1px solid transparent; background: #F8FAFC; border-radius: 12px; padding: 16px; font-family: 'Nunito'; font-size: 16px; resize: vertical; outline: none; margin-bottom: 16px; min-height: 100px; transition:0.2s;}
        .form-post-textarea:focus { border-color: var(--biru); background: white;}
        
        /* Board Picker Styles */
        .form-input-search { width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--abu-muda); margin-bottom: 8px; font-family: 'Nunito'; font-size: 14px; background: #F8FAFC; outline:none; transition:0.2s;}
        .form-input-search:focus { border-color: var(--biru); background: white;}
        #board_search_results { border: 1px solid var(--abu-muda); border-radius: 12px; max-height: 250px; overflow-y: auto; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 16px; margin-top: -4px;}
        .board-result-item { padding: 12px 16px; border-bottom: 1px solid var(--abu-muda); cursor: pointer; display: flex; align-items: center; gap: 12px; transition:0.2s;}
        .board-result-item:last-child { border-bottom: none; }
        .board-result-item:hover { background: #F0FDF9; }
        .board-item-ikon { font-size: 30px; width:44px; height:44px; background:#F8FAFC; border-radius:8px; display:flex; align-items:center; justify-content:center;}
        .board-item-info h5 { margin:0 0 2px 0; font-size:15px; font-family:'Baloo 2', cursive; color:var(--gelap);}
        .board-item-info p { margin:0; font-size:12px; color:var(--abu); font-weight:600;}
        
        .btn-hapus-papan { background: #FEE2E2; color: #DC2626; border: none; width: 32px; height: 32px; border-radius: 50%; font-weight:bold; cursor:pointer; font-size:16px; transition:0.2s;}
        .btn-hapus-papan:hover { background: #FECACA; transform:scale(1.1);}

        .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top:8px;}
        .btn-batal { padding: 10px 20px; background: transparent; color: var(--abu); border: none; cursor: pointer; font-weight: 700; border-radius: 10px;}
        .btn-batal:hover { background: #F1F5F9; }
        .btn-kirim { padding: 10px 28px; background: var(--biru); color: white; border: none; border-radius: 10px; font-weight: 800; font-size:16px; cursor: pointer; transition:0.2s; box-shadow: 0 4px 12px rgba(78,205,196,0.3);}
        .btn-kirim:hover { background: var(--biru-tua); transform:translateY(-2px);}
        
        /* Postingan Card */
        .post-card { background: var(--putih-card); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow); border: 1px solid #E2E8F0; }
        .post-header { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
        .post-author-info { flex: 1; }
        .post-author-name { font-size: 16px; font-weight: 800; color: var(--gelap); margin: 0 0 2px 0; display: flex; align-items: center; gap: 6px; }
        
        /* Button Follow Inline */
        .btn-follow-inline { font-size: 11px; padding: 4px 10px; border-radius: 12px; font-weight: 800; cursor: pointer; border: none; background: #DBEAFE; color: #1D4ED8; transition: 0.2s; margin-left: 8px;}
        .btn-follow-inline:hover { background: #BFDBFE; transform: scale(1.05);}
        .btn-follow-inline.following { background: #F1F5F9; color: var(--gelap); border: 1px solid var(--abu-muda); }
        
        .badge-peran { font-size: 11px; padding: 2px 8px; background: #E0F2FE; color: #0284C7; border-radius: 10px; font-weight: 800; text-transform: uppercase;}
        .post-meta { font-size: 13px; color: var(--abu); margin: 0; font-weight:600;}
        .post-body { font-size: 16px; line-height: 1.6; color: #334155; margin-bottom: 16px; white-space: pre-wrap; word-wrap: break-word;}
        
        .post-attachment { display: block; border: 1px solid var(--abu-muda); border-radius: 16px; overflow: hidden; text-decoration: none; color: var(--gelap); margin-bottom: 16px; transition: 0.2s;}
        .post-attachment:hover { background: #F8FAFC; border-color: var(--abu); }
        .attachment-cover { background: #E2E8F0; height: 180px; display: flex; align-items: center; justify-content: center; font-size: 80px; }
        .attachment-footer { padding: 12px 16px; background: white; border-top: 1px solid var(--abu-muda); }
        .attachment-domain { font-size: 12px; color: var(--abu); font-weight: 700; margin-bottom: 4px; display: block; text-transform: uppercase;}
        .attachment-title { font-size: 16px; font-weight: 800; margin: 0 0 4px 0; font-family: 'Nunito', sans-serif;}
        .attachment-desc { font-size: 14px; color: var(--abu); margin: 0; }
        
        .post-actions { display: flex; align-items: center; border-top: 1px solid var(--abu-muda); padding-top: 16px; gap: 12px; }
        .btn-post { flex: 1; padding: 12px; border-radius: 99px; text-align: center; text-decoration: none; font-weight: 800; font-size: 15px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; border:none; box-shadow: 0 2px 4px rgba(0,0,0,0.02);}
        .btn-like { background: #FFE4E6; color: #E11D48; }
        .btn-like:hover { background: #FECDD3; transform: translateY(-2px); }
        .btn-like.liked { background: #F43F5E; color: white; font-weight: 900; box-shadow: 0 4px 12px rgba(244,63,94,0.3); }
        .btn-komen { background: #E0F2FE; color: #0369A1; }
        .btn-komen:hover { background: #BAE6FD; transform: translateY(-2px); }
        .btn-action-save { background: #F0FDF4; color: #15803D; }
        .btn-action-save:hover { background: #DCFCE7; transform: translateY(-2px); }
        
        /* CARD PENDAMPING */
        .user-card { background: var(--putih-card); border-radius: var(--radius-lg); padding: 20px; box-shadow: var(--shadow); border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 16px; transition:0.2s;}
        .user-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .user-card .create-avatar { width: 60px; height: 60px; font-size: 24px; }
        .user-info { flex: 1; }
        .user-name { font-size: 18px; font-weight: 800; color: var(--gelap); margin: 0 0 4px 0; }
        .user-stats { font-size: 13px; color: var(--abu); margin: 0; font-weight: 600; }
        .btn-follow-large { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 14px; cursor: pointer; border: none; background: var(--biru); color: white; transition: 0.2s; box-shadow: 0 4px 12px rgba(78,205,196,0.3);}
        .btn-follow-large:hover { transform: translateY(-2px); background: var(--biru-tua); }
        .btn-follow-large.following { background: #F1F5F9; color: var(--gelap); box-shadow: none; border: 1px solid var(--abu-muda); }

        /* Sidebar */
        .sidebar { display: flex; flex-direction: column; gap: 20px; }
        .widget { background: var(--putih-card); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow); border: 1px solid #E2E8F0; }
        .widget h3 { font-family: 'Baloo 2', cursive; font-size: 20px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .widget p { font-size: 14px; color: var(--abu); margin-bottom: 16px; font-weight:600;}
        .form-input-widget { width: 100%; padding: 12px 16px; border: 2px solid var(--abu-muda); border-radius: 12px; font-family: 'Nunito'; font-size: 14px; font-weight: 600; outline: none; background: #F8FAFC; margin-bottom:12px; transition:0.2s;}
        .form-input-widget:focus { border-color:var(--biru); background:white;}
        .btn-cari { width: 100%; padding: 12px; background: var(--gelap); color: white; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; transition:0.2s;}
        .btn-cari:hover { background: #1E293B; }
        
        .alert-sukses { background: #D1FAE5; color: #065F46; padding: 12px 16px; border-radius: 12px; margin-bottom: 0; font-weight: 700; border: 1px solid #A7F3D0;}

        /* Modal Komentar */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; display: none; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; }
        .modal-overlay.active { display: flex; opacity: 1; animation: fadeIn 0.2s ease-out;}
        .modal-container { background: white; width: 100%; max-width: 600px; height: 85vh; border-radius: 24px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); transform: translateY(20px); transition: 0.3s;}
        .modal-overlay.active .modal-container { transform: translateY(0); }
        @media (max-width: 650px) { .modal-container { height: 100vh; border-radius: 0; } }
        
        .modal-header { padding: 16px 24px; border-bottom: 1px solid var(--abu-muda); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; font-family: inherit; font-size: 20px; font-weight: 800; }
        .btn-close-modal { background: #F1F5F9; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 16px; font-weight: bold; cursor: pointer; color: var(--gelap); transition: 0.2s;}
        .btn-close-modal:hover { background: #E2E8F0; }
        
        .modal-body { padding: 24px; overflow-y: auto; flex: 1; background: #F8FAFC; position: relative;}
        .modal-footer { padding: 16px 24px; border-top: 1px solid var(--abu-muda); background: white; }

        .komentar-list { display: flex; flex-direction: column; gap: 20px; }
        .komentar-item { display: flex; gap: 12px; }
        .komentar-avatar { width: 44px; height: 44px; border-radius: 50%; background: #E0F2FE; flex-shrink: 0; display:flex; align-items:center; justify-content:center; font-weight:800; color:#0369A1; object-fit:cover;}
        .komentar-content { flex: 1; }
        .komentar-bubble { background: white; padding: 12px 16px; border-radius: 4px 16px 16px 16px; border: 1px solid var(--abu-muda); box-shadow: 0 1px 2px rgba(0,0,0,0.02); display: inline-block;}
        .komentar-name { font-size: 14px; font-weight: 800; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;}
        .komentar-text { font-size: 15px; margin: 0; line-height: 1.5; color: var(--gelap); }
        .komentar-time { font-size: 12px; color: var(--abu); margin-top: 6px; display:block; font-weight:600; margin-left: 4px;}

        .komentar-input-area { display: flex; gap: 12px; align-items: flex-end; }
        .komentar-textarea { flex: 1; border: 1px solid var(--abu-muda); border-radius: 20px; padding: 12px 16px; font-family: inherit; font-size: 15px; resize: none; outline: none; background: #F8FAFC; max-height:120px; transition:0.2s;}
        .komentar-textarea:focus { border-color: var(--biru); background: white; }
        .btn-send-komentar { background: var(--biru); color: white; border: none; width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; font-size: 18px; flex-shrink:0;}
        .btn-send-komentar:hover { background: var(--biru-tua); transform: scale(1.05);}
        .btn-send-komentar:disabled { background: var(--abu-muda); cursor: not-allowed; transform:none;}

        @keyframes fadeIn { from {opacity:0; transform:translateY(-10px);} to {opacity:1; transform:translateY(0);} }
    </style>
</head>
<body>

<?php include 'inc/navbar.php'; ?>

<div class="social-layout">
    <!-- AREA KIRI: FEED UTAMA -->
    <div class="feed-area">
        
        <?php tampilFlash(); ?>

        <!-- ================= BAGIAN POSTINGAN ================= -->
        <?php if ($search_filter === 'postingan' || $search_filter === 'semua'): ?>
            
            <?php if ($search_filter === 'semua' && $search_query && !empty($posts)): ?>
                <h3 style="margin-bottom: 16px; font-family: 'Baloo 2', cursive; font-size: 20px; color:var(--gelap);">📝 Hasil Postingan</h3>
            <?php endif; ?>

            <?php if ($is_logged_in && empty($search_query) && $search_filter === 'postingan'): ?>
            <!-- Form Buat Postingan -->
            <div class="create-post-card" style="margin-bottom: 24px;">
                <div class="create-header">
                    <?php if (!empty($usr['foto_profil'])): ?>
                        <img src="uploads/profil/<?= htmlspecialchars($usr['foto_profil']) ?>" class="create-avatar">
                    <?php else: ?>
                        <div class="create-avatar"><?= strtoupper(substr($usr['nama'], 0, 1)) ?></div>
                    <?php endif; ?>
                    <button class="create-input-btn" id="btn-trigger-post" onclick="document.getElementById('form-post-expand').style.display='block'; this.style.display='none';">Bagikan cerita atau papan AAC Anda hari ini...</button>
                </div>
                
                <div id="form-post-expand">
                    <form method="POST">
                        <input type="hidden" name="aksi" value="buat_post">
                        <input type="hidden" name="papan_id" id="input_papan_id" value="">

                        <textarea name="isi_teks" class="form-post-textarea" placeholder="Tuliskan pengalaman Anda, tips, atau progres anak di sini..."></textarea>
                        
                        <div id="board_picker_area">
                            <label style="font-size:13px; font-weight:800; color:var(--abu); margin-bottom:6px; display:block;">Papan AAC yang ditautkan (Opsional)</label>
                            
                            <!-- Search Input -->
                            <div id="board_search_container">
                                <input type="text" id="board_search_input" class="form-input-search" placeholder="🔍 Cari nama papan atau nama anak..." autocomplete="off">
                                <div id="board_search_results" style="display:none;"></div>
                            </div>

                            <!-- Preview Selected Board -->
                            <div id="board_selected_preview" style="display:none; margin-top: 12px; margin-bottom: 16px; position: relative;">
                                <button type="button" class="btn-hapus-papan" onclick="hapusPapanTerpilih()" title="Hapus Tautan Papan" style="position:absolute; right:10px; top:10px; z-index:10; background:rgba(0,0,0,0.5); color:white; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer;">✖</button>
                                <div class="post-attachment" style="margin-bottom:0; cursor:default; pointer-events:none;">
                                    <div class="attachment-cover" id="preview_ikon">📋</div>
                                    <div class="attachment-footer">
                                        <span class="attachment-domain">PAHAMIKU.COM/PAPAN</span>
                                        <h4 class="attachment-title" id="preview_nama">Nama Papan</h4>
                                        <p class="attachment-desc" id="preview_meta">Profil Anak • Grid</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-batal" onclick="batalPost()">Batal</button>
                            <button type="submit" class="btn-kirim">Kirim Postingan</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Feed List -->
            <div style="display:flex; flex-direction:column; gap:24px;">
            <?php if (empty($posts)): ?>
                <?php if ($search_filter === 'postingan'): ?>
                    <div class="post-card" style="text-align:center; padding:60px 20px;">
                        <span style="font-size:50px;">📭</span>
                        <h3 style="font-family:'Baloo 2', cursive; font-size:24px; margin-top:10px;">Belum ada postingan</h3>
                        <p style="color:var(--abu); font-weight:600;">Jadilah yang pertama membagikan cerita hari ini!</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach ($posts as $p): 
                    $inisial = strtoupper(substr($p['pembuat'], 0, 1));
                    $is_following = in_array($p['kreator_id'], $following_ids);
                    $show_follow_btn = $is_logged_in && ($p['kreator_id'] != $_SESSION['user_id']);
                ?>
                <div class="post-card" id="post-<?= $p['post_id'] ?>">
                    <!-- Header: Kreator -->
                    <div class="post-header">
                        <?php if (!empty($p['foto_profil'])): ?>
                            <img src="uploads/profil/<?= htmlspecialchars($p['foto_profil']) ?>" class="create-avatar" style="width:50px; height:50px;">
                        <?php else: ?>
                            <div class="create-avatar" style="width:50px; height:50px;"><?= $inisial ?></div>
                        <?php endif; ?>
                        <div class="post-author-info">
                            <h4 class="post-author-name">
                                <a href="profil-kreator.php?id=<?= $p['kreator_id'] ?>" style="color:inherit; text-decoration:none; transition:0.2s;" onmouseover="this.style.color='var(--biru)'" onmouseout="this.style.color='inherit'">
                                    <?= htmlspecialchars($p['pembuat']) ?>
                                </a>
                                <span style="color: #38BDF8; font-size: 14px;" title="Terverifikasi">☑️</span>
                                
                                <?php if($show_follow_btn): ?>
                                    <button class="btn-follow-inline btn-tgl-follow-<?= $p['kreator_id'] ?> <?= $is_following ? 'following' : '' ?>" onclick="toggleFollow(<?= $p['kreator_id'] ?>)">
                                        <?= $is_following ? 'Diikuti' : '+ Ikuti' ?>
                                    </button>
                                <?php endif; ?>
                            </h4>
                            <p class="post-meta">
                                <?php if(!empty($p['peran'])): ?>
                                    <span class="badge-peran"><?= htmlspecialchars($p['peran']) ?></span> •
                                <?php endif; ?>
                                <?= date('d M Y H:i', strtotime($p['created_at'])) ?>
                            </p>
                        </div>
                        <?php if ($is_logged_in && $p['kreator_id'] == $_SESSION['user_id']): ?>
                            <button onclick="hapusPostingan(<?= $p['post_id'] ?>)" style="background:#FEE2E2; border:1px solid #FCA5A5; color:#DC2626; cursor:pointer; font-size:13px; font-weight:800; padding:6px 14px; border-radius: 20px; transition:0.2s; margin-left:auto; display:flex; align-items:center; gap:4px;" onmouseover="this.style.background='#FCA5A5'; this.style.color='#991B1B';" onmouseout="this.style.background='#FEE2E2'; this.style.color='#DC2626';" title="Hapus Postingan">
                                🗑️ Hapus
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Body: Teks -->
                    <?php if(!empty($p['isi_teks'])): ?>
                        <div class="post-body"><?= htmlspecialchars($p['isi_teks']) ?></div>
                    <?php endif; ?>

                    <!-- Lampiran Papan -->
                    <?php if ($p['papan_id']): ?>
                    <div style="border: 1px solid var(--abu-muda); border-radius: 16px; overflow: hidden; margin-bottom: 16px;">
                        <a href="papan/index.php?papan_id=<?= $p['papan_id'] ?>" class="post-attachment" style="margin-bottom:0; border:none; border-radius:0;">
                            <div class="attachment-cover"><?= !empty($p['ikon_papan']) ? htmlspecialchars($p['ikon_papan']) : '📋' ?></div>
                            <div class="attachment-footer">
                                <span class="attachment-domain">PAHAMIKU.COM/PAPAN</span>
                                <h4 class="attachment-title"><?= htmlspecialchars($p['nama_papan']) ?></h4>
                                <p class="attachment-desc">
                                    Kategori: <?= htmlspecialchars($p['kategori'] ?? 'Umum') ?> &nbsp;•&nbsp; Grid: <?= htmlspecialchars($p['grid']) ?>
                                </p>
                            </div>
                        </a>
                        <?php if(!empty($p['anak_id']) && $p['anak_is_public']): ?>
                            <div style="background:#F8FAFC; padding:10px 16px; border-top:1px solid var(--abu-muda); text-align:right;">
                                <a href="profil-anak.php?id=<?= $p['anak_id'] ?>" style="font-size:13px; font-weight:800; color:var(--biru); text-decoration:none; transition:0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">🧒 Profil ABK: <?= htmlspecialchars($p['nama_anak']) ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Aksi -->
                    <div class="post-actions">
                        <button class="btn-post btn-like <?= $p['is_liked'] ? 'liked' : '' ?>" onclick="toggleLike(<?= $p['post_id'] ?>, this)">
                            <?= $p['is_liked'] ? '❤️' : '🤍' ?> <span class="like-count"><?= $p['total_likes'] ?></span> Suka
                        </button>
                        <!-- TOMBOL KOMENTAR -->
                        <button class="btn-post btn-komen" onclick="bukaModalKomentar(<?= $p['post_id'] ?>)">
                            💬 <span class="komen-count-<?= $p['post_id'] ?>"><?= $p['total_komentar'] ?></span> Balasan
                        </button>
                        <button class="btn-post" style="background:#F1F5F9; color:var(--gelap);" onclick="bagikanPostingan(<?= $p['post_id'] ?>, this)">
                            🔗 Bagikan
                        </button>
                        <?php if ($p['papan_id']): ?>
                        <a href="dashboard/papan-duplikat.php?papan_id=<?= $p['papan_id'] ?>" class="btn-post btn-action-save">
                            ⬇️ Simpan Papan
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ================= BAGIAN DAFTAR PENGGUNA ================= -->
        <?php if ($search_filter === 'pengguna' || ($search_filter === 'semua' && $search_query)): ?>
            
            <?php if ($search_filter === 'semua' && !empty($users_list)): ?>
                <h3 style="margin-top: 32px; margin-bottom: 16px; font-family: 'Baloo 2', cursive; font-size: 20px; color:var(--gelap);">👥 Hasil Profil Pengguna</h3>
            <?php endif; ?>

            <div style="display:flex; flex-direction:column; gap:16px;">
            <?php if(!$is_logged_in): ?>
                <div class="post-card" style="text-align:center; padding:60px 20px;">
                    <span style="font-size:50px;">🔒</span>
                    <h3 style="font-family:'Baloo 2', cursive; font-size:24px; margin-top:10px;">Fitur Terkunci</h3>
                    <p style="color:var(--abu); font-weight:600; margin-bottom:20px;">Silakan login untuk mencari dan menemukan profil pendamping lain.</p>
                    <a href="login-pendamping.php" class="btn-kirim" style="text-decoration:none; display:inline-block;">Login Sekarang</a>
                </div>
            <?php elseif (empty($users_list)): ?>
                <?php if ($search_filter === 'pengguna'): ?>
                    <div class="post-card" style="text-align:center; padding:60px 20px;">
                        <span style="font-size:50px;">🔍</span>
                        <h3 style="font-family:'Baloo 2', cursive; font-size:24px; margin-top:10px;">Belum ada hasil</h3>
                        <p style="color:var(--abu); font-weight:600;">Gunakan kotak pencarian untuk mencari nama spesifik.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach ($users_list as $u): 
                    $inisial = strtoupper(substr($u['nama'], 0, 1));
                    $is_following = in_array($u['id'], $following_ids);
                ?>
                <div class="user-card">
                    <?php if (!empty($u['foto_profil'])): ?>
                        <img src="uploads/profil/<?= htmlspecialchars($u['foto_profil']) ?>" class="create-avatar">
                    <?php else: ?>
                        <div class="create-avatar"><?= $inisial ?></div>
                    <?php endif; ?>
                    
                    <div class="user-info">
                        <h4 class="user-name">
                            <a href="profil-kreator.php?id=<?= $u['id'] ?>" style="color:inherit; text-decoration:none; transition:0.2s;" onmouseover="this.style.color='var(--biru)'" onmouseout="this.style.color='inherit'">
                                <?= htmlspecialchars($u['nama']) ?>
                            </a>
                            <?php if(!empty($u['peran'])): ?>
                                <span class="badge-peran" style="font-size:9px; vertical-align:middle; margin-left:4px;"><?= htmlspecialchars($u['peran']) ?></span>
                            <?php endif; ?>
                        </h4>
                        <p class="user-stats">👤 <?= $u['total_followers'] ?> Pengikut</p>
                    </div>

                    <?php if ($is_logged_in && $u['id'] == $_SESSION['user_id']): ?>
                        <button class="btn-follow-large" style="background:#F1F5F9; color:var(--abu); box-shadow:none; cursor:default;" disabled>Profil Anda</button>
                    <?php else: ?>
                        <button class="btn-follow-large btn-tgl-follow-<?= $u['id'] ?> <?= $is_following ? 'following' : '' ?>" onclick="toggleFollow(<?= $u['id'] ?>)">
                            <?= $is_following ? 'Diikuti' : '+ Ikuti' ?>
                        </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ================= BAGIAN DAFTAR PAPAN AAC ================= -->
        <?php if ($search_filter === 'papan' || ($search_filter === 'semua' && $search_query)): ?>
            
            <?php if ($search_filter === 'semua' && !empty($boards_list)): ?>
                <h3 style="margin-top: 32px; margin-bottom: 16px; font-family: 'Baloo 2', cursive; font-size: 20px; color:var(--gelap);">📋 Hasil Koleksi Papan AAC</h3>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:20px; margin-bottom: 24px;">
            <?php if (empty($boards_list)): ?>
                <?php if ($search_filter === 'papan'): ?>
                    <div class="post-card" style="text-align:center; padding:60px 20px; grid-column: 1/-1;">
                        <span style="font-size:50px;">🔍</span>
                        <h3 style="font-family:'Baloo 2', cursive; font-size:24px; margin-top:10px;">Belum ada hasil</h3>
                        <p style="color:var(--abu); font-weight:600;">Gunakan kata kunci untuk mencari judul papan AAC.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach ($boards_list as $b): ?>
                <div class="board-card" style="background: white; border: 1px solid var(--abu-muda); border-radius: var(--radius-lg); overflow: hidden; transition: 0.2s; display: flex; flex-direction: column;">
                    <div class="board-ikon-area" style="background: #F8FAFC; height: 140px; display: flex; align-items: center; justify-content: center; font-size: 60px; border-bottom: 1px solid var(--abu-muda); position:relative;">
                        <?= !empty($b['ikon_papan']) ? htmlspecialchars($b['ikon_papan']) : '📋' ?>
                    </div>
                    <div class="board-info" style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
                        <h4 class="board-title" style="font-size: 18px; font-weight: 800; margin-bottom: 8px; font-family: 'Baloo 2', cursive; color: var(--gelap);"><?= htmlspecialchars($b['nama_papan']) ?></h4>
                        <p class="board-desc" style="font-size: 14px; color: var(--abu); margin-bottom: 16px; line-height: 1.5; flex: 1;"><?= !empty($b['deskripsi']) ? htmlspecialchars(substr($b['deskripsi'], 0, 80)).'...' : 'Tidak ada deskripsi.' ?></p>
                        
                        <div class="board-meta" style="font-size: 12px; font-weight: 700; color: #94A3B8; display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <span style="background: #F1F5F9; padding: 4px 10px; border-radius: 8px;">Grid <?= htmlspecialchars($b['grid']) ?></span>
                            <span style="background: #F1F5F9; padding: 4px 10px; border-radius: 8px;"><?= htmlspecialchars($b['kategori'] ?? 'Umum') ?></span>
                        </div>
                        
                        <a href="profil-kreator.php?id=<?= $b['kreator_id'] ?>" style="display:block; text-align:center; margin-bottom:12px; font-size:13px; font-weight:800; color:var(--abu); text-decoration:none;" onmouseover="this.style.color='var(--biru)'" onmouseout="this.style.color='var(--abu)'">👤 Kreator: <?= htmlspecialchars($b['nama_pembuat']) ?></a>
                        
                        <?php if($b['anak_is_public']): ?>
                            <a href="profil-anak.php?id=<?= $b['anak_id'] ?>" style="display:block; text-align:center; margin-bottom:16px; font-size:13px; font-weight:800; color:var(--biru); text-decoration:none;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">🧒 Profil ABK: <?= htmlspecialchars($b['nama_anak']) ?></a>
                        <?php endif; ?>

                        <a href="papan/index.php?papan_id=<?= $b['id'] ?>" class="btn-view-board" style="background: var(--gelap); color: white; text-align: center; padding: 12px; border-radius: 12px; text-decoration: none; font-weight: 800; transition: 0.2s; display:block;">Lihat Papan</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- AREA KANAN: SIDEBAR -->
    <div class="sidebar">
        <?php if ($is_logged_in && $usr): ?>
        <!-- Widget Profil Saya -->
        <div class="widget" style="text-align: center;">
            <?php if(!empty($usr['foto_profil'])): ?>
                <img src="uploads/profil/<?= htmlspecialchars($usr['foto_profil']) ?>" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 3px solid var(--putih); box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 12px;">
            <?php else: ?>
                <div style="width: 70px; height: 70px; border-radius: 50%; background: #E0F2FE; color: #0369A1; font-size: 28px; font-weight: 800; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto;">
                    <?= strtoupper(substr($usr['nama'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            
            <h3 style="margin-bottom: 4px; font-size: 18px; color: var(--gelap);"><?= htmlspecialchars($usr['nama']) ?></h3>
            <?php if(!empty($usr['peran'])): ?>
                <span class="badge-peran" style="display:inline-block; margin-bottom:16px;"><?= htmlspecialchars($usr['peran']) ?></span>
            <?php endif; ?>
            
            <a href="profil-kreator.php?id=<?= $usr['id'] ?>" style="display:block; width:100%; padding:10px; background:var(--abu-muda); color:var(--gelap); text-decoration:none; border-radius:10px; font-weight:800; transition:0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='var(--abu-muda)'">👀 Lihat Profil Saya</a>
        </div>
        <?php endif; ?>
        
        <!-- Widget Pencarian Pintar -->
        <div class="widget">
            <h3>🔍 Cari Sesuatu?</h3>
            <p>Gunakan filter di bawah untuk mencari postingan atau profil spesifik.</p>
            <form action="komunitas.php" method="GET">
                <input type="text" name="q" class="form-input-widget" placeholder="Cari nama atau topik..." value="<?= htmlspecialchars($search_query) ?>">
                
                <select name="filter" class="form-input-widget" style="cursor: pointer;">
                    <option value="postingan" <?= $search_filter == 'postingan' ? 'selected' : '' ?>>📖 Postingan</option>
                    <option value="pengguna" <?= $search_filter == 'pengguna' ? 'selected' : '' ?>>👥 Akun Pengguna</option>
                    <option value="papan" <?= $search_filter == 'papan' ? 'selected' : '' ?>>📋 Koleksi Papan AAC</option>
                    <option value="semua" <?= $search_filter == 'semua' ? 'selected' : '' ?>>🔎 Cari Semua</option>
                </select>

                <button type="submit" class="btn-cari">Cari Sekarang</button>
            </form>
        </div>

        <!-- Widget Komunitas -->
        <div class="widget" style="background: linear-gradient(135deg, #F0FDF9, #E0F2FE); border-color:#BAE6FD;">
            <h3>🤝 Tentang Komunitas</h3>
            <p>Ruang berbagi inspirasi papan AAC antar pendamping. Temukan papan yang tepat untuk kebutuhan anak Anda dan bagikan cerita progres mereka.</p>
            <?php if(!$is_logged_in): ?>
                <a href="login-pendamping.php" style="display:block; text-align:center; background:var(--biru); color:white; padding:10px; border-radius:10px; font-weight:800; text-decoration:none;">Daftar & Gabung</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL KOMENTAR -->
<div class="modal-overlay" id="modalKomentar">
    <div class="modal-container">
        <div class="modal-header">
            <h3>💬 Diskusi Postingan</h3>
            <button class="btn-close-modal" onclick="tutupModalKomentar()">✖</button>
        </div>
        
        <div class="modal-body" id="modal_komentar_body">
            <div style="text-align:center; padding:40px; color:var(--abu);">Memuat diskusi... ⏳</div>
        </div>
        
        <div class="modal-footer">
            <?php if($is_logged_in): ?>
                <div class="komentar-input-area">
                    <input type="hidden" id="input_komentar_post_id" value="">
                    <?php if (!empty($usr['foto_profil'])): ?>
                        <img src="uploads/profil/<?= htmlspecialchars($usr['foto_profil']) ?>" class="komentar-avatar" style="width:40px; height:40px;">
                    <?php else: ?>
                        <div class="komentar-avatar" style="width:40px; height:40px;"><?= strtoupper(substr($usr['nama'], 0, 1)) ?></div>
                    <?php endif; ?>
                    <textarea id="input_isi_komentar" class="komentar-textarea" rows="1" placeholder="Tulis balasan Anda..." oninput="this.style.height = '';this.style.height = this.scrollHeight + 'px'"></textarea>
                    <button class="btn-send-komentar" onclick="kirimKomentar()" id="btn_send_komentar" title="Kirim">➤</button>
                </div>
            <?php else: ?>
                <div style="text-align:center; color:var(--abu); font-size:14px; padding:10px 0;">
                    Silakan <a href="login-pendamping.php" style="color:var(--biru); font-weight:800; text-decoration:none;">Login</a> untuk ikut berdiskusi.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>

<script>
// Logic Batal Post
function batalPost() {
    document.getElementById('form-post-expand').style.display='none';
    document.getElementById('btn-trigger-post').style.display='block';
    // Reset Form
    document.querySelector('.form-post-textarea').value = '';
    hapusPapanTerpilih();
}

// Data Papan User (Dikirim dari PHP ke JS)
const myBoards = <?= json_encode($my_boards) ?>;
const inputSearch = document.getElementById('board_search_input');
const resultsContainer = document.getElementById('board_search_results');
const inputPapanId = document.getElementById('input_papan_id');
const previewArea = document.getElementById('board_selected_preview');
const searchArea = document.getElementById('board_search_container');

// Logic Board Picker
if(inputSearch) {
    inputSearch.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        resultsContainer.innerHTML = '';
        
        if (query.length === 0) {
            resultsContainer.style.display = 'none';
            return;
        }

        const filtered = myBoards.filter(b => 
            b.nama_papan.toLowerCase().includes(query) || 
            b.nama_anak.toLowerCase().includes(query)
        );

        if (filtered.length === 0) {
            resultsContainer.innerHTML = '<div style="padding:14px; text-align:center; color:gray; font-size:13px; font-weight:600;">Papan tidak ditemukan.</div>';
        } else {
            filtered.forEach(b => {
                const item = document.createElement('div');
                item.className = 'board-result-item';
                item.innerHTML = `
                    <div class="board-item-ikon">${b.ikon_papan || '📋'}</div>
                    <div class="board-item-info">
                        <h5>${b.nama_papan}</h5>
                        <p>Profil: ${b.nama_anak} • Kategori: ${b.kategori || 'Umum'}</p>
                    </div>
                `;
                item.onclick = function() {
                    pilihPapan(b);
                };
                resultsContainer.appendChild(item);
            });
        }
        resultsContainer.style.display = 'block';
    });

    // Sembunyikan hasil saat klik di luar
    document.addEventListener('click', function(e) {
        if (!document.getElementById('board_search_container').contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
}

function pilihPapan(b) {
    inputPapanId.value = b.id;
    document.getElementById('preview_ikon').textContent = b.ikon_papan || '📋';
    document.getElementById('preview_nama').textContent = b.nama_papan;
    document.getElementById('preview_meta').textContent = `Profil: ${b.nama_anak} • Grid: ${b.grid}`;
    
    searchArea.style.display = 'none';
    inputSearch.value = '';
    resultsContainer.style.display = 'none';
    previewArea.style.display = 'block';
}

function hapusPapanTerpilih() {
    inputPapanId.value = '';
    previewArea.style.display = 'none';
    searchArea.style.display = 'block';
}

// Logic Follow Ajax
function toggleFollow(targetId) {
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
            // Update semua tombol yang terkait dengan targetId ini (di feed maupun di tab orang)
            const btns = document.querySelectorAll(`.btn-tgl-follow-${targetId}`);
            btns.forEach(btn => {
                if (d.followed) {
                    btn.classList.add('following');
                    btn.textContent = 'Diikuti';
                } else {
                    btn.classList.remove('following');
                    btn.textContent = '+ Ikuti';
                }
            });
        } else {
            alert(d.pesan);
        }
    });
}

// Logic Like Ajax
function toggleLike(postId, btn) {
    <?php if (!$is_logged_in): ?>
        alert('Silakan login terlebih dahulu untuk menyukai postingan.');
        window.location.href = '<?= BASE_URL ?>login-pendamping.php';
        return;
    <?php endif; ?>

    const fd = new FormData();
    fd.append('aksi', 'toggle_like_post');
    fd.append('post_id', postId);

    fetch('komunitas.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'sukses') {
            btn.querySelector('.like-count').textContent = d.total;
            if (d.liked) {
                btn.classList.add('liked');
                btn.innerHTML = `❤️ <span class="like-count">${d.total}</span> Suka`;
            } else {
                btn.classList.remove('liked');
                btn.innerHTML = `🤍 <span class="like-count">${d.total}</span> Suka`;
            }
        }
    });
}

// ==========================================
// LOGIC KOMENTAR (MODAL & AJAX)
// ==========================================
const modalKomentar = document.getElementById('modalKomentar');
const modalBody = document.getElementById('modal_komentar_body');
const inputKomentar = document.getElementById('input_isi_komentar');
const inputKomentarPostId = document.getElementById('input_komentar_post_id');
const btnSendKomentar = document.getElementById('btn_send_komentar');

function bukaModalKomentar(postId) {
    modalKomentar.classList.add('active');
    document.body.style.overflow = 'hidden'; // cegah scroll background
    if(inputKomentarPostId) inputKomentarPostId.value = postId;
    if(inputKomentar) {
        inputKomentar.value = '';
        inputKomentar.style.height = '';
    }
    
    modalBody.innerHTML = '<div style="text-align:center; padding:40px; color:var(--abu);">Memuat diskusi... ⏳</div>';

    fetch(`komunitas.php?aksi=ambil_komentar&post_id=${postId}`)
    .then(r => r.json())
    .then(d => {
        if(d.status === 'sukses') {
            renderDaftarKomentar(d.data);
        } else {
            modalBody.innerHTML = '<div style="text-align:center; color:red;">Gagal memuat komentar.</div>';
        }
    })
    .catch(() => {
        modalBody.innerHTML = '<div style="text-align:center; color:red;">Gagal memuat komentar.</div>';
    });
}

function tutupModalKomentar() {
    modalKomentar.classList.remove('active');
    document.body.style.overflow = '';
}

// Tutup saat klik di luar modal container
modalKomentar.addEventListener('click', function(e) {
    if(e.target === modalKomentar) {
        tutupModalKomentar();
    }
});

function renderDaftarKomentar(data) {
    if(data.length === 0) {
        modalBody.innerHTML = `
            <div style="text-align:center; padding:50px 20px;">
                <div style="font-size:50px; margin-bottom:10px;">💭</div>
                <h4 style="color:var(--gelap); margin-bottom:6px; font-family:'Baloo 2', cursive; font-size:22px;">Belum ada balasan</h4>
                <p style="color:var(--abu); font-size:15px; margin:0; font-weight:600;">Jadilah yang pertama memulai diskusi di postingan ini!</p>
            </div>
        `;
        return;
    }

    let html = '<div class="komentar-list">';
    data.forEach(k => {
        let inisial = k.nama.charAt(0).toUpperCase();
        let ava = k.foto_profil 
            ? `<img src="uploads/profil/${k.foto_profil}" class="komentar-avatar">` 
            : `<div class="komentar-avatar">${inisial}</div>`;
        
        let peranBadge = k.peran ? `<span class="badge-peran" style="font-size:10px;">${k.peran}</span>` : '';
        
        // Format tanggal sederhana
        let tgl = new Date(k.created_at);
        let timeStr = tgl.toLocaleString('id-ID', {day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'});

        html += `
        <div class="komentar-item">
            ${ava}
            <div class="komentar-content">
                <div class="komentar-bubble">
                    <div class="komentar-name">${k.nama} ${peranBadge}</div>
                    <p class="komentar-text">${k.isi_komentar.replace(/\n/g, '<br>')}</p>
                </div>
                <span class="komentar-time">${timeStr}</span>
            </div>
        </div>
        `;
    });
    html += '</div>';
    modalBody.innerHTML = html;
    
    // Auto scroll ke paling bawah (komentar terbaru)
    setTimeout(() => {
        modalBody.scrollTop = modalBody.scrollHeight;
    }, 100);
}

function kirimKomentar() {
    <?php if(!$is_logged_in): ?> return; <?php endif; ?>
    
    if(!inputKomentar) return;
    
    const isi = inputKomentar.value.trim();
    const postId = inputKomentarPostId.value;
    
    if(!isi || !postId) return;

    btnSendKomentar.disabled = true;
    btnSendKomentar.innerHTML = '⏳';
    
    const fd = new FormData();
    fd.append('aksi', 'tambah_komentar');
    fd.append('post_id', postId);
    fd.append('isi_komentar', isi);

    fetch('komunitas.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        btnSendKomentar.disabled = false;
        btnSendKomentar.innerHTML = '➤';
        
        if(d.status === 'sukses') {
            inputKomentar.value = '';
            inputKomentar.style.height = '';
            
            // Refresh isi modal untuk mendapatkan komentar terbaru
            fetch(`komunitas.php?aksi=ambil_komentar&post_id=${postId}`)
            .then(r => r.json())
            .then(d2 => {
                if(d2.status === 'sukses') {
                    renderDaftarKomentar(d2.data);
                    
                    // Update angka komentar di feed halaman belakang
                    const countEl = document.querySelector(`.komen-count-${postId}`);
                    if(countEl) {
                        countEl.textContent = d2.data.length; // Total array komentar
                    }
                }
            });
        }
    })
    .catch(() => {
        btnSendKomentar.disabled = false;
        btnSendKomentar.innerHTML = '➤';
        alert("Gagal mengirim komentar. Periksa koneksi Anda.");
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
