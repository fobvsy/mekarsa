<?php
/**
 * Mekarsa Coffee Bar - Admin Kelola Pesanan
 * Menampilkan daftar pesanan, melihat detail item, dan memperbarui status
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once dirname(__DIR__) . '/src/config/database.php';

$flashMsg   = '';
$flashType  = 'success';

try {
    $pdo = getDBConnection();

    // ----- ACTION: UPDATE STATUS -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if ($id > 0 && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
            $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?")->execute([$status, $id]);
            header("Location: orders.php?msg=status_updated");
            exit;
        }
    }

    // ----- ACTION: DELETE ORDER -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // order_items will be deleted automatically if ON DELETE CASCADE is set
            $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);
            header("Location: orders.php?msg=deleted");
            exit;
        }
    }

    // ----- Messages -----
    $msgMap = [
        'status_updated' => ['Status pesanan berhasil diperbarui!', 'success'],
        'deleted'        => ['Data pesanan berhasil dihapus.', 'success'],
        'error'          => ['Terjadi kesalahan. Silakan coba lagi.', 'error']
    ];
    if (isset($_GET['msg']) && isset($msgMap[$_GET['msg']])) {
        [$flashMsg, $flashType] = $msgMap[$_GET['msg']];
    }

    // ----- FETCH: Orders -----
    $search       = trim($_GET['search'] ?? '');
    $filterStatus = $_GET['status'] ?? '';
    
    $where  = [];
    $params = [];
    if (!empty($search)) {
        $where[]  = "(customer_name LIKE ? OR customer_phone LIKE ? OR id = ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = (int)$search;
    }
    if (in_array($filterStatus, ['pending', 'confirmed', 'completed', 'cancelled'])) {
        $where[]  = "order_status = ?";
        $params[] = $filterStatus;
    }

    $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $listStmt = $pdo->prepare("SELECT * FROM orders $whereSQL ORDER BY order_date DESC");
    $listStmt->execute($params);
    $orders = $listStmt->fetchAll();

    // ----- FETCH: Order Items for all orders to use in JS Modal -----
    // This approach is okay for small to medium lists.
    $orderIds = array_column($orders, 'id');
    $itemsByOrder = [];
    if (!empty($orderIds)) {
        $in  = str_repeat('?,', count($orderIds) - 1) . '?';
        $sql = "SELECT oi.*, p.name AS product_name 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id IN ($in)";
        $itemStmt = $pdo->prepare($sql);
        $itemStmt->execute($orderIds);
        $allItems = $itemStmt->fetchAll();
        foreach ($allItems as $item) {
            $itemsByOrder[$item['order_id']][] = $item;
        }
    }

    // ----- Stats -----
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) AS total, 
            SUM(order_status='pending') AS pending_count,
            SUM(order_status='completed') AS completed_count,
            SUM(total_price) AS revenue
        FROM orders
    ");
    $stats = $statsStmt->fetch();

} catch (PDOException $e) {
    $orders = [];
    $itemsByOrder = [];
    $stats = ['total'=>0, 'pending_count'=>0, 'completed_count'=>0, 'revenue'=>0];
    $flashMsg  = 'Database error: ' . $e->getMessage();
    $flashType = 'error';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan — Mekarsa Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700;800&family=Inter:wght@400;500;600&family=Anton&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #0a0a0a; --bg-sidebar: #0d0d0d; --bg-card: #111111; --bg-input: #1a1a1a;
            --border: #27272a; --orange: #F27121; --orange-dark: #e06010; --orange-glow: rgba(242, 113, 33, 0.15);
            --text: #ffffff; --text-muted: #a1a1aa; 
            --green: #22c55e; --blue: #3b82f6; --yellow: #eab308; --red: #ef4444; --purple: #a855f7;
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

        .page-content { padding: 2rem; flex: 1; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-family: var(--font-head); font-size: 1.75rem; font-weight: 800; margin-bottom: 0.25rem; }
        .page-header p { color: var(--text-muted); font-size: 0.9rem; }

        .page-alert { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.9rem 1.1rem; border-radius: 10px; font-size: 0.9rem; margin-bottom: 1.75rem; }
        .page-alert--success { background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.25); color: var(--green); }
        .page-alert--error   { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); color: var(--red); }

        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; }
        .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; }
        .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink:0; }
        .stat-icon--orange { background: rgba(242,113,33,0.15); color: var(--orange); }
        .stat-icon--yellow { background: rgba(234,179,8,0.15); color: var(--yellow); }
        .stat-icon--green { background: rgba(34,197,94,0.15); color: var(--green); }
        .stat-val { font-family: var(--font-price); font-size: 1.6rem; line-height: 1; margin-bottom: 0.25rem; }
        .stat-lbl { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        .filter-bar { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
        .filter-bar form { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
        .search-group { position: relative; flex: 1; min-width: 200px; }
        .search-group i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .search-input { width: 100%; padding: 0.75rem 1rem 0.75rem 2.7rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; }
        .filter-select { padding: 0.75rem 1rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; cursor: pointer; }
        .filter-select option { background: var(--bg-card); }
        .btn-filter { padding: 0.75rem 1.5rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-filter:hover { background: var(--orange-dark); }
        .btn-reset { padding: 0.75rem 1rem; background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: 8px; font-size: 0.85rem; cursor: pointer; }
        .btn-reset:hover { color: var(--text); border-color: var(--text-muted); }

        .table-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .table-wrapper { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 800px; }
        .data-table th { padding: 0.8rem 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border); }
        .data-table td { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; vertical-align: middle; }
        .data-table tr:hover td { background: rgba(255,255,255,0.02); }

        .order-id { font-family: var(--font-head); font-weight: 700; color: var(--orange); }
        .customer-info { font-weight: 600; margin-bottom: 0.2rem; }
        .customer-phone { font-size: 0.8rem; color: var(--text-muted); }
        .price { font-family: var(--font-price); font-size: 1.1rem; color: var(--text); letter-spacing: 0.5px; }

        .badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .badge--pending { background: rgba(234,179,8,0.15); color: var(--yellow); border: 1px solid rgba(234,179,8,0.3); }
        .badge--confirmed { background: rgba(59,130,246,0.15); color: var(--blue); border: 1px solid rgba(59,130,246,0.3); }
        .badge--completed { background: rgba(34,197,94,0.15); color: var(--green); border: 1px solid rgba(34,197,94,0.3); }
        .badge--cancelled { background: rgba(239,68,68,0.15); color: var(--red); border: 1px solid rgba(239,68,68,0.3); }

        .action-group { display: flex; align-items: center; gap: 0.4rem; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-input); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; cursor: pointer; transition: 0.2s; color: var(--text-muted); }
        .action-btn:hover { border-color: var(--orange); color: var(--orange); }
        .action-btn--primary:hover { border-color: var(--blue); color: var(--blue); }
        .action-btn--danger:hover { border-color: var(--red); color: var(--red); }

        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 200; align-items: center; justify-content: center; padding: 2rem 1rem; backdrop-filter: blur(4px); }
        .modal-overlay.open { display: flex; }
        .modal { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 600px; animation: modalIn 0.3s ease; display: flex; flex-direction: column; max-height: 90vh; }
        @keyframes modalIn { from { opacity:0; transform: scale(0.96) translateY(20px); } to { opacity:1; transform:none; } }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.5rem; border-bottom: 1px solid var(--border); }
        .modal-title { font-family: var(--font-head); font-size: 1.15rem; font-weight: 800; }
        .modal-close { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem; }
        .modal-body { padding: 1.5rem; overflow-y: auto; }
        .modal-footer { padding: 1.25rem 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }

        .detail-row { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem; }
        .detail-label { color: var(--text-muted); }
        .detail-val { font-weight: 600; }
        .detail-notes { background: rgba(242,113,33,0.05); border: 1px dashed var(--orange); padding: 1rem; border-radius: 8px; margin-top: 1rem; font-size: 0.85rem; color: var(--text); }

        .items-table { width: 100%; margin-top: 1.5rem; border-collapse: collapse; font-size: 0.85rem; }
        .items-table th { text-align: left; padding: 0.5rem; border-bottom: 1px solid var(--border); color: var(--text-muted); font-weight: 600; }
        .items-table td { padding: 0.75rem 0.5rem; border-bottom: 1px solid var(--border); }
        .items-table .t-price { font-family: var(--font-price); font-size: 0.95rem; }

        .status-form { display: flex; gap: 0.5rem; align-items: center; }
        .btn-update { padding: 0.6rem 1rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        
        .empty-state { text-align: center; padding: 4rem 1.5rem; color: var(--text-muted); }
        .empty-state i { font-size: 3rem; opacity: 0.2; margin-bottom: 1rem; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-toggle { display: flex; }
            .main { margin-left: 0; }
            .topbar { padding: 0 1rem; }
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
        <a href="orders.php" class="nav-item active"><i class="fas fa-receipt"></i> Pesanan</a>
        <a href="support-services.php" class="nav-item"><i class="fas fa-shoe-prints"></i> Layanan</a>
        <a href="settings.php" class="nav-item"><i class="fas fa-gear"></i> Pengaturan</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')"><i class="fas fa-right-from-bracket"></i> Logout</a>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <nav class="breadcrumb"><span>Pesanan</span></nav>
        </div>
    </header>

    <main class="page-content">
        <div class="page-header">
            <h1><i class="fas fa-receipt" style="color:var(--orange);margin-right:0.5rem;font-size:1.4rem;"></i>Pesanan Masuk</h1>
            <p>Kelola pesanan dari pelanggan, update status, dan pantau penjualan.</p>
        </div>

        <?php if (!empty($flashMsg)): ?>
            <div class="page-alert page-alert--<?= $flashType ?>" id="pageAlert">
                <i class="fas <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <span><?= $flashMsg ?></span>
            </div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon stat-icon--orange"><i class="fas fa-receipt"></i></div>
                <div>
                    <div class="stat-val"><?= $stats['total'] ?? 0 ?></div>
                    <div class="stat-lbl">Total Pesanan</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon--yellow"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="stat-val"><?= $stats['pending_count'] ?? 0 ?></div>
                    <div class="stat-lbl">Pending (Baru)</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon--green"><i class="fas fa-check-double"></i></div>
                <div>
                    <div class="stat-val"><?= $stats['completed_count'] ?? 0 ?></div>
                    <div class="stat-lbl">Selesai</div>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <form method="GET" action="orders.php">
                <div class="search-group">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" name="search" class="search-input" placeholder="Cari ID, Nama, atau No HP..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <select name="status" class="filter-select">
                    <option value="">Semua Status</option>
                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $filterStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn-filter">Filter</button>
                <?php if ($search || $filterStatus): ?>
                    <a href="orders.php" class="btn-reset">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-card">
            <div class="table-wrapper">
                <?php if (!empty($orders)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $ord): ?>
                                <?php
                                    $badgeClass = 'badge--pending';
                                    $icon = 'fa-clock';
                                    $statusTxt = 'Pending';
                                    if ($ord['order_status'] === 'confirmed') { $badgeClass = 'badge--confirmed'; $icon = 'fa-fire-burner'; $statusTxt = 'Confirmed'; }
                                    if ($ord['order_status'] === 'completed') { $badgeClass = 'badge--completed'; $icon = 'fa-check-double'; $statusTxt = 'Selesai'; }
                                    if ($ord['order_status'] === 'cancelled') { $badgeClass = 'badge--cancelled'; $icon = 'fa-xmark'; $statusTxt = 'Dibatalkan'; }
                                ?>
                                <tr>
                                    <td><span class="order-id">#ORD-<?= str_pad($ord['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                                    <td>
                                        <div class="customer-info"><?= htmlspecialchars($ord['customer_name']) ?></div>
                                        <div class="customer-phone"><i class="fas fa-phone" style="font-size:0.7rem;"></i> <?= htmlspecialchars($ord['customer_phone']) ?></div>
                                    </td>
                                    <td style="color:var(--text-muted); font-size:0.85rem;"><?= date('d M Y, H:i', strtotime($ord['order_date'])) ?></td>
                                    <td class="price">Rp<?= number_format($ord['total_price'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>"><i class="fas <?= $icon ?>"></i> <?= $statusTxt ?></span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <button class="action-btn action-btn--primary" title="Lihat Detail" onclick='openDetail(<?= json_encode($ord) ?>, <?= json_encode($itemsByOrder[$ord['id']] ?? []) ?>)'>
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus data pesanan ini secara permanen?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $ord['id'] ?>">
                                                <button type="submit" class="action-btn action-btn--danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h3>Belum ada pesanan</h3>
                        <p><?= ($search||$filterStatus) ? 'Tidak ada pesanan yang sesuai filter.' : 'Belum ada pesanan masuk dari pelanggan.' ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<!-- Modal Detail -->
<div class="modal-overlay" id="detailModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Detail Pesanan <span id="mdlOrderId" class="order-id" style="margin-left:0.5rem;"></span></h2>
            <button class="modal-close" onclick="closeDetail()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="detail-row">
                <span class="detail-label">Tanggal Masuk:</span>
                <span class="detail-val" id="mdlDate"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Nama Pelanggan:</span>
                <span class="detail-val" id="mdlName"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">No. WhatsApp:</span>
                <span class="detail-val" id="mdlPhone"></span>
            </div>

            <div id="mdlNotesWrap" class="detail-notes" style="display:none;">
                <strong>Catatan Pelanggan:</strong><br>
                <span id="mdlNotes"></span>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item Menu</th>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="mdlItems">
                    <!-- JS Injected -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align:right; font-weight:700; padding-top:1rem; border-bottom:none;">Total Keseluruhan:</td>
                        <td style="text-align:right; border-bottom:none; padding-top:1rem; color:var(--orange);" class="t-price" id="mdlTotal"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="modal-footer">
            <form method="POST" action="orders.php" class="status-form">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="mdlUpdateId">
                <select name="status" id="mdlStatusSelect" class="filter-select" style="min-width:140px;">
                    <option value="pending">⏳ Pending</option>
                    <option value="confirmed">🔥 Confirmed</option>
                    <option value="completed">✅ Selesai</option>
                    <option value="cancelled">❌ Batal</option>
                </select>
                <button type="submit" class="btn-update">Update Status</button>
            </form>
        </div>
    </div>
</div>

<script>
    const sidebar = document.getElementById('sidebar'), overlay = document.getElementById('sidebarOverlay'), toggleBtn = document.getElementById('sidebarToggle');
    if(toggleBtn) toggleBtn.addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('visible'); });
    if(overlay) overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('visible'); });

    const alertEl = document.getElementById('pageAlert');
    if(alertEl) { setTimeout(() => { alertEl.style.opacity = '0'; setTimeout(() => alertEl.remove(), 500); }, 4000); }

    const modal = document.getElementById('detailModal');

    window.openDetail = function(order, items) {
        document.getElementById('mdlOrderId').textContent = '#ORD-' + String(order.id).padStart(4, '0');
        document.getElementById('mdlDate').textContent = new Date(order.order_date).toLocaleString('id-ID');
        document.getElementById('mdlName').textContent = order.customer_name;
        document.getElementById('mdlPhone').textContent = order.customer_phone;
        
        const notesWrap = document.getElementById('mdlNotesWrap');
        if(order.notes) {
            document.getElementById('mdlNotes').textContent = order.notes;
            notesWrap.style.display = 'block';
        } else {
            notesWrap.style.display = 'none';
        }

        document.getElementById('mdlTotal').textContent = 'Rp' + parseInt(order.total_price).toLocaleString('id-ID');
        
        document.getElementById('mdlUpdateId').value = order.id;
        document.getElementById('mdlStatusSelect').value = order.order_status;

        const tbody = document.getElementById('mdlItems');
        tbody.innerHTML = '';
        if(items.length > 0) {
            items.forEach(it => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${it.product_name || 'Produk Terhapus'}</strong><br><small style="color:var(--text-muted);">@ Rp${parseInt(it.price).toLocaleString('id-ID')}</small></td>
                    <td style="text-align:center;">x${it.quantity}</td>
                    <td style="text-align:right;" class="t-price">Rp${parseInt(it.subtotal).toLocaleString('id-ID')}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:var(--text-muted); font-style:italic;">Data item tidak ditemukan.</td></tr>';
        }

        modal.classList.add('open');
    };

    window.closeDetail = function() { modal.classList.remove('open'); };
    modal.addEventListener('click', function(e) { if(e.target === this) closeDetail(); });
</script>
</body>
</html>
