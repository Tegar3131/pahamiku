<?php
// logout.php
// Hancurkan session dan kembali ke beranda

if (session_status() === PHP_SESSION_NONE) session_start();

// Simpan jenis siapa yang logout — untuk redirect yang tepat
$jenis = $_GET['jenis'] ?? 'pendamping'; // 'pendamping' atau 'abk'

include 'inc/config.php';

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke halaman yang sesuai
if ($jenis === 'abk') {
    header('Location: ' . BASE_URL . 'login-abk.php');
} else {
    header('Location: ' . BASE_URL . 'login-pendamping.php');
}
exit;
