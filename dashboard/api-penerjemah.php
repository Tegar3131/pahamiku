<?php
include '../inc/config.php';
cekLoginPendamping();
header('Content-Type: application/json; charset=utf-8');

$teks = trim($_GET['q'] ?? '');
if ($teks === '') {
    http_response_code(400);
    echo json_encode(['status' => 'gagal', 'pesan' => 'Parameter q wajib diisi', 'en' => '']);
    exit;
}

// MENGGUNAKAN JALUR RAHASIA GOOGLE TRANSLATE (Tanpa API Key, 100% Gratis & Akurat)
$url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=id&tl=en&dt=t&q=" . urlencode($teks);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
// Tambahkan User-Agent agar Google tidak mengira ini robot spam
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$res = curl_exec($ch);
curl_close($ch);

$hasil_en = $teks; // Fallback jika gagal

if ($res) {
    $json = json_decode($res, true);
    // Struktur JSON dari URL ini agak unik, teks terjemahannya ada di array terdalam
    if (isset($json[0][0][0])) {
        $hasil_en = $json[0][0][0];
    }
}

// BERSERSIH KARAKTER: Pastikan tidak ada kode HTML aneh atau spasi berlebih
$hasil_en = html_entity_decode($hasil_en, ENT_QUOTES, 'UTF-8');
$hasil_en = trim(preg_replace('/\s+/', ' ', $hasil_en));

if ($res === false) {
    http_response_code(502);
    echo json_encode([
        'status' => 'gagal',
        'pesan' => 'Layanan penerjemah sedang bermasalah',
        'en' => strtolower($teks)
    ]);
    exit;
}

echo json_encode(['status' => 'sukses', 'en' => strtolower($hasil_en)]);
