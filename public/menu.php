<?php
/**
 * Ini adalah halaman frontend ONLY untuk Katalog Produk.
 * Data di bawah ini bersifat DUMMY (mock data) untuk memudahkan pembuatan struktur frontend.
 * Nantinya, variabel $categories dan $products ini dapat diganti dengan hasil fetch dari database MySQL menggunakan PHP.
 */

$products = [
    [
        'id' => 1,
        'name' => 'KopSu Mekarsa',
        'category' => 'Signature Drinks',
        'desc' => 'Menu signature atau menu khas Mekarsa dengan paduan espresso yang pas dan krimer yang lembut.',
        'price' => 'Rp18.000',
        'image' => 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'badge' => 'Signature'
    ],
    [
        'id' => 2,
        'name' => 'Americano',
        'category' => 'Coffee',
        'desc' => 'Menu coffee dasar dengan cita rasa espresso bold yang murni dan menyegarkan.',
        'price' => 'Rp15.000',
        'image' => 'https://images.unsplash.com/photo-1551030173-122aabc4489c?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'badge' => null
    ],
    [
        'id' => 3,
        'name' => 'KopSu Gula Aren',
        'category' => 'Signature Drinks',
        'desc' => 'Menu kopi susu dengan rasa gula aren asli yang legit dan otentik khas cita rasa lokal.',
        'price' => 'Rp20.000',
        'image' => 'https://images.unsplash.com/photo-1578314675249-a6910f80cc4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'badge' => 'Best Seller'
    ],
    [
        'id' => 4,
        'name' => 'KopSu Vanilla',
        'category' => 'Signature Drinks',
        'desc' => 'Menu kopi susu dengan sentuhan sirup vanilla premium yang manis dan harum.',
        'price' => 'Rp20.000',
        'image' => 'https://images.unsplash.com/photo-1497935586351-b67a49e012bf?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'badge' => null
    ],
    [
        'id' => 5,
        'name' => 'Matcha Latte',
        'category' => 'Non-Coffee',
        'desc' => 'Paduan serbuk matcha premium Jepang dengan susu segar yang creamy.',
        'price' => 'Rp22.000',
        'image' => 'https://images.unsplash.com/photo-1515823662972-da6a2e4d3002?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'badge' => 'New'
    ],
    [
        'id' => 6,
        'name' => 'Butterscotch',
        'category' => 'Signature Drinks',
        'desc' => 'Varian manis dengan karakter rasa butterscotch yang kaya dan karamel lembut.',
        'price' => 'Rp20.000',
        'image' => 'https://images.unsplash.com/photo-1582294154948-e8d93f7da201?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'badge' => null
    ],
    [
        'id' => 7,
        'name' => 'V60 Manual Brew',
        'category' => 'Manual Brew',
        'desc' => 'Seduhan manual dengan metode V60 menggunakan beans pilihan lokal maupun internasional.',
        'price' => 'Rp22.000',
        'image' => 'https://images.unsplash.com/photo-1495474472205-51f7743d1a8c?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'badge' => null
    ],
    [
        'id' => 8,
        'name' => 'French Fries',
        'category' => 'Snack',
        'desc' => 'Kentang goreng renyah dengan taburan bumbu rahasia khas Mekarsa.',
        'price' => 'Rp15.000',
        'image' => 'https://images.unsplash.com/photo-1576107232684-1279f3908594?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'badge' => null
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Menu - Mekarsa Coffee Bar</title>
    <!-- Fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="description" content="Katalog menu lengkap Mekarsa Coffee Bar. Jelajahi berbagai pilihan coffee, non-coffee, signature drinks, dan snack kami.">
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
                <li><a href="menu.php" class="active">Menu</a></li>
                <li><a href="about.php">Tentang Kami</a></li>
                <li><a href="index.php#shoeclean">Shoe Clean</a></li>
                <li><a href="#contact">Kontak</a></li>
            </ul>
            <div class="nav-actions">
                <a href="https://wa.me/6285933504096" target="_blank" class="btn btn-primary"><i class="fab fa-whatsapp"></i> Pesan Sekarang</a>
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
                    <input type="text" placeholder="Cari kopi favoritmu...">
                    <button><i class="fas fa-search"></i></button>
                </div>
                

            </div>

            <!-- Product Grid -->
            <div class="menu-grid">
                <?php foreach($products as $product): ?>
                    <div class="menu-card">
                        <?php if($product['badge']): ?>
                            <div class="menu-badge"><?= htmlspecialchars($product['badge']) ?></div>
                        <?php endif; ?>
                        
                        <div class="menu-img">
                            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="menu-content">
                            <h3 class="menu-title"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="menu-desc"><?= htmlspecialchars($product['desc']) ?></p>
                            <div class="menu-footer">
                                <span class="price"><?= htmlspecialchars($product['price']) ?></span>
                                <?php 
                                    $waText = urlencode("Halo Mekarsa, saya ingin pesan " . $product['name']);
                                    $waLink = "https://wa.me/6285933504096?text=" . $waText;
                                ?>
                                <a href="<?= $waLink ?>" target="_blank" class="btn-icon" title="Pesan via WhatsApp">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

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
                        <li><a href="index.php#shoeclean">Layanan Shoe Clean</a></li>
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
                        <li><i class="fas fa-map-marker-alt" style="color: var(--color-orange); margin-right: 8px;"></i> Jl. Pabelan I, Gatak, Pabelan, Kec. Kartasura, Sukoharjo, Jawa Tengah 57169</li>
                        <li><i class="fab fa-whatsapp" style="color: var(--color-orange); margin-right: 8px;"></i> 085933504096</li>
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
