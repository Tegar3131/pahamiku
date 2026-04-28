<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$profil_id = (int)($_GET['profil_id'] ?? 0);
$error = '';

// 1. Pastikan profil milik pendamping ini
$stmt = $conn->prepare("SELECT nama, jenis_abk FROM profil_abk WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $profil_id, $_SESSION['user_id']);
$stmt->execute();
$profil = $stmt->get_result()->fetch_assoc();

if (!$profil) redirect('dashboard/index.php');

// 2. Proses Simpan Papan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_papan'])) {
    $nama_papan = bersihkan($_POST['nama_papan']);
    $grid       = $_POST['grid']; 
    $ikon_papan = bersihkan($_POST['ikon_papan']); // Tambahkan baris ini

    // Ubah query INSERT untuk memasukkan ikon_papan
    $stmt_ins = $conn->prepare("INSERT INTO papan (profil_id, nama_papan, ikon_papan, grid) VALUES (?, ?, ?, ?)");
    $stmt_ins->bind_param('isss', $profil_id, $nama_papan, $ikon_papan, $grid);
    
    if ($stmt_ins->execute()) {
        $papan_id = $conn->insert_id;
        setFlash('sukses', "Papan '$nama_papan' berhasil dibuat. Silakan pilih simbol!");
        redirect("dashboard/papan-edit.php?id=$papan_id");
    }
}
// Avatar map untuk visual header
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
    <title>Buat Papan Baru — PAHAMIKU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --biru: #4ECDC4;
            --biru-tua: #2BB5AC;
            --kuning: #FFD93D;
            --gelap: #1A1A2E;
            --abu: #6B7280;
            --radius: 24px;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #F3F4F6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 40px 0;
            width: 100%;
        }

        .card-custom {
            border: none;
            border-radius: var(--radius);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }

        .card-header-custom {
            background-color: var(--biru);
            padding: 30px;
            color: white;
            text-align: center;
            border: none;
        }

        .header-icon { font-size: 50px; margin-bottom: 10px; display: block; }
        
        h2 { 
            font-family: 'Baloo 2', cursive; 
            font-weight: 800; 
            margin: 0;
            font-size: 26px;
        }

        .card-body { padding: 40px; }

        .form-label {
            font-weight: 700;
            color: var(--gelap);
            margin-bottom: 8px;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        .form-control, .form-select {
            padding: 12px 16px;
            border-radius: 14px;
            border: 2px solid #E5E7EB;
            font-weight: 600;
            transition: all 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--biru);
            box-shadow: 0 0 0 4px rgba(78, 205, 196, 0.1);
        }

        .btn-simpan {
            background-color: var(--biru);
            border: none;
            color: white;
            padding: 15px;
            border-radius: 16px;
            font-family: 'Baloo 2', cursive;
            font-size: 20px;
            font-weight: 700;
            width: 100%;
            margin-top: 10px;
            transition: all 0.2s;
        }

        .btn-simpan:hover {
            background-color: var(--biru-tua);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(78, 205, 196, 0.3);
        }

        .btn-kembali {
            color: var(--abu);
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 25px;
            transition: color 0.2s;
        }

        .btn-kembali:hover { color: var(--biru-tua); }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .card-body { padding: 24px; }
            .card-header-custom { padding: 20px; }
            h2 { font-size: 22px; }
        }
    </style>
</head>
<body>

<?php include '../inc/navbar.php'; ?>

<div class="main-wrapper">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            
            <a href="papan-list.php?profil_id=<?= $profil_id ?>" class="btn-kembali">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
                Kembali ke Daftar Papan
            </a>

            <div class="card card-custom">
                <div class="card-header-custom">
                    <span class="header-icon"><?= $avatar ?></span>
                    <h2>Buat Papan Baru</h2>
                    <p class="mb-0 opacity-75">Untuk: <strong><?= htmlspecialchars($profil['nama']) ?></strong></p>
                </div>
                <div class="card-body">
                    <form method="POST">
                       <div class="mb-4">
    <label class="form-label">Nama Papan</label>
    <input type="text" name="nama_papan" class="form-control" 
           placeholder="Contoh: Sarapan Pagi, Di Sekolah" required>
    <div class="form-text mt-2">Gunakan nama yang mudah dipahami pendamping.</div>
</div>

<div class="mb-4">
    <label class="form-label">Ikon Papan</label>
    <div class="input-group position-relative" style="max-width: 200px;">
        <button class="btn btn-outline-secondary" type="button" id="tombol-emoji">
            📋 Pilih
        </button>
        <input type="text" name="ikon_papan" id="input-ikon" class="form-control text-center" value="📋" readonly required>
        <!-- Wadah Emoji Picker element -->
        <div id="wadah-emoji" style="display: none; position: absolute; z-index: 1050; top: 110%; left: 0; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border-radius: 8px;">
            <emoji-picker></emoji-picker>
        </div>
    </div>
    <div class="form-text mt-2">Klik tombol "Pilih" untuk mencari emoji yang sesuai dengan tema.</div>
</div>
                        
                        <div class="mb-4">
                            <label class="form-label">Ukuran Kotak (Grid)</label>
                            <select name="grid" class="form-select">
                                <option value="2x3">2 x 3 (Kecil - 6 Simbol)</option>
                                <option value="3x4" selected>3 x 4 (Sedang - 12 Simbol)</option>
                                <option value="4x5">4 x 5 (Besar - 20 Simbol)</option>
                            </select>
                            <div class="form-text mt-2">Tentukan seberapa banyak simbol yang ingin ditampilkan.</div>
                        </div>

                        <button type="submit" class="btn btn-simpan">
                            Buat Papan & Susun Simbol →
                        </button>
                    </form>
                </div>
            </div></div>
    </div>
</div>
</div>

<?php include '../inc/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tombolEmoji = document.querySelector('#tombol-emoji');
    const inputIkon = document.querySelector('#input-ikon');
    const wadahEmoji = document.querySelector('#wadah-emoji');
    const picker = document.querySelector('emoji-picker');

    // Munculkan popup saat tombol diklik
    tombolEmoji.addEventListener('click', (e) => {
        e.preventDefault();
        wadahEmoji.style.display = wadahEmoji.style.display === 'none' ? 'block' : 'none';
    });

    // Ketika emoji dipilih dari popup
    picker.addEventListener('emoji-click', event => {
        const emoji = event.detail.unicode;
        inputIkon.value = emoji;
        tombolEmoji.innerHTML = emoji + ' Pilih';
        wadahEmoji.style.display = 'none'; // sembunyikan kembali
    });

    // Sembunyikan ketika diklik di luar area tombol dan picker
    document.addEventListener('click', (event) => {
        if (!tombolEmoji.contains(event.target) && !wadahEmoji.contains(event.target)) {
            wadahEmoji.style.display = 'none';
        }
    });
});
</script>
</body>
</html>