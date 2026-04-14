<?php
// dashboard/profil-hapus.php
// Hapus profil ABK — hanya via GET dengan konfirmasi JS
// Foreign key ON DELETE CASCADE akan hapus papan & favorit otomatis

if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$id = (int) ($_GET['id'] ?? 0);

if ($id) {
    // Pastikan profil ini milik pendamping yang login
    $stmt = $conn->prepare("
        SELECT nama FROM profil_abk 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param('ii', $id, $_SESSION['user_id']);
    $stmt->execute();
    $profil = $stmt->get_result()->fetch_assoc();

    if ($profil) {
        $hapus = $conn->prepare("DELETE FROM profil_abk WHERE id = ?");
        $hapus->bind_param('i', $id);
        $hapus->execute();
        setFlash('sukses', "Profil {$profil['nama']} berhasil dihapus.");
    } else {
        setFlash('gagal', 'Profil tidak ditemukan.');
    }
}

redirect('dashboard/index.php');
