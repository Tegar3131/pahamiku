<?php
$is_logged_in = isset($_SESSION['user_id']);
$user_nama = '';
if ($is_logged_in && function_exists('getUserLogin')) {
    global $conn;
    $u = getUserLogin($conn);
    if ($u) $user_nama = $u['nama'];
}
?>
<style>
.global-navbar {
    background: white;
    padding: 0 28px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    position: sticky;
    top: 0;
    z-index: 1000;
    font-family: 'Nunito', sans-serif;
}
.global-navbar-brand {
    font-family: 'Baloo 2', cursive;
    font-size: 24px;
    font-weight: 800;
    background: linear-gradient(135deg, #FF6B6B, #FFD93D, #4ECDC4);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-decoration: none;
}
.global-navbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}
.global-navbar-name {
    font-size: 14px;
    font-weight: 700;
    color: #6B7280;
}
.global-navbar-name span {
    color: #1A1A2E;
}
.global-navbar-text-link {
    font-size: 14px;
    font-weight: 800;
    color: #4ECDC4;
    text-decoration: none;
    transition: color 0.2s;
}
.global-navbar-text-link:hover {
    color: #2BB5AC;
}
.global-navbar-btn {
    padding: 8px 16px;
    background: #F3F4F6;
    border: none;
    border-radius: 10px;
    font-family: 'Nunito', sans-serif;
    font-size: 13px;
    font-weight: 800;
    color: #6B7280;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}
.global-navbar-btn:hover {
    background: #FEF2F2;
    color: #FF6B6B;
}
@media (max-width: 600px) {
    .global-navbar { padding: 0 16px; }
    .global-navbar-name { display: none; }
}
@media (max-width: 480px) {
    .global-navbar { height: auto; min-height: 60px; padding: 10px 12px; }
    .global-navbar-right { flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
    .global-navbar-brand { font-size: 20px; }
    .global-navbar-text-link { font-size: 13px; margin-right: 0 !important; }
    .global-navbar-btn { padding: 6px 12px; font-size: 12px; }
}
</style>

<div class="global-navbar">
    <a href="<?= BASE_URL ?>index.php" class="global-navbar-brand">PAHAMIKU</a>
    <div class="global-navbar-right">
        <?php if ($is_logged_in): ?>
            <a href="<?= BASE_URL ?>dashboard/index.php" class="global-navbar-text-link">Dashboard</a>
            <div class="global-navbar-name">
                Halo, <span><?= htmlspecialchars($user_nama) ?></span> 👋
            </div>
            <a href="<?= BASE_URL ?>logout.php?jenis=pendamping" class="global-navbar-btn">Keluar</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>index.php" class="global-navbar-text-link" style="margin-right: 8px;">Beranda</a>
            <a href="<?= BASE_URL ?>tentang.php" class="global-navbar-text-link" style="margin-right: 8px;">Tentang</a>
            <a href="<?= BASE_URL ?>login-abk.php" class="global-navbar-text-link" style="margin-right: 8px;">Area ABK</a>
            <a href="<?= BASE_URL ?>login-pendamping.php" class="global-navbar-btn" style="background:#4ECDC4; color:white;">Login Pendamping</a>
        <?php endif; ?>
    </div>
</div>
