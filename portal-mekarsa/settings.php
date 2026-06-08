<?php
/**
 * Mekarsa Coffee Bar - Admin Pengaturan Website
 * Mengelola informasi umum bisnis (nama, kontak, alamat, sosmed)
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once dirname(__DIR__) . '/src/config/database.php';

$flashMsg   = '';
$flashType  = 'success';
$formErrors = [];

try {
    $pdo = getDBConnection();

    // Pastikan selalu ada 1 baris di tabel settings
    $checkStmt = $pdo->query("SELECT COUNT(*) FROM settings");
    if ($checkStmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO settings (business_name, tagline) VALUES ('Mekarsa Coffee Bar', 'Brewing the best moments')");
    }

    // ----- ACTION: UPDATE SETTINGS -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
        $business_name = trim($_POST['business_name'] ?? '');
        $tagline       = trim($_POST['tagline'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $address       = trim($_POST['address'] ?? '');
        $phone         = trim($_POST['phone'] ?? '');
        $whatsapp      = trim($_POST['whatsapp'] ?? '');
        $instagram     = trim($_POST['instagram'] ?? '');
        $maps_link     = trim($_POST['maps_link'] ?? '');
        $opening_hours = trim($_POST['opening_hours'] ?? '');

        if (empty($business_name)) {
            $formErrors['business_name'] = 'Nama bisnis tidak boleh kosong.';
        }

        if (empty($formErrors)) {
            $sql = "UPDATE settings SET 
                    business_name = ?, tagline = ?, description = ?, 
                    address = ?, phone = ?, whatsapp = ?, 
                    instagram = ?, maps_link = ?, opening_hours = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $business_name, $tagline, $description,
                $address, $phone, $whatsapp,
                $instagram, $maps_link, $opening_hours
            ]);

            header("Location: settings.php?msg=updated");
            exit;
        } else {
            $flashType = 'error';
            $flashMsg  = 'Gagal menyimpan. Silakan periksa kolom yang wajib diisi.';
        }
    }

    // ----- Messages -----
    if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
        $flashMsg  = 'Pengaturan website berhasil diperbarui!';
        $flashType = 'success';
    }

    // ----- FETCH: Current Settings -----
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    // If there was an error in POST, use the POSTed values so user doesn't lose input
    if (!empty($formErrors) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $settings = array_merge($settings, $_POST);
    }

} catch (PDOException $e) {
    $settings = [];
    $flashMsg  = 'Database error: ' . $e->getMessage();
    $flashType = 'error';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Website — Mekarsa Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700;800&family=Inter:wght@400;500;600&family=Anton&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #0a0a0a; --bg-sidebar: #0d0d0d; --bg-card: #111111; --bg-input: #1a1a1a;
            --border: #27272a; --orange: #F27121; --orange-dark: #e06010; --orange-glow: rgba(242, 113, 33, 0.15);
            --text: #ffffff; --text-muted: #a1a1aa; 
            --green: #22c55e; --blue: #3b82f6; --red: #ef4444;
            --sidebar-w: 260px; --topbar-h: 65px;
            --font-head: 'Poppins', sans-serif; --font-body: 'Inter', sans-serif; --font-price: 'Anton', sans-serif;
        }
        html, body { height: 100%; }
        body { font-family: var(--font-body); background: var(--bg); color: var(--text); display: flex; min-height: 100vh; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }
        button { font-family: var(--font-body); }

        .sidebar { width: var(--sidebar-w); background: var(--bg-sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; transition: transform 0.3s ease; overflow-y: auto; }
        .sidebar-brand { padding: 1.5rem; display: flex; align-items: center; gap: 0.75rem; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .sidebar-brand-icon { width: 38px; height: 38px; background: var(--orange); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff; box-shadow: 0 0 12px rgba(242,113,33,0.3); }
        .sidebar-brand-text { font-family: var(--font-head); font-weight: 800; font-size: 1.2rem; }
        .sidebar-brand-text span { color: var(--orange); }
        .sidebar-nav { flex: 1; padding: 1.25rem 0; }
        .nav-section-label { padding: 0.5rem 1.5rem 0.3rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); opacity: 0.6; }
        .nav-item { display: flex; align-items: center; gap: 0.8rem; padding: 0.7rem 1.5rem; color: var(--text-muted); font-size: 0.9rem; font-weight: 500; transition: all 0.2s; position: relative; }
        .nav-item:hover { color: var(--text); background: rgba(255,255,255,0.04); }
        .nav-item.active { color: var(--orange); background: var(--orange-glow); }
        .nav-item.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--orange); border-radius: 0 2px 2px 0; }
        .nav-item i { width: 18px; text-align: center; }
        .sidebar-footer { padding: 1.25rem 1.5rem; border-top: 1px solid var(--border); flex-shrink: 0; }
        .btn-logout { display: flex; align-items: center; justify-content: center; gap: 0.6rem; padding: 0.6rem 1rem; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); border-radius: 8px; color: var(--red); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; width: 100%; }
        .btn-logout:hover { background: rgba(239,68,68,0.15); }

        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar { height: var(--topbar-h); background: rgba(10,10,10,0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; position: sticky; top: 0; z-index: 50; }
        .topbar-left { display: flex; align-items: center; gap: 1rem; }
        .sidebar-toggle { display: none; width: 38px; height: 38px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text); cursor: pointer; align-items: center; justify-content: center; }
        .breadcrumb { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--text-muted); }
        .breadcrumb span { color: var(--text); font-weight: 600; }

        .page-content { padding: 2rem; flex: 1; max-width: 1000px; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-family: var(--font-head); font-size: 1.75rem; font-weight: 800; margin-bottom: 0.25rem; }
        .page-header p { color: var(--text-muted); font-size: 0.9rem; }

        .page-alert { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.9rem 1.1rem; border-radius: 10px; font-size: 0.9rem; margin-bottom: 1.75rem; }
        .page-alert--success { background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.25); color: var(--green); }
        .page-alert--error   { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); color: var(--red); }

        .settings-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; margin-bottom: 2rem; }
        .settings-header { padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 0.75rem; }
        .settings-header i { color: var(--orange); font-size: 1.25rem; }
        .settings-header h2 { font-family: var(--font-head); font-size: 1.15rem; font-weight: 700; }
        
        .settings-body { padding: 2rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        @media(max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-group.full-width { grid-column: 1 / -1; }
        
        .form-label { display: block; font-weight: 600; font-size: 0.85rem; color: var(--text); margin-bottom: 0.5rem; }
        .form-label .req { color: var(--orange); }
        .form-label-hint { font-size: 0.75rem; color: var(--text-muted); font-weight: 400; margin-left: 0.5rem; }
        
        .input-icon-wrap { position: relative; }
        .input-icon-wrap i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1rem; }
        
        .form-input, .form-textarea { width: 100%; padding: 0.8rem 1rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; transition: 0.2s; }
        .input-icon-wrap .form-input { padding-left: 2.75rem; }
        .form-input:focus, .form-textarea:focus { border-color: var(--orange); box-shadow: 0 0 0 3px rgba(242,113,33,0.1); }
        .form-textarea { resize: vertical; min-height: 100px; line-height: 1.5; }
        
        .field-error { font-size: 0.78rem; color: var(--red); display: block; margin-top: 0.4rem; }

        .settings-footer { padding: 1.5rem 2rem; background: rgba(26,26,26,0.5); border-top: 1px solid var(--border); display: flex; justify-content: flex-end; }
        .btn-save { padding: 0.8rem 2rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-family: var(--font-head); font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 15px rgba(242,113,33,0.3); }
        .btn-save:hover { background: var(--orange-dark); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(242,113,33,0.4); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-toggle { display: flex; }
            .main { margin-left: 0; }
            .topbar { padding: 0 1rem; }
            .page-content { padding: 1.5rem 1rem; }
            .settings-body { padding: 1.5rem 1rem; }
            .settings-footer { padding: 1.5rem 1rem; }
        }
    </style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <a href="dashboard.php" class="sidebar-brand">
        <div class="sidebar-brand-icon"><i class="fas fa-mug-hot"></i></div>
        <div class="sidebar-brand-text">Mekarsa<span>.</span></div>
    </a>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Utama</div>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <div class="nav-section-label" style="margin-top:0.75rem;">Kelola Konten</div>
        <a href="products.php" class="nav-item"><i class="fas fa-mug-saucer"></i> Produk</a>
        <a href="product-categories.php" class="nav-item"><i class="fas fa-tags"></i> Kategori Produk</a>
        <a href="articles.php" class="nav-item"><i class="fas fa-newspaper"></i> Artikel</a>
        <a href="article-categories.php" class="nav-item"><i class="fas fa-folder-open"></i> Kategori Artikel</a>
        <a href="gallery.php" class="nav-item"><i class="fas fa-images"></i> Galeri</a>
        <a href="testimonials.php" class="nav-item"><i class="fas fa-star"></i> Testimoni</a>
        <div class="nav-section-label" style="margin-top:0.75rem;">Operasional</div>
        <a href="orders.php" class="nav-item"><i class="fas fa-receipt"></i> Pesanan</a>
        <a href="support-services.php" class="nav-item"><i class="fas fa-shoe-prints"></i> Layanan</a>
        <a href="settings.php" class="nav-item active"><i class="fas fa-gear"></i> Pengaturan</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')"><i class="fas fa-right-from-bracket"></i> Logout</a>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:99;"></div>

<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <nav class="breadcrumb"><span>Pengaturan Website</span></nav>
        </div>
    </header>

    <main class="page-content">
        <div class="page-header">
            <h1><i class="fas fa-gear" style="color:var(--orange);margin-right:0.5rem;font-size:1.4rem;"></i>Pengaturan Umum</h1>
            <p>Kelola informasi dasar bisnis yang akan ditampilkan pada website publik.</p>
        </div>

        <?php if (!empty($flashMsg)): ?>
            <div class="page-alert page-alert--<?= $flashType ?>" id="pageAlert">
                <i class="fas <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <span><?= $flashMsg ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="settings.php">
            <input type="hidden" name="action" value="update">
            
            <div class="settings-card">
                <div class="settings-header">
                    <i class="fas fa-store"></i>
                    <h2>Informasi Bisnis</h2>
                </div>
                <div class="settings-body form-grid">
                    <div class="form-group">
                        <label class="form-label">Nama Bisnis <span class="req">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-shop"></i>
                            <input type="text" name="business_name" class="form-input" required 
                                   value="<?= htmlspecialchars($settings['business_name'] ?? '') ?>"
                                   placeholder="Contoh: Mekarsa Coffee Bar">
                        </div>
                        <?php if (isset($formErrors['business_name'])): ?><span class="field-error"><?= $formErrors['business_name'] ?></span><?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tagline (Slogan)</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-quote-left"></i>
                            <input type="text" name="tagline" class="form-input" 
                                   value="<?= htmlspecialchars($settings['tagline'] ?? '') ?>"
                                   placeholder="Contoh: Brewing the best moments">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Deskripsi Singkat <span class="form-label-hint">Tampil di halaman About atau Footer</span></label>
                        <textarea name="description" class="form-textarea" placeholder="Ceritakan sedikit tentang kedai kopi Anda..."><?= htmlspecialchars($settings['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jam Operasional</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-clock"></i>
                            <input type="text" name="opening_hours" class="form-input" 
                                   value="<?= htmlspecialchars($settings['opening_hours'] ?? '') ?>"
                                   placeholder="Contoh: Senin - Minggu, 15:00 - 23:00">
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-header">
                    <i class="fas fa-address-book"></i>
                    <h2>Kontak & Lokasi</h2>
                </div>
                <div class="settings-body form-grid">
                    <div class="form-group">
                        <label class="form-label">Nomor WhatsApp <span class="form-label-hint">Gunakan format 628xxx</span></label>
                        <div class="input-icon-wrap">
                            <i class="fab fa-whatsapp"></i>
                            <input type="text" name="whatsapp" class="form-input" 
                                   value="<?= htmlspecialchars($settings['whatsapp'] ?? '') ?>"
                                   placeholder="6281234567890">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Username Instagram <span class="form-label-hint">Tanpa @</span></label>
                        <div class="input-icon-wrap">
                            <i class="fab fa-instagram"></i>
                            <input type="text" name="instagram" class="form-input" 
                                   value="<?= htmlspecialchars($settings['instagram'] ?? '') ?>"
                                   placeholder="mekarsacoffeebar">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Telepon (Opsional)</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-phone"></i>
                            <input type="text" name="phone" class="form-input" 
                                   value="<?= htmlspecialchars($settings['phone'] ?? '') ?>"
                                   placeholder="021-123456">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Alamat Kedai</label>
                        <textarea name="address" class="form-textarea" style="min-height: 80px;" placeholder="Alamat lengkap..."><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Link Google Maps <span class="form-label-hint">Tempel URL GMaps dari browser</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-map-location-dot"></i>
                            <input type="url" name="maps_link" class="form-input" 
                                   value="<?= htmlspecialchars($settings['maps_link'] ?? '') ?>"
                                   placeholder="https://goo.gl/maps/...">
                        </div>
                    </div>
                </div>
                <div class="settings-footer">
                    <button type="submit" class="btn-save"><i class="fas fa-save" style="margin-right:0.5rem;"></i> Simpan Perubahan</button>
                </div>
            </div>
        </form>

    </main>
</div>

<script>
    const sidebar = document.getElementById('sidebar'), overlay = document.getElementById('sidebarOverlay'), toggleBtn = document.getElementById('sidebarToggle');
    if(toggleBtn) toggleBtn.addEventListener('click', () => { sidebar.classList.add('open'); overlay.style.display = 'block'; });
    if(overlay) overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.style.display = 'none'; });

    const alertEl = document.getElementById('pageAlert');
    if(alertEl) { setTimeout(() => { alertEl.style.opacity = '0'; setTimeout(() => alertEl.remove(), 500); }, 4000); }
</script>
</body>
</html>
