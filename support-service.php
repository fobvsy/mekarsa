<?php
require_once __DIR__ . '/src/config/database.php';

try {
    $pdo = getDBConnection();
    
    // Ambil daftar layanan
    $stmt = $pdo->query("SELECT * FROM support_services WHERE status = 'active' ORDER BY id ASC");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil setting untuk kontak & WA
    $setStmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $setStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'business_name' => 'Mekarsa Coffee Bar',
        'description' => 'Mekarsa Shoe Clean & Coffee Bar. Coffee First, Clean Vibes Always.',
        'whatsapp' => '6285933504096',
        'address' => 'Jl. Pabelan I, Kartasura',
        'opening_hours' => 'Setiap Hari'
    ];
    $wa_clean = preg_replace('/[^0-9]/', '', $settings['whatsapp']);

} catch (PDOException $e) {
    die("Database error.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Shoe Clean - Mekarsa Coffee Bar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <meta name="description" content="Layanan perawatan dan pembersihan sepatu profesional di Mekarsa Shoe Clean & Coffee Bar, Kartasura. Regular clean, deep clean, unyellowing, repaint, dan restorasi.">
    <style>
        .page-header {
            padding: 6rem 0 3rem;
            text-align: center;
            background: var(--color-bg-secondary);
            border-bottom: 1px solid var(--color-border);
        }

        .page-badge {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.3rem 1.2rem;
            background: rgba(242,113,33,0.12);
            border: 1px solid rgba(242,113,33,0.25);
            border-radius: 20px;
            color: var(--color-orange);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .page-title { font-size: 3rem; margin-bottom: 0.8rem; }
        .page-subtitle {
            color: var(--color-text-muted);
            font-size: 1.1rem;
            max-width: 580px;
            margin: 0 auto;
        }

        /* Intro */
        .intro-section {
            padding: 5rem 0;
            display: flex;
            align-items: center;
            gap: 4rem;
        }

        .intro-img {
            flex: 1;
        }

        .intro-img img {
            width: 100%;
            height: 420px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid var(--color-border);
            filter: brightness(0.9);
        }

        .intro-content { flex: 1; }

        .intro-content h2 {
            text-align: left;
            margin-bottom: 1.2rem;
        }

        .intro-content p {
            color: var(--color-text-muted);
            font-size: 1.05rem;
            margin-bottom: 1.2rem;
            line-height: 1.8;
        }

        /* Services Grid */
        .services-catalog {
            padding: 5rem 0 6rem;
            background: var(--color-bg-secondary);
            border-top: 1px solid var(--color-border);
            border-bottom: 1px solid var(--color-border);
        }

        .service-catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.8rem;
        }

        .service-catalog-card {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 10px;
            padding: 2rem;
            transition: transform 0.3s ease, border-color 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .service-catalog-card:hover {
            transform: translateY(-6px);
            border-color: var(--color-orange);
        }

        .service-icon-wrap {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            background: rgba(242,113,33,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: var(--color-orange);
            margin-bottom: 1.2rem;
        }

        .service-card-name {
            font-size: 1.2rem;
            margin-bottom: 0.6rem;
            color: var(--color-text-main);
        }

        .service-card-desc {
            font-size: 0.95rem;
            color: var(--color-text-muted);
            line-height: 1.7;
            flex: 1;
            margin-bottom: 1.5rem;
        }

        .service-card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.2rem;
            border-top: 1px solid var(--color-border);
            font-size: 0.9rem;
        }

        .service-card-price {
            font-family: var(--font-price);
            color: var(--color-orange);
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }

        .service-card-duration {
            color: var(--color-text-muted);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .service-card-duration i {
            color: var(--color-orange);
            font-size: 0.8rem;
        }

        /* FAQ */
        .faq-section {
            padding: 5rem 0;
        }

        .faq-list {
            max-width: 780px;
            margin: 3rem auto 0;
        }

        .faq-item {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .faq-question {
            width: 100%;
            background: transparent;
            border: none;
            padding: 1.3rem 1.5rem;
            text-align: left;
            color: var(--color-text-main);
            font-family: var(--font-body);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: color 0.3s;
        }

        .faq-question:hover { color: var(--color-orange); }
        .faq-question i { color: var(--color-orange); transition: transform 0.3s; }
        .faq-question.open i { transform: rotate(45deg); }

        .faq-answer {
            display: none;
            padding: 0 1.5rem 1.3rem;
            color: var(--color-text-muted);
            line-height: 1.7;
            font-size: 0.97rem;
        }

        .faq-answer.open { display: block; }

        @media (max-width: 768px) {
            .intro-section { flex-direction: column; }
            .intro-content h2 { text-align: center; }
        }
    </style>
</head>
<body>

    <!-- Header & Navbar -->
    <header class="header">
        <div class="container navbar">
            <a href="index.php" class="nav-logo">
                <img src="public/images/logo.png" alt="Mekarsa Logo" class="navbar-logo-img">
                Mekarsa<span>.</span>
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
                <a href="https://wa.me/<?= $wa_clean ?>" target="_blank" class="btn btn-primary">
                    <i class="fab fa-whatsapp"></i> Konsultasi Sekarang
                </a>
            </div>
        </div>
    </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <span class="page-badge"><i class="fas fa-shoe-prints"></i> Layanan Pendukung</span>
            <h1 class="page-title">Shoe Clean Service</h1>
            <p class="page-subtitle">Percayakan perawatan sepatu kesayanganmu kepada kami. Serahkan sepatu, nikmati kopi — kami urus sisanya.</p>
        </div>
    </section>

    <!-- Intro Section -->
    <section class="intro-section container">
        <div class="intro-img">
            <img src="https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Layanan Shoe Clean Mekarsa">
        </div>
        <div class="intro-content">
            <h2>Sepatu Bersih,<br>Kopi Nikmat.</h2>
            <p>Mekarsa Shoe Clean hadir sebagai identitas unik yang melengkapi pengalaman nongkrong di Mekarsa Coffee Bar. Sambil kamu bersantai menikmati secangkir kopi, tim kami merawat sepatu kesayanganmu dengan penuh perhatian.</p>
            <p>Kami menggunakan produk-produk perawatan berkualitas dan teknik yang tepat untuk setiap jenis material sepatu, mulai dari kanvas, kulit, suede, hingga mesh. Hasilnya? Sepatu kembali bersih, terawat, dan siap dipakai.</p>
            <a href="https://wa.me/<?= $wa_clean ?>?text=Halo%20<?= explode(' ', $settings['business_name'])[0] ?>,%20saya%20ingin%20konsultasi%20layanan%20Shoe%20Clean" target="_blank" class="btn btn-primary" style="margin-top: 0.5rem;">
                <i class="fab fa-whatsapp"></i> Konsultasi via WhatsApp
            </a>
        </div>
    </section>

    <!-- Services Catalog -->
    <section class="services-catalog">
        <div class="container">
            <h2>Daftar Layanan</h2>
            <p class="section-subtitle">Pilih layanan yang sesuai dengan kebutuhan sepatu kamu.</p>

            <div class="service-catalog-grid">
                <?php foreach ($services as $svc): ?>
                    <div class="service-catalog-card">
                        <?php if(!empty($svc['image'])): ?>
                            <div class="service-icon-wrap" style="width:100%; height:180px; background:none; border-radius:8px; overflow:hidden; margin-bottom:1.2rem;">
                                <img src="public/uploads/services/<?= htmlspecialchars($svc['image']) ?>" alt="<?= htmlspecialchars($svc['title']) ?>" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                        <?php else: ?>
                            <div class="service-icon-wrap">
                                <i class="fas fa-shoe-prints"></i>
                            </div>
                        <?php endif; ?>
                        <h3 class="service-card-name"><?= htmlspecialchars($svc['title']) ?></h3>
                        <p class="service-card-desc"><?= nl2br(htmlspecialchars($svc['description'])) ?></p>
                        <div class="service-card-meta">
                            <span class="service-card-price">Mulai Rp<?= number_format($svc['price'], 0, ',', '.') ?></span>
                            <span class="service-card-duration">
                                <i class="far fa-star"></i> Garansi Kepuasan
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p style="text-align: center; margin-top: 2.5rem; color: var(--color-text-muted); font-size: 0.9rem;">
                <i class="fas fa-info-circle" style="color: var(--color-orange);"></i>
                Estimasi harga dapat berbeda tergantung kondisi, ukuran, dan material sepatu. Hubungi kami untuk konsultasi gratis.
            </p>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <h2>Pertanyaan Umum</h2>
            <p class="section-subtitle">Belum yakin? Baca dulu jawaban atas pertanyaan yang sering ditanyakan.</p>

            <div class="faq-list">
                <div class="faq-item">
                    <button class="faq-question" id="faq1-btn" aria-expanded="false" aria-controls="faq1-answer">
                        Berapa lama waktu pengerjaan? <i class="fas fa-plus"></i>
                    </button>
                    <div class="faq-answer" id="faq1-answer">
                        Tergantung layanan yang dipilih. Regular Clean selesai dalam 1–2 jam, sementara layanan seperti Repaint atau Restorasi bisa memakan waktu 1–3 hari. Estimasi waktu akan dikonfirmasi saat konsultasi.
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" id="faq2-btn" aria-expanded="false" aria-controls="faq2-answer">
                        Jenis sepatu apa saja yang bisa ditangani? <i class="fas fa-plus"></i>
                    </button>
                    <div class="faq-answer" id="faq2-answer">
                        Kami menangani berbagai jenis sepatu: sneakers, boots, formal, kasual, berbahan kanvas, kulit, suede, mesh, dan bahan sintetis. Hubungi kami terlebih dahulu untuk sepatu berbahan khusus.
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" id="faq3-btn" aria-expanded="false" aria-controls="faq3-answer">
                        Apakah bisa diantar dan dijemput? <i class="fas fa-plus"></i>
                    </button>
                    <div class="faq-answer" id="faq3-answer">
                        Saat ini layanan antar-jemput masih dalam tahap pengembangan. Kamu bisa menitipkan sepatu langsung di lokasi kami sambil menikmati menu kopi yang tersedia.
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" id="faq4-btn" aria-expanded="false" aria-controls="faq4-answer">
                        Bagaimana cara pemesanan layanan shoe clean? <i class="fas fa-plus"></i>
                    </button>
                    <div class="faq-answer" id="faq4-answer">
                        Cukup hubungi admin <?= htmlspecialchars($settings['business_name']) ?> melalui WhatsApp di nomor <?= htmlspecialchars($settings['whatsapp'] ?? '') ?> atau datang langsung ke lokasi kami. Tim kami akan melakukan konsultasi gratis sebelum pengerjaan dimulai.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container">
            <h2>Siap Menitipkan Sepatumu?</h2>
            <p>Hubungi kami sekarang untuk konsultasi gratis. Datang ke Mekarsa, sambil ngopi, sepatu beres!</p>
            <a href="https://wa.me/<?= $wa_clean ?>?text=Halo%20<?= explode(' ', $settings['business_name'])[0] ?>,%20saya%20ingin%20konsultasi%20layanan%20Shoe%20Clean" target="_blank" class="btn btn-primary">
                <i class="fab fa-whatsapp"></i> Hubungi Admin
            </a>
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
    <a href="https://wa.me/<?= $wa_clean ?>" target="_blank" class="float-wa" title="Konsultasi via WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- FAQ Accordion Script -->
    <script>
        document.querySelectorAll('.faq-question').forEach(btn => {
            btn.addEventListener('click', function () {
                const answer = this.nextElementSibling;
                const isOpen = answer.classList.contains('open');

                // Close all
                document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));
                document.querySelectorAll('.faq-question').forEach(b => b.classList.remove('open'));

                // Toggle current
                if (!isOpen) {
                    answer.classList.add('open');
                    this.classList.add('open');
                    this.setAttribute('aria-expanded', 'true');
                } else {
                    this.setAttribute('aria-expanded', 'false');
                }
            });
        });
    </script>

    <script src="public/js/main.js"></script>
</body>
</html>
