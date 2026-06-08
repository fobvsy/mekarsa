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

    // Filter Kategori
    $filterCat = $_GET['category'] ?? '';
    $where = ["status = 'show'"];
    $params = [];
    
    if (in_array($filterCat, ['interior', 'exterior', 'events', 'products', 'others'])) {
        $where[] = "category = ?";
        $params[] = $filterCat;
    }
    
    $whereSQL = "WHERE " . implode(' AND ', $where);

    // Ambil Galeri
    $galStmt = $pdo->prepare("SELECT * FROM gallery $whereSQL ORDER BY created_at DESC");
    $galStmt->execute($params);
    $galleries = $galStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error connecting to database.");
}

$categoryLabels = [
    'interior' => 'Interior',
    'exterior' => 'Eksterior',
    'events'   => 'Event / Acara',
    'products' => 'Produk',
    'others'   => 'Lainnya'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri - <?= htmlspecialchars($settings['business_name']) ?></title>
    <!-- Fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="public/css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="description" content="Lihat galeri foto suasana, event, dan produk <?= htmlspecialchars(strip_tags($settings['business_name'])) ?>.">
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

        .gallery-section {
            padding-bottom: 6rem;
        }

        .gallery-filters {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 3rem;
        }

        .filter-btn {
            padding: 0.6rem 1.5rem;
            border-radius: 30px;
            border: 1px solid var(--color-border);
            background: transparent;
            color: var(--color-white);
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--color-orange);
            border-color: var(--color-orange);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .gallery-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 4/3;
            background: #27272a;
            group;
        }

        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .gallery-item:hover .gallery-img {
            transform: scale(1.1);
        }

        .gallery-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 1.5rem;
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-title {
            color: #fff;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease 0.1s;
        }

        .gallery-cat {
            color: var(--color-orange);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .gallery-item:hover .gallery-title,
        .gallery-item:hover .gallery-cat {
            transform: translateY(0);
            opacity: 1;
        }
        
        .empty-gallery {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 0;
            color: var(--color-text-muted);
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
            <h1 class="page-title">Galeri Mekarsa</h1>
            <p class="page-subtitle">Lihatlah sekilas momen, produk, dan suasana hangat di <?= htmlspecialchars($settings['business_name']) ?>.</p>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="gallery-section">
        <div class="container">
            
            <!-- Filters -->
            <div class="gallery-filters">
                <a href="gallery.php" class="filter-btn <?= $filterCat === '' ? 'active' : '' ?>">Semua</a>
                <?php foreach ($categoryLabels as $key => $label): ?>
                    <a href="gallery.php?category=<?= $key ?>" class="filter-btn <?= $filterCat === $key ? 'active' : '' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Grid -->
            <div class="gallery-grid">
                <?php if (!empty($galleries)): ?>
                    <?php foreach ($galleries as $gal): ?>
                        <div class="gallery-item">
                            <img src="public/uploads/gallery/<?= htmlspecialchars($gal['image']) ?>" alt="<?= htmlspecialchars($gal['title'] ?? 'Galeri') ?>" class="gallery-img">
                            <div class="gallery-overlay">
                                <span class="gallery-cat"><?= $categoryLabels[$gal['category']] ?? 'Lainnya' ?></span>
                                <?php if ($gal['title']): ?>
                                    <h3 class="gallery-title"><?= htmlspecialchars($gal['title']) ?></h3>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-gallery">
                        <i class="fas fa-images" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>Belum ada foto yang ditampilkan di kategori ini.</p>
                    </div>
                <?php endif; ?>
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

</body>
</html>
