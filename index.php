<?php
require_once __DIR__ . '/src/config/database.php';
try {
    $pdo = getDBConnection();

    // 1. Ambil Settings
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

    // 2. Ambil Featured Products (maks 4)
    $prodStmt = $pdo->query("SELECT * FROM products WHERE status = 'active' AND is_featured = 1 ORDER BY id DESC LIMIT 4");
    $featuredProducts = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Ambil Artikel Terbaru (maks 3)
    $artStmt = $pdo->query("SELECT * FROM articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
    $latestArticles = $artStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Ambil Testimoni (maks 6)
    $testStmt = $pdo->query("SELECT * FROM testimonials WHERE status = 'show' ORDER BY id DESC LIMIT 6");
    $testimonials = $testStmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Ambil Galeri Terbaru (maks 6)
    $galStmt = $pdo->query("SELECT * FROM gallery WHERE status = 'show' ORDER BY created_at DESC LIMIT 6");
    $galleries = $galStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error connecting to database.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['business_name'] . ' - ' . $settings['tagline']) ?></title>
    <!-- Fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="public/css/style.css?v=1780916164">
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars(strip_tags($settings['description'])) ?>">
    <style>
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .gallery-item { position: relative; border-radius: 12px; overflow: hidden; aspect-ratio: 4/3; background: #27272a; }
        .gallery-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .gallery-item:hover .gallery-img { transform: scale(1.1); }
        .gallery-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); opacity: 0; transition: opacity 0.3s ease; display: flex; flex-direction: column; justify-content: flex-end; padding: 1.5rem; }
        .gallery-item:hover .gallery-overlay { opacity: 1; }
        .gallery-title { color: #fff; font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem; transform: translateY(15px); opacity: 0; transition: all 0.3s ease 0.1s; }
        .gallery-cat { color: var(--color-orange); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; transform: translateY(15px); opacity: 0; transition: all 0.3s ease; }
        .gallery-item:hover .gallery-title, .gallery-item:hover .gallery-cat { transform: translateY(0); opacity: 1; }
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

    <!-- Hero Section -->
    <section id="home" class="hero container">
        <div class="hero-content">
            <span class="hero-tagline"><?= htmlspecialchars($settings['tagline']) ?></span>
            <h1 class="hero-title">Experience the Best Local Coffee Bar.</h1>
            <p class="hero-desc"><?= nl2br(htmlspecialchars($settings['description'])) ?></p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="#menu" class="btn btn-primary">Lihat Menu</a>
                <a href="#about" class="btn btn-outline">Eksplor Mekarsa</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="public/images/image1.jpeg" alt="Suasana Mekarsa Coffee Bar">
        </div>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="menu-section">
        <div class="container">
            <h2>Katalog Menu</h2>
            <p class="section-subtitle">Pilihan kopi terbaik untuk menemani harimu</p>
            
            <div class="menu-grid">
                <?php if (!empty($featuredProducts)): ?>
                    <?php foreach ($featuredProducts as $prod): ?>
                        <div class="menu-card">
                            <?php if ($prod['is_featured']): ?>
                                <div class="menu-badge">Unggulan</div>
                            <?php endif; ?>
                            <div class="menu-img">
                                <?php if ($prod['image']): ?>
                                    <img src="public/uploads/products/<?= $prod['image'] ?>" alt="<?= htmlspecialchars($prod['name']) ?>">
                                <?php else: ?>
                                    <div style="width:100%; height:100%; background:#27272a; display:flex; align-items:center; justify-content:center;">
                                        <i class="fas fa-image" style="font-size:2rem; color:#52525b;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="menu-content">
                                <span class="menu-category">Pilihan Tersedia</span>
                                <h3 class="menu-title"><a href="product-detail.php?id=<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?></a></h3>
                                <p class="menu-desc"><?= htmlspecialchars(substr($prod['description'] ?? '', 0, 100)) ?>...</p>
                                <div class="menu-footer">
                                    <span class="price">Rp<?= number_format($prod['price'], 0, ',', '.') ?></span>
                                    <a href="https://wa.me/<?= $wa_clean ?>?text=Halo%20<?= htmlspecialchars(explode(' ', $settings['business_name'])[0]) ?>,%20saya%20ingin%20pesan%20<?= urlencode($prod['name']) ?>" target="_blank" class="btn-icon" title="Pesan via WhatsApp">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align: center; color: var(--color-gray);">Produk belum tersedia.</p>
                <?php endif; ?>
            </div>

            <div style="text-align: center; margin-top: 3rem;">
                <a href="menu.php" class="btn btn-outline">Lihat Semua Menu</a>
            </div>
        </div>
    </section>

    <!-- Layanan Pendukung (Shoe Clean) -->
    <section id="shoeclean" class="services container">
        <div class="service-img">
            <img src="public/images/image1.jpeg" alt="Layanan Shoe Clean Mekarsa">
        </div>
        <div class="service-content">
            <h2>Shoe Clean Service</h2>
            <p>Selain menyajikan kopi terbaik, Mekarsa juga memiliki identitas unik melalui layanan perawatan sepatu. Nongkrong sambil menunggu sepatu kesayanganmu kembali bersih!</p>
            <ul class="service-list">
                <li><i class="fas fa-check-circle"></i> Deep Cleaning Treatment</li>
                <li><i class="fas fa-check-circle"></i> Unyellowing & Repaint</li>
                <li><i class="fas fa-check-circle"></i> Fast Service Guarantee</li>
            </ul>
            <a href="https://wa.me/<?= $wa_clean ?>" target="_blank" class="btn btn-primary">Konsultasi Sepatu</a>
        </div>
    </section>

    <!-- Galeri Section -->
    <section class="gallery-section container" style="padding: 6rem 0;">
        <h2>Galeri Mekarsa</h2>
        <p class="section-subtitle">Momen dan suasana yang tertangkap kamera.</p>
        
        <div class="gallery-grid">
            <?php if (!empty($galleries)): ?>
                <?php foreach ($galleries as $gal): ?>
                    <div class="gallery-item">
                        <img src="public/uploads/gallery/<?= htmlspecialchars($gal['image']) ?>" alt="<?= htmlspecialchars($gal['title'] ?? 'Galeri') ?>" class="gallery-img">
                        <div class="gallery-overlay">
                            <?php 
                            $catLabel = ['interior' => 'Interior', 'exterior' => 'Eksterior', 'events' => 'Event', 'products' => 'Produk', 'others' => 'Lainnya']; 
                            $catStr = $catLabel[$gal['category']] ?? 'Lainnya';
                            ?>
                            <span class="gallery-cat"><?= $catStr ?></span>
                            <?php if ($gal['title']): ?>
                                <h3 class="gallery-title"><?= htmlspecialchars($gal['title']) ?></h3>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align: center; color: var(--color-gray);">Belum ada foto galeri terbaru.</p>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 3rem;">
            <a href="gallery.php" class="btn btn-outline" style="border-radius: 30px;">Lihat Semua Galeri</a>
        </div>
    </section>

    <!-- Artikel Terbaru Section -->
    <section class="article-section container" style="padding: 6rem 0;">
        <h2>Artikel Terbaru</h2>
        <p class="section-subtitle">Wawasan seputar kopi, promo, dan lifestyle.</p>
        
        <div class="article-grid">
            <?php if (!empty($latestArticles)): ?>
                <?php foreach ($latestArticles as $art): ?>
                    <article class="article-card">
                        <div class="article-img">
                            <?php if ($art['image']): ?>
                                <img src="public/uploads/articles/<?= $art['image'] ?>" alt="<?= htmlspecialchars($art['title']) ?>">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background:#27272a; display:flex; align-items:center; justify-content:center;">
                                    <i class="fas fa-newspaper" style="font-size:2rem; color:#52525b;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="article-content">
                            <div class="article-meta">
                                <span class="article-category">Artikel</span>
                                <span><?= date('d M Y', strtotime($art['created_at'])) ?></span>
                            </div>
                            <h3 class="article-title">
                                <a href="article-detail.php?slug=<?= htmlspecialchars($art['slug']) ?>"><?= htmlspecialchars($art['title']) ?></a>
                            </h3>
                            <p class="article-excerpt"><?= htmlspecialchars(substr(strip_tags($art['content']), 0, 100)) ?>...</p>
                            <div>
                                <a href="article-detail.php?slug=<?= htmlspecialchars($art['slug']) ?>" class="article-readmore">
                                    Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align: center; color: var(--color-gray);">Belum ada artikel terbaru.</p>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 3rem;">
            <a href="articles.php" class="btn btn-outline" style="border-radius: 30px;">Lihat Semua Artikel</a>
        </div>
    </section>

    <!-- Testimoni Carousel Section -->
    <section class="menu-section" style="padding: 5rem 0;">
        <div class="container">
            <h2>Kata Mereka</h2>
            <p class="section-subtitle">Ulasan jujur dari pelanggan setia Mekarsa.</p>

            <div class="carousel-wrapper">
                <div class="carousel-track" id="testimonialTrack">
                    <?php if (!empty($testimonials)): ?>
                        <?php foreach ($testimonials as $testi): ?>
                            <div class="carousel-slide">
                                <div class="testimonial-card">
                                    <i class="fas fa-quote-right testimonial-quote-icon"></i>
                                    <p class="testimonial-content">"<?= htmlspecialchars($testi['message']) ?>"</p>
                                    <div class="testimonial-author">
                                        <div class="testimonial-avatar">
                                            <?= strtoupper(substr($testi['customer_name'], 0, 2)) ?>
                                        </div>
                                        <div class="testimonial-author-info">
                                            <h4><?= htmlspecialchars($testi['customer_name']) ?></h4>
                                            <p>Pelanggan</p>
                                            <div class="testimonial-rating">
                                                <?php for ($i = 0; $i < $testi['rating']; $i++): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php endfor; ?>
                                                <?php for ($i = $testi['rating']; $i < 5; $i++): ?>
                                                    <i class="fas fa-star" style="color:var(--color-gray);"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="carousel-slide">
                            <div class="testimonial-card" style="text-align:center;">
                                <p class="testimonial-content">Belum ada ulasan.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Carousel Controls -->
            <div class="carousel-controls">
                <button class="carousel-btn" id="prevBtn" aria-label="Sebelumnya"><i class="fas fa-arrow-left"></i></button>
                <div class="carousel-dots" id="carouselDots"></div>
                <button class="carousel-btn" id="nextBtn" aria-label="Berikutnya"><i class="fas fa-arrow-right"></i></button>
            </div>

            <div style="text-align: center; margin-top: 2.5rem;">
                <a href="testimonials.php" class="btn btn-outline" style="border-radius: 30px;">Lihat Semua Testimoni</a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Siap Menikmati Kopi Terbaik?</h2>
            <p>Pesan sekarang secara online melalui WhatsApp dan nikmati promo menarik minggu ini.</p>
            <a href="https://wa.me/<?= $wa_clean ?>" target="_blank" class="btn btn-primary" style="background-color: var(--color-white); color: var(--color-black);">Hubungi Kami di WA</a>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="index.php" class="nav-logo" style="display: block; margin-bottom: 1rem;"><?= htmlspecialchars(explode(' ', $settings['business_name'])[0]) ?><span>.</span></a>
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
                &copy; 2026 Mekarsa Coffee Bar. All Rights Reserved.
             <a href="portal-mekarsa/login.php" style="color: inherit; text-decoration: none; margin-left: 10px; opacity: 0.3; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.3'" title="Admin Login"><i class="fas fa-lock" style="font-size:0.85em;"></i></a>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp -->
    <a href="https://wa.me/<?= $wa_clean ?>" target="_blank" class="float-wa" title="Hubungi kami via WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Smooth Scrolling + Carousel Script -->
    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Testimonial Carousel
        (function () {
            const track = document.getElementById('testimonialTrack');
            const slides = track ? track.querySelectorAll('.carousel-slide') : [];
            const dotsContainer = document.getElementById('carouselDots');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            if (!track || slides.length === 0) return;

            let current = 0;
            let autoTimer;

            // Build dots
            slides.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
                dot.setAttribute('aria-label', 'Slide ' + (i + 1));
                dot.addEventListener('click', () => goTo(i));
                dotsContainer.appendChild(dot);
            });

            function goTo(index) {
                current = (index + slides.length) % slides.length;
                track.style.transform = `translateX(-${current * 100}%)`;
                document.querySelectorAll('.carousel-dot').forEach((d, i) =>
                    d.classList.toggle('active', i === current)
                );
            }

            function startAuto() {
                autoTimer = setInterval(() => goTo(current + 1), 4500);
            }

            function stopAuto() {
                clearInterval(autoTimer);
            }

            prevBtn.addEventListener('click', () => { stopAuto(); goTo(current - 1); startAuto(); });
            nextBtn.addEventListener('click', () => { stopAuto(); goTo(current + 1); startAuto(); });

            startAuto();
        })();
    </script>
    <script src="public/js/main.js"></script>
</body>
</html>
