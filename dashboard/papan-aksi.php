<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$aksi = $_POST['aksi'] ?? '';

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