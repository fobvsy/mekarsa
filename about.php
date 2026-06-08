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
} catch (PDOException $e) {
    die("Error connecting to database.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - <?= htmlspecialchars($settings['business_name']) ?></title>
    <!-- Fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="public/css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="description" content="Tentang <?= htmlspecialchars(strip_tags($settings['business_name'])) ?>. <?= htmlspecialchars(strip_tags($settings['description'])) ?>">
    <style>
        .page-header {
            padding: 8rem 0 4rem;
            text-align: center;
            background-color: var(--color-bg-secondary);
            border-bottom: 1px solid var(--color-border);
        }
        
        .page-title {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: var(--color-orange);
        }
        
        .page-subtitle {
            font-size: 1.2rem;
            color: var(--color-text-muted);
            max-width: 600px;
            margin: 0 auto;
        }

        .about-section {
            padding: 5rem 0;
            display: flex;
            align-items: center;
            gap: 4rem;
        }

        .about-content {
            flex: 1;
        }

        .about-content h2 {
            text-align: left;
            margin-bottom: 1.5rem;
        }

        .about-content p {
            font-size: 1.1rem;
            color: var(--color-text-muted);
            margin-bottom: 1.5rem;
        }

        .about-image {
            flex: 1;
        }

        .about-image img {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--color-border);
            filter: brightness(0.9);
        }

        .values-section {
            padding: 5rem 0;
            background-color: var(--color-bg-secondary);
            border-top: 1px solid var(--color-border);
            border-bottom: 1px solid var(--color-border);
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .value-card {
            background-color: var(--color-bg-card);
            padding: 2rem;
            border-radius: 8px;
            border: 1px solid var(--color-border);
            text-align: center;
            transition: transform 0.3s ease, border-color 0.3s;
        }

        .value-card:hover {
            transform: translateY(-5px);
            border-color: var(--color-orange);
        }

        .value-icon {
            font-size: 2.5rem;
            color: var(--color-orange);
            margin-bottom: 1.5rem;
        }

        .value-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--color-text-main);
        }

        .value-desc {
            font-size: 0.95rem;
            color: var(--color-text-muted);
        }

        @media (max-width: 768px) {
            .about-section {
                flex-direction: column;
                text-align: center;
            }
            .about-content h2 {
                text-align: center;
            }
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
            <h1 class="page-title">Profil Mekarsa</h1>
            <p class="page-subtitle">Mengenal lebih dekat konsep dan nilai di balik Mekarsa Shoe Clean & Coffee Bar.</p>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section container">
        <div class="about-image">
            <img src="public/images/image2.jpeg" alt="Suasana Mekarsa Coffee Bar">
        </div>
        <div class="about-content">
            <h2>Konsep Kami</h2>
            <p><strong>Mekarsa Shoe Clean & Coffee Bar</strong> adalah sebuah UMKM lokal yang inovatif. Berangkat dari kebutuhan anak muda akan tempat nongkrong yang asik, kami menggabungkan dua layanan sekaligus: Coffee Bar modern dan jasa perawatan sepatu (Shoe Clean).</p>
            <p>Meskipun memiliki layanan shoe clean, fokus utama kami adalah menghadirkan produk coffee dan non-coffee terbaik dengan kualitas premium. Mekarsa diposisikan sebagai tempat yang nyaman untuk bersantai, menyelesaikan tugas, atau sekadar berkumpul bersama teman dan komunitas di kawasan Pabelan, Kartasura.</p>
            <p>Dengan karakter brand yang <em>Modern, Bersih, Hangat, Minimalis, dan dekat dengan Anak Muda</em>, kami memastikan setiap cangkir kopi yang disajikan memberikan impresi dan memori yang menyenangkan.</p>
        </div>
    </section>

    <!-- Brand Values Section -->
    <section class="values-section">
        <div class="container">
            <h2>Nilai Brand Kami</h2>
            <p class="section-subtitle">Pilar utama yang membangun pengalaman tak terlupakan di Mekarsa.</p>
            
            <div class="values-grid">
                <div class="value-card">
                    <i class="fas fa-leaf value-icon"></i>
                    <h3 class="value-title">Clean</h3>
                    <p class="value-desc">Tampilan brand yang selalu bersih, rapi, dan memberikan kenyamanan maksimal bagi setiap pengunjung.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-bolt value-icon"></i>
                    <h3 class="value-title">Modern</h3>
                    <p class="value-desc">Visual dan pelayanan minimalis, tegas, dan selalu sejalan dengan tren serta gaya hidup anak muda.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-mug-hot value-icon"></i>
                    <h3 class="value-title">Warm</h3>
                    <p class="value-desc">Menghadirkan suasana yang ramah, nyaman, dan santai selayaknya rumah kedua untuk bersosialisasi.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-coffee value-icon"></i>
                    <h3 class="value-title">Coffee Lifestyle</h3>
                    <p class="value-desc">Menjadikan kopi berkualitas sebagai bagian tak terpisahkan dari gaya hidup dan produktivitas Anda.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-handshake value-icon"></i>
                    <h3 class="value-title">Trustworthy</h3>
                    <p class="value-desc">Informasi yang transparan terkait kualitas menu, harga bersahabat, dan pelayanan yang dapat diandalkan.</p>
                </div>
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
                &copy; 2026 Mekarsa Coffee Bar. All Rights Reserved.
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
