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

    // Tangani pencarian (Search)
    $search = $_GET['q'] ?? '';
    $query = "SELECT articles.*, article_categories.name as category_name 
              FROM articles 
              LEFT JOIN article_categories ON articles.category_id = article_categories.id 
              WHERE articles.status = 'published'";
    $params = [];
    
    if ($search !== '') {
        $query .= " AND (articles.title LIKE ? OR articles.content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $query .= " ORDER BY articles.created_at DESC";
    $artStmt = $pdo->prepare($query);
    $artStmt->execute($params);
    $articles = $artStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error connecting to database.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog & Artikel - <?= htmlspecialchars($settings['business_name']) ?></title>
    <!-- Fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="public/css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="description" content="Baca artikel terbaru dari <?= htmlspecialchars(strip_tags($settings['business_name'])) ?> mengenai kopi, lifestyle, dan tips perawatan sepatu.">
    <style>
        .page-header {
            padding: 6rem 0 3rem;
            text-align: center;
            background-color: var(--color-bg-main);
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

        .article-section {
            padding-bottom: 6rem;
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
            <ul class="nav-links">
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Blog & Berita</h1>
            <p class="page-subtitle">Kumpulan wawasan seputar kopi, lifestyle, update promo, hingga tips dan trik merawat sepatu dari Mekarsa.</p>
        </div>
    </section>

    <!-- Article Catalog Section -->
    <section class="article-section">
        <div class="container">
            
            <!-- Filter & Search (Reusing existing CSS for consistency) -->
            <div class="filter-section">
                <!-- Search Bar -->
                <div class="search-bar">
                    <form action="articles.php" method="GET" style="display:flex; width:100%;">
                        <input type="text" name="q" placeholder="Cari artikel..." value="<?= htmlspecialchars($search) ?>" style="flex:1;">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>

            <!-- Article Grid -->
            <div class="article-grid">
                <?php if (!empty($articles)): ?>
                    <?php foreach($articles as $article): ?>
                        <article class="article-card">
                            <div class="article-img">
                                <?php if ($article['image']): ?>
                                    <img src="public/uploads/articles/<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                                <?php else: ?>
                                    <div style="width:100%; height:100%; background:#27272a; display:flex; align-items:center; justify-content:center;">
                                        <i class="fas fa-newspaper" style="font-size:2rem; color:#52525b;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="article-content">
                                <div class="article-meta">
                                    <span class="article-category"><?= htmlspecialchars($article['category_name'] ?? 'Artikel') ?></span>
                                    <span><?= date('d M Y', strtotime($article['created_at'])) ?></span>
                                </div>
                                <h3 class="article-title">
                                    <a href="article-detail.php?slug=<?= htmlspecialchars($article['slug']) ?>"><?= htmlspecialchars($article['title']) ?></a>
                                </h3>
                                <p class="article-excerpt"><?= htmlspecialchars(substr(strip_tags($article['content']), 0, 150)) ?>...</p>
                                <div>
                                    <a href="article-detail.php?slug=<?= htmlspecialchars($article['slug']) ?>" class="article-readmore">
                                        Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align: center; color: var(--color-gray);">Artikel tidak ditemukan.</p>
                <?php endif; ?>
            </div>

            <!-- Pagination Placeholder -->
            <div style="text-align: center; margin-top: 4rem;">
                <a href="#" class="btn btn-outline" style="border-radius: 30px;">Muat Lebih Banyak</a>
            </div>

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

</body>
</html>
