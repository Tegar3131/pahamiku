<?php
// inc/helper_ikon.php

/**
 * Ambil ikon untuk sebuah papan.
 * Prioritas: (1) tersimpan di DB → (2) cocokkan keyword → (3) tebak via AI → (4) fallback avatar
 */
function get_ikon_papan(array $papan, mysqli $conn): string {
    // 1. Sudah ada ikon tersimpan di DB → langsung pakai
    if (!empty($papan['ikon_papan'])) {
        return $papan['ikon_papan'];
    }

    $nama = $papan['nama_papan'];
    $nama_lower = strtolower($nama);

    // 2. Cek keyword lokal dulu (cepat, tanpa API)
    $ikon = _match_keyword($nama_lower, $papan['is_favorit']);

    // 3. Kalau tidak cocok keyword → tanya AI
    if ($ikon === null) {
        $ikon = _tebak_ikon_via_ai($nama);
    }

    // 4. Simpan hasil ke DB agar tidak memanggil API lagi
    if ($ikon !== null) {
        $stmt = $conn->prepare("UPDATE papan SET ikon_papan = ? WHERE id = ?");
        $stmt->bind_param('si', $ikon, $papan['id']);
        $stmt->execute();
    }

    // 5. Fallback terakhir: avatar inisial (ditangani di sisi HTML/CSS)
    return $ikon ?? '';
}

/**
 * Cocokkan kata kunci dari nama papan ke emoji.
 * Kembalikan null jika tidak ada yang cocok.
 */
function _match_keyword(string $nama_lower, bool $is_favorit): ?string {
    if ($is_favorit) return '⭐';

    $peta = [
        // Kebutuhan dasar
        'makan'        => '🍽️',
        'minum'        => '🥤',
        'lapar'        => '🍽️',
        'haus'         => '🥤',
        'toilet'       => '🚽',
        'kamar mandi'  => '🚿',
        'tidur'        => '😴',
        'istirahat'    => '😴',
        // Tempat
        'sekolah'      => '🏫',
        'kelas'        => '🏫',
        'rumah'        => '🏠',
        'perpustakaan' => '📚',
        'taman'        => '🌳',
        'rumah sakit'  => '🏥',
        'dokter'       => '🏥',
        // Emosi
        'perasaan'     => '😊',
        'emosi'        => '😊',
        'senang'       => '😄',
        'sedih'        => '😢',
        'marah'        => '😠',
        'takut'        => '😨',
        'sakit'        => '🤒',
        // Aktivitas
        'bermain'      => '🎮',
        'main'         => '🎮',
        'olahraga'     => '⚽',
        'belajar'      => '📖',
        'musik'        => '🎵',
        'menggambar'   => '🎨',
        'gambar'       => '🎨',
        // Darurat
        'darurat'      => '🚨',
        'bantuan'      => '🆘',
        'tolong'       => '🆘',
    ];

    foreach ($peta as $kata => $emoji) {
        if (strpos($nama_lower, $kata) !== false) {
            return $emoji;
        }
    }

    return null; // tidak ada yang cocok
}

/**
 * Panggil Claude API untuk menebak emoji terbaik dari nama papan.
 * Kembalikan null jika gagal.
 */
function _tebak_ikon_via_ai(string $nama_papan): ?string {
    $api_key = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
    if (empty($api_key)) return null;

    $prompt = "Kamu membantu aplikasi AAC (Augmentative and Alternative Communication) untuk anak-anak dengan kebutuhan khusus.
Berikan SATU emoji yang paling tepat mewakili papan komunikasi bernama: \"$nama_papan\".
Jawab HANYA dengan emoji tersebut, tanpa teks atau penjelasan apapun.";

    $payload = json_encode([
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 10,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 5, // batas 5 detik agar halaman tidak lambat
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01',
        ],
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) return null;

    $data = json_decode($response, true);
    $ikon = trim($data['content'][0]['text'] ?? '');

    // Validasi: pastikan hasilnya benar-benar emoji (bukan teks panjang)
    if (mb_strlen($ikon) > 8) return null;

    return $ikon ?: null;
}