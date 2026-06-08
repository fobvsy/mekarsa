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

    // Ambil Produk berdasarkan ID
    $id = $_GET['id'] ?? 0;
    if (!$id) {
        header("Location: menu.php");
        exit;
    }

    $prodStmt = $pdo->prepare("SELECT products.*, product_categories.name as category_name 
                               FROM products 
                               LEFT JOIN product_categories ON products.category_id = product_categories.id 
                               WHERE products.id = ? AND products.status = 'active'");
    $prodStmt->execute([$id]);
    $product = $prodStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Produk tidak ditemukan atau tidak tersedia.");
    }

    // Hitung total item di keranjang untuk badge navbar
    $cart_count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cart_count += $item['quantity'];
        }
    }

    // Ambil Produk Terkait (Maksimal 3, di kategori yang sama)
    $relatedStmt = $pdo->prepare("SELECT * FROM products 
                                  WHERE category_id = ? AND id != ? AND status = 'active' 
                                  ORDER BY RAND() LIMIT 3");
    $relatedStmt->execute([$product['category_id'], $id]);
    $relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error connecting to database.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - <?= htmlspecialchars($settings['business_name']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <meta name="description" content="<?= htmlspecialchars(substr($product['description'] ?? '', 0, 150)) ?>...">
    <style>
        .product-detail-container {
            padding: 8rem 0 5rem;
            max-width: 1100px;
            margin: 0 auto;
        }

        .product-split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .product-image-box {
            border-radius: 16px;
            overflow: hidden;
            background: var(--color-bg-secondary);
            aspect-ratio: 1/1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        .product-image-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-image-box:hover img {
            transform: scale(1.05);
        }

        .product-info-box h1 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            color: var(--color-white);
        }

        .product-category-label {
            display: inline-block;
            background: rgba(242, 113, 33, 0.15);
            color: var(--color-orange);
            padding: 0.3rem 1rem;
            border-radius: 30px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .product-price {
            font-family: var(--font-price);
            font-size: 2.5rem;
            color: var(--color-orange);
            margin-bottom: 1.5rem;
        }

        .product-description {
            font-size: 1.05rem;
            color: var(--color-text-muted);
            line-height: 1.8;
            margin-bottom: 2.5rem;
        }

        .product-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .product-actions form {
            flex: 1;
            min-width: 200px;
        }

        .btn-add-cart {
            width: 100%;
            padding: 1.2rem;
            border-radius: 8px;
            background: var(--color-orange);
            color: #fff;
            font-family: var(--font-head);
            font-weight: 700;
            font-size: 1.1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-add-cart:hover {
            background: #e06010;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(242,113,33,0.4);
        }

        .btn-whatsapp {
            flex: 1;
            min-width: 200px;
            padding: 1.2rem;
            border-radius: 8px;
            background: transparent;
            border: 2px solid var(--color-border);
            color: var(--color-text-muted);
            font-family: var(--font-head);
            font-weight: 700;
            font-size: 1.1rem;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-transform: uppercase;
        }

        .btn-whatsapp:hover {
            border-color: #25D366;
            color: #25D366;
        }

        /* Related Products */
        .related-products {
            margin-top: 6rem;
            border-top: 1px solid var(--color-border);
            padding-top: 4rem;
        }

        @media (max-width: 900px) {
            .product-split { grid-template-columns: 1fr; gap: 2rem; }
            .product-info-box h1 { font-size: 2.2rem; }
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

    <!-- Product Detail Container -->
    <main class="product-detail-container container">
        
        <div class="product-split">
            <!-- Left: Image -->
            <div class="product-image-box">
                <?php if($product['image']): ?>
                    <img src="public/uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <?php else: ?>
                    <i class="fas fa-image" style="font-size:5rem; color:#52525b;"></i>
                <?php endif; ?>
            </div>

            <!-- Right: Info -->
            <div class="product-info-box">
                <span class="product-category-label"><?= htmlspecialchars($product['category_name'] ?? 'Pilihan Spesial') ?></span>
                <h1><?= htmlspecialchars($product['name']) ?></h1>
                <div class="product-price">Rp<?= number_format($product['price'], 0, ',', '.') ?></div>
                
                <p class="product-description">
                    <?= nl2br(htmlspecialchars($product['description'] ?? 'Belum ada deskripsi untuk produk ini.')) ?>
                </p>

                <div class="product-actions">
                    <!-- Form Tambah ke Keranjang -->
                    <form action="cart_action.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['name']) ?>">
                        <input type="hidden" name="product_price" value="<?= $product['price'] ?>">
                        <button type="submit" class="btn-add-cart">
                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                        </button>
                    </form>

                    <!-- Tombol Pesan Langsung via WA -->
                    <a href="https://wa.me/<?= $wa_clean ?>?text=Halo%20<?= htmlspecialchars(explode(' ', $settings['business_name'])[0]) ?>,%20saya%20tertarik%20dengan%20produk%20<?= urlencode($product['name']) ?>" target="_blank" class="btn-whatsapp">
                        <i class="fab fa-whatsapp"></i> Tanya via WA
                    </a>
                </div>
                
                <div style="margin-top: 2rem;">
                    <a href="menu.php" style="color: var(--color-text-muted); text-decoration: none; font-size: 0.9rem;">
                        <i class="fas fa-arrow-left" style="margin-right: 5px;"></i> Kembali ke Menu
                    </a>
                </div>
            </div>
        </div>

        <!-- Related Products Section -->
        <?php if(!empty($relatedProducts)): ?>
        <div class="related-products">
            <h2 style="font-size: 2rem; margin-bottom: 2rem; text-align: center;">Mungkin Anda Juga Suka</h2>
            <div class="menu-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php foreach($relatedProducts as $rel): ?>
                    <div class="menu-card">
                        <div class="menu-img">
                            <?php if($rel['image']): ?>
                                <img src="public/uploads/products/<?= htmlspecialchars($rel['image']) ?>" alt="<?= htmlspecialchars($rel['name']) ?>">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background:#27272a; display:flex; align-items:center; justify-content:center;">
                                    <i class="fas fa-image" style="font-size:2rem; color:#52525b;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="menu-content">
                            <h3 class="menu-title"><a href="product-detail.php?id=<?= $rel['id'] ?>"><?= htmlspecialchars($rel['name']) ?></a></h3>
                            <div class="menu-footer" style="margin-top: 1rem;">
                                <span class="price">Rp<?= number_format($rel['price'], 0, ',', '.') ?></span>
                                <a href="product-detail.php?id=<?= $rel['id'] ?>" class="btn-icon" style="text-decoration:none;">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer id="contact" class="footer" style="margin-top: 4rem;">
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

    <!-- Floating Cart -->
    <?php if ($cart_count > 0): ?>
    <a href="order.php" class="cart-float" title="Lihat Keranjang">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-badge"><?= $cart_count ?></span>
    </a>
    <?php endif; ?>

</body>
</html>
