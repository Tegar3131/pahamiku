<?php
// ============================================================
//  inc/config.php
//  File ini di-include di SEMUA halaman PHP
//  Fungsi: koneksi DB + array master simbol + fungsi bantu
// ============================================================


// ------------------------------------------------------------
// 1. KONEKSI DATABASE
// ------------------------------------------------------------

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // ganti sesuai MySQL Anda
define('DB_PASS', '');            // ganti sesuai MySQL Anda
define('DB_NAME', 'pahamiku');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

// Cek koneksi — tampilkan error jika gagal
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}


// ------------------------------------------------------------
// 2. KONFIGURASI UMUM
// ------------------------------------------------------------

// Sesuaikan dengan nama folder proyekmu
define('BASE_URL', 'http://localhost/pahamiku/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');
define('SYMBOL_URL', BASE_URL . 'img/symbols/');

// Ukuran maksimal upload foto (2MB)
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024);


// ------------------------------------------------------------
// 3. MULAI SESSION
// Dipanggil sekali di config.php — tidak perlu di file lain
// ------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ------------------------------------------------------------
// 4. FUNGSI BANTU AUTENTIKASI
// ------------------------------------------------------------

/**
 * Cek apakah pendamping sudah login
 * Panggil di setiap halaman dashboard
 */
function cekLoginPendamping() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login-pendamping.php');
        exit;
    }
}

/**
 * Cek apakah ABK sudah login
 * Panggil di halaman papan
 */
function cekLoginABK() {
    if (!isset($_SESSION['profil_id'])) {
        header('Location: ' . BASE_URL . 'login-abk.php');
        exit;
    }
}

/**
 * Ambil data user pendamping yang sedang login
 */
function getUserLogin($conn) {
    if (!isset($_SESSION['user_id'])) return null;
    $id   = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, nama, email FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Ambil data profil ABK yang sedang aktif
 */
function getProfilABK($conn) {
    if (!isset($_SESSION['profil_id'])) return null;
    $id   = (int) $_SESSION['profil_id'];
    $stmt = $conn->prepare("SELECT * FROM profil_abk WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}


// ------------------------------------------------------------
// 5. FUNGSI BANTU SIMBOL
// ------------------------------------------------------------

/**
 * Ambil semua simbol dari DB, dikelompokkan per kategori
 * Return: array['dasar'] = [ [...], [...] ]
 */
function getAllSimbol($conn) {
    $result = $conn->query("SELECT * FROM master_simbol ORDER BY kategori, label");
    $simbol = [];
    while ($row = $result->fetch_assoc()) {
        $simbol[$row['kategori']][] = $row;
    }
    return $simbol;
}

/**
 * Ambil satu simbol berdasarkan id
 */
function getSimbolById($conn, $simbol_id) {
    $stmt = $conn->prepare("SELECT * FROM master_simbol WHERE id = ?");
    $stmt->bind_param('s', $simbol_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Render satu kartu simbol (HTML)
 * Dipakai di halaman pilih-simbol dan papan
 * 
 * $simbol  = array data simbol
 * $mode    = 'pilih' (dengan checkbox) | 'tampil' (klik untuk bicara)
 * $terpilih = array id simbol yang sudah dipilih
 */
function renderKartuSimbol($simbol, $mode = 'tampil', $terpilih = []) {
    $id       = htmlspecialchars($simbol['simbol_id'] ?? $simbol['id']);
    $label    = htmlspecialchars($simbol['label_custom'] ?? $simbol['label']);
    $emoji    = htmlspecialchars($simbol['emoji_custom'] ?? $simbol['emoji']);
    $img_file = $simbol['foto_path'] ?? ($simbol['img_file'] ?? null);
    $checked  = in_array($id, $terpilih) ? 'checked' : '';
    $aktif    = in_array($id, $terpilih) ? 'kartu-aktif' : '';

    // Tentukan sumber gambar
    if ($simbol['foto_path'] ?? false) {
        // Foto upload dari user
        $img_src = UPLOAD_URL . $simbol['foto_path'];
    } elseif ($img_file) {
        // Gambar dari library ARASAAC
        $img_src = SYMBOL_URL . $img_file;
    } else {
        $img_src = null;
    }

    ob_start();
    if ($mode === 'pilih') {
        // Mode pilih: ada checkbox
        ?>
        <label class="kartu-simbol <?= $aktif ?>" for="sym_<?= $id ?>">
            <input type="checkbox" 
                   name="simbol[]" 
                   value="<?= $id ?>" 
                   id="sym_<?= $id ?>"
                   <?= $checked ?>>
            <div class="kartu-isi">
                <?php if ($img_src): ?>
                    <img src="<?= $img_src ?>" 
                         alt="<?= $label ?>"
                         onerror="this.style.display='none';
                                  this.nextElementSibling.style.display='block'">
                    <span class="emoji-fallback" style="display:none"><?= $emoji ?></span>
                <?php else: ?>
                    <span class="emoji-besar"><?= $emoji ?></span>
                <?php endif; ?>
                <p class="kartu-label"><?= $label ?></p>
            </div>
            <span class="centang">✓</span>
        </label>
        <?php
    } else {
        // Mode tampil: klik untuk highlight + suara
        ?>
        <div class="kartu-simbol kartu-bicara" 
             onclick="bicarakan('<?= $label ?>', this)"
             data-label="<?= $label ?>">
            <div class="kartu-isi">
                <?php if ($img_src): ?>
                    <img src="<?= $img_src ?>" 
                         alt="<?= $label ?>"
                         onerror="this.style.display='none';
                                  this.nextElementSibling.style.display='block'">
                    <span class="emoji-fallback" style="display:none"><?= $emoji ?></span>
                <?php else: ?>
                    <span class="emoji-besar"><?= $emoji ?></span>
                <?php endif; ?>
                <p class="kartu-label"><?= $label ?></p>
            </div>
        </div>
        <?php
    }
    return ob_get_clean();
}


// ------------------------------------------------------------
// 6. FUNGSI BANTU UMUM
// ------------------------------------------------------------

/**
 * Bersihkan input dari user — cegah XSS
 */
function bersihkan($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect ke halaman lain
 */
// Perbaikan fungsi redirect agar lebih aman
function redirect($url) {
    // Menghapus slash di awal $url jika ada agar tidak double dengan BASE_URL
    $url = ltrim($url, '/');
    header('Location: ' . BASE_URL . $url);
    exit;
}

/**
 * Simpan pesan flash (berhasil/gagal) ke session
 * Dipakai setelah simpan/hapus data
 */
function setFlash($tipe, $pesan) {
    // $tipe: 'sukses' atau 'gagal'
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

/**
 * Tampilkan pesan flash — otomatis hapus setelah ditampilkan
 */
function tampilFlash() {
    if (!isset($_SESSION['flash'])) return;
    $flash = $_SESSION['flash'];
    $kelas = $flash['tipe'] === 'sukses' ? 'alert-sukses' : 'alert-gagal';
    echo "<div class='alert {$kelas}'>{$flash['pesan']}</div>";
    unset($_SESSION['flash']);
}

/**
 * Label nama kategori simbol — dari kode ke teks Indonesia
 */
function labelKategori($kategori) {
    $map = [
        'dasar'          => '🤲 Kebutuhan Dasar',
        'perasaan'       => '😊 Perasaan',
        'makanan'        => '🍎 Makanan & Minuman',
        'aktivitas'      => '🏃 Aktivitas',
        'tempat'         => '🏠 Tempat',
        'perpustakaan'   => '📚 Perpustakaan',
        'darurat'        => '🚨 Darurat',
    ];
    return $map[$kategori] ?? ucfirst($kategori);
}

// ------------------------------------------------------------
// 7. FUNGSI BANTU ARASAAC API
// ------------------------------------------------------------

/**
 * Mendapatkan URL gambar piktogram dari ID ARASAAC
 */
function getArasaacImg($id) {
    return "https://api.arasaac.org/api/pictograms/" . $id;
}

/**
 * Mencari simbol berdasarkan kata kunci (Panggil via API)
 */
function searchArasaac($keyword) {
    $url = "https://api.arasaac.org/api/pictograms/id/search/" . urlencode($keyword);
    $response = @file_get_contents($url);
    return $response ? json_decode($response, true) : [];
}