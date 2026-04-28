<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$profil_id = (int)($_GET['profil_id'] ?? 0);

// 1. Ambil info profil ABK
$stmt = $conn->prepare("SELECT nama, jenis_abk FROM profil_abk WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $profil_id, $_SESSION['user_id']);
$stmt->execute();
$profil = $stmt->get_result()->fetch_assoc();

if (!$profil) redirect('dashboard/index.php');

// 2. Ambil semua papan milik profil ini
$stmt_papan = $conn->prepare("SELECT * FROM papan WHERE profil_id = ? ORDER BY created_at DESC");
$stmt_papan->bind_param('i', $profil_id);
$stmt_papan->execute();
$papan_list = $stmt_papan->get_result()->fetch_all(MYSQLI_ASSOC);

// Avatar map untuk header
$avatar_map = [
    'ASD' => '🧩', 'ADHD' => '⚡', 'Tunagrahita' => '🌟', 
    'Down Syndrome' => '🌈', 'Disleksia' => '📖'
];
$avatar = $avatar_map[$profil['jenis_abk']] ?? '😊';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Komunikasi <?= htmlspecialchars($profil['nama']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        /* Menggunakan variabel warna PAHAMIKU agar konsisten */
        :root {
            --biru: #4ECDC4;
            --biru-tua: #2BB5AC;
            --kuning: #FFD93D;
            --putih: #FFFDF7;
            --gelap: #1A1A2E;
            --abu: #6B7280;
            --merah: #FF6B6B;
            --radius: 20px;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #F3F4F6;
            color: var(--gelap);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-wrapper {
            flex: 1;
            width: 100%;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Header Profil */
        .header-profil {
            background: white;
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            animation: masuk 0.5s ease both;
        }

        .profil-info { display: flex; align-items: center; gap: 20px; }
        .profil-avatar { font-size: 50px; }
        .profil-teks h1 { 
            font-family: 'Baloo 2', cursive; 
            margin: 0; font-size: 28px; 
        }
        .profil-teks p { margin: 0; color: var(--abu); font-weight: 600; }

        /* Grid Papan */
        .grid-papan {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }

        .card-papan {
            background: white;
            border-radius: var(--radius);
            padding: 24px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 3px solid transparent;
            animation: masuk 0.5s ease both;
        }

        .card-papan:hover {
            transform: translateY(-5px);
            border-color: var(--biru);
            box-shadow: 0 12px 30px rgba(78, 205, 196, 0.2);
        }

        .papan-ikon { font-size: 40px; margin-bottom: 15px; display: block; }
        .papan-nama { font-family: 'Baloo 2', cursive; font-size: 20px; margin-bottom: 5px; }
        .papan-grid-size { font-size: 14px; color: var(--abu); margin-bottom: 20px; font-weight: 700; }

        /* Tombol Aksi */
        .aksi-papan { display: flex; gap: 10px; }
        .btn {
            flex: 1;
            padding: 10px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 800;
            font-size: 13px;
            transition: all 0.2s;
            text-align: center;
        }
        .btn-edit { background: var(--kuning); color: var(--gelap); }
        .btn-hapus { background: #FEF2F2; color: var(--merah); }
        .btn-cetak { background: #E0F2FE; color: #0284C7; }
        .btn-tambah {
            background: var(--gelap);
            color: white;
            padding: 12px 24px;
            border-radius: 15px;
            font-family: 'Baloo 2';
            font-size: 18px;
            display: inline-block;
            text-decoration: none;
        }

        .btn-kembali {
            text-decoration: none;
            color: var(--abu);
            font-weight: 700;
            margin-bottom: 20px;
            display: inline-block;
        }

        @keyframes masuk {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .header-profil {
                flex-direction: column;
                text-align: center;
                gap: 20px;
                padding: 20px;
            }
            .profil-info { flex-direction: column; gap: 10px; }
            .btn-tambah { width: 100%; padding: 12px; }
            .grid-papan { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include '../inc/navbar.php'; ?>

<div class="main-wrapper">
<div class="container">
    <a href="index.php" class="btn-kembali">← Kembali ke Dashboard</a>

    <div class="header-profil">
        <div class="profil-info">
            <span class="profil-avatar"><?= $avatar ?></span>
            <div class="profil-teks">
                <h1>Papan <?= htmlspecialchars($profil['nama']) ?></h1>
                <p>Kelola papan komunikasi untuk si kecil</p>
            </div>
        </div>
        <a href="papan-buat.php?profil_id=<?= $profil_id ?>" class="btn-tambah">+ Papan Baru</a>
    </div>

    <div class="grid-papan">
        <?php if (empty($papan_list)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 50px;">
                <p style="color: var(--abu); font-weight: 700;">Belum ada papan komunikasi.<br>Yuk, buatkan satu untuk <?= htmlspecialchars($profil['nama']) ?>!</p>
            </div>
        <?php else: ?>
            <?php foreach ($papan_list as $p): ?>
                <div class="card-papan">
                    <span class="papan-ikon">📋</span>
                    <div class="papan-nama"><?= htmlspecialchars($p['nama_papan']) ?></div>
                    <div class="papan-grid-size">Ukuran Kotak: <?= $p['grid'] ?></div>
                    <div class="aksi-papan">
                        <a href="papan-cetak.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-cetak">Cetak</a>
                        
                        <a href="papan-edit.php?id=<?= $p['id'] ?>" class="btn btn-edit">Atur</a>
                        <a href="papan-hapus.php?id=<?= $p['id'] ?>" 
                           onclick="return confirm('Hapus papan ini?')" 
                           class="btn btn-hapus">Hapus</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</div>

<?php include '../inc/footer.php'; ?>

</body>
</html>