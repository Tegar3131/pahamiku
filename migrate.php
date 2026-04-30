<?php
include 'inc/config.php';

$sqls = [
    "ALTER TABLE users ADD COLUMN peran ENUM('orang tua', 'guru', 'terapis', 'caregiver', 'lainnya') DEFAULT 'orang tua' AFTER foto_profil",
    "ALTER TABLE profil_abk ADD COLUMN kategori_usia VARCHAR(50) NULL AFTER foto_profil",
    "ALTER TABLE profil_abk ADD COLUMN kebutuhan_komunikasi VARCHAR(100) NULL AFTER kategori_usia",
    "ALTER TABLE profil_abk ADD COLUMN deskripsi_singkat TEXT NULL AFTER kebutuhan_komunikasi",
    "ALTER TABLE profil_abk ADD COLUMN is_public BOOLEAN DEFAULT FALSE AFTER deskripsi_singkat",
    "CREATE TABLE postingan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        isi_teks TEXT NULL,
        papan_id INT NULL,
        profil_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE postingan_suka (
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (post_id, user_id),
        FOREIGN KEY (post_id) REFERENCES postingan(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE postingan_komentar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        isi_komentar TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES postingan(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE user_follows (
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (follower_id, following_id),
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // ── Migrasi Manajemen Papan ABK ──
    "ALTER TABLE papan ADD COLUMN is_aktif BOOLEAN DEFAULT TRUE AFTER is_favorit",
    "ALTER TABLE papan ADD COLUMN urutan_tampil INT DEFAULT 0 AFTER is_aktif",
    "CREATE TABLE profil_papan_preset (
        profil_id INT NOT NULL,
        papan_id INT NOT NULL,
        is_aktif BOOLEAN DEFAULT TRUE,
        urutan_tampil INT DEFAULT 0,
        PRIMARY KEY (profil_id, papan_id),
        FOREIGN KEY (profil_id) REFERENCES profil_abk(id) ON DELETE CASCADE,
        FOREIGN KEY (papan_id) REFERENCES papan(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($sqls as $query) {
    try {
        if ($conn->query($query) === TRUE) {
            echo "Sukses: " . substr($query, 0, 50) . "...\n<br>";
        }
    } catch (Exception $e) {
        echo "Lewati: " . $e->getMessage() . "\n<br>";
    }
}
echo "Migrasi Selesai.\n";
?>
