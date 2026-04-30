<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'inc/config.php';

// Kalau sudah login, langsung ke halaman dashboard
if (isset($_SESSION['user_id'])) {
    redirect('dashboard/index.php');
}

$error  = '';
$sukses = '';

// Mode tampil awal: 'masuk' atau 'daftar'
$mode = $_GET['mode'] ?? 'masuk';
if (isset($_POST['aksi'])) $mode = $_POST['aksi'];

// ── Proses: Daftar Akun Baru ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'daftar') {
    $nama     = bersihkan($_POST['nama'] ?? '');
    $email    = bersihkan($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirm  = $_POST['konfirm'] ?? '';

    if (!$nama || !$email || !$password) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek email sudah terdaftar
        $cek  = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $cek->bind_param('s', $email);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $nama, $email, $hash);
            if ($stmt->execute()) {
                $sukses = 'Akun berhasil dibuat! Silakan masuk.';
                $mode   = 'masuk'; // Pindah ke tab masuk
            } else {
                $error = 'Gagal membuat akun. Coba lagi.';
            }
        }
    }
}

// ── Proses: Masuk ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'masuk') {
    $email    = bersihkan($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id, nama, password FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $hasil = $stmt->get_result()->fetch_assoc();

        if ($hasil && password_verify($password, $hasil['password'])) {
            $_SESSION['user_id']   = $hasil['id'];
            $_SESSION['user_nama'] = $hasil['nama'];
            redirect('dashboard/index.php');
        } else {
            $error = 'Email atau password salah.';
        }
    }
}

// Variabel $mode sudah di-set di atas
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAHAMIKU — Masuk Pendamping</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --biru:     #4ECDC4;
            --biru-tua: #2BB5AC;
            --putih:    #FFFDF7;
            --gelap:    #1A1A2E;
            --abu:      #6B7280;
            --merah:    #FF6B6B;
            --hijau:    #6BCB77;
            --radius:   20px;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #E0F7F6 0%, #FFFDF7 60%, #FFF3E0 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 440px;
            animation: masuk 0.5s ease both;
        }

        /* ── Kembali ── */
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

        /* ── Card ── */
        .card {
            background: white;
            border-radius: 28px;
            padding: 40px 36px;
            box-shadow: 0 12px 48px rgba(78,205,196,0.15);
        }

        .card-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .card-ikon { font-size: 52px; margin-bottom: 12px; }
        .card-judul {
            font-family: 'Baloo 2', cursive;
            font-size: 28px;
            font-weight: 800;
            color: var(--gelap);
        }
        .card-sub {
            font-size: 14px;
            color: var(--abu);
            margin-top: 4px;
        }

        /* ── Tab masuk / daftar ── */
        .tab-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #F3F4F6;
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 28px;
        }
        .tab-btn {
            padding: 10px;
            border: none;
            background: transparent;
            border-radius: 10px;
            font-family: 'Nunito', sans-serif;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--abu);
        }
        .tab-btn.aktif {
            background: white;
            color: var(--biru-tua);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* ── Alert ── */
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .alert-gagal  { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }
        .alert-sukses { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }

        /* ── Form ── */
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 800;
            color: var(--gelap);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-family: 'Nunito', sans-serif;
            font-size: 15px;
            font-weight: 600;
            color: var(--gelap);
            transition: border-color 0.2s;
            outline: none;
            background: #FAFAFA;
        }
        .form-group input:focus {
            border-color: var(--biru);
            background: white;
        }
        .form-group input::placeholder { color: #9CA3AF; font-weight: 400; }

        /* ── Tombol submit ── */
        .btn-submit {
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
            letter-spacing: 0.5px;
        }
        .btn-submit:hover {
            background: var(--biru-tua);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(78,205,196,0.4);
        }
        .btn-submit:active { transform: translateY(0); }

        /* ── Form section (show/hide) ── */
        .form-section { display: none; }
        .form-section.aktif { display: block; }

        /* ── Animasi ── */
        @keyframes masuk {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<?php include 'inc/navbar.php'; ?>

<div class="main-wrapper">
<div class="container">

    <a href="<?= BASE_URL ?>index.php" class="kembali">← Kembali ke Beranda</a>

    <div class="card">
        <div class="card-header">
            <div class="card-ikon">👩‍🏫</div>
            <div class="card-judul">PAHAMIKU</div>
            <p class="card-sub">Portal Pendamping</p>
        </div>

        <!-- Tab Masuk / Daftar -->
        <div class="tab-group">
            <button class="tab-btn <?= $mode === 'masuk'  ? 'aktif' : '' ?>"
                    onclick="gantiTab('masuk', this)">Masuk</button>
            <button class="tab-btn <?= $mode === 'daftar' ? 'aktif' : '' ?>"
                    onclick="gantiTab('daftar', this)">Daftar</button>
        </div>

        <!-- Pesan Error / Sukses -->
        <?php if ($error):  ?><div class="alert alert-gagal"><?= $error ?></div><?php endif; ?>
        <?php if ($sukses): ?><div class="alert alert-sukses"><?= $sukses ?></div><?php endif; ?>

        <!-- ── FORM MASUK ── -->
        <div class="form-section <?= $mode === 'masuk' ? 'aktif' : '' ?>" id="form-masuk">
            <form method="POST" action="">
                <input type="hidden" name="aksi" value="masuk">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" 
                           placeholder="contoh@email.com"
                           value="<?= bersihkan($_POST['email'] ?? '') ?>"
                           required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" 
                           placeholder="Password Anda"
                           required>
                </div>
                <button type="submit" class="btn-submit">Masuk →</button>
            </form>
        </div>

        <!-- ── FORM DAFTAR ── -->
        <div class="form-section <?= $mode === 'daftar' ? 'aktif' : '' ?>" id="form-daftar">
            <form method="POST" action="">
                <input type="hidden" name="aksi" value="daftar">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" 
                           placeholder="Nama Anda"
                           value="<?= bersihkan($_POST['nama'] ?? '') ?>"
                           required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" 
                           placeholder="contoh@email.com"
                           value="<?= bersihkan($_POST['email'] ?? '') ?>"
                           required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" 
                           placeholder="Minimal 6 karakter"
                           required>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" name="konfirm" 
                           placeholder="Ulangi password"
                           required>
                </div>
                <button type="submit" class="btn-submit">Buat Akun →</button>
            </form>
        </div>

</div><!-- /card -->
</div><!-- /container -->
</div><!-- /main-wrapper -->

<?php include 'inc/footer.php'; ?>

<script>
function gantiTab(tab, el = null) {
    // Update tombol tab
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.classList.remove('aktif');
    });
    if (el) el.classList.add('aktif');
    else if (event && event.target) {
        event.target.classList.add('aktif');
    }

    // Sembunyikan semua form, tampilkan yang dipilih
    document.querySelectorAll('.form-section').forEach(function(f) {
        f.classList.remove('aktif');
    });
    document.getElementById('form-' + tab).classList.add('aktif');
}
</script>
</body>
</html>
