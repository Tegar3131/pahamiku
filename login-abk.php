<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'inc/config.php'; 

// Kalau sudah login, langsung ke halaman pilih papan
if (isset($_SESSION['profil_id'])) {
    redirect('papan/pilih.php');
}

$error = '';
$daftar_profil = [];

// Ambil daftar profil berdasarkan user_id pendamping (kalau ada sesi pendamping)
// Atau tampilkan semua profil untuk demo — bisa dibatasi nanti
$sql = "SELECT p.id, p.nama, p.jenis_abk, p.foto_profil, u.nama AS nama_pendamping
        FROM profil_abk p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.nama ASC";
$hasil = $conn->query($sql);
while ($row = $hasil->fetch_assoc()) {
    $daftar_profil[] = $row;
}

// ── Proses: Verifikasi PIN ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profil_id = (int) ($_POST['profil_id'] ?? 0);
    $pin       = bersihkan($_POST['pin'] ?? '');

    if (!$profil_id || !$pin) {
        $error = 'Pilih nama dan masukkan PIN.';
    } else {
        $stmt = $conn->prepare("SELECT id, nama, jenis_abk FROM profil_abk WHERE id = ? AND pin = ?");
        $stmt->bind_param('is', $profil_id, $pin);
        $stmt->execute();
        $profil = $stmt->get_result()->fetch_assoc();

        if ($profil) {
            $_SESSION['profil_id']   = $profil['id'];
            $_SESSION['profil_nama'] = $profil['nama'];
            $_SESSION['jenis_abk']   = $profil['jenis_abk'];
            redirect('papan/pilih.php');
        } else {
            $error = 'PIN salah. Coba lagi ya! 😊';
        }
    }
}

// Profil yang dipilih (dari klik kartu)
$profil_pilih = (int) ($_GET['profil'] ?? $_POST['profil_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAHAMIKU — Halo, Siapa Kamu?</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --kuning:     #FFD93D;
            --kuning-tua: #E8B800;
            --putih:      #FFFDF7;
            --gelap:      #1A1A2E;
            --abu:        #6B7280;
            --merah:      #FF6B6B;
            --hijau:      #6BCB77;
            --radius:     20px;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(160deg, #FFFBEA 0%, #FFFDF7 50%, #F0FDF4 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-wrapper {
            flex: 1;
            padding: 20px;
        }

        .kembali {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            color: var(--abu);
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 24px;
            transition: color 0.2s;
        }
        .kembali:hover { color: var(--kuning-tua); }

        .wrapper {
            max-width: 560px;
            margin: 0 auto;
        }

        /* ── Header ── */
        .header {
            text-align: center;
            margin-bottom: 36px;
            animation: masuk 0.5s ease both;
        }
        .header-ikon { font-size: 56px; margin-bottom: 8px; }
        .header-judul {
            font-family: 'Baloo 2', cursive;
            font-size: 32px;
            font-weight: 800;
            color: var(--gelap);
        }
        .header-sub {
            font-size: 16px;
            color: var(--abu);
            margin-top: 6px;
            font-weight: 600;
        }

        /* ── Error ── */
        .alert-gagal {
            background: #FEF2F2;
            color: #DC2626;
            border: 2px solid #FECACA;
            border-radius: 14px;
            padding: 14px 18px;
            font-size: 15px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
        }

        /* ── Step 1: Pilih Profil ── */
        .step { animation: masuk 0.5s ease both; }
        .step-judul {
            font-size: 16px;
            font-weight: 800;
            color: var(--abu);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
            text-align: center;
        }

        .profil-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }

        .kartu-profil {
            background: white;
            border: 3px solid #E5E7EB;
            border-radius: 20px;
            padding: 20px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }
        .kartu-profil:hover {
            border-color: var(--kuning-tua);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(232,184,0,0.2);
        }
        .kartu-profil.terpilih {
            border-color: var(--kuning-tua);
            background: #FFFBEA;
            box-shadow: 0 0 0 4px rgba(255,217,61,0.3);
        }
        .profil-foto-login {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 8px;
            display: block;
            border: 3px solid #E5E7EB;
            cursor: zoom-in;
            transition: all 0.2s;
        }
        .profil-foto-login:hover { transform: scale(1.1); border-color: var(--kuning-tua); }

        /* ── Lightbox Popup ── */
        .lightbox {
            display: none; 
            position: fixed; 
            z-index: 9999; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .lightbox.tampil {
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
        }
        .lightbox img {
            max-width: 90%;
            max-height: 80vh;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }
        .lightbox.tampil img { transform: scale(1); }
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: #fff;
            font-size: 44px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            transition: color 0.2s;
        }
        .lightbox-close:hover { color: var(--kuning-tua); }
        .kartu-profil .avatar {
            font-size: 44px;
            margin-bottom: 8px;
            display: block;
            line-height: 1;
        }
        .kartu-profil .nama-profil {
            font-size: 16px;
            font-weight: 800;
            color: var(--gelap);
        }
        .kartu-profil .jenis {
            font-size: 11px;
            color: var(--abu);
            margin-top: 3px;
            font-weight: 600;
        }
        .kartu-profil .centang-profil {
            display: none;
            font-size: 20px;
            margin-top: 6px;
        }
        .kartu-profil.terpilih .centang-profil { display: block; }

        /* Kosong — belum ada profil */
        .kosong {
            text-align: center;
            padding: 40px 20px;
            color: var(--abu);
            background: white;
            border-radius: 20px;
            border: 2px dashed #E5E7EB;
        }
        .kosong .e { font-size: 40px; margin-bottom: 12px; display: block; }
        .kosong p  { font-size: 15px; font-weight: 600; }
        .kosong a  { color: #2BB5AC; text-decoration: none; font-weight: 800; }

        /* ── Step 2: Input PIN ── */
        .pin-area {
            background: white;
            border-radius: 24px;
            padding: 32px 28px;
            box-shadow: 0 8px 32px rgba(255,217,61,0.2);
            text-align: center;
            display: none; /* Muncul setelah pilih profil */
        }
        .pin-area.tampil { display: block; animation: masuk 0.3s ease both; }

        .pin-nama {
            font-family: 'Baloo 2', cursive;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .pin-instruksi {
            font-size: 14px;
            color: var(--abu);
            font-weight: 600;
            margin-bottom: 24px;
        }

        /* Indikator PIN (titik-titik) */
        .pin-indikator {
            display: flex;
            justify-content: center;
            gap: 14px;
            margin-bottom: 24px;
        }
        .pin-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 3px solid #D1D5DB;
            background: transparent;
            transition: all 0.15s;
        }
        .pin-dot.isi {
            background: var(--kuning-tua);
            border-color: var(--kuning-tua);
            transform: scale(1.2);
        }

        /* Keypad angka */
        .keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            max-width: 280px;
            margin: 0 auto 20px;
        }
        .key {
            aspect-ratio: 1;
            border: 2px solid #E5E7EB;
            border-radius: 16px;
            background: #FAFAFA;
            font-family: 'Baloo 2', cursive;
            font-size: 28px;
            font-weight: 700;
            color: var(--gelap);
            cursor: pointer;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .key:hover {
            background: var(--kuning);
            border-color: var(--kuning-tua);
            transform: scale(1.08);
        }
        .key:active { transform: scale(0.95); }
        .key.hapus {
            background: #FEF2F2;
            border-color: #FECACA;
            color: var(--merah);
            font-size: 22px;
        }
        .key.nol {
            grid-column: 2;
        }

        /* Tombol masuk */
        .btn-masuk-pin {
            width: 100%;
            max-width: 280px;
            padding: 16px;
            background: var(--kuning);
            color: var(--gelap);
            border: none;
            border-radius: 14px;
            font-family: 'Baloo 2', cursive;
            font-size: 20px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            display: none;
        }
        .btn-masuk-pin.tampil { display: block; animation: masuk 0.3s ease both; }
        .btn-masuk-pin:hover {
            background: var(--kuning-tua);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255,217,61,0.5);
        }

        /* Input PIN tersembunyi (dikirim ke server) */
        input[name="pin"] { display: none; }
        input[name="profil_id"] { display: none; }

        @keyframes masuk {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 400px) {
            .profil-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<?php include 'inc/navbar.php'; ?>

<div class="main-wrapper">
<div class="wrapper">

    <a href="<?= BASE_URL ?>index.php" class="kembali">← Kembali</a>

    <!-- Header -->
    <div class="header">
        <div class="header-ikon">👋</div>
        <div class="header-judul">Halo! Siapa kamu?</div>
        <p class="header-sub">Pilih namamu di bawah ini</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-gagal"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="form-pin">
        <input type="hidden" name="profil_id" id="input-profil-id" value="<?= $profil_pilih ?>">
        <input type="hidden" name="pin" id="input-pin" value="">

        <!-- Step 1: Pilih profil -->
        <div class="step">
            <p class="step-judul">Pilih namamu</p>

            <?php if (empty($daftar_profil)): ?>
                <div class="kosong">
                    <span class="e">🙈</span>
                    <p>Belum ada profil pengguna.<br>
                    Minta pendampingmu untuk <a href="<?= BASE_URL ?>login-pendamping.php">buat akun dulu</a>.</p>
                </div>
            <?php else: ?>
                <div class="profil-grid">
                    <?php foreach ($daftar_profil as $profil): ?>
                        <?php
                        $aktif = ($profil['id'] == $profil_pilih) ? 'terpilih' : '';
                        // Avatar emoji berdasarkan jenis ABK
                        $avatar_map = [
                            'ASD'           => '🧩',
                            'ADHD'          => '⚡',
                            'Tunagrahita'   => '🌟',
                            'Down Syndrome' => '🌈',
                            'Disleksia'     => '📖',
                        ];
                        $avatar = $avatar_map[$profil['jenis_abk']] ?? '😊';
                        ?>
                        <div class="kartu-profil <?= $aktif ?>"
                             onclick="pilihProfil(<?= $profil['id'] ?>, '<?= htmlspecialchars($profil['nama']) ?>')">
                            <?php if (!empty($profil['foto_profil'])): ?>
                                <img src="uploads/profil/<?= htmlspecialchars($profil['foto_profil']) ?>" alt="Foto Wajah" class="profil-foto-login" onclick="bukaLightbox(event, this.src)">
                            <?php else: ?>
                                <span class="avatar"><?= $avatar ?></span>
                            <?php endif; ?>
                            <div class="nama-profil"><?= htmlspecialchars($profil['nama']) ?></div>
                            <?php if ($profil['jenis_abk']): ?>
                                <div class="jenis"><?= htmlspecialchars($profil['jenis_abk']) ?></div>
                            <?php endif; ?>
                            <div class="centang-profil">✅</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Step 2: Input PIN (muncul setelah pilih profil) -->
        <div class="pin-area <?= $profil_pilih ? 'tampil' : '' ?>" id="pin-area">
            <div class="pin-nama" id="pin-nama">
                <?php
                if ($profil_pilih) {
                    foreach ($daftar_profil as $p) {
                        if ($p['id'] == $profil_pilih) echo htmlspecialchars($p['nama']);
                    }
                }
                ?>
            </div>
            <p class="pin-instruksi">Masukkan PIN 4 angka kamu 🔢</p>

            <!-- Indikator titik-titik -->
            <div class="pin-indikator">
                <div class="pin-dot" id="dot-0"></div>
                <div class="pin-dot" id="dot-1"></div>
                <div class="pin-dot" id="dot-2"></div>
                <div class="pin-dot" id="dot-3"></div>
            </div>

            <!-- Keypad -->
            <div class="keypad">
                <button type="button" class="key" onclick="tekanAngka('1')">1</button>
                <button type="button" class="key" onclick="tekanAngka('2')">2</button>
                <button type="button" class="key" onclick="tekanAngka('3')">3</button>
                <button type="button" class="key" onclick="tekanAngka('4')">4</button>
                <button type="button" class="key" onclick="tekanAngka('5')">5</button>
                <button type="button" class="key" onclick="tekanAngka('6')">6</button>
                <button type="button" class="key" onclick="tekanAngka('7')">7</button>
                <button type="button" class="key" onclick="tekanAngka('8')">8</button>
                <button type="button" class="key" onclick="tekanAngka('9')">9</button>
                <button type="button" class="key hapus" onclick="hapusAngka()">⌫</button>
                <button type="button" class="key nol" onclick="tekanAngka('0')">0</button>
            </div>

            <!-- Tombol Masuk (muncul setelah 4 angka) -->
            <button type="submit" class="btn-masuk-pin" id="btn-masuk-pin">
                Masuk! 🎉
            </button>
        </div>

    </form>
</div><!-- /wrapper -->
</div><!-- /main-wrapper -->

<?php include 'inc/footer.php'; ?>

<script>
let pinSekarang = '';
let profilDipilih = <?= $profil_pilih ?: 0 ?>;

function pilihProfil(id, nama) {
    // Reset PIN dulu
    pinSekarang = '';
    updateDots();

    // Update UI profil
    document.querySelectorAll('.kartu-profil').forEach(function(k) {
        k.classList.remove('terpilih');
    });
    event.currentTarget.classList.add('terpilih');

    // Update hidden input
    profilDipilih = id;
    document.getElementById('input-profil-id').value = id;

    // Tampilkan area PIN
    document.getElementById('pin-area').classList.add('tampil');
    document.getElementById('pin-nama').textContent = nama;

    // Scroll ke area PIN
    document.getElementById('pin-area').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function tekanAngka(angka) {
    if (pinSekarang.length >= 4) return;
    pinSekarang += angka;
    document.getElementById('input-pin').value = pinSekarang;
    updateDots();

    // Auto submit kalau sudah 4 angka
    if (pinSekarang.length === 4) {
        document.getElementById('btn-masuk-pin').classList.add('tampil');
        // Sedikit delay agar pengguna lihat titik ke-4 terisi
        setTimeout(function() {
            if (profilDipilih > 0) {
                document.getElementById('form-pin').submit();
            }
        }, 600);
    }
}

function hapusAngka() {
    pinSekarang = pinSekarang.slice(0, -1);
    document.getElementById('input-pin').value = pinSekarang;
    document.getElementById('btn-masuk-pin').classList.remove('tampil');
    updateDots();
}

function updateDots() {
    for (let i = 0; i < 4; i++) {
        const dot = document.getElementById('dot-' + i);
        if (i < pinSekarang.length) {
            dot.classList.add('isi');
        } else {
            dot.classList.remove('isi');
        }
    }
}

// Fitur Buka Lightbox
function bukaLightbox(e, srcUrl) {
    if(e) e.stopPropagation(); // Mencegah form pin terbuka otomatis ketika mau nge-zoom gambar
    
    const lightbox = document.getElementById('lightbox-foto');
    document.getElementById('lightbox-img').src = srcUrl;
    lightbox.style.display = 'flex';
    setTimeout(() => { lightbox.classList.add('tampil'); }, 10);
}
function tutupLightbox() {
    const lightbox = document.getElementById('lightbox-foto');
    lightbox.classList.remove('tampil');
    setTimeout(() => { lightbox.style.display = 'none'; }, 300);
}

</script>

<!-- ── Lightbox Container ── -->
<div id="lightbox-foto" class="lightbox" onclick="tutupLightbox()">
    <span class="lightbox-close">&times;</span>
    <img id="lightbox-img" src="" alt="Perbesaran Foto" onclick="event.stopPropagation()">
</div>

</body>
</html>
