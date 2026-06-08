<?php
/**
 * Halaman frontend ONLY untuk Form Pemesanan Produk.
 * Data produk di bawah bersifat DUMMY (mock data).
 * Nantinya diganti dengan query: SELECT * FROM products WHERE status = 'available'
 */

$products = [
    ['id' => 1, 'name' => 'KopSu Mekarsa',   'price' => 18000],
    ['id' => 2, 'name' => 'Americano',         'price' => 15000],
    ['id' => 3, 'name' => 'KopSu Gula Aren',  'price' => 20000],
    ['id' => 4, 'name' => 'KopSu Vanilla',    'price' => 20000],
    ['id' => 5, 'name' => 'Butterscotch',      'price' => 20000],
    ['id' => 6, 'name' => 'Matcha Latte',      'price' => 22000],
    ['id' => 7, 'name' => 'V60 Manual Brew',   'price' => 22000],
    ['id' => 8, 'name' => 'French Fries',      'price' => 15000],
];

// Encode data produk untuk digunakan di JavaScript
$products_json = json_encode($products);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pemesanan - Mekarsa Coffee Bar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <meta name="description" content="Pesan menu kopi favoritmu di Mekarsa Coffee Bar secara online. Isi form pemesanan dan kami akan menghubungimu melalui WhatsApp.">
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
                <li><a href="order.php" class="active">Pesan</a></li>
            </ul>
            <div class="nav-actions">
                <a href="https://wa.me/6285933504096" target="_blank" class="btn btn-primary">
                    <i class="fab fa-whatsapp"></i> Pesan Sekarang
                </a>
            </div>
        </div>
    </header>

    <!-- Page Header -->
    <section style="padding: 5rem 0 2rem; text-align: center; background: var(--color-bg-secondary); border-bottom: 1px solid var(--color-border);">
        <div class="container">
            <h1 style="font-size: 3rem; margin-bottom: 0.8rem;">Form Pemesanan</h1>
            <p style="color: var(--color-text-muted); font-size: 1.1rem; max-width: 560px; margin: 0 auto;">
                Isi form di bawah dan pesananmu akan diteruskan ke WhatsApp admin Mekarsa secara otomatis.
            </p>
        </div>
    </section>

    <!-- Order Section -->
    <section class="order-section">
        <div class="container">
            <div class="order-layout">

                <!-- Form Card -->
                <div class="order-form-card">
                    <!-- Form itself -->
                    <form id="orderForm" novalidate>

                        <div class="form-row">
                            <!-- Nama -->
                            <div class="form-group">
                                <label for="nama">Nama Lengkap <span class="required">*</span></label>
                                <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: Rizky Aditya">
                                <p class="form-error" id="namaError">Nama wajib diisi.</p>
                            </div>

                            <!-- Nomor WhatsApp -->
                            <div class="form-group">
                                <label for="whatsapp">Nomor WhatsApp <span class="required">*</span></label>
                                <input type="tel" id="whatsapp" name="whatsapp" class="form-control" placeholder="Contoh: 08123456789">
                                <p class="form-error" id="whatsappError">Nomor WhatsApp wajib diisi.</p>
                            </div>
                        </div>

                        <!-- Pilih Produk -->
                        <div class="form-group">
                            <label for="produk">Pilih Produk <span class="required">*</span></label>
                            <select id="produk" name="produk" class="form-control">
                                <option value="">-- Pilih Menu --</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>">
                                        <?= htmlspecialchars($p['name']) ?> — Rp<?= number_format($p['price'], 0, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-error" id="produkError">Produk wajib dipilih.</p>
                        </div>

                        <div class="form-row">
                            <!-- Jumlah -->
                            <div class="form-group">
                                <label for="jumlah">Jumlah <span class="required">*</span></label>
                                <input type="number" id="jumlah" name="jumlah" class="form-control" placeholder="1" min="1" max="20" value="1">
                                <p class="form-error" id="jumlahError">Jumlah minimal 1.</p>
                            </div>

                            <!-- Metode Pengambilan -->
                            <div class="form-group">
                                <label for="metode">Metode Pengambilan <span class="required">*</span></label>
                                <select id="metode" name="metode" class="form-control">
                                    <option value="">-- Pilih Metode --</option>
                                    <option value="Dine-in">Dine-in (Makan di Tempat)</option>
                                    <option value="Take-away">Take-away (Dibawa Pulang)</option>
                                </select>
                                <p class="form-error" id="metodeError">Metode pengambilan wajib dipilih.</p>
                            </div>
                        </div>

                        <!-- Catatan Tambahan -->
                        <div class="form-group">
                            <label for="catatan">Catatan Tambahan <span style="color: var(--color-text-muted); font-weight: 400;">(Opsional)</span></label>
                            <textarea id="catatan" name="catatan" class="form-control" rows="3" placeholder="Contoh: less sugar, extra ice, atau permintaan khusus lainnya…"></textarea>
                        </div>

                        <button type="submit" id="submitBtn" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1rem; font-size: 1rem; border-radius: 6px;">
                            <i class="fas fa-paper-plane"></i> Kirim Pesanan
                        </button>

                    </form>

                    <!-- Success State -->
                    <div class="order-success" id="orderSuccess">
                        <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                        <h3>Pesanan Terkirim!</h3>
                        <p>Terima kasih telah memesan di Mekarsa. Klik tombol di bawah untuk melanjutkan konfirmasi via WhatsApp admin kami.</p>
                        <a href="#" id="waConfirmBtn" target="_blank" class="btn btn-primary" style="font-size: 1rem; padding: 1rem 2rem; margin-bottom: 1rem;">
                            <i class="fab fa-whatsapp"></i> Konfirmasi via WhatsApp
                        </a>
                        <br>
                        <a href="order.php" class="btn btn-outline" style="border-radius: 30px;">Pesan Lagi</a>
                    </div>
                </div>

                <!-- Order Summary Sidebar -->
                <div class="order-summary-card">
                    <h3>Ringkasan Pesanan</h3>

                    <div class="summary-item">
                        <span>Produk</span>
                        <strong id="summaryProduct">—</strong>
                    </div>
                    <div class="summary-item">
                        <span>Harga Satuan</span>
                        <strong id="summaryPrice">—</strong>
                    </div>
                    <div class="summary-item">
                        <span>Jumlah</span>
                        <strong id="summaryQty">—</strong>
                    </div>
                    <div class="summary-item">
                        <span>Metode</span>
                        <strong id="summaryMetode">—</strong>
                    </div>

                    <hr class="summary-divider">

                    <div class="summary-total">
                        <span>Total</span>
                        <span class="price" id="summaryTotal">Rp0</span>
                    </div>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        Konfirmasi pesanan akan dilakukan melalui <strong>WhatsApp</strong>. Pastikan nomor yang kamu masukkan aktif.
                    </div>

                    <div class="info-box" style="margin-top: 1rem;">
                        <i class="fas fa-clock"></i>
                        Jam operasional: <strong>Senin–Jumat 10:00–22:00</strong> & <strong>Sabtu–Minggu 09:00–23:00</strong>
                    </div>
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
                        <img src="images/logo.png" alt="Mekarsa Logo" class="navbar-logo-img">
                        Mekarsa<span>.</span>
                    </a>
                    <p>Mekarsa Shoe Clean & Coffee Bar. Coffee First, Clean Vibes Always.</p>
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
                        <li><a href="order.php">Form Pemesanan</a></li>
                        <li><a href="testimonials.php">Testimoni</a></li>
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

    <script>
    (function () {
        const products = <?= $products_json ?>;
        const productMap = {};
        products.forEach(p => { productMap[p.id] = p; });

        const formEl      = document.getElementById('orderForm');
        const successEl   = document.getElementById('orderSuccess');
        const waBtn       = document.getElementById('waConfirmBtn');

        // Summary elements
        const sumProduct  = document.getElementById('summaryProduct');
        const sumPrice    = document.getElementById('summaryPrice');
        const sumQty      = document.getElementById('summaryQty');
        const sumMetode   = document.getElementById('summaryMetode');
        const sumTotal    = document.getElementById('summaryTotal');

        // Inputs
        const produkEl  = document.getElementById('produk');
        const jumlahEl  = document.getElementById('jumlah');
        const metodeEl  = document.getElementById('metode');
        const namaEl    = document.getElementById('nama');
        const waEl      = document.getElementById('whatsapp');

        function formatRp(num) {
            return 'Rp' + num.toLocaleString('id-ID');
        }

        function updateSummary() {
            const prodId  = parseInt(produkEl.value);
            const qty     = parseInt(jumlahEl.value) || 0;
            const metode  = metodeEl.options[metodeEl.selectedIndex]?.text || '—';

            if (prodId && productMap[prodId]) {
                const p     = productMap[prodId];
                const total = p.price * qty;
                sumProduct.textContent = p.name;
                sumPrice.textContent   = formatRp(p.price);
                sumQty.textContent     = qty;
                sumMetode.textContent  = metode !== '-- Pilih Metode --' ? metode : '—';
                sumTotal.textContent   = formatRp(total);
            } else {
                sumProduct.textContent = '—';
                sumPrice.textContent   = '—';
                sumQty.textContent     = '—';
                sumMetode.textContent  = '—';
                sumTotal.textContent   = 'Rp0';
            }
        }

        produkEl.addEventListener('change', updateSummary);
        jumlahEl.addEventListener('input', updateSummary);
        metodeEl.addEventListener('change', updateSummary);

        // Validation
        function showError(id, msg) {
            const el = document.getElementById(id);
            el.textContent = msg;
            el.classList.add('visible');
        }

        function clearErrors() {
            document.querySelectorAll('.form-error').forEach(e => e.classList.remove('visible'));
        }

        function validate() {
            let valid = true;
            clearErrors();

            if (!namaEl.value.trim()) {
                showError('namaError', 'Nama wajib diisi.');
                valid = false;
            }
            if (!waEl.value.trim() || !/^[0-9+]{8,15}$/.test(waEl.value.trim())) {
                showError('whatsappError', 'Nomor WhatsApp wajib diisi dan harus valid.');
                valid = false;
            }
            if (!produkEl.value) {
                showError('produkError', 'Produk wajib dipilih.');
                valid = false;
            }
            if (!jumlahEl.value || parseInt(jumlahEl.value) < 1) {
                showError('jumlahError', 'Jumlah minimal 1.');
                valid = false;
            }
            if (!metodeEl.value) {
                showError('metodeError', 'Metode pengambilan wajib dipilih.');
                valid = false;
            }

            return valid;
        }

        formEl.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validate()) return;

            const nama    = namaEl.value.trim();
            const wa      = waEl.value.trim();
            const produk  = productMap[parseInt(produkEl.value)];
            const jumlah  = parseInt(jumlahEl.value);
            const metode  = metodeEl.options[metodeEl.selectedIndex].text;
            const catatan = document.getElementById('catatan').value.trim();
            const total   = produk.price * jumlah;

            // Build WhatsApp message
            let msg  = `Halo Mekarsa! Saya ingin memesan:\n\n`;
                msg += `👤 Nama: ${nama}\n`;
                msg += `📱 No. WA: ${wa}\n`;
                msg += `☕ Produk: ${produk.name}\n`;
                msg += `🔢 Jumlah: ${jumlah} pcs\n`;
                msg += `💰 Total: ${formatRp(total)}\n`;
                msg += `🛍️ Metode: ${metode}\n`;
            if (catatan) msg += `📝 Catatan: ${catatan}\n`;
            msg += `\nMohon konfirmasi pesanan saya. Terima kasih!`;

            const waUrl = 'https://wa.me/6285933504096?text=' + encodeURIComponent(msg);
            waBtn.href  = waUrl;

            // Show success state
            formEl.style.display = 'none';
            successEl.classList.add('visible');
        });
    })();
    </script>

</body>
</html>
