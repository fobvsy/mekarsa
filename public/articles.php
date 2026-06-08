<?php
/**
 * Ini adalah halaman frontend ONLY untuk Daftar Artikel.
 * Data di bawah ini bersifat DUMMY (mock data) untuk memudahkan pembuatan struktur frontend.
 * Nantinya, variabel $articles ini dapat diganti dengan hasil fetch dari database MySQL menggunakan PHP.
 */

$articles = [
    [
        'id' => 1,
        'title' => 'Rekomendasi Menu Coffee untuk Menemani Tugas Kuliah',
        'category' => 'Lifestyle',
        'date' => '12 Juni 2026',
        'excerpt' => 'Mengerjakan tugas kuliah seringkali membutuhkan fokus tinggi. Berikut adalah rekomendasi menu kopi dari Mekarsa yang dijamin bikin kamu tetap melek dan produktif seharian penuh.',
        'image' => 'https://images.unsplash.com/photo-1497935586351-b67a49e012bf?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
    ],
    [
        'id' => 2,
        'title' => 'Perbedaan Kopi Susu dan Manual Brew',
        'category' => 'Edukasi',
        'date' => '10 Juni 2026',
        'excerpt' => 'Masih bingung mau pesan kopi susu atau manual brew? Artikel ini akan membahas perbedaan mencolok dari segi rasa, tekstur, dan metode seduhnya agar kamu tidak salah pilih menu.',
        'image' => 'https://images.unsplash.com/photo-1495474472205-51f7743d1a8c?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
    ],
    [
        'id' => 3,
        'title' => 'Promo Spesial Libur Semester: Beli 2 Gratis 1!',
        'category' => 'Promo',
        'date' => '08 Juni 2026',
        'excerpt' => 'Menyambut libur semester, Mekarsa memberikan promo Buy 2 Get 1 Free untuk semua varian Signature Drinks. Jangan sampai ketinggalan promo spesial yang sangat terbatas ini.',
        'image' => 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
    ],
    [
        'id' => 4,
        'title' => 'Cara Merawat Sepatu Sneakers Agar Tidak Mudah Kuning',
        'category' => 'Tips & Trick',
        'date' => '05 Juni 2026',
        'excerpt' => 'Sering kesal karena bagian midsole sneakers putih kamu menguning (yellowing)? Yuk pelajari cara merawatnya dengan tepat, atau percayakan pada layanan Shoe Clean di Mekarsa.',
        'image' => 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
    ],
    [
        'id' => 5,
        'title' => 'Mengenal Biji Kopi Lokal yang Kami Gunakan',
        'category' => 'Edukasi',
        'date' => '01 Juni 2026',
        'excerpt' => 'Kualitas espresso yang baik berasal dari biji kopi pilihan. Mari berkenalan dengan house blend lokal unggulan yang menjadi rahasia di balik nikmatnya racikan Mekarsa.',
        'image' => 'https://images.unsplash.com/photo-1559525839-b184a4d698c7?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog & Artikel - Mekarsa Coffee Bar</title>
    <!-- Fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="description" content="Baca artikel terbaru dari Mekarsa Coffee Bar mengenai edukasi kopi, lifestyle, promo menarik, dan tips perawatan sepatu.">
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
                <img src="images/logo.png" alt="Mekarsa Logo" class="navbar-logo-img">
                Mekarsa<span>.</span>
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="about.php">Tentang Kami</a></li>
                <li><a href="articles.php" class="active">Artikel</a></li>
                <li><a href="index.php#contact">Kontak</a></li>
            </ul>
            <div class="nav-actions">
                <a href="https://wa.me/6285933504096" target="_blank" class="btn btn-primary"><i class="fab fa-whatsapp"></i> Pesan Sekarang</a>
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
                    <input type="text" placeholder="Cari artikel...">
                    <button><i class="fas fa-search"></i></button>
                </div>
            </div>

            <!-- Article Grid -->
            <div class="article-grid">
                <?php foreach($articles as $article): ?>
                    <article class="article-card">
                        <div class="article-img">
                            <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                        </div>
                        <div class="article-content">
                            <div class="article-meta">
                                <span class="article-category"><?= htmlspecialchars($article['category']) ?></span>
                                <span><?= htmlspecialchars($article['date']) ?></span>
                            </div>
                            <h3 class="article-title">
                                <a href="article-detail.php?id=<?= $article['id'] ?>"><?= htmlspecialchars($article['title']) ?></a>
                            </h3>
                            <p class="article-excerpt"><?= htmlspecialchars($article['excerpt']) ?></p>
                            <div>
                                <a href="article-detail.php?id=<?= $article['id'] ?>" class="article-readmore">
                                    Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
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
