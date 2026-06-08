<?php
require_once __DIR__ . '/src/config/database.php';
try {
    $pdo = getDBConnection();
    
    // Ambil Settings
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'business_name' => 'Mekarsa Coffee Bar',
        'tagline' => 'Coffee First, Clean Vibes Always',
        'description' => '',
        'whatsapp' => '6285933504096',
        'instagram' => '',
        'address' => '',
        'opening_hours' => ''
    ];
    $wa_clean = preg_replace('/[^0-9]/', '', $settings['whatsapp']);

    // Ambil Testimonials
    $testStmt = $pdo->query("SELECT * FROM testimonials WHERE status = 'show' ORDER BY id DESC");
    $testimonials = $testStmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil Stats
    $statStmt = $pdo->query("SELECT COUNT(id) as total_reviews, AVG(rating) as avg_rating FROM testimonials WHERE status = 'show'");
    $stats = $statStmt->fetch(PDO::FETCH_ASSOC);
    $total_reviews = $stats['total_reviews'] ?: 0;
    $avg_rating = $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : '5.0';

} catch (PDOException $e) {
    die("Error connecting to database.");
}

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
    <title>Testimoni Pelanggan - <?= htmlspecialchars($settings['business_name']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css?v=1780916164">
    <meta name="description" content="Apa kata pelanggan setia <?= htmlspecialchars(strip_tags($settings['business_name'])) ?>? Baca ulasan jujur mereka.">
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
                <img src="public/images/logo.png" alt="Mekarsa Logo" class="navbar-logo-img">
                <?= htmlspecialchars(explode(' ', $settings['business_name'])[0]) ?><span>.</span>
            </a>
                                    <button class="hamburger" id="hamburger" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="about.php">Tentang Kami</a></li>
                <li><a href="support-service.php">Shoe Clean</a></li>
                <li><a href="contact.php">Kontak</a></li>
            </ul>
            <div class="nav-actions">
                <a href="https://wa.me/<?= $wa_clean ?>" target="_blank" class="btn btn-primary">
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
                    <span class="stat-number"><?= $avg_rating ?></span>
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
                    <span class="stat-number"><?= $total_reviews ?></span>
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
                <?php if (!empty($testimonials)): ?>
                    <?php foreach ($testimonials as $t): ?>
                        <div class="testimonial-card">
                            <i class="fas fa-quote-right testimonial-quote-icon"></i>
                            <p class="testimonial-content">"<?= htmlspecialchars($t['message']) ?>"</p>
                            <div class="testimonial-author">
                                <div class="testimonial-avatar">
                                    <?= strtoupper(substr($t['customer_name'], 0, 2)) ?>
                                </div>
                                <div class="testimonial-author-info">
                                    <h4><?= htmlspecialchars($t['customer_name']) ?></h4>
                                    <p>Pelanggan</p>
                                    <div class="testimonial-rating">
                                        <?= renderStars($t['rating']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--color-gray); width: 100%;">Belum ada testimoni.</p>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Jadilah Bagian dari Keluarga Mekarsa!</h2>
            <p>Kunjungi kami dan rasakan sendiri pengalaman coffee terbaik di Kartasura.</p>
            <a href="https://wa.me/<?= $wa_clean ?>" target="_blank" class="btn btn-primary">
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
                        <img src="public/images/logo.png" alt="Mekarsa Logo" class="navbar-logo-img">
                        <?= htmlspecialchars(explode(' ', $settings['business_name'])[0]) ?><span>.</span>
                    </a>
                    <p><?= htmlspecialchars($settings['description'] ?? 'Mekarsa Coffee Bar') ?></p>
                    <div class="social-links">
                        <?php if(!empty($settings['instagram'])): ?>
                            <a href="https://instagram.com/<?= htmlspecialchars($settings['instagram']) ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <a href="https://wa.me/<?= $wa_clean ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                                        <h4>Menu Cepat</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="menu.php">Menu Coffee</a></li>
                        <li><a href="about.php">Tentang Kami</a></li>
                        <li><a href="support-service.php">Layanan Shoe Clean</a></li>
                        <li><a href="contact.php">Kontak</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Jam Buka</h4>
                    <ul class="footer-links">
                        <li><?= htmlspecialchars($settings['opening_hours'] ?? 'Buka Setiap Hari') ?></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Kontak & Lokasi</h4>
                    <ul class="footer-links">
                        <li>
                            <i class="fas fa-map-marker-alt" style="color: var(--color-orange); margin-right: 8px;"></i>
                            <?= htmlspecialchars($settings['address'] ?? '') ?>
                        </li>
                        <li>
                            <i class="fab fa-whatsapp" style="color: var(--color-orange); margin-right: 8px;"></i>
                            <?= htmlspecialchars($settings['whatsapp'] ?? '') ?>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($settings['business_name']) ?>. All Rights Reserved.
             <a href="portal-mekarsa/login.php" style="color: inherit; text-decoration: none; margin-left: 10px; opacity: 0.3; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.3'" title="Admin Login"><i class="fas fa-lock" style="font-size:0.85em;"></i></a>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp -->
    <a href="https://wa.me/<?= $wa_clean ?>" target="_blank" class="float-wa" title="Hubungi kami via WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

    <script src="public/js/main.js"></script>
</body>
</html>
