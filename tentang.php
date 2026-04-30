<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'inc/config.php';
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami — PAHAMIKU</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --biru: #4ECDC4;
            --gelap: #1A1A2E;
            --abu: #64748B;
            --putih: #F8FAFC;
        }
        body { font-family: 'Nunito', sans-serif; background: var(--putih); color: var(--gelap); line-height: 1.6; display: flex; flex-direction: column; min-height: 100vh;}
        
        .hero-about {
            background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            padding: 80px 20px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .hero-about h1 {
            font-family: 'Baloo 2', cursive;
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 16px;
            position: relative;
            z-index: 2;
        }
        .hero-about p {
            font-size: 18px;
            font-weight: 600;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
            opacity: 0.9;
        }
        
        .content-container {
            max-width: 800px;
            margin: -40px auto 40px auto;
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            position: relative;
            z-index: 3;
            flex: 1;
        }
        
        @media (max-width: 600px) {
            .content-container { margin-top: -20px; padding: 24px; border-radius: 20px; }
            .hero-about { padding: 60px 20px; }
            .hero-about h1 { font-size: 32px; }
        }

        .section-title {
            font-family: 'Baloo 2', cursive;
            font-size: 26px;
            color: var(--gelap);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px dashed #E2E8F0;
            padding-bottom: 10px;
        }
        
        .text-body {
            font-size: 16px;
            color: #475569;
            margin-bottom: 24px;
        }

        .attribution-box {
            background: #F0FDF4;
            border: 2px solid #BBF7D0;
            border-radius: 16px;
            padding: 24px;
            margin-top: 30px;
        }
        .attribution-box h3 {
            font-family: 'Baloo 2', cursive;
            color: #166534;
            font-size: 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .attribution-box p {
            font-size: 15px;
            color: #166534;
            margin-bottom: 12px;
        }
        .attribution-box ul {
            list-style-type: disc;
            padding-left: 24px;
            color: #15803D;
            font-size: 14px;
        }
        .attribution-box ul li {
            margin-bottom: 8px;
        }
        .attribution-box a {
            color: #047857;
            font-weight: 800;
            text-decoration: none;
        }
        .attribution-box a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php include 'inc/navbar.php'; ?>

<div class="hero-about">
    <h1>Tentang PAHAMIKU</h1>
    <p>Membuka pintu komunikasi untuk setiap anak, menghubungkan hati melalui bahasa visual yang inklusif.</p>
</div>

<div class="content-container">
    <h2 class="section-title"><span>🌟</span> Apa itu PAHAMIKU?</h2>
    <p class="text-body">
        <strong>PAHAMIKU</strong> adalah sebuah platform inovatif pembuat Papan Komunikasi AAC (<em>Augmentative and Alternative Communication</em>) yang dirancang khusus untuk membantu Anak Berkebutuhan Khusus (ABK) yang mengalami tantangan verbal, seperti anak dengan spektrum Autisme, ADHD, Disleksia, Down Syndrome, maupun kondisi lainnya.
    </p>
    <p class="text-body">
        Kami percaya bahwa komunikasi adalah hak asasi manusia. Aplikasi ini hadir untuk memberdayakan para orang tua, guru, terapis, dan pendamping dalam merakit media visual komunikasi yang bisa dipersonalisasi sesuai kebutuhan unik setiap anak.
    </p>

    <h2 class="section-title" style="margin-top: 40px;"><span>✨</span> Fitur Unggulan PAHAMIKU</h2>
    <div class="text-body" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; margin-bottom: 40px;">
        <div style="background: var(--putih); padding: 20px; border-radius: 16px; border: 1px solid var(--abu-muda);">
            <h4 style="font-family: 'Baloo 2', cursive; font-size: 18px; color: var(--gelap); margin-bottom: 8px;">🎨 Pembuat Papan Kustom</h4>
            <p style="font-size: 14px; margin: 0;">Rancang grid sesuai kemampuan anak, gunakan ribuan simbol visual, atau tambahkan emoji kustom yang familier bagi anak.</p>
        </div>
        <div style="background: var(--putih); padding: 20px; border-radius: 16px; border: 1px solid var(--abu-muda);">
            <h4 style="font-family: 'Baloo 2', cursive; font-size: 18px; color: var(--gelap); margin-bottom: 8px;">🗣️ Suara Terintegrasi (TTS)</h4>
            <p style="font-size: 14px; margin: 0;">Setiap kartu di papan komunikasi akan mengeluarkan suara secara otomatis dalam bahasa Indonesia ketika ditekan oleh anak.</p>
        </div>
        <div style="background: var(--putih); padding: 20px; border-radius: 16px; border: 1px solid var(--abu-muda);">
            <h4 style="font-family: 'Baloo 2', cursive; font-size: 18px; color: var(--gelap); margin-bottom: 8px;">🌐 Kloning Papan Publik</h4>
            <p style="font-size: 14px; margin: 0;">Tak perlu repot merancang dari awal. Jelajahi Galeri Papan Publik dan salin (duplikat) langsung papan pengguna lain ke akun anak Anda.</p>
        </div>
        <div style="background: var(--putih); padding: 20px; border-radius: 16px; border: 1px solid var(--abu-muda);">
            <h4 style="font-family: 'Baloo 2', cursive; font-size: 18px; color: var(--gelap); margin-bottom: 8px;">👨‍👩‍👦 Multi-Profil Anak (ABK)</h4>
            <p style="font-size: 14px; margin: 0;">Satu akun Pendamping dapat mengelola banyak profil anak, lengkap dengan kode sandi unik masing-masing agar privasi mereka terjaga.</p>
        </div>
    </div>

    <h2 class="section-title"><span>🤝</span> Komunitas yang Saling Membantu</h2>
    <p class="text-body">
        PAHAMIKU tidak hanya sebuah alat pembangun (<em>builder</em>) papan, melainkan juga wadah jejaring sosial. Orang tua dan terapis dapat saling berbagi karya (Papan Publik), bertukar cerita, serta menduplikasi bahan belajar. Dengan semangat <em>open-source</em> dan berbagi, perjalanan mendampingi ABK menjadi tidak lagi terasa sepi.
    </p>

    <!-- KOTAK ATRIBUSI HAK CIPTA -->
    <div class="attribution-box">
        <h3>⚖️ Atribusi & Hak Cipta API (Penting)</h3>
        <p>Dalam mengembangkan perangkat lunak ini agar mudah diakses secara gratis oleh masyarakat, PAHAMIKU memanfaatkan teknologi pihak ketiga yang tunduk pada aturan hak cipta dan lisensi terbuka berikut:</p>
        
        <ul>
            <li>
                <strong>Piktogram ARASAAC (Simbol Visual):</strong> 
                Simbol dan piktogram yang digunakan dalam aplikasi ini bersumber dari <a href="https://arasaac.org/" target="_blank">ARASAAC</a>. Simbol piktografik yang digunakan adalah kekayaan/hak milik dari <strong>Pemerintah Aragón (Spanyol)</strong> dan diciptakan oleh <strong>Sergio Palao</strong> untuk ARASAAC.<br>
                Aset-aset tersebut didistribusikan di bawah lisensi <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/" target="_blank">Creative Commons BY-NC-SA (Atribusi - NonKomersial - BerbagiSerupa)</a>. Oleh karena itu, platform PAHAMIKU <strong>tidak ditujukan untuk komersialisasi</strong>.
            </li>
            <li style="margin-top: 12px;">
                <strong>MyMemory Translation API:</strong> 
                Untuk menjembatani kata kunci pencarian dalam Bahasa Indonesia ke basis data ARASAAC (yang berbasis bahasa Spanyol/Inggris), kami menggunakan layanan mesin penerjemah gratis dari <a href="https://mymemory.translated.net/" target="_blank">MyMemory</a>.
            </li>
        </ul>
        <p style="margin-top: 16px; font-weight: 700; text-align: center;">
            Dengan menggunakan PAHAMIKU, Anda setuju untuk menghargai karya cipta dari para pahlawan digital di atas.
        </p>
    </div>
</div>

<?php include 'inc/footer.php'; ?>

</body>
</html>
