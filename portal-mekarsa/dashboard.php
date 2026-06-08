<?php
/**
 * Mekarsa Coffee Bar - Admin Dashboard
 * Menampilkan ringkasan data: produk, kategori, artikel, testimoni, pesanan, galeri
 */

session_start();

define('BASE_URL', '../');

// Auth check
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once dirname(__DIR__) . '/src/config/database.php';

// Handle pesan logout dari query param
$logoutMsg = isset($_GET['logout']) ? 'Anda berhasil logout dari sistem.' : '';

// --- Ambil data ringkasan dari database ---
try {
    $pdo = getDBConnection();

    $stats = [];

    // Jumlah produk aktif
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
    $stats['products'] = (int)$stmt->fetchColumn();

    // Total semua produk
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $stats['products_total'] = (int)$stmt->fetchColumn();

    // Jumlah kategori produk
    $stmt = $pdo->query("SELECT COUNT(*) FROM product_categories");
    $stats['categories'] = (int)$stmt->fetchColumn();

    // Jumlah artikel published
    $stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'");
    $stats['articles'] = (int)$stmt->fetchColumn();

    // Total artikel
    $stmt = $pdo->query("SELECT COUNT(*) FROM articles");
    $stats['articles_total'] = (int)$stmt->fetchColumn();

    // Jumlah testimoni
    $stmt = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE status = 'show'");
    $stats['testimonials'] = (int)$stmt->fetchColumn();

    // Jumlah pesanan
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $stats['orders'] = (int)$stmt->fetchColumn();

    // Pesanan pending
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'");
    $stats['orders_pending'] = (int)$stmt->fetchColumn();

    // Total revenue (semua pesanan)
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE order_status != 'cancelled'");
    $stats['revenue'] = (float)$stmt->fetchColumn();

    // 5 pesanan terbaru
    $stmt = $pdo->query("
        SELECT id, customer_name, customer_phone, total_price, order_status, order_date
        FROM orders
        ORDER BY order_date DESC
        LIMIT 5
    ");
    $recentOrders = $stmt->fetchAll();

    // 5 produk terbaru
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.price, p.status, p.is_featured, pc.name AS category_name
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $recentProducts = $stmt->fetchAll();

} catch (PDOException $e) {
    // Tampilkan error aman saat development
    $dbError = $e->getMessage();
    $stats   = array_fill_keys(['products','products_total','categories','articles','articles_total','testimonials','orders','orders_pending','revenue'], 0);
    $recentOrders   = [];
    $recentProducts = [];
}

// Helper: format Rupiah
function formatRupiah(float $amount): string {
    return 'Rp' . number_format($amount, 0, ',', '.');
}

// Helper: order status badge
function orderStatusBadge(string $status): string {
    $map = [
        'pending'   => ['label' => 'Pending',    'color' => '#f59e0b'],
        'confirmed' => ['label' => 'Dikonfirmasi','color' => '#3b82f6'],
        'completed' => ['label' => 'Selesai',     'color' => '#22c55e'],
        'cancelled' => ['label' => 'Dibatalkan',  'color' => '#f87171'],
    ];
    $info = $map[$status] ?? ['label' => ucfirst($status), 'color' => '#a1a1aa'];
    return '<span class="badge" style="background:' . $info['color'] . '20;color:' . $info['color'] . ';border:1px solid ' . $info['color'] . '40;">' . $info['label'] . '</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Mekarsa Coffee Bar</title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700;800&family=Inter:wght@400;500;600&family=Anton&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ===== RESET & TOKENS ===== */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg:          #0a0a0a;
            --bg-sidebar:  #0d0d0d;
            --bg-card:     #111111;
            --bg-input:    #1a1a1a;
            --border:      #27272a;
            --orange:      #F27121;
            --orange-dark: #e06010;
            --orange-glow: rgba(242, 113, 33, 0.15);
            --text:        #ffffff;
            --text-muted:  #a1a1aa;
            --green:       #22c55e;
            --blue:        #3b82f6;
            --yellow:      #f59e0b;
            --red:         #f87171;
            --sidebar-w:   260px;
            --topbar-h:    65px;
            --font-head:   'Poppins', sans-serif;
            --font-body:   'Inter', sans-serif;
            --font-price:  'Anton', sans-serif;
        }

        html, body { height: 100%; }

        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        a { text-decoration: none; color: inherit; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            padding: 1.5rem 1.5rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
        }

        .sidebar-brand-icon {
            width: 38px; height: 38px;
            background: var(--orange);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            color: #fff;
            flex-shrink: 0;
            box-shadow: 0 0 12px rgba(242,113,33,0.3);
        }

        .sidebar-brand-text {
            font-family: var(--font-head);
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--text);
        }

        .sidebar-brand-text span { color: var(--orange); }

        .sidebar-brand-sub {
            font-size: 0.7rem;
            color: var(--text-muted);
            font-family: var(--font-body);
        }

        /* Nav */
        .sidebar-nav {
            flex: 1;
            padding: 1.25rem 0;
            overflow-y: auto;
        }

        .nav-section-label {
            padding: 0.5rem 1.5rem 0.3rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            opacity: 0.6;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.7rem 1.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
            cursor: pointer;
        }

        .nav-item:hover {
            color: var(--text);
            background: rgba(255,255,255,0.04);
        }

        .nav-item.active {
            color: var(--orange);
            background: var(--orange-glow);
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: var(--orange);
            border-radius: 0 2px 2px 0;
        }

        .nav-item i {
            width: 18px;
            text-align: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--orange);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            font-family: var(--font-head);
        }

        .nav-badge--yellow { background: var(--yellow); }

        /* Sidebar footer */
        .sidebar-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--border);
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .admin-avatar {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: var(--orange);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800;
            font-size: 0.9rem;
            color: #fff;
            font-family: var(--font-head);
            flex-shrink: 0;
        }

        .admin-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .admin-role {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 1rem;
            background: rgba(248,113,113,0.08);
            border: 1px solid rgba(248,113,113,0.2);
            border-radius: 8px;
            color: var(--red);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            font-family: var(--font-body);
            text-decoration: none;
        }

        .btn-logout:hover {
            background: rgba(248,113,113,0.15);
            border-color: rgba(248,113,113,0.4);
        }

        /* ===== MAIN CONTENT ===== */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ===== TOP BAR ===== */
        .topbar {
            height: var(--topbar-h);
            background: rgba(10,10,10,0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-toggle {
            display: none;
            width: 38px; height: 38px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-card);
            color: var(--text);
            cursor: pointer;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .breadcrumb a { color: var(--text-muted); }
        .breadcrumb a:hover { color: var(--orange); }
        .breadcrumb span { color: var(--text); font-weight: 600; }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .topbar-time {
            font-size: 0.82rem;
            color: var(--text-muted);
            font-feature-settings: 'tnum';
        }

        .topbar-view-site {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.82rem;
            color: var(--text-muted);
            transition: all 0.2s;
        }

        .topbar-view-site:hover {
            border-color: var(--orange);
            color: var(--orange);
        }

        /* ===== PAGE CONTENT ===== */
        .page-content {
            padding: 2rem;
            flex: 1;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-family: var(--font-head);
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 0.25rem;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* ===== ALERT ===== */
        .page-alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.9rem 1.1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 1.75rem;
            background: rgba(74,222,128,0.08);
            border: 1px solid rgba(74,222,128,0.25);
            color: var(--green);
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: border-color 0.3s, transform 0.2s, box-shadow 0.3s;
            cursor: default;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }

        .stat-card--orange:hover { border-color: var(--orange); box-shadow: 0 8px 24px rgba(242,113,33,0.15); }
        .stat-card--green:hover  { border-color: var(--green);  box-shadow: 0 8px 24px rgba(34,197,94,0.12); }
        .stat-card--blue:hover   { border-color: var(--blue);   box-shadow: 0 8px 24px rgba(59,130,246,0.12); }
        .stat-card--yellow:hover { border-color: var(--yellow); box-shadow: 0 8px 24px rgba(245,158,11,0.12); }

        /* Glow dot behind card */
        .stat-card::after {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 120px; height: 120px;
            border-radius: 50%;
            opacity: 0.06;
        }

        .stat-card--orange::after { background: var(--orange); }
        .stat-card--green::after  { background: var(--green); }
        .stat-card--blue::after   { background: var(--blue); }
        .stat-card--yellow::after { background: var(--yellow); }

        .stat-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .stat-icon--orange { background: rgba(242,113,33,0.15); color: var(--orange); }
        .stat-icon--green  { background: rgba(34,197,94,0.12);  color: var(--green); }
        .stat-icon--blue   { background: rgba(59,130,246,0.12); color: var(--blue); }
        .stat-icon--yellow { background: rgba(245,158,11,0.12); color: var(--yellow); }

        .stat-value {
            font-family: var(--font-price);
            font-size: 2.2rem;
            letter-spacing: 1px;
            line-height: 1;
            margin-bottom: 0.3rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-sub {
            margin-top: 0.5rem;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .stat-sub strong { color: var(--text); }

        /* ===== TABLES SECTION ===== */
        .tables-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 1100px) { .tables-grid { grid-template-columns: 1fr; } }

        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .table-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .table-card-header h3 {
            font-family: var(--font-head);
            font-size: 1rem;
            font-weight: 700;
        }

        .table-card-header a {
            font-size: 0.8rem;
            color: var(--orange);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .table-card-header a:hover { text-decoration: underline; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .data-table th {
            padding: 0.75rem 1.5rem;
            text-align: left;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid var(--border);
        }

        .data-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .data-table tr:last-child td { border-bottom: none; }

        .data-table tr:hover td { background: rgba(255,255,255,0.02); }

        .data-table .customer-name { font-weight: 600; color: var(--text); }
        .data-table .sub-text { font-size: 0.78rem; color: var(--text-muted); }
        .data-table .price-text { font-family: var(--font-price); color: var(--orange); font-size: 1rem; }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            font-family: var(--font-head);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge--active   { background: rgba(34,197,94,0.12);  color: var(--green);  border: 1px solid rgba(34,197,94,0.3); }
        .badge--inactive { background: rgba(161,161,170,0.1); color: var(--text-muted); border: 1px solid rgba(161,161,170,0.2); }
        .badge--featured { background: rgba(245,158,11,0.12); color: var(--yellow); border: 1px solid rgba(245,158,11,0.3); }

        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .empty-state p { font-size: 0.9rem; }

        /* ===== QUICK ACTIONS ===== */
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .quick-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.1rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
            transition: all 0.2s;
            cursor: pointer;
        }

        .quick-btn i { color: var(--orange); font-size: 0.85rem; }

        .quick-btn:hover {
            border-color: var(--orange);
            background: var(--orange-glow);
            color: var(--orange);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-toggle { display: flex; }
            .main { margin-left: 0; }
            .topbar { padding: 0 1rem; }
            .page-content { padding: 1.25rem 1rem; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .topbar-time { display: none; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
        }

        /* Overlay for mobile sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 99;
        }

        .sidebar-overlay.visible { display: block; }
    </style>
</head>
<body>

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Admin Navigation">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon"><i class="fas fa-mug-hot"></i></div>
            <div>
                <div class="sidebar-brand-text">Mekarsa<span>.</span></div>
                <div class="sidebar-brand-sub">Admin Panel</div>
            </div>
        </a>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Utama</div>
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-gauge-high"></i>
                Dashboard
            </a>

            <div class="nav-section-label" style="margin-top:0.75rem;">Kelola Konten</div>
            <a href="products.php" class="nav-item">
                <i class="fas fa-mug-saucer"></i>
                Produk Coffee
                <?php if (!empty($stats['products_total'])): ?>
                    <span class="nav-badge"><?= $stats['products_total'] ?></span>
                <?php endif; ?>
            </a>
            <a href="product-categories.php" class="nav-item">
                <i class="fas fa-tags"></i>
                Kategori Produk
            </a>
            <a href="articles.php" class="nav-item">
                <i class="fas fa-newspaper"></i>
                Artikel
            </a>
            <a href="article-categories.php" class="nav-item">
                <i class="fas fa-folder-open"></i>
                Kategori Artikel
            </a>
            <a href="gallery.php" class="nav-item">
                <i class="fas fa-images"></i>
                Galeri
            </a>
            <a href="testimonials.php" class="nav-item">
                <i class="fas fa-star"></i>
                Testimoni
            </a>

            <div class="nav-section-label" style="margin-top:0.75rem;">Operasional</div>
            <a href="orders.php" class="nav-item">
                <i class="fas fa-receipt"></i>
                Pesanan
                <?php if (!empty($stats['orders_pending'])): ?>
                    <span class="nav-badge nav-badge--yellow"><?= $stats['orders_pending'] ?></span>
                <?php endif; ?>
            </a>
            <a href="support-services.php" class="nav-item">
                <i class="fas fa-shoe-prints"></i>
                Layanan Pendukung
            </a>
            <a href="settings.php" class="nav-item">
                <i class="fas fa-gear"></i>
                Pengaturan Website
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-info">
                <div class="admin-avatar"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?></div>
                <div>
                    <div class="admin-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></div>
                    <div class="admin-role">Super Admin</div>
                </div>
            </div>
            <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">
                <i class="fas fa-right-from-bracket"></i>
                Logout
            </a>
        </div>
    </aside>

    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ===== MAIN ===== -->
    <div class="main">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <nav class="breadcrumb" aria-label="breadcrumb">
                    <a href="dashboard.php"><i class="fas fa-house" style="font-size:0.8rem;"></i></a>
                    <i class="fas fa-chevron-right" style="font-size:0.65rem;"></i>
                    <span>Dashboard</span>
                </nav>
            </div>
            <div class="topbar-right">
                <span class="topbar-time" id="currentTime"></span>
                <a href="../index.php" class="topbar-view-site" target="_blank" rel="noopener">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                    Lihat Website
                </a>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">

            <!-- Page Header -->
            <div class="page-header">
                <h1>Dashboard 📊</h1>
                <p>Selamat datang kembali, <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong>. Berikut ringkasan data website Mekarsa.</p>
            </div>

            <!-- Success/Logout Alert -->
            <?php if (!empty($logoutMsg)): ?>
                <div class="page-alert" role="status" id="pageAlert">
                    <i class="fas fa-circle-check"></i>
                    <?= htmlspecialchars($logoutMsg) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($dbError)): ?>
                <div class="page-alert" style="background:rgba(248,113,113,0.08);border-color:rgba(248,113,113,0.25);color:var(--red);" role="alert">
                    <i class="fas fa-triangle-exclamation"></i>
                    <strong>Database Error:</strong> <?= htmlspecialchars($dbError) ?>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="products.php?action=add" class="quick-btn">
                    <i class="fas fa-plus"></i> Tambah Produk
                </a>
                <a href="articles.php?action=add" class="quick-btn">
                    <i class="fas fa-pen-to-square"></i> Buat Artikel
                </a>
                <a href="orders.php" class="quick-btn">
                    <i class="fas fa-receipt"></i> Lihat Pesanan
                </a>
                <a href="gallery.php?action=add" class="quick-btn">
                    <i class="fas fa-images"></i> Upload Galeri
                </a>
                <a href="settings.php" class="quick-btn">
                    <i class="fas fa-gear"></i> Pengaturan
                </a>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <!-- Produk Aktif -->
                <div class="stat-card stat-card--orange">
                    <div class="stat-icon stat-icon--orange">
                        <i class="fas fa-mug-saucer"></i>
                    </div>
                    <div class="stat-value"><?= $stats['products'] ?></div>
                    <div class="stat-label">Produk Aktif</div>
                    <div class="stat-sub">Total: <strong><?= $stats['products_total'] ?></strong> produk</div>
                </div>

                <!-- Kategori -->
                <div class="stat-card stat-card--blue">
                    <div class="stat-icon stat-icon--blue">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-value"><?= $stats['categories'] ?></div>
                    <div class="stat-label">Kategori Produk</div>
                    <div class="stat-sub">Untuk filter menu</div>
                </div>

                <!-- Artikel -->
                <div class="stat-card stat-card--green">
                    <div class="stat-icon stat-icon--green">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stat-value"><?= $stats['articles'] ?></div>
                    <div class="stat-label">Artikel Published</div>
                    <div class="stat-sub">Total: <strong><?= $stats['articles_total'] ?></strong> artikel</div>
                </div>

                <!-- Testimoni -->
                <div class="stat-card stat-card--yellow">
                    <div class="stat-icon stat-icon--yellow">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-value"><?= $stats['testimonials'] ?></div>
                    <div class="stat-label">Testimoni Aktif</div>
                    <div class="stat-sub">Ditampilkan ke publik</div>
                </div>

                <!-- Pesanan -->
                <div class="stat-card stat-card--orange">
                    <div class="stat-icon stat-icon--orange">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-value"><?= $stats['orders'] ?></div>
                    <div class="stat-label">Total Pesanan</div>
                    <div class="stat-sub">Pending: <strong style="color:var(--yellow)"><?= $stats['orders_pending'] ?></strong></div>
                </div>

                <!-- Revenue -->
                <div class="stat-card stat-card--green">
                    <div class="stat-icon stat-icon--green">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-value" style="font-size:1.5rem;"><?= formatRupiah($stats['revenue']) ?></div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-sub">Pesanan non-cancelled</div>
                </div>
            </div>

            <!-- Tables -->
            <div class="tables-grid">
                <!-- Recent Orders -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-receipt" style="color:var(--orange);margin-right:0.5rem;"></i> Pesanan Terbaru</h3>
                        <a href="orders.php">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (!empty($recentOrders)): ?>
                        <table class="data-table" role="table" aria-label="Pesanan terbaru">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Pelanggan</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td style="color:var(--text-muted);">#<?= $order['id'] ?></td>
                                        <td>
                                            <div class="customer-name"><?= htmlspecialchars($order['customer_name']) ?></div>
                                            <div class="sub-text"><?= htmlspecialchars($order['customer_phone']) ?></div>
                                        </td>
                                        <td class="price-text"><?= formatRupiah((float)$order['total_price']) ?></td>
                                        <td><?= orderStatusBadge($order['order_status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Belum ada pesanan yang masuk.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Products -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-mug-saucer" style="color:var(--orange);margin-right:0.5rem;"></i> Produk Terbaru</h3>
                        <a href="products.php">Kelola <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (!empty($recentProducts)): ?>
                        <table class="data-table" role="table" aria-label="Produk terbaru">
                            <thead>
                                <tr>
                                    <th>Nama Produk</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentProducts as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="customer-name"><?= htmlspecialchars($product['name']) ?></div>
                                            <div class="sub-text"><?= htmlspecialchars($product['category_name'] ?? '-') ?></div>
                                        </td>
                                        <td class="price-text"><?= formatRupiah((float)$product['price']) ?></td>
                                        <td>
                                            <?php if ($product['is_featured']): ?>
                                                <span class="badge badge--featured">Featured</span>
                                            <?php elseif ($product['status'] === 'active'): ?>
                                                <span class="badge badge--active">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge badge--inactive">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-mug-saucer"></i>
                            <p>Belum ada produk yang ditambahkan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div><!-- /main -->

    <script>
    (function () {
        'use strict';

        /* ===== Live Clock ===== */
        const timeEl = document.getElementById('currentTime');
        function updateClock() {
            if (!timeEl) return;
            const now = new Date();
            const opts = { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' };
            timeEl.textContent = now.toLocaleString('id-ID', opts);
        }
        updateClock();
        setInterval(updateClock, 60000);

        /* ===== Mobile Sidebar Toggle ===== */
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar   = document.getElementById('sidebar');
        const overlay   = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('visible');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('visible');
            document.body.style.overflow = '';
        }

        if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
        if (overlay)   overlay.addEventListener('click', closeSidebar);

        /* ===== Auto-dismiss alert ===== */
        const alertEl = document.getElementById('pageAlert');
        if (alertEl) {
            setTimeout(() => {
                alertEl.style.transition = 'opacity 0.5s';
                alertEl.style.opacity = '0';
                setTimeout(() => alertEl.remove(), 500);
            }, 5000);
        }

        /* ===== Stat card counter animation ===== */
        document.querySelectorAll('.stat-value').forEach(el => {
            const raw = el.textContent.trim();
            // Only animate pure numbers (not currency strings)
            if (/^\d+$/.test(raw)) {
                const target = parseInt(raw, 10);
                let current  = 0;
                const step   = Math.max(1, Math.floor(target / 30));
                const timer  = setInterval(() => {
                    current = Math.min(current + step, target);
                    el.textContent = current;
                    if (current >= target) clearInterval(timer);
                }, 40);
            }
        });

    })();
    </script>

</body>
</html>
