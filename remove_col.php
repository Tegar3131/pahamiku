<?php
include 'inc/config.php';
$res = $conn->query("ALTER TABLE postingan DROP COLUMN gambar_lampiran");
if ($res) {
    echo "Kolom gambar_lampiran berhasil dihapus dari tabel postingan.";
} else {
    echo "Gagal menghapus kolom: " . $conn->error;
}
?>
