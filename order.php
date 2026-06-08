<?php
session_start();
require_once __DIR__ . '/src/config/database.php';

$pdo = getDBConnection();
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

$cart = $_SESSION['cart'] ?? [];
$cart_count = 0;
$total_price = 0;
foreach ($cart as $item) {
    $cart_count += $item['quantity'];
    $total_price += $item['price'] * $item['quantity'];
}

$success = false;
$wa_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $nama = trim($_POST['nama'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $metode = trim($_POST['metode'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    if (!empty($nama) && !empty($whatsapp) && !empty($metode) && !empty($cart)) {
        try {
            $pdo->beginTransaction();
            
            $final_notes = "[$metode]";
            if ($catatan) {
                $final_notes .= " " . $catatan;
            }

            $stmt = $pdo->prepare("INSERT INTO orders (customer_name, customer_phone, total_price, order_status, notes) VALUES (?, ?, ?, 'pending', ?)");
            $stmt->execute([$nama, $whatsapp, $total_price, $final_notes]);
            $order_id = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?)");
            
            $order_summary_text = "Halo Mekarsa! Saya ingin memesan:\n\n";
            $order_summary_text .= "👤 Nama: $nama\n📱 No. WA: $whatsapp\n🛍️ Metode: $metode\n\n*Pesanan:*\n";

            foreach ($cart as $id => $item) {
                $sub = $item['price'] * $item['quantity'];
                $itemStmt->execute([$order_id, $id, $item['price'], $item['quantity'], $sub]);
                
                $order_summary_text .= "- " . $item['name'] . " (" . $item['quantity'] . "x) = Rp" . number_format($sub, 0, ',', '.') . "\n";
            }
            
            $order_summary_text .= "\n*Total: Rp" . number_format($total_price, 0, ',', '.') . "*\n";
            if ($catatan) $order_summary_text .= "📝 Catatan: $catatan\n";
            $order_summary_text .= "\nMohon konfirmasi pesanan saya. Terima kasih!";

            $pdo->commit();

            unset($_SESSION['cart']);
            $cart = [];
            $cart_count = 0;
            $success = true;
            $wa_link = "https://wa.me/" . $wa_clean . "?text=" . urlencode($order_summary_text);

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    } else {
        $error = "Mohon lengkapi form pemesanan dengan benar.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang & Checkout - <?= htmlspecialchars($settings['business_name']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        .cart-table th { text-align: left; padding: 1rem; border-bottom: 1px solid var(--color-border); color: var(--color-text-muted); }
        .cart-table td { padding: 1rem; border-bottom: 1px solid var(--color-border); vertical-align: middle; }
        .cart-qty-form { display: flex; align-items: center; gap: 0.5rem; }
        .qty-input { width: 50px; padding: 0.3rem; text-align: center; background: var(--color-bg-input); border: 1px solid var(--color-border); color: var(--color-white); border-radius: 4px; }
        .btn-update { background: var(--color-bg-secondary); color: var(--color-text-muted); border: 1px solid var(--color-border); padding: 0.4rem 0.6rem; border-radius: 4px; cursor: pointer; }
        .btn-update:hover { color: var(--color-white); border-color: var(--color-white); }
        .btn-remove { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); padding: 0.4rem 0.6rem; border-radius: 4px; cursor: pointer; }
        .btn-remove:hover { background: #ef4444; color: #fff; }
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
        </div>
    </header>

    <section style="padding: 6rem 0 2rem; text-align: center; background: var(--color-bg-main); border-bottom: 1px solid var(--color-border);">
        <div class="container">
            <h1 style="font-size: 2.5rem; margin-bottom: 0.8rem;">Keranjang Belanja</h1>
            <p style="color: var(--color-text-muted); font-size: 1rem; max-width: 560px; margin: 0 auto;">
                Selesaikan pesananmu di bawah ini.
            </p>
        </div>
    </section>

    <section class="order-section">
        <div class="container">
            <?php if (isset($error)): ?>
                <div style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="order-success visible" style="display:block; text-align:center; padding: 3rem 1rem;">
                    <div class="success-icon" style="font-size:4rem; color:var(--color-green); margin-bottom:1rem;"><i class="fas fa-check-circle"></i></div>
                    <h3>Pesanan Berhasil Disimpan!</h3>
                    <p style="margin-bottom:2rem; color:var(--color-text-muted);">Pesananmu telah masuk ke sistem kami. Klik tombol di bawah ini untuk mengonfirmasinya via WhatsApp admin.</p>
                    <a href="<?= $wa_link ?>" target="_blank" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem; margin-bottom: 1rem;">
                        <i class="fab fa-whatsapp"></i> Konfirmasi Pesanan via WhatsApp
                    </a>
                    <br><br>
                    <a href="menu.php" class="btn btn-outline" style="border-radius: 30px;">Kembali ke Menu</a>
                </div>
            <?php else: ?>

                <?php if (empty($cart)): ?>
                    <div style="text-align: center; padding: 5rem 0;">
                        <i class="fas fa-shopping-basket" style="font-size: 4rem; color: var(--color-border); margin-bottom: 1rem;"></i>
                        <h3>Keranjangmu masih kosong</h3>
                        <p style="color: var(--color-text-muted); margin-bottom: 2rem;">Yuk, pilih menu kopi favoritmu dulu!</p>
                        <a href="menu.php" class="btn btn-primary">Lihat Menu</a>
                    </div>
                <?php else: ?>
                    <div class="order-layout">
                        <!-- Form Card -->
                        <div class="order-form-card" style="padding: 2rem;">
                            <h3 style="margin-bottom:1.5rem; font-family:var(--font-head);">Daftar Pesanan</h3>
                            
                            <div style="overflow-x:auto;">
                                <table class="cart-table">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Harga</th>
                                            <th>Qty</th>
                                            <th>Subtotal</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart as $id => $item): ?>
                                            <tr>
                                                <td style="font-weight:600;"><?= htmlspecialchars($item['name']) ?></td>
                                                <td>Rp<?= number_format($item['price'], 0, ',', '.') ?></td>
                                                <td>
                                                    <form action="cart_action.php" method="POST" class="cart-qty-form">
                                                        <input type="hidden" name="action" value="update">
                                                        <input type="hidden" name="product_id" value="<?= $id ?>">
                                                        <input type="number" name="quantity" class="qty-input" value="<?= $item['quantity'] ?>" min="1" max="50">
                                                        <button type="submit" class="btn-update" title="Update"><i class="fas fa-rotate"></i></button>
                                                    </form>
                                                </td>
                                                <td style="font-weight:700; color:var(--color-orange);">Rp<?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></td>
                                                <td>
                                                    <form action="cart_action.php" method="POST" style="margin:0;">
                                                        <input type="hidden" name="action" value="remove">
                                                        <input type="hidden" name="product_id" value="<?= $id ?>">
                                                        <button type="submit" class="btn-remove" title="Hapus"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <form action="cart_action.php" method="POST" style="text-align:right;">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="btn btn-outline" onclick="return confirm('Kosongkan semua keranjang?');" style="font-size:0.85rem; padding:0.5rem 1rem; border-color:#ef4444; color:#ef4444;"><i class="fas fa-ban"></i> Kosongkan Keranjang</button>
                            </form>
                            
                            <hr style="border:0; border-bottom:1px solid var(--color-border); margin: 2rem 0;">

                            <h3 style="margin-bottom:1.5rem; font-family:var(--font-head);">Data Pengiriman</h3>
                            <form id="checkoutForm" method="POST" action="order.php">
                                <input type="hidden" name="checkout" value="1">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="nama">Nama Lengkap <span class="required">*</span></label>
                                        <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: Rizky Aditya" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="whatsapp">Nomor WhatsApp <span class="required">*</span></label>
                                        <input type="tel" id="whatsapp" name="whatsapp" class="form-control" placeholder="Contoh: 08123456789" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="metode">Metode Pengambilan <span class="required">*</span></label>
                                    <select id="metode" name="metode" class="form-control" required>
                                        <option value="">-- Pilih Metode --</option>
                                        <option value="Dine-in">Dine-in (Makan di Tempat)</option>
                                        <option value="Take-away">Take-away (Dibawa Pulang)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="catatan">Catatan Tambahan <span style="color: var(--color-text-muted); font-weight: 400;">(Opsional)</span></label>
                                    <textarea id="catatan" name="catatan" class="form-control" rows="3" placeholder="Contoh: less sugar, extra ice, dll…"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1rem; font-size: 1rem; border-radius: 6px;">
                                    <i class="fas fa-lock"></i> Selesaikan Pesanan
                                </button>
                            </form>

                        </div>

                        <!-- Order Summary Sidebar -->
                        <div class="order-summary-card">
                            <h3>Ringkasan Transaksi</h3>
                            <div class="summary-item">
                                <span>Total Item</span>
                                <strong><?= $cart_count ?> item</strong>
                            </div>
                            <hr class="summary-divider">
                            <div class="summary-total">
                                <span>Total Bayar</span>
                                <span class="price">Rp<?= number_format($total_price, 0, ',', '.') ?></span>
                            </div>
                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                Pesanan akan tercatat di sistem kami, kemudian kamu akan diarahkan ke <strong>WhatsApp</strong> untuk konfirmasi dan pembayaran.
                            </div>
                        </div>

                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="index.php" class="nav-logo" style="display: block; margin-bottom: 1rem;">
                        <img src="public/images/logo.png" alt="Mekarsa Logo" class="navbar-logo-img">
                        <?= htmlspecialchars(explode(' ', $settings['business_name'])[0]) ?><span>.</span>
                    </a>
                    <p><?= htmlspecialchars($settings['description'] ?? 'Mekarsa Coffee Bar') ?></p>
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
            </div>
            <div class="footer-bottom">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($settings['business_name']) ?>. All Rights Reserved.
             <a href="portal-mekarsa/login.php" style="color: inherit; text-decoration: none; margin-left: 10px; opacity: 0.3; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.3'" title="Admin Login"><i class="fas fa-lock" style="font-size:0.85em;"></i></a>
            </div>
        </div>
    </footer>
    <script src="public/js/main.js"></script>
</body>
</html>
