<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$aksi = $_POST['aksi'] ?? '';

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

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'sukses',
        'liked' => $liked,
        'total' => $total
    ]);
    exit;
}

if ($aksi === 'tambah_simbol') {
    $papan_id  = (int)$_POST['papan_id'];
    $simbol_id = bersihkan($_POST['simbol_id']);
    $label     = bersihkan($_POST['label']);

    // HANYA simpan ke tabel papan_simbol
    // Kita tidak lagi melakukan INSERT ke master_simbol
    $stmt = $conn->prepare("INSERT INTO papan_simbol (papan_id, simbol_id, label_custom) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $papan_id, $simbol_id, $label);
    
    if ($stmt->execute()) {
        echo 'sukses';
    } else {
        echo 'gagal';
    }
}

if ($aksi === 'hapus_simbol') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM papan_simbol WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo 'sukses';
}

// FITUR 1: SIMPAN URUTAN DRAG & DROP
if ($aksi === 'update_urutan') {
    $data = json_decode($_POST['data'], true);
    foreach ($data as $item) {
        $stmt = $conn->prepare("UPDATE papan_simbol SET urutan = ? WHERE id = ?");
        $stmt->bind_param('ii', $item['urutan'], $item['id']);
        $stmt->execute();
    }
    echo 'sukses';
    exit;
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
        echo 'sukses';
    } else {
        echo 'gagal';
    }
    exit;
}

// FITUR 2: UPLOAD FOTO DARI KAMERA/GALERI
if ($aksi === 'upload_kamera') {
    $papan_id = (int)$_POST['papan_id'];
    $label = bersihkan($_POST['label']);

    if(isset($_FILES['foto'])) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $filename = 'upload-' . time() . '.' . $ext; // Penamaan unik
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
            echo 'sukses';
        } else {
            echo 'gagal_upload';
        }
    }
    exit;
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
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'sukses', 'is_aktif' => (bool)$baru]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'gagal', 'pesan' => 'Papan tidak ditemukan']);
    }
    exit;
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
        echo json_encode(['status' => 'gagal']);
        exit;
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
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'sukses']);
    exit;
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
        header('Content-Type: application/json');
        echo json_encode(['status' => 'gagal']);
        exit;
    }
    
    // Verifikasi bahwa papan_id adalah papan preset (profil_id IS NULL)
    $stmt_papan = $conn->prepare("SELECT id FROM papan WHERE id = ? AND profil_id IS NULL");
    $stmt_papan->bind_param('i', $papan_id);
    $stmt_papan->execute();
    if ($stmt_papan->get_result()->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'gagal', 'pesan' => 'Bukan papan preset']);
        exit;
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
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'sukses', 'is_aktif' => (bool)$baru]);
    exit;
}