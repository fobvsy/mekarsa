<?php
/**
 * Ini adalah halaman frontend ONLY untuk Detail Artikel.
 * Data di bawah bersifat DUMMY (mock data) untuk memudahkan pembuatan struktur HTML.
 * Nantinya, ini bisa diganti dengan query database seperti:
 * $article_id = $_GET['id'];
 * $article = fetch_article_by_id($article_id);
 */

$article = [
    'title' => 'Rekomendasi Menu Coffee untuk Menemani Tugas Kuliah',
    'category' => 'Lifestyle',
    'date' => '12 Juni 2026',
    'author' => 'Admin Mekarsa',
    'image' => 'https://images.unsplash.com/photo-1497935586351-b67a49e012bf?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
    'content' => '
        <p>Mengerjakan tugas kuliah seringkali membutuhkan fokus tinggi, apalagi jika <i>deadline</i> sudah di depan mata. Memilih tempat nongkrong yang tepat dengan sajian minuman yang bisa membangkitkan semangat adalah kunci produktivitas.</p>
        
        <h2>1. KopSu Mekarsa (Signature)</h2>
        <p>Jika kamu membutuhkan asupan kafein yang tidak terlalu <i>strong</i> namun tetap memberikan efek melek, <strong>KopSu Mekarsa</strong> adalah pilihan utama. Paduan espresso dengan krimer lembut dan manis yang pas sangat cocok menemani berjam-jam di depan laptop tanpa membuat perut kembung.</p>

        <blockquote>"Kopi yang enak tidak hanya membangkitkan mata, tapi juga menginspirasi ide-ide brilian."</blockquote>

        <h2>2. Americano Bold</h2>
        <p>Bagi kamu pejuang <i>skripsi</i> yang butuh fokus ekstra tanpa tambahan gula, <strong>Americano</strong> kami menggunakan house blend lokal yang segar. Efek kafeinnya langsung terasa dan rasanya yang <i>clean</i> tidak akan membuat enek walau diminum perlahan.</p>

        <h2>Pentingnya Suasana yang Mendukung</h2>
        <p>Selain minuman, suasana Mekarsa yang dirancang khusus dengan gaya <i>Neo-Minimalist</i>, pencahayaan hangat, dan fasilitas stop kontak di setiap meja sangat mendukung produktivitas kamu. Ruangan yang bersih dan musik yang tidak terlalu berisik membantu menjaga konsentrasi.</p>
        
        <img src="https://images.unsplash.com/photo-1498804103079-a6351b050096?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Suasana bekerja di cafe">
        
        <p>Jangan lupa untuk beristirahat setiap 2 jam agar mata tidak lelah. Jika sepatu kamu kebetulan kotor, kamu juga bisa sekalian menitipkannya di layanan <strong>Shoe Clean</strong> kami sembari kamu menyelesaikan tugasmu. Datang kotor, pulang tugas selesai dan sepatu bersih!</p>
    '
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> - Mekarsa Coffee Bar</title>
    <!-- Fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars(substr(strip_tags($article['content']), 0, 150)) ?>...">
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

    <!-- Article Detail Section -->
    <main class="article-detail-container">
        
        <div class="article-header">
            <span class="article-category"><?= htmlspecialchars($article['category']) ?></span>
            <h1><?= htmlspecialchars($article['title']) ?></h1>
            <div class="article-meta-info">
                <span><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($article['date']) ?></span>
                <span><i class="far fa-user"></i> Ditulis oleh <?= htmlspecialchars($article['author']) ?></span>
            </div>
        </div>

        <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="article-hero-img">

        <div class="article-body">
            <!-- Render konten HTML artikel (dalam praktik nyata harus aman dari XSS/disanitasi terlebih dahulu) -->
            <?= $article['content'] ?>
        </div>

        <div class="article-footer">
            <a href="articles.php" class="btn btn-outline" style="border-radius: 30px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Blog
            </a>
            
            <div class="share-links">
                <p>Bagikan:</p>
                <a href="#" title="Share to Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="Share to Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" title="Share to WhatsApp"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>

    </main>

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
