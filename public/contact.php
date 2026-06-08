<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak & Lokasi - Mekarsa Coffee Bar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <meta name="description" content="Temukan lokasi Mekarsa Coffee Bar di Jl. Pabelan I, Kartasura. Hubungi kami via WhatsApp atau Instagram @mekarsaa.">
    <style>
        /* Page Header */
        .page-header {
            padding: 6rem 0 3rem;
            text-align: center;
            background: var(--color-bg-secondary);
            border-bottom: 1px solid var(--color-border);
        }
        .page-title { font-size: 3rem; margin-bottom: 0.8rem; }
        .page-subtitle {
            color: var(--color-text-muted);
            font-size: 1.1rem;
            max-width: 560px;
            margin: 0 auto;
        }

        /* Contact Layout */
        .contact-section { padding: 5rem 0 6rem; }
        .contact-layout {
            display: grid;
            grid-template-columns: 1fr 1.6fr;
            gap: 3rem;
            align-items: start;
        }

        /* Info Panel */
        .contact-info-panel {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .contact-card {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 10px;
            padding: 1.8rem;
            display: flex;
            align-items: flex-start;
            gap: 1.2rem;
            transition: border-color 0.3s, transform 0.3s;
        }
        .contact-card:hover {
            border-color: var(--color-orange);
            transform: translateX(5px);
        }

        .contact-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: rgba(242,113,33,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--color-orange);
            flex-shrink: 0;
        }

        .contact-card-body h4 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--color-orange);
            margin-bottom: 0.4rem;
        }
        .contact-card-body p,
        .contact-card-body a {
            color: var(--color-text-main);
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.6;
        }
        .contact-card-body a:hover { color: var(--color-orange); }

        /* Hours Table */
        .hours-table { width: 100%; }
        .hours-table tr td { padding: 0.2rem 0; font-size: 0.97rem; }
        .hours-table td:last-child { text-align: right; color: var(--color-text-muted); }

        /* Map Panel */
        .map-panel {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            overflow: hidden;
        }

        .map-panel iframe {
            width: 100%;
            height: 480px;
            border: none;
            display: block;
            filter: invert(90%) hue-rotate(180deg) brightness(0.9) contrast(1.1);
        }

        .map-panel-footer {
            padding: 1.2rem 1.5rem;
            border-top: 1px solid var(--color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.8rem;
        }

        .map-panel-footer p {
            font-size: 0.9rem;
            color: var(--color-text-muted);
        }
        .map-panel-footer p i { color: var(--color-orange); margin-right: 0.4rem; }

        /* Social Media Row */
        .social-section {
            background: var(--color-bg-secondary);
            border-top: 1px solid var(--color-border);
            border-bottom: 1px solid var(--color-border);
            padding: 4rem 0;
        }

        .social-grid {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
            margin-top: 2.5rem;
        }

        .social-card {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 2rem 2.5rem;
            text-align: center;
            transition: transform 0.3s, border-color 0.3s;
            min-width: 180px;
        }
        .social-card:hover {
            transform: translateY(-6px);
            border-color: var(--color-orange);
        }
        .social-card i {
            font-size: 2.5rem;
            color: var(--color-orange);
            margin-bottom: 1rem;
        }
        .social-card h4 { font-size: 1rem; margin-bottom: 0.3rem; }
        .social-card p { font-size: 0.9rem; color: var(--color-text-muted); }

        @media (max-width: 900px) {
            .contact-layout { grid-template-columns: 1fr; }
            .map-panel iframe { height: 320px; }
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
                <li><a href="articles.php">Artikel</a></li>
                <li><a href="contact.php" class="active">Kontak</a></li>
            </ul>
            <div class="nav-actions">
                <a href="https://wa.me/6285933504096" target="_blank" class="btn btn-primary">
                    <i class="fab fa-whatsapp"></i> Pesan Sekarang
                </a>
            </div>
        </div>
    </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Kontak & Lokasi</h1>
            <p class="page-subtitle">Temukan kami di Pabelan, Kartasura. Kami siap menyambut kamu setiap hari.</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-layout">

                <!-- Info Panel -->
                <div class="contact-info-panel">

                    <!-- Alamat -->
                    <div class="contact-card">
                        <div class="contact-card-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="contact-card-body">
                            <h4>Alamat</h4>
                            <p>Jl. Pabelan I, Gatak, Pabelan, Kec. Kartasura, Kabupaten Sukoharjo, Jawa Tengah 57169</p>
                        </div>
                    </div>

                    <!-- WhatsApp -->
                    <a href="https://wa.me/6285933504096" target="_blank" class="contact-card" style="text-decoration: none;">
                        <div class="contact-card-icon"><i class="fab fa-whatsapp"></i></div>
                        <div class="contact-card-body">
                            <h4>WhatsApp</h4>
                            <p>085933504096</p>
                            <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-top: 0.2rem;">Klik untuk langsung chat →</p>
                        </div>
                    </a>

                    <!-- Instagram -->
                    <a href="https://instagram.com/mekarsaa" target="_blank" class="contact-card" style="text-decoration: none;">
                        <div class="contact-card-icon"><i class="fab fa-instagram"></i></div>
                        <div class="contact-card-body">
                            <h4>Instagram</h4>
                            <p>@mekarsaa</p>
                            <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-top: 0.2rem;">Ikuti kami untuk promo terbaru →</p>
                        </div>
                    </a>

                    <!-- Jam Operasional -->
                    <div class="contact-card" style="flex-direction: column; align-items: flex-start; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1.2rem;">
                            <div class="contact-card-icon"><i class="fas fa-clock"></i></div>
                            <div class="contact-card-body">
                                <h4>Jam Operasional</h4>
                            </div>
                        </div>
                        <table class="hours-table">
                            <tr>
                                <td><strong>Senin – Jumat</strong></td>
                                <td>10:00 – 22:00 WIB</td>
                            </tr>
                            <tr>
                                <td><strong>Sabtu – Minggu</strong></td>
                                <td>09:00 – 23:00 WIB</td>
                            </tr>
                            <tr>
                                <td><strong>Hari Libur Nasional</strong></td>
                                <td>Tetap Buka ✓</td>
                            </tr>
                        </table>
                    </div>

                </div>

                <!-- Map Panel -->
                <div class="map-panel">
                    <!-- OpenStreetMap embed koordinat Mekarsa: -7.557739, 110.766043 -->
                    <iframe
                        src="https://www.openstreetmap.org/export/embed.html?bbox=110.7460%2C-7.5777%2C110.7860%2C-7.5377&layer=mapnik&marker=-7.557739%2C110.766043"
                        allowfullscreen
                        loading="lazy"
                        title="Lokasi Mekarsa Coffee Bar di Peta">
                    </iframe>
                    <div class="map-panel-footer">
                        <p><i class="fas fa-map-pin"></i> Jl. Pabelan I, Gatak, Kartasura, Sukoharjo 57169</p>
                        <a href="https://www.google.com/maps?q=-7.557739,110.766043"
                           target="_blank" class="btn btn-outline" style="border-radius: 30px; padding: 0.5rem 1.2rem; font-size: 0.85rem;">
                            <i class="fas fa-external-link-alt"></i> Buka di Google Maps
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Social Media Section -->
    <section class="social-section">
        <div class="container">
            <h2>Temukan Kami di Media Sosial</h2>
            <p class="section-subtitle">Ikuti akun kami untuk mendapatkan update menu, promo, dan konten seru dari Mekarsa.</p>
            <div class="social-grid">
                <a href="https://instagram.com/mekarsaa" target="_blank" class="social-card" style="text-decoration: none;">
                    <i class="fab fa-instagram"></i>
                    <h4>Instagram</h4>
                    <p>@mekarsaa</p>
                </a>
                <a href="https://wa.me/6285933504096" target="_blank" class="social-card" style="text-decoration: none;">
                    <i class="fab fa-whatsapp"></i>
                    <h4>WhatsApp</h4>
                    <p>085933504096</p>
                </a>
                <a href="#" class="social-card" style="text-decoration: none;">
                    <i class="fab fa-tiktok"></i>
                    <h4>TikTok</h4>
                    <p>@mekarsa.coffee</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="index.php" class="nav-logo" style="display: block; margin-bottom: 1rem;">
                        <img src="images/logo.png" alt="Mekarsa Logo" class="navbar-logo-img">
                        Mekarsa<span>.</span>
                    </a>
                    <p>Mekarsa Shoe Clean & Coffee Bar. Coffee First, Clean Vibes Always. Tempat nongkrong modern dengan sajian kopi lokal premium di Kartasura.</p>
                    <div class="social-links">
                        <a href="https://instagram.com/mekarsaa" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                        <a href="https://wa.me/6285933504096" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Menu Cepat</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="menu.php">Menu Coffee</a></li>
                        <li><a href="about.php">Tentang Kami</a></li>
                        <li><a href="articles.php">Artikel</a></li>
                        <li><a href="testimonials.php">Testimoni</a></li>
                        <li><a href="support-service.php">Shoe Clean</a></li>
                        <li><a href="order.php">Form Pemesanan</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Jam Buka</h4>
                    <ul class="footer-links">
                        <li>Senin – Jumat: 10:00 – 22:00</li>
                        <li>Sabtu – Minggu: 09:00 – 23:00</li>
                        <li>*Hari libur tetap buka</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Kontak & Lokasi</h4>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt" style="color: var(--color-orange); margin-right: 8px;"></i> Jl. Pabelan I, Gatak, Pabelan, Kec. Kartasura, Sukoharjo 57169</li>
                        <li><i class="fab fa-whatsapp" style="color: var(--color-orange); margin-right: 8px;"></i> 085933504096</li>
                        <li><i class="fab fa-instagram" style="color: var(--color-orange); margin-right: 8px;"></i> @mekarsaa</li>
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
