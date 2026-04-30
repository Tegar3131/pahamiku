<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'inc/config.php';

// Kalau sudah login sebagai ABK, langsung ke halaman pilih papan
if (isset($_SESSION['profil_id'])) {
    redirect('papan/pilih.php');
}

// Ambil beberapa papan publik terbaru untuk dipamerkan di beranda
$top_boards = [];
$qs = "SELECT p.id, p.nama_papan, p.ikon_papan, p.deskripsi, p.grid, u.nama AS pembuat 
       FROM papan p 
       LEFT JOIN profil_abk pr ON p.profil_id = pr.id 
       LEFT JOIN users u ON pr.user_id = u.id 
       WHERE p.access_type = 'public' 
       ORDER BY p.id DESC LIMIT 3";
$res_top = $conn->query($qs);
if ($res_top) {
    while ($row = $res_top->fetch_assoc()) {
        if (empty($row['pembuat'])) $row['pembuat'] = 'Sistem PAHAMIKU';
        $top_boards[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAHAMIKU — Papan Komunikasi AAC & Komunitas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & Variabel ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --kuning:     #FFD93D;
            --kuning-tua: #F4C430;
            --biru:       #4ECDC4;
            --biru-tua:   #2BB5AC;
            --merah:      #FF6B6B;
            --hijau:      #6BCB77;
            --putih:      #FFFDF7;
            --gelap:      #1A1A2E;
            --abu:        #6B7280;
            --abu-muda:   #F3F4F6;
            --radius-lg:  24px;
            --radius:     16px;
            --shadow:     0 12px 36px rgba(0,0,0,0.08);
            --shadow-hover: 0 20px 48px rgba(0,0,0,0.12);
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--putih);
            color: var(--gelap);
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ── Hero Section ── */
        .hero {
            position: relative;
            padding: 80px 20px;
            text-align: center;
            overflow: hidden;
            background: linear-gradient(180deg, #F0FDF9 0%, var(--putih) 100%);
        }

        .hero-dekor {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            z-index: 0;
            animation: pulse-float 6s ease-in-out infinite;
        }
        .hero-dekor.biru { width: 400px; height: 400px; background: var(--biru); top: -100px; left: -100px; }
        .hero-dekor.kuning { width: 300px; height: 300px; background: var(--kuning); bottom: -50px; right: -50px; animation-delay: 2s;}

        @keyframes pulse-float {
            0%, 100% { transform: scale(1) translateY(0); }
            50% { transform: scale(1.05) translateY(-20px); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
            animation: masukAtas 0.8s ease both;
        }

        .tagline-badge {
            display: inline-block;
            padding: 6px 16px;
            background: #fffbea;
            color: #d97706;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 20px;
            border: 1px solid #fde68a;
        }

        .hero h1 {
            font-family: 'Baloo 2', cursive;
            font-size: clamp(36px, 6vw, 64px);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
            color: var(--gelap);
        }
        .hero h1 span {
            background: linear-gradient(135deg, #FF6B6B 0%, #FFD93D 50%, #4ECDC4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: clamp(16px, 2vw, 20px);
            color: var(--abu);
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .hero-btns {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-hero {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 16px 32px;
            border-radius: 50px;
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.25s ease;
            border: none;
            cursor: pointer;
        }
        .btn-hero-primary {
            background: var(--biru);
            color: white;
            box-shadow: 0 8px 24px rgba(78,205,196,0.3);
        }
        .btn-hero-primary:hover {
            transform: translateY(-4px);
            background: var(--biru-tua);
            box-shadow: 0 12px 32px rgba(78,205,196,0.4);
        }
        .btn-hero-secondary {
            background: white;
            color: var(--gelap);
            border: 2px solid #E5E7EB;
        }
        .btn-hero-secondary:hover {
            border-color: var(--gelap);
            transform: translateY(-4px);
        }

        /* ── Preset Section (Papan Siap Pakai) ── */
        .preset-section {
            max-width: 1000px;
            margin: -40px auto 40px;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }
        .preset-container {
            background: white;
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow);
            border: 2px solid var(--abu-muda);
        }
        .preset-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .preset-header h3 {
            font-family: 'Baloo 2', cursive;
            font-size: 20px;
            color: var(--gelap);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .preset-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        .btn-preset {
            background: var(--putih);
            border: 2px solid var(--abu-muda);
            border-radius: var(--radius);
            padding: 16px 12px;
            text-align: center;
            text-decoration: none;
            color: var(--gelap);
            font-weight: 800;
            font-size: 14px;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .btn-preset span.e { font-size: 32px; }
        .btn-preset:hover {
            border-color: var(--kuning-tua);
            background: #FFFBF0;
            transform: translateY(-4px);
        }

        /* ── Fitur / Keunggulan ── */
        .fitur-section {
            padding: 60px 20px 80px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .section-title {
            text-align: center;
            font-family: 'Baloo 2', cursive;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 40px;
            color: var(--gelap);
        }

        .fitur-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .fitur-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow);
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .fitur-card:hover {
            transform: translateY(-8px);
            border-color: var(--biru);
            box-shadow: var(--shadow-hover);
        }
        .fitur-ikon {
            font-size: 48px;
            margin-bottom: 20px;
            display: inline-block;
            padding: 16px;
            border-radius: 50%;
            background: var(--abu-muda);
        }
        .fitur-card:nth-child(1) .fitur-ikon { background: #E0F2FE; }
        .fitur-card:nth-child(2) .fitur-ikon { background: #FFFBEB; }
        .fitur-card:nth-child(3) .fitur-ikon { background: #FEF2F2; }

        .fitur-card h3 {
            font-family: 'Baloo 2', cursive;
            font-size: 22px;
            margin-bottom: 12px;
        }
        .fitur-card p {
            font-size: 15px;
            color: var(--abu);
            line-height: 1.6;
        }

        /* ── Showcase Papan Publik ── */
        .showcase-section {
            background: #F8FAFC;
            padding: 80px 20px;
        }
        .showcase-container {
            max-width: 1100px;
            margin: 0 auto;
        }
        .showcase-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 40px;
        }
        .showcase-header .desc {
            color: var(--abu);
            font-weight: 600;
        }

        .board-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }
        .board-card {
            background: white;
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 12px;
            border: 1px solid #E5E7EB;
        }
        .board-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
            border-color: var(--biru);
        }
        .board-header { display: flex; align-items: center; gap: 12px; }
        .board-icon { font-size: 32px; }
        .board-title { font-family: 'Baloo 2', cursive; font-size: 20px; color: var(--gelap); }
        .board-meta { font-size: 12px; font-weight: 700; color: #10B981; background: #D1FAE5; padding: 4px 10px; border-radius: 12px; display: inline-block; width: max-content;}
        .board-desc { font-size: 14px; color: var(--abu); flex-grow: 1; }
        .board-creator { font-size: 12px; color: #94A3B8; font-weight: 700; border-top: 1px solid #F1F5F9; padding-top: 12px; margin-top: auto;}

        /* ── CTA Sederhana untuk Anak ── */
        .cta-abk {
            padding: 60px 20px;
            text-align: center;
        }
        .cta-abk-box {
            max-width: 600px;
            margin: 0 auto;
            background: linear-gradient(135deg, #FFD93D, #F4C430);
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }
        .cta-abk-box h2 { font-family: 'Baloo 2'; font-size: 28px; margin-bottom: 20px; }
        .cta-abk-box p { font-weight: 600; margin-bottom: 30px; }

        @keyframes masukAtas {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .hero { padding: 60px 20px; }
            .showcase-header { flex-direction: column; align-items: flex-start; gap: 16px; }
            .preset-grid { grid-template-columns: repeat(2, 1fr); }
            .preset-section { margin-top: 20px; }
        }
    </style>
</head>
<body>

<?php include 'inc/navbar.php'; ?>

<!-- 1. HERO SECTION -->
<div class="hero">
    <div class="hero-dekor biru"></div>
    <div class="hero-dekor kuning"></div>
    <div class="hero-content">
        <span class="tagline-badge">🌟 Komunikasi Inklusif AAC</span>
        <h1>Buat Suara Lebih Nyaring dengan <span>Komunitas</span></h1>
        <p>Aplikasi pembuatan papan komunikasi AAC untuk Anak Berkebutuhan Khusus (ABK). Rancang papan secara personal, bagikan ke publik, dan jelajahi ribuan karya dari orang tua serta terapis lainnya.</p>
        <div class="hero-btns">
            <a href="galeri-papan.php" class="btn-hero btn-hero-primary" style="background:var(--merah); box-shadow:0 8px 24px rgba(255,107,107,0.3);">📋 Jelajahi Papan komunitas</a>
            <a href="komunitas.php" class="btn-hero btn-hero-primary">🌐 Jelajahi Komunitas</a>
            <?php if ($is_logged_in): ?>
                <a href="dashboard/index.php" class="btn-hero btn-hero-secondary">🚀 Masuk Dashboard</a>
            <?php else: ?>
                <a href="login-pendamping.php" class="btn-hero btn-hero-secondary">👨‍🏫 Buat Papan</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 2. PRESET PAPAN SIAP PAKAI -->
<div class="preset-section">
    <div class="preset-container">
        <div class="preset-header">
            <h3>⚡ Coba Sekarang: Papan Siap Pakai</h3>
            <span style="font-size:13px; color:var(--abu); font-weight:600;">Langsung pakai tanpa login</span>
        </div>
        <div class="preset-grid">
            <a href="papan/index.php?preset=dasar" class="btn-preset">
                <span class="e">🤲</span>
                Kebutuhan Dasar
            </a>
            <a href="papan/index.php?preset=perpustakaan" class="btn-preset">
                <span class="e">📚</span>
                Perpustakaan
            </a>
            <a href="papan/index.php?preset=perasaan" class="btn-preset">
                <span class="e">😊</span>
                Perasaan
            </a>
            <a href="papan/index.php?preset=darurat" class="btn-preset">
                <span class="e">🚨</span>
                Darurat / Keamanan
            </a>
        </div>
    </div>
</div>

<!-- 3. FITUR SECTION -->
<div class="fitur-section">
    <h2 class="section-title">Bagaimana Kami Membantu?</h2>
    <div class="fitur-grid">
        <div class="fitur-card">
            <span class="fitur-ikon">👨‍👩‍👦</span>
            <h3>Bagi Orang Tua</h3>
            <p>Buat papan spesifik dengan foto makanan, mainan, atau tempat favorit si anak agar ia lebih mudah mengenali dan berkomunikasi di rumah.</p>
        </div>
        <div class="fitur-card">
            <span class="fitur-ikon">👩‍⚕️</span>
            <h3>Bagi Terapis / Guru</h3>
            <p>Bagikan papan standar pengajaran Anda ke komunitas. Terapis atau guru lain bisa memakai dan menduplikasi papan Anda untuk murid mereka.</p>
        </div>
        <div class="fitur-card">
            <span class="fitur-ikon">🧒</span>
            <h3>Bagi Anak (ABK)</h3>
            <p>Desain papan bebas distraksi dengan teks-to-speech otomatis. Anak cukup menyentuh gambar, dan perangkat akan membacakannya.</p>
        </div>
    </div>
</div>

<!-- 4. SHOWCASE PAPAN PUBLIK -->
<div class="showcase-section">
    <div class="showcase-container">
        <div class="showcase-header">
            <div>
                <h2 class="section-title" style="text-align: left; margin-bottom: 8px;">Papan Populer Komunitas</h2>
                <p class="desc">Intip papan kreasi pendamping yang dibagikan dan siap Anda gunakan.</p>
            </div>
            <a href="galeri-papan.php" class="btn-hero btn-hero-secondary" style="padding: 10px 24px; font-size: 14px;">Lihat Galeri Penuh →</a>
        </div>
        
        <div class="board-grid">
            <?php if (empty($top_boards)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #94A3B8;">
                    <span style="font-size: 40px; display: block; margin-bottom: 10px;">📭</span>
                    <p style="font-weight: 700;">Belum ada papan komunitas yang dipublikasikan.<br>Jadilah yang pertama untuk berbagi!</p>
                </div>
            <?php else: ?>
                <?php foreach($top_boards as $p): ?>
                    <div class="board-card">
                        <div class="board-header">
                            <span class="board-icon"><?= !empty($p['ikon_papan']) ? htmlspecialchars($p['ikon_papan']) : '📋' ?></span>
                            <h3 class="board-title"><?= htmlspecialchars($p['nama_papan']) ?></h3>
                        </div>
                        <span class="board-meta">Grid: <?= htmlspecialchars($p['grid']) ?></span>
                        <p class="board-desc">
                            <?= !empty($p['deskripsi']) ? htmlspecialchars(substr($p['deskripsi'], 0, 80)) . '...' : 'Papan ini dibagikan ke publik untuk digunakan secara gratis.' ?>
                        </p>
                        <div class="board-creator">
                            Dibuat oleh: <?= htmlspecialchars($p['pembuat']) ?>
                        </div>
                        <a href="papan/index.php?papan_id=<?= $p['id'] ?>" class="btn-hero btn-hero-secondary" style="padding: 8px 16px; font-size: 13px; justify-content: center; width: 100%; margin-top: 8px;">Pratinjau Papan</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 5. LOGIN ABK CTA -->
<div class="cta-abk">
    <div class="cta-abk-box">
        <h2>Punya akun dari pendamping?</h2>
        <p>Klik tombol di bawah ini untuk mengaktifkan mode AAC khusus anak Anda, terbebas dari pengaturan maupun distraksi.</p>
        <a href="login-abk.php" class="btn-hero" style="background: var(--gelap); color: white;">Akses Mode Pengguna (ABK)</a>
    </div>
</div>

<?php include 'inc/footer.php'; ?>

</body>
</html>
