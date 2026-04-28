<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'inc/config.php';

// Kalau sudah login sebagai pendamping, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    redirect('dashboard/index.php');
}
// Kalau sudah login sebagai ABK, langsung ke papan
if (isset($_SESSION['profil_id'])) {
    redirect('papan/index.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAHAMIKU — Papan Komunikasi AAC</title>
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
            --ungu:       #A78BFA;
            --putih:      #FFFDF7;
            --gelap:      #1A1A2E;
            --abu:        #6B7280;
            --radius:     20px;
            --shadow:     0 8px 32px rgba(0,0,0,0.10);
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--putih);
            color: var(--gelap);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Background dekoratif ── */
        .bg-dekor {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .bg-dekor span {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            animation: mengambang 8s ease-in-out infinite;
        }
        .bg-dekor span:nth-child(1) {
            width: 400px; height: 400px;
            background: var(--kuning);
            top: -100px; left: -100px;
            animation-delay: 0s;
        }
        .bg-dekor span:nth-child(2) {
            width: 300px; height: 300px;
            background: var(--biru);
            bottom: -80px; right: -80px;
            animation-delay: 3s;
        }
        .bg-dekor span:nth-child(3) {
            width: 200px; height: 200px;
            background: var(--merah);
            top: 40%; left: 70%;
            animation-delay: 1.5s;
        }
        @keyframes mengambang {
            0%, 100% { transform: translateY(0px) scale(1); }
            50%       { transform: translateY(-20px) scale(1.05); }
        }

        /* ── Layout utama ── */
        .wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            gap: 40px;
        }

        /* ── Header brand ── */
        .brand {
            text-align: center;
            animation: masukAtas 0.7s ease both;
        }
        .brand-logo {
            font-family: 'Baloo 2', cursive;
            font-size: clamp(48px, 10vw, 80px);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #FF6B6B 0%, #FFD93D 50%, #4ECDC4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .brand-tagline {
            margin-top: 8px;
            font-size: 18px;
            font-weight: 600;
            color: var(--abu);
            letter-spacing: 0.5px;
        }

        /* ── Kartu pilihan login ── */
        .pilihan-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            width: 100%;
            max-width: 560px;
            animation: masukBawah 0.7s ease 0.2s both;
        }

        .kartu-login {
            background: white;
            border-radius: var(--radius);
            padding: 36px 24px;
            text-align: center;
            text-decoration: none;
            color: var(--gelap);
            box-shadow: var(--shadow);
            border: 3px solid transparent;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            cursor: pointer;
        }
        .kartu-login:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 48px rgba(0,0,0,0.15);
        }
        .kartu-login.pendamping:hover { border-color: var(--biru); }
        .kartu-login.pengguna:hover   { border-color: var(--kuning); }

        .kartu-login .ikon {
            font-size: 56px;
            line-height: 1;
            display: block;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
        }
        .kartu-login .judul {
            font-family: 'Baloo 2', cursive;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.2;
        }
        .kartu-login .deskripsi {
            font-size: 13px;
            color: var(--abu);
            line-height: 1.5;
        }
        .kartu-login .btn-masuk {
            margin-top: 4px;
            padding: 10px 24px;
            border-radius: 50px;
            font-family: 'Nunito', sans-serif;
            font-size: 14px;
            font-weight: 800;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .kartu-login.pendamping .btn-masuk {
            background: var(--biru);
            color: white;
        }
        .kartu-login.pengguna .btn-masuk {
            background: var(--kuning);
            color: var(--gelap);
        }
        .kartu-login .btn-masuk:hover {
            filter: brightness(1.1);
            transform: scale(1.05);
        }

        /* ── Atau gunakan preset ── */
        .preset-section {
            width: 100%;
            max-width: 560px;
            animation: masukBawah 0.7s ease 0.4s both;
        }
        .preset-judul {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            color: var(--abu);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .preset-judul::before,
        .preset-judul::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #E5E7EB;
        }
        .preset-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        .btn-preset {
            background: white;
            border: 2px solid #E5E7EB;
            border-radius: 16px;
            padding: 16px 8px;
            text-align: center;
            text-decoration: none;
            color: var(--gelap);
            font-family: 'Nunito', sans-serif;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .btn-preset span.e { font-size: 28px; }
        .btn-preset:hover {
            border-color: var(--kuning-tua);
            background: #FFFBF0;
            transform: translateY(-3px);
        }

        /* ── Footer info ── */
        .footer-info {
            text-align: center;
            font-size: 12px;
            color: #9CA3AF;
            animation: masukBawah 0.7s ease 0.6s both;
        }
        .footer-info a { color: var(--biru-tua); text-decoration: none; }

        /* ── Animasi ── */
        @keyframes masukAtas {
            from { opacity: 0; transform: translateY(-30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes masukBawah {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Responsif HP kecil ── */
        @media (max-width: 768px) {
            .wrapper { justify-content: flex-start; padding-top: 60px; }
        }
        @media (max-width: 480px) {
            .pilihan-grid { grid-template-columns: 1fr; }
            .preset-grid  { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<?php include 'inc/navbar.php'; ?>

<!-- Dekorasi background -->
<div class="bg-dekor">
    <span></span><span></span><span></span>
</div>

<div class="wrapper">

    <!-- Brand Header -->
    <div class="brand">
        <div class="brand-logo">PAHAMIKU</div>
        <p class="brand-tagline">Bantu Aku Bicara 🗣️</p>
    </div>

    <!-- Dua Pilihan Login -->
    <div class="pilihan-grid">

        <!-- Kartu: Pendamping -->
        <a href="<?= BASE_URL ?>login-pendamping.php" class="kartu-login pendamping">
            <span class="ikon">👩‍🏫</span>
            <div class="judul">Saya Pendamping</div>
            <p class="deskripsi">Orang tua, guru, atau terapis yang mengelola papan</p>
            <button class="btn-masuk" onclick="event.preventDefault(); window.location='<?= BASE_URL ?>login-pendamping.php'">
                Masuk
            </button>
        </a>

        <!-- Kartu: Pengguna ABK -->
        <a href="<?= BASE_URL ?>login-abk.php" class="kartu-login pengguna">
            <span class="ikon">🧒</span>
            <div class="judul">Saya Pengguna</div>
            <p class="deskripsi">Masuk dengan nama dan PIN dari pendamping</p>
            <button class="btn-masuk" onclick="event.preventDefault(); window.location='<?= BASE_URL ?>login-abk.php'">
                Masuk
            </button>
        </a>

    </div>

    <!-- Preset Langsung Pakai -->
    <div class="preset-section">
        <div class="preset-judul">atau langsung gunakan papan siap pakai</div>
        <div class="preset-grid">
            <a href="<?= BASE_URL ?>papan/index.php?preset=dasar" class="btn-preset">
                <span class="e">🤲</span>
                Kebutuhan Dasar
            </a>
            <a href="<?= BASE_URL ?>papan/index.php?preset=perpustakaan" class="btn-preset">
                <span class="e">📚</span>
                Perpustakaan
            </a>
            <a href="<?= BASE_URL ?>papan/index.php?preset=perasaan" class="btn-preset">
                <span class="e">😊</span>
                Perasaan
            </a>
            <a href="<?= BASE_URL ?>papan/index.php?preset=darurat" class="btn-preset">
                <span class="e">🚨</span>
                Darurat
            </a>
        </div>
    </div>

</div>

<?php include 'inc/footer.php'; ?>

</body>
</html>
