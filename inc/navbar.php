<?php
$is_logged_in = isset($_SESSION['user_id']);
$user_nama = '';
if ($is_logged_in && function_exists('getUserLogin')) {
    global $conn;
    $u = getUserLogin($conn);
    if ($u) $user_nama = $u['nama'];
}
?>
<!-- Include Bootstrap CSS & JS for Navbar -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>

<style>
/* Custom overrides for the Bootstrap Navbar to keep the PAHAMIKU branding */
.global-navbar {
    background: white;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    font-family: 'Nunito', sans-serif;
}
.global-navbar .navbar-brand {
    font-family: 'Baloo 2', cursive;
    font-size: 24px;
    font-weight: 800;
    background: linear-gradient(135deg, #FF6B6B, #FFD93D, #4ECDC4);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    padding-left: 10px;
}
.global-navbar .nav-link {
    font-size: 14px;
    font-weight: 800;
    color: #4ECDC4 !important;
    transition: color 0.2s;
    padding: 10px 15px !important;
}
.global-navbar .nav-link:hover {
    color: #2BB5AC !important;
}
.global-navbar .nav-user-greeting {
    font-size: 14px;
    font-weight: 700;
    color: #6B7280;
    display: flex;
    align-items: center;
    margin-right: 15px;
    padding: 10px 15px;
}
.global-navbar .nav-user-greeting span {
    color: #1A1A2E;
    margin-left: 4px;
}
.global-navbar .nav-btn {
    padding: 8px 20px;
    background: #F3F4F6;
    border: none;
    border-radius: 10px;
    font-family: 'Nunito', sans-serif;
    font-size: 13px;
    font-weight: 800;
    color: #6B7280;
    text-decoration: none;
    transition: all 0.2s;
    display: inline-block;
    text-align: center;
}
.global-navbar .nav-btn:hover {
    background: #FEF2F2;
    color: #FF6B6B;
}
.global-navbar .nav-btn-primary {
    background: #4ECDC4;
    color: white !important;
}
.global-navbar .nav-btn-primary:hover {
    background: #2BB5AC;
    color: white !important;
}
.global-navbar .navbar-toggler {
    border: none;
}
.global-navbar .navbar-toggler:focus {
    box-shadow: none;
}
</style>

<nav class="navbar navbar-expand-lg global-navbar sticky-top">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand" href="<?= BASE_URL ?>index.php">PAHAMIKU</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center text-center text-lg-start mt-3 mt-lg-0 pb-3 pb-lg-0">
        <?php if ($is_logged_in): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>index.php">Beranda</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>galeri-papan.php">Galeri Papan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>komunitas.php">Komunitas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>dashboard/index.php">Dashboard</a>
            </li>
            <li class="nav-item d-none d-lg-block">
                <div class="nav-user-greeting">
                    Halo, <span><?= htmlspecialchars($user_nama) ?></span> 👋
                </div>
            </li>
            <li class="nav-item mt-2 mt-lg-0">
                <a class="nav-btn w-100" href="<?= BASE_URL ?>logout.php?jenis=pendamping">Keluar</a>
            </li>
        <?php else: ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>index.php">Beranda</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>galeri-papan.php">Galeri Papan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>komunitas.php">Komunitas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>login-abk.php">Area ABK</a>
            </li>
            <li class="nav-item mt-2 mt-lg-0 ms-lg-2">
                <a class="nav-btn nav-btn-primary w-100" href="<?= BASE_URL ?>login-pendamping.php">Login Pendamping</a>
            </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
