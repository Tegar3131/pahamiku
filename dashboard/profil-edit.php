<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$id = (int)($_GET['id'] ?? 0);
$error = '';

// Ambil data profil lama
$stmt = $conn->prepare("SELECT * FROM profil_abk WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $_SESSION['user_id']);
$stmt->execute();
$profil = $stmt->get_result()->fetch_assoc();

if (!$profil) redirect('dashboard/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = bersihkan($_POST['nama'] ?? '');
    $pin       = bersihkan($_POST['pin'] ?? '');
    $jenis_abk = bersihkan($_POST['jenis_abk'] ?? '');

    if (!$nama) {
        $error = 'Nama pengguna wajib diisi.';
    } elseif (strlen($pin) !== 4 || !ctype_digit($pin)) {
        $error = 'PIN harus tepat 4 angka.';
    } else {
        $foto_profil = $profil['foto_profil'];

        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (!in_array($ext, $allowed)) {
                $error = 'Format foto harus berupa JPG, PNG, atau WEBP.';
            } elseif ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran foto terlalu besar. Maksimal 2MB.';
            } else {
                $foto_baru = uniqid('profil_') . '.' . $ext;
                $tujuan = '../uploads/profil/' . $foto_baru;
                if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $tujuan)) {
                    if (!empty($foto_profil) && file_exists('../uploads/profil/' . $foto_profil)) {
                        unlink('../uploads/profil/' . $foto_profil);
                    }
                    $foto_profil = $foto_baru;
                } else {
                    $error = 'Gagal menyimpan foto profil ke server.';
                }
            }
        }

        if (!$error) {
            $update = $conn->prepare("UPDATE profil_abk SET nama=?, pin=?, jenis_abk=?, foto_profil=? WHERE id=?");
            $update->bind_param('ssssi', $nama, $pin, $jenis_abk, $foto_profil, $id);
            if ($update->execute()) {
                setFlash('sukses', "Profil {$nama} berhasil diperbarui! 🎉");
                redirect('dashboard/index.php');
            } else {
                $error = 'Gagal menyimpan profil. Coba lagi.';
            }
        }
    }
}

$jenis_abk_list = [
    'ASD', 'ADHD', 'Tunagrahita', 'Down Syndrome',
    'Disleksia', 'Diskalkulia', 'Tunarungu', 'Tunadaksa', 'Lainnya'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAHAMIKU — Edit Profil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --kuning:   #FFD93D;
            --biru:     #4ECDC4;
            --biru-tua: #2BB5AC;
            --merah:    #FF6B6B;
            --putih:    #FFFDF7;
            --gelap:    #1A1A2E;
            --abu:      #6B7280;
            --abu-muda: #F3F4F6;
        }
        body {
            font-family: 'Nunito', sans-serif;
            background: var(--abu-muda);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-wrapper {
            flex: 1;
            padding: 32px 20px;
        }
        .wrapper {
            max-width: 480px;
            margin: 0 auto;
        }
        .kembali {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            color: var(--abu);
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 24px;
            transition: color 0.2s;
        }
        .kembali:hover { color: var(--biru-tua); }

        .card {
            background: white;
            border-radius: 24px;
            padding: 36px 32px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .card-judul {
            font-family: 'Baloo 2', cursive;
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .card-sub {
            font-size: 14px;
            color: var(--abu);
            font-weight: 600;
            margin-bottom: 28px;
        }

        .alert-gagal {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--gelap);
            margin-bottom: 7px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-family: 'Nunito', sans-serif;
            font-size: 15px;
            font-weight: 600;
            color: var(--gelap);
            background: #FAFAFA;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--biru);
            background: white;
        }
        .form-hint {
            font-size: 12px;
            color: var(--abu);
            font-weight: 600;
            margin-top: 6px;
        }

        /* Preview PIN */
        .pin-preview {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .pin-box {
            width: 44px;
            height: 44px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Baloo 2', cursive;
            font-size: 22px;
            font-weight: 800;
            color: var(--gelap);
            background: #FAFAFA;
            transition: all 0.2s;
        }
        .pin-box.isi {
            border-color: var(--kuning);
            background: #FFFBEA;
        }

        .btn-simpan {
            width: 100%;
            padding: 16px;
            background: var(--biru);
            color: white;
            border: none;
            border-radius: 14px;
            font-family: 'Baloo 2', cursive;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }
        .btn-simpan:hover {
            background: var(--biru-tua);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(78,205,196,0.4);
        }

        @keyframes masuk {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .wrapper { animation: masuk 0.4s ease both; }
    </style>
</head>
<body>

<?php include '../inc/navbar.php'; ?>

<div class="main-wrapper">
<div class="wrapper">
    <a href="index.php" class="kembali">← Kembali ke Dashboard</a>

    <div class="card">
        <div class="card-judul">✏️ Edit Profil</div>
        <p class="card-sub">Ubah data pengguna PAHAMIKU ini.</p>

        <?php if ($error): ?>
            <div class="alert-gagal"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
        
            <!-- Foto Profil -->
            <div class="form-group">
                <label>Foto Wajah (Opsional)</label>
                <?php if (!empty($profil['foto_profil'])): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="../uploads/profil/<?= htmlspecialchars($profil['foto_profil']) ?>" alt="Foto Profil" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #E5E7EB; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                    </div>
                <?php endif; ?>
                <input type="file" name="foto_profil" accept="image/jpeg, image/png, image/webp" style="padding: 10px;">
                <p class="form-hint">Maksimal 2MB. Kosongkan jika tidak ingin mengubah foto.</p>
            </div>

            <!-- Nama -->
            <div class="form-group">
                <label>Nama Pengguna *</label>
                <input type="text" name="nama" 
                       placeholder="Contoh: Raka, Sari, Bimo"
                       value="<?= htmlspecialchars($_POST['nama'] ?? $profil['nama']) ?>"
                       required>
            </div>

            <!-- Jenis ABK -->
            <div class="form-group">
                <label>Jenis Kebutuhan Khusus</label>
                <select name="jenis_abk">
                    <option value="">-- Pilih (opsional) --</option>
                    <?php 
                    $curr_jenis = $_POST['jenis_abk'] ?? $profil['jenis_abk'];
                    foreach ($jenis_abk_list as $j): ?>
                        <option value="<?= $j ?>" 
                            <?= ($curr_jenis === $j) ? 'selected' : '' ?>>
                            <?= $j ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="form-hint">Pilihan ini mempengaruhi avatar di halaman login.</p>
            </div>

            <!-- PIN -->
            <div class="form-group">
                <label>PIN 4 Angka *</label>
                <input type="number" name="pin" 
                       id="input-pin"
                       placeholder="Contoh: 1234"
                       min="1000" max="9999"
                       value="<?= htmlspecialchars($_POST['pin'] ?? $profil['pin']) ?>"
                       oninput="updatePinPreview(this.value)"
                       required>
                <!-- Preview PIN -->
                <div class="pin-preview">
                    <div class="pin-box" id="pb0">_</div>
                    <div class="pin-box" id="pb1">_</div>
                    <div class="pin-box" id="pb2">_</div>
                    <div class="pin-box" id="pb3">_</div>
                </div>
                <p class="form-hint">
                    Ubah PIN hanya jika diperlukan. Pendamping dapat melihat dan mengatur ulang PIN dari sini.
                </p>
            </div>

            <button type="submit" class="btn-simpan">Simpan Perubahan →</button>
        </form>
    </div>
</div>
</div>

<?php include '../inc/footer.php'; ?>

<script>
function updatePinPreview(val) {
    var digits = val.toString().slice(0, 4).split('');
    for (var i = 0; i < 4; i++) {
        var box = document.getElementById('pb' + i);
        if (digits[i]) {
            box.textContent = digits[i];
            box.classList.add('isi');
        } else {
            box.textContent = '_';
            box.classList.remove('isi');
        }
    }
}
// Inisialisasi kalau ada nilai sebelumnya
var pinAwal = document.getElementById('input-pin').value;
if (pinAwal) updatePinPreview(pinAwal);
</script>
</body>
</html>