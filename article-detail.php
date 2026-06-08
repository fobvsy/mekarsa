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

    // Ambil Artikel
    $slug = $_GET['slug'] ?? '';
    if (!$slug) {
        header("Location: articles.php");
        exit;
    }

    $artStmt = $pdo->prepare("SELECT articles.*, article_categories.name as category_name 
                              FROM articles 
                              LEFT JOIN article_categories ON articles.category_id = article_categories.id 
                              WHERE articles.slug = ? AND articles.status = 'published'");
    $artStmt->execute([$slug]);
    $article = $artStmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        die("Artikel tidak ditemukan atau belum dipublikasikan.");
    }

} catch (PDOException $e) {
    die("Error connecting to database.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> - <?= htmlspecialchars($settings['business_name']) ?></title>
    <!-- Fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="public/css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars(substr(strip_tags($article['content']), 0, 150)) ?>...">
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
                <a href="https://wa.me/<?= $wa_clean ?>" target="_blank" class="btn btn-primary"><i class="fab fa-whatsapp"></i> Pesan Sekarang</a>
            </div>
        </div>
    </header>

    <!-- Article Detail Section -->
    <main class="article-detail-container">
        
        <div class="article-header">
            <span class="article-category"><?= htmlspecialchars($article['category_name'] ?? 'Artikel') ?></span>
            <h1><?= htmlspecialchars($article['title']) ?></h1>
            <div class="article-meta-info">
                <span><i class="far fa-calendar-alt"></i> <?= date('d M Y', strtotime($article['created_at'])) ?></span>
                <span><i class="far fa-user"></i> Ditulis oleh Admin</span>
            </div>
        </div>

        <?php if ($article['image']): ?>
            <img src="public/uploads/articles/<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="article-hero-img">
        <?php endif; ?>

        <div class="article-body">
            <!-- Render konten HTML artikel -->
            <?= $article['content'] ?>
        </div>

        <div class="article-footer">
            <a href="articles.php" class="btn btn-outline" style="border-radius: 30px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Blog
            </a>
            
            <div class="share-links">
                <p>Bagikan:</p>
                <a href="#" title="Share to Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="Share to Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" title="Share to WhatsApp"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>

    </main>

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
                        <li><i class="fas fa-map-marker-alt" style="color: var(--color-orange); margin-right: 8px;"></i> <?= htmlspecialchars($settings['address'] ?? '') ?></li>
                        <li><i class="fab fa-whatsapp" style="color: var(--color-orange); margin-right: 8px;"></i> <?= htmlspecialchars($settings['whatsapp'] ?? '') ?></li>
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
