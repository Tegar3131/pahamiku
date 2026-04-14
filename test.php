// Buat file test.php sementara, isi:
<?php
include 'inc/config.php';
$simbol = getAllSimbol($conn);
echo count($simbol); // harus tampil angka kategori
?>