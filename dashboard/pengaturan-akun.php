<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$error = '';
$sukses = '';

// Ambil data user
$stmt = $conn->prepare("SELECT nama, email, foto_profil, bio, peran FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = bersihkan($_POST['nama'] ?? '');
    $bio   = bersihkan($_POST['bio'] ?? '');
    $peran = bersihkan($_POST['peran'] ?? 'orang tua');

    if (!$nama) {
        $error = 'Nama tidak boleh kosong.';
    } else {
        $foto_profil = $user['foto_profil'];

        // Upload Foto
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (!in_array($ext, $allowed)) {
                $error = 'Format foto harus JPG, PNG, atau WEBP.';
            } elseif ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran maksimal 2MB.';
            } else {
                $foto_baru = uniqid('user_') . '.' . $ext;
                $tujuan = '../uploads/profil/' . $foto_baru;
                
                if (!is_dir('../uploads/profil/')) mkdir('../uploads/profil/', 0755, true);

                if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $tujuan)) {
                    if (!empty($foto_profil) && file_exists('../uploads/profil/' . $foto_profil)) {
                        unlink('../uploads/profil/' . $foto_profil);
                    }
                    $foto_profil = $foto_baru;
                } else {
                    $error = 'Gagal upload foto.';
                }
            }
        }

        if (!$error) {
            // Update Database (kita abaikan jika kolom bio tidak ada pakai try catch, walau seharusnya ada)
            try {
                $upd = $conn->prepare("UPDATE users SET nama = ?, bio = ?, peran = ?, foto_profil = ? WHERE id = ?");
                $upd->bind_param('ssssi', $nama, $bio, $peran, $foto_profil, $_SESSION['user_id']);
                $upd->execute();
                
                // Refresh data session
                $_SESSION['user_nama'] = $nama;
                $sukses = 'Profil berhasil diperbarui.';
                
                // Refresh user array
                $user['nama'] = $nama;
                $user['bio'] = $bio;
                $user['peran'] = $peran;
                $user['foto_profil'] = $foto_profil;
            } catch (Exception $e) {
                $error = 'Gagal menyimpan data: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun Pendamping</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --biru: #4ECDC4; --biru-tua: #2BB5AC; --gelap: #1A1A2E; 
            --abu: #6B7280; --abu-muda: #F3F4F6;
        }
        body { font-family: 'Nunito', sans-serif; background: var(--abu-muda); color: var(--gelap); min-height: 100vh; display: flex; flex-direction: column;}
        .main-wrapper { max-width: 600px; margin: 40px auto; width: 100%; padding: 0 20px; flex: 1; }
        .card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .card-judul { font-family: 'Baloo 2', cursive; font-size: 26px; font-weight: 800; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 800; font-size: 13px; margin-bottom: 8px; text-transform: uppercase; color: var(--gelap); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 14px; border: 2px solid #E5E7EB; border-radius: 12px; font-family: 'Nunito', sans-serif; font-size: 15px; background: #FAFAFA;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--biru); background: white; outline: none; }
        
        .btn-submit { width: 100%; padding: 16px; background: var(--biru); color: white; border: none; border-radius: 12px; font-family: 'Baloo 2', cursive; font-size: 18px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: var(--biru-tua); }
        
        .alert-sukses { background: #D1FAE5; color: #065F46; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; }
        .alert-gagal { background: #FEE2E2; color: #991B1B; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; }
        
        .avatar-preview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #E5E7EB; margin-bottom: 10px; }
    </style>
</head>
<body>

<?php include '../inc/navbar.php'; ?>

<div class="main-wrapper">
    <nav aria-label="breadcrumb" style="margin-bottom: 24px;">
        <ol style="list-style: none; display: flex; gap: 10px; align-items: center; font-weight: 800; font-size: 14px; color: var(--abu); margin: 0; padding: 0;">
            <li><a href="../komunitas.php" style="color: var(--biru); text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='var(--gelap)'" onmouseout="this.style.color='var(--biru)'">Komunitas</a></li>
            <li style="font-size: 12px; opacity: 0.5;">▶</li>
            <li><a href="../profil-kreator.php?id=<?= $_SESSION['user_id'] ?>" style="color: var(--biru); text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='var(--gelap)'" onmouseout="this.style.color='var(--biru)'">Profil Publik</a></li>
            <li style="font-size: 12px; opacity: 0.5;">▶</li>
            <li style="color: var(--gelap);">⚙️ Edit Profil</li>
        </ol>
    </nav>
    <div class="card">
        <div class="card-judul">⚙️ Pengaturan Profil Publik</div>
        <p style="color:var(--abu); margin-bottom:20px;">Profil ini akan tampil di Komunitas saat Anda membagikan cerita atau papan AAC.</p>

        <?php if ($sukses) echo "<div class='alert-sukses'>$sukses</div>"; ?>
        <?php if ($error) echo "<div class='alert-gagal'>$error</div>"; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Foto Profil</label>
                <?php if (!empty($user['foto_profil'])): ?>
                    <img src="../uploads/profil/<?= htmlspecialchars($user['foto_profil']) ?>" class="avatar-preview">
                <?php endif; ?>
                <input type="file" name="foto_profil" accept="image/*">
            </div>

            <div class="form-group">
                <label>Nama Anda</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
            </div>

            <div class="form-group">
                <label>Peran Pendamping</label>
                <select name="peran">
                    <option value="orang tua" <?= $user['peran'] === 'orang tua' ? 'selected' : '' ?>>Orang Tua</option>
                    <option value="guru" <?= $user['peran'] === 'guru' ? 'selected' : '' ?>>Guru</option>
                    <option value="terapis" <?= $user['peran'] === 'terapis' ? 'selected' : '' ?>>Terapis</option>
                    <option value="caregiver" <?= $user['peran'] === 'caregiver' ? 'selected' : '' ?>>Caregiver</option>
                    <option value="lainnya" <?= $user['peran'] === 'lainnya' ? 'selected' : '' ?>>Lainnya</option>
                </select>
            </div>

            <div class="form-group">
                <label>Bio Singkat</label>
                <textarea name="bio" rows="3" placeholder="Ceritakan sedikit tentang Anda..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-submit">Simpan Profil</button>
        </form>
    </div>
</div>

<?php include '../inc/footer.php'; ?>
</body>
</html>
