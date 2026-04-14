<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

// Ambil ID papan dari URL
$id = (int)($_GET['id'] ?? 0);

// 1. Ambil data papan untuk memastikan kepemilikan dan menampilkan nama papan di layar konfirmasi
$stmt = $conn->prepare("
    SELECT p.id, p.nama_papan, p.profil_id, pr.nama as nama_anak 
    FROM papan p 
    JOIN profil_abk pr ON p.profil_id = pr.id 
    WHERE p.id = ? AND pr.user_id = ?
");
$stmt->bind_param('ii', $id, $_SESSION['user_id']);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

// Jika data tidak ditemukan, balikkan ke dashboard
if (!$data) redirect('dashboard/index.php');

$profil_id = $data['profil_id'];

// 2. Jika tombol "Ya, Hapus" diklik (Proses Penghapusan)
if (isset($_POST['konfirmasi_hapus'])) {
    // Karena ada ON DELETE CASCADE, isi simbol di dalam papan otomatis ikut terhapus.
    $hapus = $conn->prepare("DELETE FROM papan WHERE id = ?");
    $hapus->bind_param('i', $id);
    
    if ($hapus->execute()) {
        setFlash('sukses', "Papan '{$data['nama_papan']}' milik {$data['nama_anak']} berhasil dihapus.");
    } else {
        setFlash('gagal', 'Gagal menghapus papan. Silakan coba lagi.');
    }
    
    redirect("dashboard/papan-list.php?profil_id=" . $profil_id);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Hapus — PAHAMIKU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --merah: #FF6B6B;
            --gelap: #1A1A2E;
            --abu: #6B7280;
        }
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #F3F4F6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .card-konfirmasi {
            border: none;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 450px;
            width: 90%;
            overflow: hidden;
            animation: bounceIn 0.5s ease;
        }
        .card-body { padding: 40px 30px; text-align: center; }
        .ikon-peringatan {
            font-size: 60px;
            color: var(--merah);
            margin-bottom: 20px;
        }
        h2 { font-family: 'Baloo 2', cursive; font-weight: 800; color: var(--gelap); }
        .nama-papan { color: var(--merah); font-weight: 700; }
        .btn-hapus {
            background-color: var(--merah);
            border: none;
            border-radius: 14px;
            padding: 12px 25px;
            font-weight: 700;
            transition: all 0.2s;
        }
        .btn-hapus:hover { background-color: #ee5253; transform: scale(1.05); }
        .btn-batal {
            color: var(--abu);
            text-decoration: none;
            font-weight: 700;
            display: block;
            margin-top: 20px;
        }
        @keyframes bounceIn {
            0% { transform: scale(0.8); opacity: 0; }
            70% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>

<div class="card card-konfirmasi">
    <div class="card-body">
        <div class="ikon-peringatan">⚠️</div>
        <h2>Hapus Papan?</h2>
        <p class="text-muted mt-3">
            Apakah kamu yakin ingin menghapus papan <br>
            <span class="nama-papan">"<?= htmlspecialchars($data['nama_papan']) ?>"</span>?<br>
            Tindakan ini tidak bisa dibatalkan.
        </p>

        <form method="POST" class="mt-4">
            <button type="submit" name="konfirmasi_hapus" class="btn btn-danger btn-hapus w-100">
                Ya, Hapus Sekarang
            </button>
        </form>

        <a href="papan-list.php?profil_id=<?= $profil_id ?>" class="btn-batal">
            Tidak, Batalkan
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>