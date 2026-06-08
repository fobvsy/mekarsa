<?php
session_start();
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
    $query = "SELECT products.*, product_categories.name as category_name 
              FROM products 
              LEFT JOIN product_categories ON products.category_id = product_categories.id 
              WHERE products.status = 'active'";
    $params = [];
    
    if ($search !== '') {
        $query .= " AND products.name LIKE ?";
        $params[] = "%$search%";
    }
    
    $query .= " ORDER BY products.id DESC";
    $prodStmt = $pdo->prepare($query);
    $prodStmt->execute($params);
    $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung total item di keranjang
    $cart_count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cart_count += $item['quantity'];
        }
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
    <title>Katalog Menu - <?= htmlspecialchars($settings['business_name']) ?></title>
    <!-- Fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="public/css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="description" content="Katalog menu lengkap <?= htmlspecialchars(strip_tags($settings['business_name'])) ?>. Jelajahi pilihan kopi dan minuman kami.">
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

        .cart-float {
            position: fixed;
            bottom: 80px;
            right: 20px;
            background: var(--color-orange);
            color: #fff;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(242,113,33,0.4);
            z-index: 99;
            transition: all 0.3s ease;
        }
        .cart-float:hover {
            transform: scale(1.1);
            color: #fff;
        }
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444; /* red */
            color: #fff;
            font-size: 0.75rem;
            font-weight: bold;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--color-bg-main);
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
                <a href="https://wa.me/<?= $wa_clean ?>" target="_blank" class="btn btn-primary"><i class="fab fa-whatsapp"></i> Pesan Sekarang</a>
            </div>
        </div>
    </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Katalog Menu</h1>
            <p class="page-subtitle">Temukan minuman favoritmu dari racikan beans pilihan dan bahan berkualitas premium Mekarsa.</p>
        </div>
    </section>

    <!-- Menu Catalog Section -->
    <section class="menu-section" style="padding-top: 3rem;">
        <div class="container">
            
            <!-- Filter & Search -->
            <div class="filter-section">
                <!-- Search Bar -->
                <div class="search-bar">
                    <form action="menu.php" method="GET" style="display:flex; width:100%;">
                        <input type="text" name="q" placeholder="Cari kopi favoritmu..." value="<?= htmlspecialchars($search) ?>" style="flex:1;">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                

            </div>

            <!-- Product Grid -->
            <div class="menu-grid">
                <?php if(!empty($products)): ?>
                    <?php foreach($products as $product): ?>
                        <div class="menu-card">
                            <?php if($product['is_featured']): ?>
                                <div class="menu-badge">Unggulan</div>
                            <?php endif; ?>
                            
                            <div class="menu-img">
                                <?php if($product['image']): ?>
                                    <img src="public/uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <div style="width:100%; height:100%; background:#27272a; display:flex; align-items:center; justify-content:center;">
                                        <i class="fas fa-image" style="font-size:2rem; color:#52525b;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="menu-content">
                                <?php if($product['category_name']): ?>
                                    <span class="menu-category"><?= htmlspecialchars($product['category_name']) ?></span>
                                <?php endif; ?>
                                <h3 class="menu-title"><a href="product-detail.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a></h3>
                                <p class="menu-desc"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 100)) ?>...</p>
                                <div class="menu-footer">
                                    <span class="price">Rp<?= number_format($product['price'], 0, ',', '.') ?></span>
                                    <form action="cart_action.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['name']) ?>">
                                        <input type="hidden" name="product_price" value="<?= $product['price'] ?>">
                                        <button type="submit" class="btn-icon" title="Tambah ke Keranjang" style="border:none; cursor:pointer;">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align: center; color: var(--color-gray);">Produk tidak ditemukan.</p>
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

    <!-- Floating Cart -->
    <?php if ($cart_count > 0): ?>
    <a href="order.php" class="cart-float" title="Lihat Keranjang">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-badge"><?= $cart_count ?></span>
    </a>
    <?php endif; ?>

    <script src="public/js/main.js"></script>
</body>
</html>
