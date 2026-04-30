<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$papan_id = (int)($_GET['papan_id'] ?? 0);

// Ambil info papan sumber
$stmt = $conn->prepare("SELECT * FROM papan WHERE id = ? AND access_type = 'public'");
$stmt->bind_param('i', $papan_id);
$stmt->execute();
$papan_sumber = $stmt->get_result()->fetch_assoc();

if (!$papan_sumber) {
    setFlash('gagal', 'Papan tidak ditemukan atau tidak tersedia untuk publik.');
    redirect('komunitas.php');
}

// Ambil profil ABK milik user ini
$stmt_profil = $conn->prepare("SELECT id, nama FROM profil_abk WHERE user_id = ?");
$stmt_profil->bind_param('i', $_SESSION['user_id']);
$stmt_profil->execute();
$profils = $stmt_profil->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($profils)) {
    setFlash('gagal', 'Anda harus memiliki setidaknya 1 profil anak untuk menduplikasi papan.');
    redirect('dashboard/index.php');
}

// Proses Duplikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profil_tujuan = (int)$_POST['profil_id'];
    
    // Pastikan profil_id benar milik user
    $valid = false;
    foreach($profils as $p) { if($p['id'] == $profil_tujuan) $valid = true; }
    
    if ($valid) {
        $conn->begin_transaction();
        try {
            // 1. Salin record papan
            $stmt_ins = $conn->prepare("INSERT INTO papan (profil_id, nama_papan, ikon_papan, grid, access_type, original_papan_id) VALUES (?, ?, ?, ?, 'private', ?)");
            $nama_baru = $papan_sumber['nama_papan'] . ' (Salinan)';
            $stmt_ins->bind_param('isssi', $profil_tujuan, $nama_baru, $papan_sumber['ikon_papan'], $papan_sumber['grid'], $papan_id);
            $stmt_ins->execute();
            $papan_baru_id = $conn->insert_id;

            // 2. Salin simbol-simbol di dalamnya
            $stmt_simbols = $conn->prepare("SELECT * FROM papan_simbol WHERE papan_id = ?");
            $stmt_simbols->bind_param('i', $papan_id);
            $stmt_simbols->execute();
            $simbols = $stmt_simbols->get_result()->fetch_all(MYSQLI_ASSOC);

            if (!empty($simbols)) {
                $stmt_ins_simbol = $conn->prepare("INSERT INTO papan_simbol (papan_id, simbol_id, label_custom, urutan) VALUES (?, ?, ?, ?)");
                foreach ($simbols as $simbol) {
                    $stmt_ins_simbol->bind_param('issi', $papan_baru_id, $simbol['simbol_id'], $simbol['label_custom'], $simbol['urutan']);
                    $stmt_ins_simbol->execute();
                }
            }

            $conn->commit();
            setFlash('sukses', 'Papan berhasil disalin ke profil Anda!');
            redirect("dashboard/papan-edit.php?id=$papan_baru_id");

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Terjadi kesalahan saat menyalin papan.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salin Papan — PAHAMIKU</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --biru:       #4ECDC4;
            --gelap:      #1A1A2E;
            --putih:      #F8FAFC;
            --abu:        #64748B;
            --radius-lg:  24px;
        }
        body { font-family: 'Nunito', sans-serif; background-color: var(--putih); color: var(--gelap); display: flex; flex-direction: column; min-height: 100vh;}
        .container { max-width: 600px; margin: 60px auto; padding: 20px; flex: 1; }
        .card { background: white; border-radius: var(--radius-lg); padding: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; }
        .icon-large { font-size: 64px; margin-bottom: 20px; display: inline-block; }
        h2 { font-family: 'Baloo 2', cursive; font-size: 28px; margin-bottom: 10px; }
        p { color: var(--abu); margin-bottom: 30px; font-size: 15px; }
        .form-group { margin-bottom: 24px; text-align: left; }
        .form-label { display: block; font-weight: 800; margin-bottom: 8px; font-size: 14px; }
        .form-select { width: 100%; padding: 14px 16px; border-radius: 12px; border: 2px solid #E2E8F0; font-family: 'Nunito', sans-serif; font-size: 15px; font-weight: 700; }
        .btn-submit { width: 100%; background: var(--biru); color: white; border: none; padding: 16px; border-radius: 12px; font-family: 'Baloo 2', cursive; font-size: 20px; cursor: pointer; transition: all 0.2s; }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(78,205,196,0.3); }
        .btn-batal { display: inline-block; margin-top: 16px; color: var(--abu); text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>

<?php include '../inc/navbar.php'; ?>

<div class="container">
    <div class="card">
        <span class="icon-large">⬇️</span>
        <h2>Salin Papan ke Akun Anda</h2>
        <p>Anda akan menyalin papan "<strong><?= htmlspecialchars($papan_sumber['nama_papan']) ?></strong>". Papan salinan ini akan menjadi milik Anda (Privat) dan bisa Anda edit isinya secara bebas.</p>

        <?php if(isset($error)): ?>
            <div style="background: #FEF2F2; color: #DC2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 700;"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Simpan ke profil anak (ABK) yang mana?</label>
                <select name="profil_id" class="form-select" required>
                    <?php foreach($profils as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit">✅ Salin & Mulai Edit Papan</button>
        </form>

        <a href="<?= BASE_URL ?>komunitas.php" class="btn-batal">Batal, kembali ke komunitas</a>
    </div>
</div>

</body>
</html>
