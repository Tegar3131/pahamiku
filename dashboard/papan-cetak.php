<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$papan_id = (int)($_GET['id'] ?? 0);

// Ambil data papan
$stmt = $conn->prepare("SELECT p.*, a.nama FROM papan p JOIN profil_abk a ON p.profil_id = a.id WHERE p.id = ?");
$stmt->bind_param('i', $papan_id);
$stmt->execute();
$papan = $stmt->get_result()->fetch_assoc();

if (!$papan) die("Papan tidak ditemukan.");

// Ambil simbol-simbol di dalam papan ini
$stmt_simbol = $conn->prepare("SELECT * FROM papan_simbol WHERE papan_id = ? ORDER BY urutan ASC");
$stmt_simbol->bind_param('i', $papan_id);
$stmt_simbol->execute();
$simbol_list = $stmt_simbol->get_result()->fetch_all(MYSQLI_ASSOC);

// Tentukan jumlah kolom berdasarkan string grid (misal '3x4' berarti 3 kolom)
$grid_parts = explode('x', $papan['grid']);
$kolom = isset($grid_parts[0]) ? (int)$grid_parts[0] : 3;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Papan - <?= htmlspecialchars($papan['nama_papan']) ?></title>
    <style>
        /* Desain Khusus Kertas A4 */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #fff;
            color: #000;
        }

        .header-cetak {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header-cetak h1 {
            margin: 0;
            font-size: 24px;
        }

        .header-cetak p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #555;
        }

        /* Grid Simbol disesuaikan dengan pengaturan grid di database */
        .grid-kertas {
            display: grid;
            grid-template-columns: repeat(<?= $kolom ?>, 1fr);
            gap: 15px;
            width: 100%;
        }

        .kartu-simbol {
            border: 2px solid #000;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 120px;
            page-break-inside: avoid; /* Mencegah kartu terpotong halaman */
        }

        .kartu-gambar {
            font-size: 50px;
            margin-bottom: 10px;
            display: block;
        }

        .kartu-teks {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .btn-print-nav {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-print-nav button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background: #4ECDC4;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
        }

        /* Aturan khusus saat dialog print muncul */
        @media print {
            .btn-print-nav { display: none !important; } /* Sembunyikan tombol print */
            body { padding: 0; }
            .kartu-simbol {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <div class="btn-print-nav">
        <button onclick="window.print()">🖨️ Cetak Sekarang</button>
        <p style="font-size: 12px; color: gray;">Gunakan kertas A4 dan orientasi Potrait/Landscape sesuai kebutuhan.</p>
    </div>

    <div class="header-cetak">
        <h1><?= htmlspecialchars($papan['nama_papan']) ?></h1>
        <p>Milik: <?= htmlspecialchars($papan['nama']) ?></p>
    </div>

    <div class="grid-kertas">
        <?php foreach ($simbol_list as $s): 
            // Memisahkan label dan suara (contoh: "Makan|Aku mau makan")
            $teks_pecah = explode('|', $s['label_custom']);
            $label_utama = $teks_pecah[0];
            
            // Cek apakah menggunakan emoji atau ARASAAC API
            $gambar_visual = '❓'; // Default
            if (strpos($s['simbol_id'], 'emoji-') === 0) {
                $gambar_visual = str_replace('emoji-', '', $s['simbol_id']);
            }
            // (Opsional) Anda bisa menambahkan logika <img> API ARASAAC di sini jika diperlukan
        ?>
            <div class="kartu-simbol">
                <span class="kartu-gambar"><?= $gambar_visual ?></span>
                <span class="kartu-teks"><?= htmlspecialchars($label_utama) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        window.onload = function() {
            // Hilangkan komentar pada baris di bawah jika ingin langsung otomatis print tanpa klik tombol
            // window.print();
        }
    </script>

</body>
</html>