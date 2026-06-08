-- ============================================================
-- Mekarsa Coffee Bar - Seed Data
-- ============================================================
-- Jalankan file ini SETELAH schema.sql (mekarsa_db.sql)
-- Password default admin: mekarsa2024
-- ============================================================

-- ============================================================
-- 1. Admin Default
-- ============================================================
-- INSERT INTO `admins` (`name`, `username`, `password`) VALUES
-- ('Admin Mekarsa', 'admin', '$2y$10$Yn/vBCRyIK/Hf0AevQuY2O.gG3.WYK.pQGyjkKeCa8G7uaDX0hf7e');

-- ============================================================
-- 2. Kategori Produk Coffee
-- ============================================================
INSERT INTO `product_categories` (`name`, `description`) VALUES
('Coffee', 'Minuman berbahan dasar espresso dan kopi'),
('Non-Coffee', 'Minuman tanpa kandungan kopi'),
('Signature Drinks', 'Menu khas dan andalan Mekarsa Coffee Bar'),
('Manual Brew', 'Kopi yang diseduh secara manual dengan metode pour-over atau drip'),
('Snack', 'Camilan ringan pendamping kopi'),
('Promo Menu', 'Menu promosi dengan harga spesial');

-- ============================================================
-- 3. Produk Coffee (Initial Menu)
-- ============================================================
INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `is_featured`, `status`) VALUES
(1, 'Americano',
 'Menu coffee dasar dengan cita rasa espresso bold yang murni dan menyegarkan. Cocok untuk kamu yang suka kopi tanpa tambahan susu.',
 15000, 0, 'active'),
(3, 'KopSu Mekarsa',
 'Menu signature khas Mekarsa — perpaduan espresso dengan krimer lembut yang seimbang. Rasa yang konsisten dan jadi favorit pelanggan setia.',
 18000, 1, 'active'),
(3, 'KopSu Gula Aren',
 'Menu kopi susu dengan rasa gula aren asli yang legit dan otentik. Cita rasa lokal yang kaya dan memikat.',
 20000, 1, 'active'),
(3, 'KopSu Vanilla',
 'Menu kopi susu dengan sentuhan sirup vanilla premium yang manis dan harum. Pilihan tepat untuk kamu yang suka rasa manis lembut.',
 20000, 0, 'active'),
(3, 'Butterscotch',
 'Minuman manis dengan karakter rasa butterscotch yang kaya dan mewah. Pilihan sempurna untuk menemani momen santai.',
 20000, 0, 'active');

-- ============================================================
-- 4. Kategori Artikel
-- ============================================================
INSERT INTO `article_categories` (`name`, `description`) VALUES
('Lifestyle', 'Artikel seputar gaya hidup, nongkrong, dan coffee culture'),
('Edukasi', 'Artikel edukasi seputar kopi, metode seduh, dan pengetahuan coffee'),
('Promo', 'Informasi promosi dan penawaran spesial Mekarsa Coffee Bar'),
('Tips & Trick', 'Tips dan trik seputar kopi dan pengalaman di Mekarsa');

-- ============================================================
-- 5. Artikel Awal
-- ============================================================
INSERT INTO `articles` (`category_id`, `title`, `slug`, `content`, `status`) VALUES
(1, 'Rekomendasi Menu Coffee untuk Menemani Tugas Kuliah',
 'rekomendasi-menu-coffee-tugas-kuliah',
 '<p>Mengerjakan tugas kuliah seringkali membutuhkan fokus tinggi dan energi ekstra. Kopi bisa menjadi teman setia yang membantu kamu tetap produktif seharian.</p><p>Di Mekarsa Coffee Bar, kami merekomendasikan <strong>Americano</strong> sebagai pilihan terbaik untuk menemani sesi belajar panjang. Rasa espresso yang bold tanpa tambahan susu memberikan caffeine kick yang tepat tanpa rasa berat di perut.</p><p>Jika kamu lebih suka rasa yang lebih lembut, <strong>KopSu Mekarsa</strong> bisa menjadi pilihan. Dengan harga yang terjangkau dan rasa yang konsisten, menu ini cocok untuk kamu yang sering nongkrong sambil mengerjakan tugas.</p>',
 'published'),
(2, 'Perbedaan Kopi Susu dan Manual Brew',
 'perbedaan-kopi-susu-manual-brew',
 '<p>Masih bingung mau pesan kopi susu atau manual brew? Keduanya sama-sama lezat, tapi memiliki karakter rasa yang sangat berbeda.</p><p><strong>Kopi Susu</strong> menggunakan espresso sebagai base yang dikombinasikan dengan susu atau krimer. Hasilnya adalah minuman yang creamy, manis, dan ringan di tenggorokan. Cocok untuk kamu yang baru mulai menikmati kopi.</p><p><strong>Manual Brew</strong> adalah metode seduh kopi tanpa mesin espresso. Menggunakan teknik pour-over, drip, atau French press, rasa kopi yang dihasilkan lebih bersih, ringan, dan memunculkan karakter asli dari biji kopi. Cocok untuk pecinta kopi yang ingin menikmati kompleksitas rasa.</p>',
 'published'),
(3, 'Promo Spesial Libur Semester: Beli 2 Gratis 1!',
 'promo-libur-semester-beli-2-gratis-1',
 '<p>Menyambut libur semester, Mekarsa Coffee Bar memberikan promo spesial yang sayang untuk dilewatkan! Dapatkan promo <strong>Buy 2 Get 1 Free</strong> untuk semua menu Signature Drinks.</p><p>Promo ini berlaku mulai tanggal 1 Juli hingga 31 Juli 2026. Kunjungi Mekarsa Coffee Bar di Jl. Pabelan I, Kartasura, atau hubungi kami melalui WhatsApp untuk informasi lebih lanjut.</p><p>Jangan lupa ajak teman-temanmu dan nikmati kopi terbaik Mekarsa bersama-sama!</p>',
 'published');

-- ============================================================
-- 6. Testimoni Pelanggan
-- ============================================================
INSERT INTO `testimonials` (`customer_name`, `message`, `rating`, `status`) VALUES
('Rizky Aditya', 'KopSu Mekarsa emang beda! Satu-satunya tempat kopi di Pabelan yang bikin aku betah berjam-jam ngerjain tugas. Rasa kopinya pas, harganya masuk akal, dan tempatnya bersih banget.', 5, 'show'),
('Sinta Dewi', 'Suka banget sama vibe-nya Mekarsa. Setelah capek kerja seharian, mampir sini minum Butterscotch sambil dengerin musik tuh rasanya healing banget. Highly recommended!', 5, 'show'),
('Farhan Nugroho', 'Konsep coffee bar + shoe clean ini unik abis. Aku sekalian bersihin sneakers sambil nongkrong dan minum KopSu Gula Aren. Pelayanannya ramah dan cepat. Bakalan balik lagi!', 5, 'show'),
('Aulia Rahmawati', 'Americano-nya strong tapi ga bikin perut mual. Cocok banget buat yang lagi ngoding atau nulis skripsi. WiFi kenceng, stop kontak banyak, tempat nyaman. 10/10!', 5, 'show'),
('Budi Santoso', 'KopSu Vanilla-nya enak banget! Pertama kali ke sini langsung jadi langganan. Porsinya juga pas dan harganya sangat worth it buat mahasiswa kayak aku.', 5, 'show');

-- ============================================================
-- 7. Pengaturan Website
-- ============================================================
INSERT INTO `settings` (`business_name`, `tagline`, `description`, `address`, `phone`, `whatsapp`, `instagram`, `opening_hours`) VALUES
('Mekarsa Coffee Bar',
 'Coffee First, Clean Vibes Always.',
 'Mekarsa Shoe Clean & Coffee Bar adalah UMKM yang menggabungkan konsep coffee bar modern dengan layanan perawatan sepatu. Kami hadir di Kartasura untuk menyajikan kopi lokal premium dengan suasana yang bersih, nyaman, dan hangat.',
 'Jl. Pabelan I, Gatak, Pabelan, Kec. Kartasura, Kabupaten Sukoharjo, Jawa Tengah 57169',
 '085933504096',
 '6285933504096',
 '@mekarsaa',
 'Senin-Jumat: 10.00-22.00 | Sabtu-Minggu: 09.00-23.00');

-- ============================================================
-- 8. Contoh Pesanan
-- ============================================================
INSERT INTO `orders` (`customer_name`, `customer_phone`, `total_price`, `order_status`, `notes`) VALUES
('Budi Santoso', '08123456789', 38000, 'completed', 'Minta extra gula aren untuk KopSu-nya'),
('Dewi Rahmawati', '08987654321', 20000, 'pending', NULL),
('Andi Wijaya', '08111222333', 35000, 'confirmed', 'Ambil di tempat jam 14.00');

-- ============================================================
-- 9. Order Items (relasi ke pesanan di atas)
-- ============================================================
INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 2, 1, 18000, 18000),
(1, 3, 1, 20000, 20000),
(2, 4, 1, 20000, 20000),
(3, 1, 1, 15000, 15000),
(3, 5, 1, 20000, 20000);
