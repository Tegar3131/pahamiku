<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$aksi = $_POST['aksi'] ?? '';
header('Content-Type: application/json; charset=utf-8');

function jsonResponse($payload, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'gagal', 'pesan' => 'Method tidak diizinkan'], 405);
}

$csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!validasiCsrf($csrfToken)) {
    jsonResponse(['status' => 'gagal', 'pesan' => 'CSRF token tidak valid'], 403);
}

if ($aksi === 'toggle_like') {
    $papan_id = (int)$_POST['papan_id'];
    $user_id  = $_SESSION['user_id'];

    // Cek apakah sudah disukai
    $stmt = $conn->prepare("SELECT 1 FROM papan_suka WHERE papan_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $papan_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Hapus like
        $stmt_del = $conn->prepare("DELETE FROM papan_suka WHERE papan_id = ? AND user_id = ?");
        $stmt_del->bind_param('ii', $papan_id, $user_id);
        $stmt_del->execute();
        $liked = false;
    } else {
        // Tambah like
        $stmt_ins = $conn->prepare("INSERT INTO papan_suka (papan_id, user_id) VALUES (?, ?)");
        $stmt_ins->bind_param('ii', $papan_id, $user_id);
        $stmt_ins->execute();
        $liked = true;
    }

    // Ambil total like terbaru
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM papan_suka WHERE papan_id = ?");
    $stmt_count->bind_param('i', $papan_id);
    $stmt_count->execute();
    $total = 0;
    $stmt_count->bind_result($total);
    $stmt_count->fetch();

    jsonResponse([
        'status' => 'sukses',
        'liked' => $liked,
        'total' => $total
    ]);
}

if ($aksi === 'tambah_simbol') {
    $papan_id  = (int)$_POST['papan_id'];
    $simbol_id = bersihkan($_POST['simbol_id']);
    $label     = bersihkan($_POST['label']);

    $stmt_cek = $conn->prepare("SELECT p.id FROM papan p JOIN profil_abk pr ON p.profil_id = pr.id WHERE p.id = ? AND pr.user_id = ?");
    $stmt_cek->bind_param('ii', $papan_id, $_SESSION['user_id']);
    $stmt_cek->execute();
    if ($stmt_cek->get_result()->num_rows === 0) {
        jsonResponse(['status' => 'gagal', 'pesan' => 'Akses ditolak'], 403);
    }

    $stmt = $conn->prepare("INSERT INTO papan_simbol (papan_id, simbol_id, label_custom) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $papan_id, $simbol_id, $label);
    
    if ($stmt->execute()) {
        jsonResponse(['status' => 'sukses']);
    } else {
        jsonResponse(['status' => 'gagal', 'pesan' => 'Gagal menyimpan'], 500);
    }
}

if ($aksi === 'hapus_simbol') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE ps FROM papan_simbol ps JOIN papan p ON ps.papan_id = p.id JOIN profil_abk pr ON p.profil_id = pr.id WHERE ps.id = ? AND pr.user_id = ?");
    $stmt->bind_param('ii', $id, $_SESSION['user_id']);
    $stmt->execute();
    jsonResponse(['status' => 'sukses']);
}

// FITUR 1: SIMPAN URUTAN DRAG & DROP
if ($aksi === 'update_urutan') {
    $data = json_decode($_POST['data'], true);
    foreach ($data as $item) {
        $stmt = $conn->prepare("UPDATE papan_simbol ps JOIN papan p ON ps.papan_id = p.id JOIN profil_abk pr ON p.profil_id = pr.id SET ps.urutan = ? WHERE ps.id = ? AND pr.user_id = ?");
        $stmt->bind_param('iii', $item['urutan'], $item['id'], $_SESSION['user_id']);
        $stmt->execute();
    }
    jsonResponse(['status' => 'sukses']);
}

// FITUR 3: UPDATE PENGATURAN PAPAN (Nama, Grid, Akses)
if ($aksi === 'update_papan_settings') {
    $papan_id  = (int)$_POST['papan_id'];
    $nama      = bersihkan($_POST['nama_papan']);
    $grid      = $_POST['grid'];
    $akses     = $_POST['access_type'];
    $deskripsi = isset($_POST['deskripsi']) ? bersihkan($_POST['deskripsi']) : '';

    // Cek kepemilikan dulu via profil_abk join
    $stmt = $conn->prepare("UPDATE papan p JOIN profil_abk pr ON p.profil_id = pr.id SET p.nama_papan = ?, p.grid = ?, p.access_type = ?, p.deskripsi = ? WHERE p.id = ? AND pr.user_id = ?");
    $stmt->bind_param('ssssii', $nama, $grid, $akses, $deskripsi, $papan_id, $_SESSION['user_id']);
    
    
    if ($stmt->execute()) {
        jsonResponse(['status' => 'sukses']);
    } else {
        jsonResponse(['status' => 'gagal', 'pesan' => 'Gagal menyimpan pengaturan'], 500);
    }
}

// FITUR 2: UPLOAD FOTO DARI KAMERA/GALERI
if ($aksi === 'upload_kamera') {
    $papan_id = (int)$_POST['papan_id'];
    $label = bersihkan($_POST['label']);

    if(isset($_FILES['foto'])) {
        $stmt_cek = $conn->prepare("SELECT p.id FROM papan p JOIN profil_abk pr ON p.profil_id = pr.id WHERE p.id = ? AND pr.user_id = ?");
        $stmt_cek->bind_param('ii', $papan_id, $_SESSION['user_id']);
        $stmt_cek->execute();
        if ($stmt_cek->get_result()->num_rows === 0) {
            jsonResponse(['status' => 'gagal', 'pesan' => 'Akses ditolak'], 403);
        }

        if (empty($_FILES['foto']['tmp_name']) || $_FILES['foto']['size'] > MAX_UPLOAD_SIZE) {
            jsonResponse(['status' => 'gagal', 'pesan' => 'Ukuran file tidak valid'], 400);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['foto']['tmp_name']);
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowedMimes[$mime])) {
            jsonResponse(['status' => 'gagal', 'pesan' => 'Format file tidak didukung'], 400);
        }

        $ext = $allowedMimes[$mime];
        $filename = 'upload-' . bin2hex(random_bytes(16)) . '.' . $ext;
        $target = '../uploads/' . $filename;
        
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Pindahkan file dari memori sementara ke folder uploads
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
            $stmt = $conn->prepare("INSERT INTO papan_simbol (papan_id, simbol_id, label_custom) VALUES (?, ?, ?)");
            $stmt->bind_param('iss', $papan_id, $filename, $label);
            $stmt->execute();
            jsonResponse(['status' => 'sukses']);
        } else {
            jsonResponse(['status' => 'gagal', 'pesan' => 'Gagal mengunggah file'], 500);
        }
    }
    jsonResponse(['status' => 'gagal', 'pesan' => 'File foto tidak ditemukan'], 400);
}

// ── MANAJEMEN PAPAN ABK: Toggle Aktif/Nonaktif ──
if ($aksi === 'toggle_papan_aktif') {
    $papan_id = (int)$_POST['papan_id'];
    
    // Cek kepemilikan: papan ini harus milik anak dari user yang login
    $stmt = $conn->prepare("SELECT p.is_aktif FROM papan p JOIN profil_abk pr ON p.profil_id = pr.id WHERE p.id = ? AND pr.user_id = ?");
    $stmt->bind_param('ii', $papan_id, $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    
    if ($row) {
        $baru = $row['is_aktif'] ? 0 : 1;
        $stmt_upd = $conn->prepare("UPDATE papan SET is_aktif = ? WHERE id = ?");
        $stmt_upd->bind_param('ii', $baru, $papan_id);
        $stmt_upd->execute();
        
        jsonResponse(['status' => 'sukses', 'is_aktif' => (bool)$baru]);
    } else {
        jsonResponse(['status' => 'gagal', 'pesan' => 'Papan tidak ditemukan'], 404);
    }
}

// ── MANAJEMEN PAPAN ABK: Update Urutan Tampil ──
if ($aksi === 'update_urutan_papan') {
    $data = json_decode($_POST['data'], true);
    $profil_id = (int)$_POST['profil_id'];
    
    // Verifikasi bahwa profil ini milik user yang login
    $stmt_cek = $conn->prepare("SELECT id FROM profil_abk WHERE id = ? AND user_id = ?");
    $stmt_cek->bind_param('ii', $profil_id, $_SESSION['user_id']);
    $stmt_cek->execute();
    if ($stmt_cek->get_result()->num_rows === 0) {
        jsonResponse(['status' => 'gagal', 'pesan' => 'Profil tidak valid'], 403);
    }
    
    foreach ($data as $item) {
        $id = (int)$item['id'];
        $urutan = (int)$item['urutan'];
        $tipe = $item['tipe'] ?? 'milik'; // 'milik' atau 'preset'
        
        if ($tipe === 'preset') {
            // Update/insert ke profil_papan_preset
            $stmt = $conn->prepare("INSERT INTO profil_papan_preset (profil_id, papan_id, urutan_tampil) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE urutan_tampil = ?");
            $stmt->bind_param('iiii', $profil_id, $id, $urutan, $urutan);
        } else {
            // Update papan milik anak
            $stmt = $conn->prepare("UPDATE papan SET urutan_tampil = ? WHERE id = ? AND profil_id = ?");
            $stmt->bind_param('iii', $urutan, $id, $profil_id);
        }
        $stmt->execute();
    }
    
    jsonResponse(['status' => 'sukses']);
}

// ── MANAJEMEN PAPAN ABK: Toggle Preset untuk Anak ──
if ($aksi === 'toggle_preset_abk') {
    $papan_id  = (int)$_POST['papan_id'];
    $profil_id = (int)$_POST['profil_id'];
    
    // Verifikasi bahwa profil ini milik user yang login
    $stmt_cek = $conn->prepare("SELECT id FROM profil_abk WHERE id = ? AND user_id = ?");
    $stmt_cek->bind_param('ii', $profil_id, $_SESSION['user_id']);
    $stmt_cek->execute();
    if ($stmt_cek->get_result()->num_rows === 0) {
        jsonResponse(['status' => 'gagal', 'pesan' => 'Profil tidak valid'], 403);
    }
    
    // Verifikasi bahwa papan_id adalah papan preset (profil_id IS NULL)
    $stmt_papan = $conn->prepare("SELECT id FROM papan WHERE id = ? AND profil_id IS NULL");
    $stmt_papan->bind_param('i', $papan_id);
    $stmt_papan->execute();
    if ($stmt_papan->get_result()->num_rows === 0) {
        jsonResponse(['status' => 'gagal', 'pesan' => 'Bukan papan preset'], 400);
    }
    
    // Cek apakah sudah ada entri
    $stmt_exist = $conn->prepare("SELECT is_aktif FROM profil_papan_preset WHERE profil_id = ? AND papan_id = ?");
    $stmt_exist->bind_param('ii', $profil_id, $papan_id);
    $stmt_exist->execute();
    $exist = $stmt_exist->get_result()->fetch_assoc();
    
    if ($exist) {
        $baru = $exist['is_aktif'] ? 0 : 1;
        $stmt_upd = $conn->prepare("UPDATE profil_papan_preset SET is_aktif = ? WHERE profil_id = ? AND papan_id = ?");
        $stmt_upd->bind_param('iii', $baru, $profil_id, $papan_id);
        $stmt_upd->execute();
    } else {
        // Belum ada entri = default tampil, jadi toggle pertama = nonaktifkan
        $baru = 0;
        $stmt_ins = $conn->prepare("INSERT INTO profil_papan_preset (profil_id, papan_id, is_aktif) VALUES (?, ?, 0)");
        $stmt_ins->bind_param('ii', $profil_id, $papan_id);
        $stmt_ins->execute();
    }
    
    jsonResponse(['status' => 'sukses', 'is_aktif' => (bool)$baru]);
}

jsonResponse(['status' => 'gagal', 'pesan' => 'Aksi tidak dikenal'], 400);
