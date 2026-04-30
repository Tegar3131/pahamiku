<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../inc/config.php';
cekLoginPendamping();

$papan_id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT p.*, pr.nama as nama_anak FROM papan p JOIN profil_abk pr ON p.profil_id = pr.id WHERE p.id = ? AND pr.user_id = ?");
$stmt->bind_param('ii', $papan_id, $_SESSION['user_id']);
$stmt->execute();
$papan = $stmt->get_result()->fetch_assoc();

if (!$papan) redirect('dashboard/index.php');

$stmt_simbol = $conn->prepare("SELECT * FROM papan_simbol WHERE papan_id = ? ORDER BY urutan ASC, id ASC");
$stmt_simbol->bind_param('i', $papan_id);
$stmt_simbol->execute();
$simbol_papan = $stmt_simbol->get_result()->fetch_all(MYSQLI_ASSOC);

$grid_cols = explode('x', $papan['grid'])[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Papan: <?= htmlspecialchars($papan['nama_papan']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --biru:        #4ECDC4;
            --biru-tua:    #35b8af;
            --biru-muda:   #e8faf9;
            --kuning:      #FFD93D;
            --kuning-tua:  #e8b800;
            --merah:       #FF6B6B;
            --merah-muda:  #fff0f0;
            --hijau:       #6BCB77;
            --ungu:        #a78bfa;
            --gelap:       #1A1A2E;
            --gelap2:      #2d2d4a;
            --abu:         #64748b;
            --abu-muda:    #f1f5f9;
            --abu-border:  #e2e8f0;
            --putih:       #ffffff;
            --radius-sm:   10px;
            --radius:      16px;
            --radius-lg:   24px;
            --shadow-sm:   0 2px 8px rgba(0,0,0,0.06);
            --shadow:      0 4px 20px rgba(0,0,0,0.08);
            --shadow-lg:   0 12px 40px rgba(0,0,0,0.12);
        }

        html { height: 100%; }

        body {
            font-family: 'Nunito', sans-serif;
            background: #f0f4f8;
            color: var(--gelap);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ═══════════════════════════════════════
           TOPBAR
        ═══════════════════════════════════════ */
        .topbar {
            background: var(--putih);
            border-bottom: 1px solid var(--abu-border);
            padding: 0 20px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 300;
            box-shadow: var(--shadow-sm);
        }
        .topbar-kiri {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            flex: 1;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: var(--abu-muda);
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'Nunito', sans-serif;
            font-size: 13px;
            font-weight: 800;
            color: var(--abu);
            text-decoration: none;
            transition: all .2s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .btn-back:hover { background: var(--abu-border); color: var(--gelap); }
        .topbar-title {
            min-width: 0;
        }
        .topbar-title h1 {
            font-family: 'Baloo 2', cursive;
            font-size: 17px;
            font-weight: 800;
            color: var(--gelap);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .topbar-title p {
            font-size: 12px;
            color: var(--abu);
            font-weight: 600;
            white-space: nowrap;
        }
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        /* Tombol "Tambah Simbol" di topbar — hanya muncul di mobile */
        .btn-open-panel {
            display: none;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            background: var(--biru);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'Nunito', sans-serif;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-open-panel:hover { background: var(--biru-tua); }

        /* ═══════════════════════════════════════
           LAYOUT UTAMA
        ═══════════════════════════════════════ */
        .layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 20px;
            flex: 1;
            padding: 20px;
            max-width: 1280px;
            margin: 0 auto;
            width: 100%;
            align-items: start;
        }

        /* ═══════════════════════════════════════
           PANEL PAPAN (kiri)
        ═══════════════════════════════════════ */
        .panel-papan {
            background: var(--putih);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow);
        }
        .panel-papan-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 12px;
        }
        .panel-papan-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .papan-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            background: var(--biru-muda);
            color: var(--biru-tua);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
        }
        .hint-drag {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #fffbea;
            border: 1px solid #fde68a;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #92400e;
        }

        /* ═══════════════════════════════════════
           GRID SIMBOL
        ═══════════════════════════════════════ */
        .grid-preview {
            display: grid;
            grid-template-columns: repeat(<?= $grid_cols ?>, 1fr);
            gap: 14px;
        }

        .slot-card {
            border: 2.5px dashed var(--abu-border);
            border-radius: var(--radius);
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 12px 8px;
            position: relative;
            background: var(--abu-muda);
            cursor: grab;
            transition: border-color .2s, box-shadow .2s, transform .15s;
            user-select: none;
        }
        .slot-card:active { cursor: grabbing; transform: scale(0.97); }
        .slot-card.filled {
            border-style: solid;
            border-color: var(--abu-border);
            background: var(--putih);
            box-shadow: var(--shadow-sm);
        }
        .slot-card.filled:hover {
            border-color: var(--biru);
            box-shadow: 0 4px 16px rgba(78,205,196,0.2);
        }
        .slot-card.ghost-card {
            opacity: 0.35;
            border: 2px dashed var(--biru);
            background: var(--biru-muda);
        }

        /* Nomor urut kecil */
        .slot-urut {
            position: absolute;
            top: 6px;
            left: 8px;
            font-size: 10px;
            font-weight: 800;
            color: var(--abu-border);
            line-height: 1;
        }
        .slot-card.filled .slot-urut { color: #cbd5e1; }

        /* Gambar & label dalam kartu */
        .slot-card img {
            max-width: 60%;
            max-height: 52%;
            object-fit: contain;
            margin-bottom: 8px;
            pointer-events: none;
            border-radius: 6px;
        }
        .slot-emoji {
            font-size: clamp(28px, 6vw, 48px);
            line-height: 1.1;
            margin-bottom: 8px;
            pointer-events: none;
        }
        .slot-label {
            font-weight: 800;
            font-size: clamp(10px, 1.8vw, 13px);
            color: var(--gelap);
            text-align: center;
            line-height: 1.2;
            pointer-events: none;
            word-break: break-word;
            max-width: 100%;
        }

        /* Tombol hapus */
        .btn-remove {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 28px;
            height: 28px;
            background: var(--merah);
            color: white;
            border: 3px solid var(--putih);
            border-radius: 50%;
            font-size: 11px;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(255,107,107,0.4);
            transition: transform .15s, background .15s;
            z-index: 5;
            line-height: 1;
        }
        .btn-remove:hover { transform: scale(1.2); background: #e85555; }

        /* Empty slot visual */
        .slot-empty-icon {
            font-size: 22px;
            opacity: 0.25;
            pointer-events: none;
        }
        .slot-empty-text {
            font-size: 9px;
            font-weight: 700;
            color: #94a3b8;
            margin-top: 4px;
            text-align: center;
            pointer-events: none;
        }

        /* ═══════════════════════════════════════
           PANEL CARI (kanan)
        ═══════════════════════════════════════ */
        .panel-cari {
            background: var(--putih);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            position: sticky;
            top: 80px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 100px);
        }

        /* Header panel cari */
        .cari-header {
            padding: 20px 20px 16px;
            border-bottom: 1px solid var(--abu-border);
            background: var(--putih);
        }
        .cari-header h3 {
            font-family: 'Baloo 2', cursive;
            font-size: 17px;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--gelap);
        }

        /* Search input */
        .search-wrap {
            display: flex;
            gap: 8px;
        }
        .search-input {
            flex: 1;
            padding: 11px 14px;
            border: 2px solid var(--abu-border);
            border-radius: var(--radius-sm);
            font-family: 'Nunito', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: var(--gelap);
            background: var(--abu-muda);
            outline: none;
            transition: border-color .2s, background .2s;
        }
        .search-input:focus {
            border-color: var(--biru);
            background: var(--putih);
        }
        .search-input::placeholder { color: #94a3b8; font-weight: 600; }

        /* Kata saran */
        .saran-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
        }
        .btn-saran {
            padding: 4px 10px;
            border: 1.5px solid var(--abu-border);
            border-radius: 20px;
            background: var(--putih);
            font-family: 'Nunito', sans-serif;
            font-size: 12px;
            font-weight: 700;
            color: var(--abu);
            cursor: pointer;
            transition: all .15s;
        }
        .btn-saran:hover {
            border-color: var(--biru);
            color: var(--biru-tua);
            background: var(--biru-muda);
        }

        /* Hasil scroll area */
        .cari-body {
            flex: 1;
            overflow-y: auto;
            padding: 14px;
            scrollbar-width: thin;
            scrollbar-color: var(--abu-border) transparent;
        }

        /* Loading */
        .loading-wrap {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            gap: 10px;
            color: var(--abu);
        }
        .loading-wrap.tampil { display: flex; }
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid var(--abu-border);
            border-top-color: var(--biru);
            border-radius: 50%;
            animation: putar .8s linear infinite;
        }
        @keyframes putar { to { transform: rotate(360deg); } }
        .loading-teks { font-size: 13px; font-weight: 700; }

        /* State awal & kosong */
        .state-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 36px 20px;
            gap: 8px;
            color: var(--abu);
            text-align: center;
        }
        .state-placeholder .e { font-size: 36px; }
        .state-placeholder p  { font-size: 13px; font-weight: 600; }

        /* Grid hasil pencarian */
        .hasil-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .result-item {
            border: 2px solid var(--abu-border);
            border-radius: var(--radius-sm);
            padding: 10px 6px 8px;
            cursor: pointer;
            text-align: center;
            background: var(--putih);
            transition: all .15s;
        }
        .result-item:hover {
            border-color: var(--biru);
            background: var(--biru-muda);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(78,205,196,0.2);
        }
        .result-item img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: contain;
            border-radius: 6px;
            margin-bottom: 6px;
        }
        .result-item p {
            margin: 0;
            font-size: 11px;
            font-weight: 800;
            color: var(--gelap);
            text-transform: capitalize;
            word-break: break-word;
            line-height: 1.2;
        }

        /* ═══════════════════════════════════════
           FOOTER PANEL CARI — Simbol Kustom
        ═══════════════════════════════════════ */
        .cari-footer {
            padding: 14px 16px;
            border-top: 1px solid var(--abu-border);
            background: var(--putih);
        }
        .kustom-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #94a3b8;
            margin-bottom: 8px;
        }
        .kustom-btns {
            display: flex;
            gap: 8px;
        }
        .btn-kustom {
            flex: 1;
            padding: 10px 8px;
            border: 2px solid var(--abu-border);
            border-radius: var(--radius-sm);
            background: var(--abu-muda);
            font-family: 'Nunito', sans-serif;
            font-size: 12px;
            font-weight: 800;
            color: var(--gelap);
            cursor: pointer;
            transition: all .2s;
            text-align: center;
            line-height: 1.3;
        }
        .btn-kustom.kuning:hover { border-color: var(--kuning-tua); background: #fffbea; }
        .btn-kustom.gelap:hover  { border-color: var(--gelap2); background: var(--gelap2); color: white; }

        /* ═══════════════════════════════════════
           MODAL — Atur Label & TTS
        ═══════════════════════════════════════ */
        .modal-backdrop-custom {
            position: fixed;
            inset: 0;
            background: rgba(15,15,30,.5);
            backdrop-filter: blur(4px);
            z-index: 600;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-backdrop-custom.tampil { display: flex; }

        .modal-box {
            background: var(--putih);
            border-radius: var(--radius-lg);
            padding: 28px 24px;
            width: 100%;
            max-width: 420px;
            box-shadow: var(--shadow-lg);
            animation: modalMasuk .25s ease;
            position: relative;
        }
        @keyframes modalMasuk {
            from { opacity: 0; transform: translateY(16px) scale(.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-tutup {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 32px;
            height: 32px;
            background: var(--abu-muda);
            border: none;
            border-radius: 50%;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s;
        }
        .modal-tutup:hover { background: var(--abu-border); }
        .modal-title {
            font-family: 'Baloo 2', cursive;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 20px;
            color: var(--gelap);
            padding-right: 32px;
        }

        /* Preview gambar di modal */
        .modal-preview-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 18px;
        }
        .modal-preview-img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: var(--radius-sm);
            border: 2px solid var(--abu-border);
            background: var(--abu-muda);
            padding: 6px;
        }

        /* Form fields */
        .form-group { margin-bottom: 14px; }
        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--gelap);
            margin-bottom: 6px;
        }
        .form-label .hint {
            font-size: 10px;
            font-weight: 600;
            text-transform: none;
            letter-spacing: 0;
            color: var(--abu);
            margin-left: 4px;
        }
        .form-input {
            width: 100%;
            padding: 11px 14px;
            border: 2px solid var(--abu-border);
            border-radius: var(--radius-sm);
            font-family: 'Nunito', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--gelap);
            background: var(--abu-muda);
            outline: none;
            transition: border-color .2s, background .2s;
        }
        .form-input:focus { border-color: var(--biru); background: var(--putih); }

        /* Emoji input besar */
        .form-input.emoji-besar {
            font-size: 36px;
            text-align: center;
            padding: 8px;
            letter-spacing: 4px;
        }

        /* Tombol submit modal */
        .btn-modal-submit {
            width: 100%;
            padding: 13px;
            background: var(--biru);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'Baloo 2', cursive;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: all .2s;
            margin-top: 6px;
            letter-spacing: .3px;
        }
        .btn-modal-submit:hover {
            background: var(--biru-tua);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78,205,196,.35);
        }
        .btn-modal-submit:active { transform: translateY(0); }

        /* ═══════════════════════════════════════
           MODAL KUSTOM — Kamera & Upload
        ═══════════════════════════════════════ */
        .kamera-area {
            display: none;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 14px;
        }
        .kamera-area.tampil { display: flex; }
        .video-kamera {
            width: 100%;
            max-height: 220px;
            background: #000;
            border-radius: var(--radius-sm);
            object-fit: cover;
        }
        .hasil-jepretan {
            display: none;
            width: 100%;
            max-height: 220px;
            border-radius: var(--radius-sm);
            border: 2.5px solid var(--biru);
            object-fit: cover;
        }
        .kamera-btns {
            display: flex;
            gap: 8px;
        }
        .btn-kamera {
            flex: 1;
            padding: 9px 10px;
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'Nunito', sans-serif;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-kamera.jepret { background: var(--biru); color: white; }
        .btn-kamera.jepret:hover { background: var(--biru-tua); }
        .btn-kamera.ulang   { background: var(--kuning); color: var(--gelap); }
        .btn-kamera.ganti   { background: var(--abu-muda); color: var(--abu); }

        .atau-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        .atau-divider::before,
        .atau-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--abu-border);
        }
        .atau-divider span {
            font-size: 12px;
            font-weight: 700;
            color: #94a3b8;
        }

        /* File input kustom */
        .file-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border: 2px dashed var(--abu-border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            color: var(--abu);
            background: var(--abu-muda);
            transition: all .2s;
        }
        .file-label:hover { border-color: var(--biru); color: var(--biru-tua); background: var(--biru-muda); }
        .file-input-hidden { display: none; }

        /* ═══════════════════════════════════════
           MOBILE DRAWER (panel cari di HP)
        ═══════════════════════════════════════ */
        .drawer-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.4);
            z-index: 400;
        }
        .drawer-overlay.tampil { display: block; }

        .drawer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--putih);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            z-index: 450;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            transform: translateY(100%);
            transition: transform .3s cubic-bezier(.32,.72,0,1);
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }
        .drawer.tampil { transform: translateY(0); }
        .drawer-handle {
            width: 40px;
            height: 4px;
            background: var(--abu-border);
            border-radius: 2px;
            margin: 12px auto 0;
            flex-shrink: 0;
        }
        .drawer-header {
            padding: 14px 20px 12px;
            border-bottom: 1px solid var(--abu-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .drawer-header h3 {
            font-family: 'Baloo 2', cursive;
            font-size: 18px;
            font-weight: 800;
        }
        .btn-tutup-drawer {
            width: 32px;
            height: 32px;
            background: var(--abu-muda);
            border: none;
            border-radius: 50%;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .drawer-search {
            padding: 12px 16px;
            border-bottom: 1px solid var(--abu-border);
            flex-shrink: 0;
        }
        .drawer-body {
            flex: 1;
            overflow-y: auto;
            padding: 12px 16px;
        }
        .drawer-footer {
            padding: 12px 16px;
            border-top: 1px solid var(--abu-border);
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }
        .drawer-footer .btn-kustom { font-size: 13px; }

        /* ═══════════════════════════════════════
           TOAST NOTIFIKASI
        ═══════════════════════════════════════ */
        .toast-container {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: none;
        }
        .toast {
            padding: 12px 20px;
            background: var(--gelap);
            color: white;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 700;
            box-shadow: var(--shadow-lg);
            animation: toastIn .25s ease;
            white-space: nowrap;
        }
        .toast.sukses { background: #16a34a; }
        .toast.gagal  { background: var(--merah); }
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ═══════════════════════════════════════
           ANIMASI MASUK
        ═══════════════════════════════════════ */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .slot-card { animation: fadeUp .3s ease both; }
        .slot-card:nth-child(1)  { animation-delay: .02s; }
        .slot-card:nth-child(2)  { animation-delay: .04s; }
        .slot-card:nth-child(3)  { animation-delay: .06s; }
        .slot-card:nth-child(4)  { animation-delay: .08s; }
        .slot-card:nth-child(5)  { animation-delay: .10s; }
        .slot-card:nth-child(6)  { animation-delay: .12s; }
        .slot-card:nth-child(7)  { animation-delay: .14s; }
        .slot-card:nth-child(8)  { animation-delay: .16s; }
        .slot-card:nth-child(9)  { animation-delay: .18s; }
        .slot-card:nth-child(10) { animation-delay: .20s; }
        .slot-card:nth-child(11) { animation-delay: .22s; }
        .slot-card:nth-child(12) { animation-delay: .24s; }

        /* ═══════════════════════════════════════
           RESPONSIVE — TABLET
        ═══════════════════════════════════════ */
        @media (max-width: 1024px) {
            .layout { grid-template-columns: 1fr 320px; gap: 16px; padding: 16px; }
        }

        /* ═══════════════════════════════════════
           RESPONSIVE — MOBILE
        ═══════════════════════════════════════ */
        @media (max-width: 768px) {
            .layout {
                grid-template-columns: 1fr;
                padding: 12px;
                gap: 12px;
            }
            /* Sembunyikan panel cari di desktop, ganti jadi drawer */
            .panel-cari { display: none; }
            .btn-open-panel { display: inline-flex; }

            .panel-papan { padding: 16px; }
            .panel-papan-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .grid-preview { gap: 8px; }
            .slot-card { padding: 8px 4px; }
            .btn-remove { width: 24px; height: 24px; font-size: 9px; top: -8px; right: -8px; border-width: 2px; }
            .hint-drag { font-size: 11px; }

            /* Floating tombol tambah simbol di sudut kanan bawah */
            .fab {
                position: fixed;
                bottom: 24px;
                right: 20px;
                width: 56px;
                height: 56px;
                background: var(--biru);
                color: white;
                border: none;
                border-radius: 50%;
                font-size: 26px;
                box-shadow: 0 6px 20px rgba(78,205,196,.5);
                cursor: pointer;
                z-index: 350;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all .2s;
            }
            .fab:hover { background: var(--biru-tua); transform: scale(1.1); }
        }

        @media (min-width: 769px) {
            .fab { display: none; }
            .drawer { display: none !important; }
            .drawer-overlay { display: none !important; }
        }

        @media (max-width: 400px) {
            .topbar { padding: 0 12px; }
            .topbar-title h1 { font-size: 15px; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════
     TOPBAR
═══════════════════════════════════════ -->
<div class="topbar">
    <div class="topbar-kiri">
        <a href="papan-list.php?profil_id=<?= $papan['profil_id'] ?>" class="btn-back">
            ← Kembali
        </a>
        <div class="topbar-title">
            <h1><?= htmlspecialchars($papan['nama_papan']) ?></h1>
            <p>👤 <?= htmlspecialchars($papan['nama_anak']) ?></p>
        </div>
    </div>
    <div class="topbar-actions">
        <!-- Pengaturan Board -->
        <button class="btn-open-panel" style="background:var(--gelap); margin-right:5px;" onclick="bukaModalSettings()">
            ⚙️ Setelan
        </button>
        <!-- Hanya tampil di mobile -->
        <button class="btn-open-panel" onclick="bukaDrawer()">
            ＋ Tambah Simbol
        </button>
    </div>
</div>


<!-- ═══════════════════════════════════════
     LAYOUT UTAMA
═══════════════════════════════════════ -->
<div class="layout">

    <!-- ── Panel Papan (kiri) ── -->
    <div class="panel-papan">
        <div class="panel-papan-header">
            <div class="panel-papan-info">
                <span class="papan-badge">📐 <?= htmlspecialchars($papan['grid']) ?></span>
                <span class="papan-badge" style="background:#f0fdf4;color:#16a34a;">
                    🃏 <?= count($simbol_papan) ?> simbol
                </span>
            </div>
            <div class="hint-drag">
                ✋ Tahan &amp; geser untuk susun ulang
            </div>
        </div>

        <div class="grid-preview" id="drag-grid">
            <?php foreach ($simbol_papan as $idx => $s):
                $parts     = explode('|', $s['label_custom']);
                $tag_gambar = $parts[0];
                $id_simbol  = $s['simbol_id'];

                if (strpos($id_simbol, 'emoji-') === 0) {
                    $ikon          = str_replace('emoji-', '', $id_simbol);
                    $elemen_gambar = '<div class="slot-emoji">' . htmlspecialchars($ikon) . '</div>';
                } elseif (strpos($id_simbol, 'upload-') === 0) {
                    $elemen_gambar = '<img src="../uploads/' . htmlspecialchars($id_simbol) . '" alt="Kustom">';
                } else {
                    $elemen_gambar = '<img src="https://api.arasaac.org/api/pictograms/' . $id_simbol . '" alt="' . htmlspecialchars($tag_gambar) . '" loading="lazy">';
                }
            ?>
                <div class="slot-card filled" data-id="<?= $s['id'] ?>">
                    <span class="slot-urut"><?= $idx + 1 ?></span>
                    <button class="btn-remove" onclick="hapusSimbol(<?= $s['id'] ?>)" title="Hapus">✕</button>
                    <?= $elemen_gambar ?>
                    <span class="slot-label"><?= htmlspecialchars($tag_gambar) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($simbol_papan)): ?>
            <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                <div style="font-size:48px; margin-bottom:12px;">📭</div>
                <p style="font-weight:700; font-size:15px;">Papan masih kosong</p>
                <p style="font-size:13px; margin-top:4px;">Cari dan tambahkan simbol dari panel sebelah kanan</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Panel Cari (kanan, desktop) ── -->
    <div class="panel-cari" id="panel-cari-desktop">
        <div class="cari-header">
            <h3>🔍 Cari Simbol ARASAAC</h3>
            <div class="search-wrap">
                <input type="text" id="input-cari" class="search-input"
                       placeholder="Ketik kata, contoh: makan..."
                       onkeyup="debouncedSearch(this.value)">
            </div>
            <div class="saran-wrap" id="saran-wrap">
                <?php foreach (['makan','minum','senang','sedih','tolong','tidur','sekolah','main','buku','pulang'] as $s): ?>
                    <button class="btn-saran" onclick="pakaiSaran('<?= $s ?>')"><?= $s ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cari-body">
            <div class="loading-wrap" id="loader">
                <div class="spinner"></div>
                <span class="loading-teks">Mencari simbol...</span>
            </div>
            <div id="hasil-cari">
                <div class="state-placeholder">
                    <span class="e">🔍</span>
                    <p>Ketik kata untuk<br>mencari simbol ARASAAC</p>
                </div>
            </div>
        </div>

        <div class="cari-footer">
            <p class="kustom-label">Tidak ada gambar yang cocok?</p>
            <div class="kustom-btns">
                <button class="btn-kustom kuning" onclick="bukaModalKustom('emoji')">
                    😀<br>Buat Emoji
                </button>
                <button class="btn-kustom gelap" onclick="bukaModalKustom('kamera')">
                    📸<br>Foto / Upload
                </button>
            </div>
        </div>
    </div>

</div><!-- /layout -->


<!-- ═══════════════════════════════════════
     MOBILE: DRAWER + FAB
═══════════════════════════════════════ -->
<div class="drawer-overlay" id="drawer-overlay" onclick="tutupDrawer()"></div>

<div class="drawer" id="drawer">
    <div class="drawer-handle"></div>
    <div class="drawer-header">
        <h3>🔍 Cari Simbol</h3>
        <button class="btn-tutup-drawer" onclick="tutupDrawer()">✕</button>
    </div>
    <div class="drawer-search">
        <div class="search-wrap">
            <input type="text" id="input-cari-mobile" class="search-input"
                   placeholder="Ketik kata..."
                   onkeyup="debouncedSearchMobile(this.value)">
        </div>
        <div class="saran-wrap" style="margin-top:8px;">
            <?php foreach (['makan','minum','senang','sedih','tolong','tidur','sekolah','main'] as $s): ?>
                <button class="btn-saran" onclick="pakaiSaranMobile('<?= $s ?>')"><?= $s ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="drawer-body">
        <div class="loading-wrap" id="loader-mobile">
            <div class="spinner"></div>
            <span class="loading-teks">Mencari simbol...</span>
        </div>
        <div id="hasil-cari-mobile">
            <div class="state-placeholder">
                <span class="e">🔍</span>
                <p>Ketik kata di atas untuk mulai mencari</p>
            </div>
        </div>
    </div>
    <div class="drawer-footer">
        <button class="btn-kustom kuning" onclick="tutupDrawer(); bukaModalKustom('emoji')">
            😀 Buat Emoji
        </button>
        <button class="btn-kustom gelap" onclick="tutupDrawer(); bukaModalKustom('kamera')">
            📸 Foto / Upload
        </button>
    </div>
</div>

<button class="fab" onclick="bukaDrawer()" title="Tambah Simbol">＋</button>


<!-- ═══════════════════════════════════════
     MODAL — ATUR LABEL & TTS
═══════════════════════════════════════ -->
<div class="modal-backdrop-custom" id="modalAturLabel">
    <div class="modal-box">
        <button class="modal-tutup" onclick="tutupModal('modalAturLabel')">✕</button>
        <div class="modal-title">🏷️ Atur Teks Simbol</div>

        <div class="modal-preview-wrap">
            <img id="preview-modal-img" src="" class="modal-preview-img" alt="Preview">
        </div>

        <div class="form-group">
            <label class="form-label">
                Teks pada Kartu
                <span class="hint">— tampil di layar</span>
            </label>
            <input type="text" id="input-tag" class="form-input" placeholder="Contoh: Makan">
        </div>
        <div class="form-group">
            <label class="form-label">
                Teks Suara <span class="hint">— diucapkan saat ditekan (opsional)</span>
            </label>
            <input type="text" id="input-suara" class="form-input" placeholder="Contoh: Aku mau makan">
        </div>
        <input type="hidden" id="hidden-simbol-id">

        <button class="btn-modal-submit" onclick="simpanKePapanLokal()">
            ✅ Tambahkan ke Papan
        </button>
    </div>
</div>


<!-- ═══════════════════════════════════════
     MODAL — PENGATURAN PAPAN
     (Akses Publik/Privat, Nama, Grid)
═══════════════════════════════════════ -->
<div class="modal-backdrop-custom" id="modalSettings">
    <div class="modal-box" style="max-width: 480px;">
        <button class="modal-tutup" onclick="tutupModal('modalSettings')">✕</button>
        <div class="modal-title">⚙️ Pengaturan Papan</div>

        <div class="form-group">
            <label class="form-label">Nama Papan</label>
            <input type="text" id="setting-nama" class="form-input" value="<?= htmlspecialchars($papan['nama_papan']) ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Deskripsi Papan</label>
            <textarea id="setting-deskripsi" class="form-input" style="height: 80px; resize: none;" placeholder="Jelaskan secara singkat tujuan papan ini..."><?= htmlspecialchars($papan['deskripsi'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Tipe Akses</label>
            <select id="setting-akses" class="form-input" onchange="toggleLinkShare()">
                <option value="private" <?= ($papan['access_type'] ?? 'private') === 'private' ? 'selected' : '' ?>>🔒 Privat (Hanya Pemilik)</option>
                <option value="public" <?= ($papan['access_type'] ?? 'private') === 'public' ? 'selected' : '' ?>>🌐 Publik (Siapa saja dengan link)</option>
            </select>
        </div>

        <div id="area-link-share" style="display: <?= ($papan['access_type'] ?? 'private') === 'public' ? 'block' : 'none' ?>; margin-bottom: 20px; padding: 12px; background: #f0fdfa; border: 1px solid #ccfbf1; border-radius: 12px;">
            <label class="form-label" style="color: #115e59;">🔗 Link Publik:</label>
            <div style="display: flex; gap: 8px;">
                <input type="text" class="form-input" style="font-size: 11px; padding: 8px; flex: 1;" readonly id="link-publik-teks" value="<?= BASE_URL ?>papan/index.php?papan_id=<?= $papan_id ?>">
                <button type="button" class="btn-modal-submit" style="width: auto; padding: 0 12px; margin: 0; font-size: 13px;" onclick="copyLinkPublik()">Salin</button>
            </div>
            <p style="font-size: 10px; color: #14b8a6; margin-top: 6px; font-weight: 700;">Pengguna lain bisa memakai papan ini tanpa harus login.</p>
        </div>

        <div class="form-group">
            <label class="form-label">Ukuran Kotak (Grid)</label>
            <select id="setting-grid" class="form-input">
                <option value="2x3" <?= $papan['grid'] === '2x3' ? 'selected' : '' ?>>2 x 3 (6 Simbol)</option>
                <option value="3x3" <?= $papan['grid'] === '3x3' ? 'selected' : '' ?>>3 x 3 (9 Simbol)</option>
                <option value="3x4" <?= $papan['grid'] === '3x4' ? 'selected' : '' ?>>3 x 4 (12 Simbol)</option>
                <option value="4x5" <?= $papan['grid'] === '4x5' ? 'selected' : '' ?>>4 x 5 (20 Simbol)</option>
            </select>
        </div>

        <button class="btn-modal-submit" onclick="simpanSettingsPapan()">
            💾 Simpan Perubahan
        </button>
    </div>
</div>


<!-- ═══════════════════════════════════════
     MODAL — SIMBOL KUSTOM
═══════════════════════════════════════ -->
<div class="modal-backdrop-custom" id="modalKustom">
    <div class="modal-box">
        <button class="modal-tutup" onclick="tutupModalKustom()">✕</button>
        <div class="modal-title" id="kustom-judul">Buat Simbol Kustom</div>

        <!-- Area Emoji -->
        <div id="area-emoji" style="display:none;">
            <div class="form-group">
                <label class="form-label">Masukkan Emoji <span class="hint">— 1–2 karakter</span></label>
                <input type="text" id="kustom-emoji" class="form-input emoji-besar"
                       maxlength="2" placeholder="🍎">
            </div>
        </div>

        <!-- Area Kamera/Upload -->
        <div id="area-kamera-live" style="display:none;">
            <div class="kamera-area tampil" id="kamera-area-inner">
                <video id="video-kamera" class="video-kamera" autoplay playsinline></video>
                <canvas id="canvas-kamera" style="display:none;"></canvas>
                <img id="hasil-jepretan" class="hasil-jepretan" alt="Hasil foto">
                <div class="kamera-btns">
                    <button type="button" id="btn-jepret" class="btn-kamera jepret" onclick="ambilFoto()">
                        📸 Jepret
                    </button>
                    <button type="button" id="btn-ulang-foto" class="btn-kamera ulang"
                            style="display:none;" onclick="ulangFoto()">🔄 Ulangi</button>
                    <button type="button" class="btn-kamera ganti" onclick="gantiKamera()">🔃</button>
                </div>
            </div>

            <div class="atau-divider"><span>atau pilih dari galeri / file</span></div>

            <label class="file-label" for="kustom-file">
                📁 <span>Pilih gambar dari perangkat</span>
            </label>
            <input type="file" id="kustom-file" class="file-input-hidden" accept="image/*">
        </div>

        <div style="height:14px;"></div>

        <div class="form-group">
            <label class="form-label">Teks pada Kartu</label>
            <input type="text" id="kustom-tag" class="form-input" placeholder="Contoh: Apel">
        </div>
        <div class="form-group">
            <label class="form-label">Teks Suara <span class="hint">— opsional</span></label>
            <input type="text" id="kustom-suara" class="form-input" placeholder="Contoh: Aku mau apel">
        </div>
        <input type="hidden" id="kustom-tipe">

        <button class="btn-modal-submit" onclick="simpanSimbolKustom()">
            🚀 Simpan Simbol
        </button>
    </div>
</div>


<!-- Toast container -->
<div class="toast-container" id="toast-container"></div>


<!-- ═══════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
// ─── STATE ────────────────────────────────────────
let timeout      = null;
let timeoutMobile = null;
let streamKamera = null;
let pakaiKameraDepan = false;
let hasilFotoBlob = null;

// ─── TOAST ────────────────────────────────────────
function tampilToast(pesan, tipe = '') {
    const el = document.createElement('div');
    el.className = 'toast ' + tipe;
    el.textContent = pesan;
    document.getElementById('toast-container').appendChild(el);
    setTimeout(() => el.remove(), 2800);
}

// ─── SORTABLE (Drag & Drop) ────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('drag-grid');
    new Sortable(grid, {
        animation: 200,
        ghostClass: 'ghost-card',
        filter: '.btn-remove',
        onEnd: function () {
            let urutanData = [];
            document.querySelectorAll('#drag-grid .filled').forEach((el, index) => {
                urutanData.push({ id: el.dataset.id, urutan: index });
            });

            // Update nomor urut visual
            document.querySelectorAll('#drag-grid .slot-card').forEach((el, i) => {
                const urut = el.querySelector('.slot-urut');
                if (urut) urut.textContent = el.classList.contains('filled') ? (i + 1) : '';
            });

            const formData = new FormData();
            formData.append('aksi', 'update_urutan');
            formData.append('data', JSON.stringify(urutanData));
            fetch('papan-aksi.php', { method: 'POST', body: formData })
                .then(() => tampilToast('✅ Urutan tersimpan', 'sukses'));
        }
    });
});

// ─── DRAWER MOBILE ────────────────────────────────
function bukaDrawer() {
    document.getElementById('drawer').classList.add('tampil');
    document.getElementById('drawer-overlay').classList.add('tampil');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('input-cari-mobile').focus(), 350);
}
function tutupDrawer() {
    document.getElementById('drawer').classList.remove('tampil');
    document.getElementById('drawer-overlay').classList.remove('tampil');
    document.body.style.overflow = '';
}

// ─── SEARCH ARASAAC ───────────────────────────────
function debouncedSearch(val) {
    clearTimeout(timeout);
    timeout = setTimeout(() => cariSimbol(val, 'desktop'), 600);
}
function debouncedSearchMobile(val) {
    clearTimeout(timeoutMobile);
    timeoutMobile = setTimeout(() => cariSimbol(val, 'mobile'), 600);
}
function pakaiSaran(kata) {
    document.getElementById('input-cari').value = kata;
    cariSimbol(kata, 'desktop');
}
function pakaiSaranMobile(kata) {
    document.getElementById('input-cari-mobile').value = kata;
    cariSimbol(kata, 'mobile');
}

async function cariSimbol(keyword, mode) {
    if (keyword.length < 2) return;
    const loaderId  = mode === 'mobile' ? 'loader-mobile'   : 'loader';
    const hasilId   = mode === 'mobile' ? 'hasil-cari-mobile' : 'hasil-cari';

    document.getElementById(loaderId).classList.add('tampil');
    document.getElementById(hasilId).innerHTML = '';

    try {
        const transRes  = await fetch(`api-penerjemah.php?q=${encodeURIComponent(keyword)}`);
        const transData = await transRes.json();
        const apiRes    = await fetch(`https://api.arasaac.org/api/pictograms/en/search/${encodeURIComponent(transData.en)}`);
        const apiData   = await apiRes.json();

        document.getElementById(loaderId).classList.remove('tampil');

        if (Array.isArray(apiData) && apiData.length > 0) {
            const grid = document.createElement('div');
            grid.className = 'hasil-grid';

            apiData.slice(0, 30).forEach(item => {
                const id = item._id || item.id;
                const el = document.createElement('div');
                el.className = 'result-item';
                el.innerHTML = `
                    <img src="https://api.arasaac.org/api/pictograms/${id}" alt="${keyword}" loading="lazy">
                    <p>${keyword}</p>`;
                el.onclick = () => {
                    if (mode === 'mobile') tutupDrawer();
                    bukaModalTts(id, keyword);
                };
                grid.appendChild(el);
            });

            document.getElementById(hasilId).innerHTML = '';
            document.getElementById(hasilId).appendChild(grid);
        } else {
            document.getElementById(hasilId).innerHTML = `
                <div class="state-placeholder">
                    <span class="e">🔎</span>
                    <p>Tidak ada simbol ditemukan.<br>Coba kata lain atau buat simbol kustom.</p>
                </div>`;
        }
    } catch (err) {
        document.getElementById(loaderId).classList.remove('tampil');
        document.getElementById(hasilId).innerHTML = `
            <div class="state-placeholder">
                <span class="e">⚠️</span>
                <p>Gagal terhubung ke ARASAAC.<br>Periksa koneksi internet.</p>
            </div>`;
    }
}

// ─── MODAL ATUR LABEL ─────────────────────────────
function bukaModalTts(id, defaultKeyword) {
    document.getElementById('preview-modal-img').src = `https://api.arasaac.org/api/pictograms/${id}`;
    document.getElementById('input-tag').value    = defaultKeyword;
    document.getElementById('input-suara').value  = defaultKeyword;
    document.getElementById('hidden-simbol-id').value = id;
    document.getElementById('modalAturLabel').classList.add('tampil');
    setTimeout(() => document.getElementById('input-tag').focus(), 100);
}
function tutupModal(id) {
    document.getElementById(id).classList.remove('tampil');
}

function simpanKePapanLokal() {
    const idSimbol = document.getElementById('hidden-simbol-id').value;
    const tag      = document.getElementById('input-tag').value.trim();
    const suara    = document.getElementById('input-suara').value.trim();
    if (!tag) { tampilToast('⚠️ Tag gambar harus diisi!', 'gagal'); return; }
    tutupModal('modalAturLabel');
    tambahKePapan(idSimbol, tag + '|' + (suara ? suara : tag));
}

// ─── TAMBAH & HAPUS SIMBOL ────────────────────────
function tambahKePapan(idSimbol, label) {
    const formData = new FormData();
    formData.append('aksi', 'tambah_simbol');
    formData.append('papan_id', <?= $papan_id ?>);
    formData.append('simbol_id', idSimbol);
    formData.append('label', label);
    fetch('papan-aksi.php', { method: 'POST', body: formData })
        .then(() => {
            tampilToast('✅ Simbol ditambahkan!', 'sukses');
            setTimeout(() => location.reload(), 800);
        });
}

function hapusSimbol(idRow) {
    if (!confirm('Hapus simbol ini dari papan?')) return;
    const formData = new FormData();
    formData.append('aksi', 'hapus_simbol');
    formData.append('id', idRow);
    fetch('papan-aksi.php', { method: 'POST', body: formData })
        .then(() => {
            tampilToast('🗑️ Simbol dihapus', '');
            setTimeout(() => location.reload(), 600);
        });
}

// ─── MODAL SIMBOL KUSTOM ──────────────────────────
function bukaModalKustom(tipe) {
    document.getElementById('kustom-tipe').value = tipe;
    document.getElementById('kustom-judul').textContent =
        tipe === 'emoji' ? '😀 Buat dari Emoji' : '📸 Jepret / Upload Foto';
    document.getElementById('area-emoji').style.display       = tipe === 'emoji'   ? 'block' : 'none';
    document.getElementById('area-kamera-live').style.display = tipe === 'kamera'  ? 'block' : 'none';

    // Reset form
    hasilFotoBlob = null;
    if (document.getElementById('kustom-file'))  document.getElementById('kustom-file').value  = '';
    if (document.getElementById('kustom-tag'))   document.getElementById('kustom-tag').value   = '';
    if (document.getElementById('kustom-suara')) document.getElementById('kustom-suara').value = '';
    if (document.getElementById('kustom-emoji')) document.getElementById('kustom-emoji').value = '';

    if (tipe === 'kamera') mulaiKamera();
    else matikanKamera();

    document.getElementById('modalKustom').classList.add('tampil');
}
function tutupModalKustom() {
    matikanKamera();
    tutupModal('modalKustom');
}

function simpanSimbolKustom() {
    const tipe  = document.getElementById('kustom-tipe').value;
    const tag   = document.getElementById('kustom-tag').value.trim();
    const suara = document.getElementById('kustom-suara').value.trim();
    const labelGabungan = tag + '|' + (suara ? suara : tag);

    if (!tag) { tampilToast('⚠️ Tag gambar harus diisi!', 'gagal'); return; }

    const formData = new FormData();
    formData.append('papan_id', <?= $papan_id ?>);
    formData.append('label', labelGabungan);

    if (tipe === 'emoji') {
        const emoji = document.getElementById('kustom-emoji').value.trim();
        if (!emoji) { tampilToast('⚠️ Emoji tidak boleh kosong!', 'gagal'); return; }
        formData.append('aksi', 'tambah_simbol');
        formData.append('simbol_id', 'emoji-' + emoji);
    } else {
        const fileInput = document.getElementById('kustom-file').files[0];
        if (!hasilFotoBlob && !fileInput) {
            tampilToast('⚠️ Jepret foto atau pilih file terlebih dahulu!', 'gagal');
            return;
        }
        formData.append('aksi', 'upload_kamera');
        if (fileInput) {
            formData.append('foto', fileInput);
        } else {
            formData.append('foto', hasilFotoBlob, 'kamera-' + Date.now() + '.jpg');
        }
    }

    fetch('papan-aksi.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(res => {
            if (res.trim() === 'sukses') {
                tutupModalKustom();
                tampilToast('✅ Simbol kustom disimpan!', 'sukses');
                setTimeout(() => location.reload(), 800);
            } else {
                tampilToast('❌ Gagal menyimpan. Coba lagi.', 'gagal');
            }
        });
}

// ─── PENGATURAN PAPAN (Akses, Nama, Grid) ──────────
function bukaModalSettings() {
    document.getElementById('modalSettings').classList.add('tampil');
}
function toggleLinkShare() {
    const akses = document.getElementById('setting-akses').value;
    document.getElementById('area-link-share').style.display = (akses === 'public') ? 'block' : 'none';
}
function copyLinkPublik() {
    const link = document.getElementById('link-publik-teks');
    link.select();
    link.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(link.value);
    tampilToast('📋 Link berhasil disalin!', 'sukses');
}
function simpanSettingsPapan() {
    const nama = document.getElementById('setting-nama').value.trim();
    const grid = document.getElementById('setting-grid').value;
    const akses = document.getElementById('setting-akses').value;
    const deskripsi = document.getElementById('setting-deskripsi').value.trim();

    if (!nama) return tampilToast('⚠️ Nama papan tidak boleh kosong', 'gagal');

    const fd = new FormData();
    fd.append('aksi', 'update_papan_settings');
    fd.append('papan_id', <?= $papan_id ?>);
    fd.append('nama_papan', nama);
    fd.append('grid', grid);
    fd.append('access_type', akses);
    fd.append('deskripsi', deskripsi);

    fetch('papan-aksi.php', { method: 'POST', body: fd })
        .then(res => res.text())
        .then(res => {
            if (res.trim() === 'sukses') {
                tampilToast('✅ Pengaturan berhasil diperbarui!', 'sukses');
                setTimeout(() => location.reload(), 800);
            } else {
                tampilToast('❌ Gagal menyimpan pengaturan', 'gagal');
            }
        });
}

// ─── KAMERA ───────────────────────────────────────
function mulaiKamera() {
    if (!navigator.mediaDevices?.getUserMedia) {
        tampilToast('Browser tidak mendukung kamera.', 'gagal');
        return;
    }
    const opsi = { video: { facingMode: pakaiKameraDepan ? 'user' : 'environment' } };
    navigator.mediaDevices.getUserMedia(opsi)
        .then(stream => {
            streamKamera = stream;
            const video = document.getElementById('video-kamera');
            video.srcObject = stream;
            video.style.display = 'block';
            document.getElementById('hasil-jepretan').style.display = 'none';
            document.getElementById('btn-jepret').style.display     = '';
            document.getElementById('btn-ulang-foto').style.display = 'none';
        })
        .catch(() => tampilToast('Kamera tidak diizinkan. Gunakan upload file.', 'gagal'));
}
function matikanKamera() {
    streamKamera?.getTracks().forEach(t => t.stop());
    streamKamera = null;
}
function gantiKamera() {
    pakaiKameraDepan = !pakaiKameraDepan;
    matikanKamera();
    mulaiKamera();
}
function ambilFoto() {
    const video  = document.getElementById('video-kamera');
    const canvas = document.getElementById('canvas-kamera');
    const img    = document.getElementById('hasil-jepretan');
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    img.src = canvas.toDataURL('image/jpeg');
    img.style.display  = 'block';
    video.style.display = 'none';
    document.getElementById('btn-jepret').style.display     = 'none';
    document.getElementById('btn-ulang-foto').style.display = '';
    canvas.toBlob(blob => { hasilFotoBlob = blob; }, 'image/jpeg', 0.85);
    matikanKamera();
}
function ulangFoto() {
    hasilFotoBlob = null;
    document.getElementById('kustom-file').value = '';
    document.getElementById('hasil-jepretan').style.display = 'none';
    mulaiKamera();
}

// Tutup kamera otomatis saat modal ditutup
document.getElementById('modalKustom').addEventListener('click', function(e) {
    if (e.target === this) tutupModalKustom();
});
document.getElementById('modalAturLabel').addEventListener('click', function(e) {
    if (e.target === this) tutupModal('modalAturLabel');
});

// Close on Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        tutupModal('modalAturLabel');
        tutupModalKustom();
        tutupDrawer();
    }
});
</script>
</body>
</html>