<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mekarsa Coffee Bar - Coffee First, Clean Vibes Always</title>
    <!-- Fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="description" content="Mekarsa Coffee Bar adalah UMKM yang menggabungkan konsep coffee bar dengan layanan perawatan sepatu di Kartasura.">
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
                <li><a href="#home" class="active">Beranda</a></li>
                <li><a href="#menu">Menu</a></li>
                <li><a href="about.php">Tentang Kami</a></li>
                <li><a href="articles.php">Artikel</a></li>
                <li><a href="#shoeclean">Shoe Clean</a></li>
                <li><a href="#contact">Kontak</a></li>
            </ul>
            <div class="nav-actions">
                <a href="https://wa.me/6285933504096" target="_blank" class="btn btn-primary"><i class="fab fa-whatsapp"></i> Pesan Sekarang</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero container">
        <div class="hero-content">
            <span class="hero-tagline">Coffee First, Clean Vibes Always</span>
            <h1 class="hero-title">Experience the Best Local Coffee Bar.</h1>
            <p class="hero-desc">Nikmati momen nongkrong dan bekerja santai dengan sajian signature coffee terbaik di Kartasura, berpadu dengan nuansa modern dan bersih.</p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="#menu" class="btn btn-primary">Lihat Menu</a>
                <a href="#about" class="btn btn-outline">Eksplor Mekarsa</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="images/image1.jpeg" alt="Suasana Mekarsa Coffee Bar">
        </div>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="menu-section">
        <div class="container">
            <h2>Katalog Menu</h2>
            <p class="section-subtitle">Pilihan kopi terbaik untuk menemani harimu</p>
            
            <div class="menu-grid">
                <!-- Product 1 -->
                <div class="menu-card">
                    <div class="menu-badge">Signature</div>
                    <div class="menu-img">
                        <img src="https://images.unsplash.com/photo-1572442388796-11668a67e53d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="KopSu Mekarsa">
                    </div>
                    <div class="menu-content">
                        <span class="menu-category">Signature Drinks</span>
                        <h3 class="menu-title">KopSu Mekarsa</h3>
                        <p class="menu-desc">Menu signature atau menu khas Mekarsa dengan paduan espresso yang pas dan krimer yang lembut.</p>
                        <div class="menu-footer">
                            <span class="price">Rp18.000</span>
                            <a href="https://wa.me/6285933504096?text=Halo%20Mekarsa,%20saya%20ingin%20pesan%20KopSu%20Mekarsa" target="_blank" class="btn-icon" title="Pesan via WhatsApp">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Product 2 -->
                <div class="menu-card">
                    <div class="menu-img">
                        <img src="https://images.unsplash.com/photo-1551030173-122aabc4489c?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Americano">
                    </div>
                    <div class="menu-content">
                        <span class="menu-category">Coffee</span>
                        <h3 class="menu-title">Americano</h3>
                        <p class="menu-desc">Menu coffee dasar dengan cita rasa espresso bold yang murni dan menyegarkan.</p>
                        <div class="menu-footer">
                            <span class="price">Rp15.000</span>
                            <a href="https://wa.me/6285933504096?text=Halo%20Mekarsa,%20saya%20ingin%20pesan%20Americano" target="_blank" class="btn-icon" title="Pesan via WhatsApp">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Product 3 -->
                <div class="menu-card">
                    <div class="menu-badge">Best Seller</div>
                    <div class="menu-img">
                        <img src="https://images.unsplash.com/photo-1578314675249-a6910f80cc4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="KopSu Gula Aren">
                    </div>
                    <div class="menu-content">
                        <span class="menu-category">Signature Drinks</span>
                        <h3 class="menu-title">KopSu Gula Aren</h3>
                        <p class="menu-desc">Menu kopi susu dengan rasa gula aren asli yang legit dan otentik khas cita rasa lokal.</p>
                        <div class="menu-footer">
                            <span class="price">Rp20.000</span>
                            <a href="https://wa.me/6285933504096?text=Halo%20Mekarsa,%20saya%20ingin%20pesan%20KopSu%20Gula%20Aren" target="_blank" class="btn-icon" title="Pesan via WhatsApp">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Product 4 -->
                <div class="menu-card">
                    <div class="menu-img">
                        <img src="https://images.unsplash.com/photo-1497935586351-b67a49e012bf?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="KopSu Vanilla">
                    </div>
                    <div class="menu-content">
                        <span class="menu-category">Signature Drinks</span>
                        <h3 class="menu-title">KopSu Vanilla</h3>
                        <p class="menu-desc">Menu kopi susu dengan sentuhan sirup vanilla premium yang manis dan harum.</p>
                        <div class="menu-footer">
                            <span class="price">Rp20.000</span>
                            <a href="https://wa.me/6285933504096?text=Halo%20Mekarsa,%20saya%20ingin%20pesan%20KopSu%20Vanilla" target="_blank" class="btn-icon" title="Pesan via WhatsApp">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 3rem;">
                <a href="#" class="btn btn-outline">Lihat Semua Menu</a>
            </div>
        </div>
    </section>

    <!-- Layanan Pendukung (Shoe Clean) -->
    <section id="shoeclean" class="services container">
        <div class="service-img">
            <img src="https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Layanan Shoe Clean Mekarsa">
        </div>
        <div class="service-content">
            <h2>Shoe Clean Service</h2>
            <p>Selain menyajikan kopi terbaik, Mekarsa juga memiliki identitas unik melalui layanan perawatan sepatu. Nongkrong sambil menunggu sepatu kesayanganmu kembali bersih!</p>
            <ul class="service-list">
                <li><i class="fas fa-check-circle"></i> Deep Cleaning Treatment</li>
                <li><i class="fas fa-check-circle"></i> Unyellowing & Repaint</li>
                <li><i class="fas fa-check-circle"></i> Fast Service Guarantee</li>
            </ul>
            <a href="https://wa.me/6285933504096" target="_blank" class="btn btn-primary">Konsultasi Sepatu</a>
        </div>
    </section>

    <!-- Artikel Terbaru Section -->
    <section class="article-section container" style="padding: 6rem 0;">
        <h2>Artikel Terbaru</h2>
        <p class="section-subtitle">Wawasan seputar kopi, promo, dan lifestyle.</p>
        
        <div class="article-grid">
            <!-- Article 1 -->
            <article class="article-card">
                <div class="article-img">
                    <img src="https://images.unsplash.com/photo-1497935586351-b67a49e012bf?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Rekomendasi Menu Coffee">
                </div>
                <div class="article-content">
                    <div class="article-meta">
                        <span class="article-category">Lifestyle</span>
                        <span>12 Juni 2026</span>
                    </div>
                    <h3 class="article-title">
                        <a href="article-detail.php?id=1">Rekomendasi Menu Coffee untuk Menemani Tugas Kuliah</a>
                    </h3>
                    <p class="article-excerpt">Mengerjakan tugas kuliah seringkali membutuhkan fokus tinggi. Berikut rekomendasi kopi dari Mekarsa agar kamu tetap melek dan produktif seharian.</p>
                    <div>
                        <a href="article-detail.php?id=1" class="article-readmore">
                            Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </article>

            <!-- Article 2 -->
            <article class="article-card">
                <div class="article-img">
                    <img src="https://images.unsplash.com/photo-1495474472205-51f7743d1a8c?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Kopi Susu dan Manual Brew">
                </div>
                <div class="article-content">
                    <div class="article-meta">
                        <span class="article-category">Edukasi</span>
                        <span>10 Juni 2026</span>
                    </div>
                    <h3 class="article-title">
                        <a href="article-detail.php?id=2">Perbedaan Kopi Susu dan Manual Brew</a>
                    </h3>
                    <p class="article-excerpt">Masih bingung mau pesan kopi susu atau manual brew? Mari bahas perbedaan mencolok dari segi rasa, tekstur, dan metode seduhnya.</p>
                    <div>
                        <a href="article-detail.php?id=2" class="article-readmore">
                            Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </article>

            <!-- Article 3 -->
            <article class="article-card">
                <div class="article-img">
                    <img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Promo Spesial">
                </div>
                <div class="article-content">
                    <div class="article-meta">
                        <span class="article-category">Promo</span>
                        <span>08 Juni 2026</span>
                    </div>
                    <h3 class="article-title">
                        <a href="article-detail.php?id=3">Promo Spesial Libur Semester: Beli 2 Gratis 1!</a>
                    </h3>
                    <p class="article-excerpt">Menyambut libur semester, Mekarsa memberikan promo Buy 2 Get 1 Free untuk Signature Drinks. Jangan sampai ketinggalan promo ini.</p>
                    <div>
                        <a href="article-detail.php?id=3" class="article-readmore">
                            Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </article>
        </div>

        <div style="text-align: center; margin-top: 3rem;">
            <a href="articles.php" class="btn btn-outline" style="border-radius: 30px;">Lihat Semua Artikel</a>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Siap Menikmati Kopi Terbaik?</h2>
            <p>Pesan sekarang secara online melalui WhatsApp dan nikmati promo menarik minggu ini.</p>
            <a href="https://wa.me/6285933504096" target="_blank" class="btn btn-primary" style="background-color: var(--color-white); color: var(--color-black);">Hubungi Kami di WA</a>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="#" class="nav-logo" style="display: block; margin-bottom: 1rem;">Mekarsa<span>.</span></a>
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
                        <li><a href="#home">Beranda</a></li>
                        <li><a href="#menu">Menu Coffee</a></li>
                        <li><a href="#about">Tentang Kami</a></li>
                        <li><a href="#shoeclean">Layanan Shoe Clean</a></li>
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

    <!-- Smooth Scrolling Script -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
