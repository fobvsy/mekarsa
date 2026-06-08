<?php
/**
 * Halaman frontend ONLY untuk Testimoni Pelanggan.
 * Data di bawah bersifat DUMMY (mock data) untuk memudahkan pembuatan struktur frontend.
 * Nantinya variabel $testimonials ini diganti dengan query database:
 * SELECT * FROM testimonials ORDER BY created_at DESC
 */

$testimonials = [
    [
        'id'       => 1,
        'name'     => 'Rizky Aditya',
        'role'     => 'Mahasiswa UMS',
        'rating'   => 5,
        'content'  => 'KopSu Mekarsa emang beda! Satu-satunya tempat kopi di Pabelan yang bikin aku betah berjam-jam ngerjain tugas. Rasa kopinya pas, harganya masuk akal, dan tempatnya bersih banget.',
        'initials' => 'RA',
    ],
    [
        'id'       => 2,
        'name'     => 'Sinta Dewi',
        'role'     => 'Karyawan Swasta',
        'rating'   => 5,
        'content'  => 'Suka banget sama vibe-nya Mekarsa. Setelah capek kerja seharian, mampir sini minum Butterscotch sambil dengerin musik tuh rasanya healing banget. Highly recommended!',
        'initials' => 'SD',
    ],
    [
        'id'       => 3,
        'name'     => 'Farhan Nugroho',
        'role'     => 'Content Creator',
        'rating'   => 5,
        'content'  => 'Konsep coffee bar + shoe clean ini unik abis. Aku sekalian bersihin sneakers sambil nongkrong dan minum KopSu Gula Aren. Pelayanannya ramah dan cepat. Bakalan balik lagi!',
        'initials' => 'FN',
    ],
    [
        'id'       => 4,
        'name'     => 'Aulia Rahmawati',
        'role'     => 'Mahasiswi UNS',
        'rating'   => 5,
        'content'  => 'Americano-nya strong tapi ga bikin perut mual. Cocok banget buat yang lagi ngoding atau nulis skripsi. WiFi kenceng, stop kontak banyak, tempat nyaman. 10/10!',
        'initials' => 'AR',
    ],
    [
        'id'       => 5,
        'name'     => 'Bima Saputra',
        'role'     => 'Pengusaha Muda',
        'rating'   => 4,
        'content'  => 'Tempatnya enak buat meeting santai. Suasana minimalis, pencahayaan bagus. V60 Manual Brew-nya juga worth it banget untuk harganya. Bakal sering balik ke sini.',
        'initials' => 'BS',
    ],
    [
        'id'       => 6,
        'name'     => 'Nadia Putri',
        'role'     => 'Freelancer',
        'rating'   => 5,
        'content'  => 'KopSu Vanilla Mekarsa adalah yang terenak yang pernah aku coba di Kartasura! Harum vanillanya pas, tidak terlalu manis, dan after-taste kopinya masih berasa. Wajib coba!',
        'initials' => 'NP',
    ],
];

function renderStars(int $rating): string {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
    }
    return $stars;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimoni Pelanggan - Mekarsa Coffee Bar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <meta name="description" content="Apa kata pelanggan setia Mekarsa Coffee Bar? Baca ulasan jujur mereka tentang menu, suasana, dan layanan kami.">
    <style>
        /* Page Header */
        .page-header {
            padding: 6rem 0 3rem;
            text-align: center;
            background-color: var(--color-bg-secondary);
            border-bottom: 1px solid var(--color-border);
        }

        .page-title {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--color-white);
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: var(--color-text-muted);
            max-width: 600px;
            margin: 0 auto;
        }

        .testimonial-section {
            padding: 5rem 0 6rem;
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 4rem;
            margin-bottom: 4rem;
            padding: 2rem;
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-family: var(--font-price);
            font-size: 2.5rem;
            color: var(--color-orange);
            display: block;
            line-height: 1;
            margin-bottom: 0.3rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--color-text-muted);
            font-weight: 500;
        }

        .overall-rating {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: #ffc107;
            font-size: 1.2rem;
            margin-top: 0.3rem;
        }
    </style>
</head>
<body>

    <!-- Header & Navbar -->
    <header class="header">
        <div class="container navbar">
            <a href="index.php" class="nav-logo">
                <img src="images/logo.png" alt="Mekarsa Logo" class="navbar-logo-img">
                Mekarsa<span>.</span>
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="about.php">Tentang Kami</a></li>
                <li><a href="articles.php">Artikel</a></li>
                <li><a href="testimonials.php" class="active">Testimoni</a></li>
            </ul>
            <div class="nav-actions">
                <a href="https://wa.me/6285933504096" target="_blank" class="btn btn-primary">
                    <i class="fab fa-whatsapp"></i> Pesan Sekarang
                </a>
            </div>
        </div>
    </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Testimoni Pelanggan</h1>
            <p class="page-subtitle">Apa kata mereka yang sudah merasakan pengalaman minum kopi dan layanan di Mekarsa.</p>
        </div>
    </section>

    <!-- Testimonial Section -->
    <section class="testimonial-section">
        <div class="container">

            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-item">
                    <span class="stat-number">4.9</span>
                    <div class="overall-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <span class="stat-label">Rating Rata-rata</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= count($testimonials) ?>+</span>
                    <span class="stat-label">Ulasan Masuk</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">98%</span>
                    <span class="stat-label">Pelanggan Puas</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">500+</span>
                    <span class="stat-label">Pelanggan Setia</span>
                </div>
            </div>

            <!-- Testimonial Grid -->
            <div class="testimonial-grid">
                <?php foreach ($testimonials as $t): ?>
                    <div class="testimonial-card">
                        <i class="fas fa-quote-right testimonial-quote-icon"></i>
                        <p class="testimonial-content">"<?= htmlspecialchars($t['content']) ?>"</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">
                                <?= htmlspecialchars($t['initials']) ?>
                            </div>
                            <div class="testimonial-author-info">
                                <h4><?= htmlspecialchars($t['name']) ?></h4>
                                <p><?= htmlspecialchars($t['role']) ?></p>
                                <div class="testimonial-rating">
                                    <?= renderStars($t['rating']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Jadilah Bagian dari Keluarga Mekarsa!</h2>
            <p>Kunjungi kami dan rasakan sendiri pengalaman coffee terbaik di Kartasura.</p>
            <a href="https://wa.me/6285933504096" target="_blank" class="btn btn-primary">
                <i class="fab fa-whatsapp"></i> Hubungi Kami
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="index.php" class="nav-logo" style="display: block; margin-bottom: 1rem;">
                        <img src="images/logo.png" alt="Mekarsa Logo" class="navbar-logo-img">
                        Mekarsa<span>.</span>
                    </a>
                    <p>Mekarsa Shoe Clean & Coffee Bar. Coffee First, Clean Vibes Always. Tempat nongkrong modern dengan sajian kopi lokal premium di Kartasura.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Menu Cepat</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="menu.php">Menu Coffee</a></li>
                        <li><a href="about.php">Tentang Kami</a></li>
                        <li><a href="articles.php">Artikel</a></li>
                        <li><a href="testimonials.php">Testimoni</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Jam Buka</h4>
                    <ul class="footer-links">
                        <li>Senin - Jumat: 10:00 - 22:00</li>
                        <li>Sabtu - Minggu: 09:00 - 23:00</li>
                        <li>*Hari libur nasional tetap buka</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Kontak & Lokasi</h4>
                    <ul class="footer-links">
                        <li>
                            <i class="fas fa-map-marker-alt" style="color: var(--color-orange); margin-right: 8px;"></i>
                            Jl. Pabelan I, Gatak, Pabelan, Kec. Kartasura, Sukoharjo, Jawa Tengah 57169
                        </li>
                        <li>
                            <i class="fab fa-whatsapp" style="color: var(--color-orange); margin-right: 8px;"></i>
                            085933504096
                        </li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; 2026 Mekarsa Coffee Bar. All Rights Reserved.
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp -->
    <a href="https://wa.me/6285933504096" target="_blank" class="float-wa" title="Hubungi kami via WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

</body>
</html>
