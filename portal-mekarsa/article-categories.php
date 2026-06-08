<?php
/**
 * Mekarsa Coffee Bar - Admin Kelola Kategori Artikel
 * CRUD kategori artikel: tambah, edit, hapus
 */

session_start();

// Auth check
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once dirname(__DIR__) . '/src/config/database.php';

// =====================================================================
// BACKEND: Proses POST Requests
// =====================================================================
$flashMsg   = '';
$flashType  = 'success';
$formErrors = [];
$editData   = null;

try {
    $pdo = getDBConnection();

    // ----- ACTION: ADD CATEGORY -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) $formErrors['name'] = 'Nama kategori wajib diisi.';

        if (empty($formErrors)) {
            $check = $pdo->prepare("SELECT id FROM article_categories WHERE name = ?");
            $check->execute([$name]);
            if ($check->fetch()) {
                $formErrors['name'] = 'Kategori dengan nama ini sudah ada.';
            }
        }

        if (empty($formErrors)) {
            $stmt = $pdo->prepare("INSERT INTO article_categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            header('Location: article-categories.php?msg=added');
            exit;
        } else {
            $flashMsg  = 'Terdapat kesalahan pada form. Periksa kembali.';
            $flashType = 'error';
        }
    }

    // ----- ACTION: EDIT CATEGORY -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
        $id          = (int)($_POST['id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($id <= 0)     $formErrors['id']   = 'ID kategori tidak valid.';
        if (empty($name)) $formErrors['name'] = 'Nama kategori wajib diisi.';

        if (empty($formErrors)) {
            $check = $pdo->prepare("SELECT id FROM article_categories WHERE name = ? AND id != ?");
            $check->execute([$name, $id]);
            if ($check->fetch()) {
                $formErrors['name'] = 'Kategori dengan nama ini sudah ada.';
            }
        }

        if (empty($formErrors)) {
            $stmt = $pdo->prepare("UPDATE article_categories SET name=?, description=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$name, $description, $id]);
            header('Location: article-categories.php?msg=updated');
            exit;
        } else {
            $flashType = 'error';
            $flashMsg  = 'Terdapat kesalahan pada form. Periksa kembali.';
            $editData  = ['id' => $id, 'name' => $name, 'description' => $description];
        }
    }

    // ----- ACTION: DELETE CATEGORY -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ?");
            $check->execute([$id]);
            $count = (int)$check->fetchColumn();

            if ($count > 0) {
                header('Location: article-categories.php?msg=error_in_use');
                exit;
            } else {
                $pdo->prepare("DELETE FROM article_categories WHERE id = ?")->execute([$id]);
                header('Location: article-categories.php?msg=deleted');
                exit;
            }
        }
        header('Location: article-categories.php?msg=error');
        exit;
    }

    // ----- GET: Load edit data -----
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
        $editId = (int)$_GET['edit'];
        if ($editId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM article_categories WHERE id = ?");
            $stmt->execute([$editId]);
            $editData = $stmt->fetch();
            if (!$editData) $editData = null;
        }
    }

    // ----- Flash messages -----
    $msgMap = [
        'added'        => ['Kategori berhasil ditambahkan!', 'success'],
        'updated'      => ['Kategori berhasil diperbarui!', 'success'],
        'deleted'      => ['Kategori berhasil dihapus.', 'success'],
        'error_in_use' => ['Gagal menghapus. Masih ada artikel di dalam kategori ini.', 'error'],
        'error'        => ['Terjadi kesalahan. Silakan coba lagi.', 'error'],
    ];
    if (isset($_GET['msg']) && isset($msgMap[$_GET['msg']])) {
        [$flashMsg, $flashType] = $msgMap[$_GET['msg']];
    }

    // ----- FETCH: Semua kategori -----
    $search = trim($_GET['search'] ?? '');
    
    $whereSQL = '';
    $params = [];
    if (!empty($search)) {
        $whereSQL = "WHERE name LIKE ? OR description LIKE ?";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $listStmt = $pdo->prepare("
        SELECT ac.*, COUNT(a.id) AS article_count
        FROM article_categories ac
        LEFT JOIN articles a ON ac.id = a.category_id
        $whereSQL
        GROUP BY ac.id
        ORDER BY ac.name ASC
    ");
    $listStmt->execute($params);
    $categories = $listStmt->fetchAll();

    $totalRows = count($categories);

    $totalCatStmt = $pdo->query("SELECT COUNT(*) FROM article_categories");
    $totalCategoriesCount = $totalCatStmt->fetchColumn();

} catch (PDOException $e) {
    $categories = [];
    $totalRows  = 0;
    $totalCategoriesCount = 0;
    if (empty($flashMsg)) {
        $flashMsg  = 'Database error: ' . $e->getMessage();
        $flashType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Artikel — Mekarsa Admin</title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700;800&family=Inter:wght@400;500;600&family=Anton&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ===== DESIGN TOKENS ===== */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #0a0a0a; --bg-sidebar: #0d0d0d; --bg-card: #111111; --bg-input: #1a1a1a;
            --border: #27272a; --orange: #F27121; --orange-dark: #e06010; --orange-glow: rgba(242, 113, 33, 0.15);
            --text: #ffffff; --text-muted: #a1a1aa; --green: #22c55e; --blue: #3b82f6; --yellow: #f59e0b; --red: #f87171;
            --sidebar-w: 260px; --topbar-h: 65px;
            --font-head: 'Poppins', sans-serif; --font-body: 'Inter', sans-serif; --font-price: 'Anton', sans-serif;
        }
        html, body { height: 100%; }
        body { font-family: var(--font-body); background: var(--bg); color: var(--text); display: flex; min-height: 100vh; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }
        button { font-family: var(--font-body); }

        /* ===== SIDEBAR ===== */
        .sidebar { width: var(--sidebar-w); background: var(--bg-sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; transition: transform 0.3s ease; overflow-y: auto; }
        .sidebar-brand { padding: 1.5rem 1.5rem 1.25rem; display: flex; align-items: center; gap: 0.75rem; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .sidebar-brand-icon { width: 38px; height: 38px; background: var(--orange); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff; flex-shrink: 0; box-shadow: 0 0 12px rgba(242,113,33,0.3); }
        .sidebar-brand-text { font-family: var(--font-head); font-weight: 800; font-size: 1.2rem; }
        .sidebar-brand-text span { color: var(--orange); }
        .sidebar-brand-sub { font-size: 0.7rem; color: var(--text-muted); }
        .sidebar-nav { flex: 1; padding: 1.25rem 0; }
        .nav-section-label { padding: 0.5rem 1.5rem 0.3rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); opacity: 0.6; }
        .nav-item { display: flex; align-items: center; gap: 0.8rem; padding: 0.7rem 1.5rem; color: var(--text-muted); font-size: 0.9rem; font-weight: 500; transition: all 0.2s; position: relative; cursor: pointer; }
        .nav-item:hover { color: var(--text); background: rgba(255,255,255,0.04); }
        .nav-item.active { color: var(--orange); background: var(--orange-glow); }
        .nav-item.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--orange); border-radius: 0 2px 2px 0; }
        .nav-item i { width: 18px; text-align: center; font-size: 0.95rem; flex-shrink: 0; }
        .sidebar-footer { padding: 1.25rem 1.5rem; border-top: 1px solid var(--border); flex-shrink: 0; }
        .admin-info { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
        .admin-avatar { width: 36px; height: 36px; border-radius: 10px; background: var(--orange); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; color: #fff; font-family: var(--font-head); flex-shrink: 0; }
        .admin-name { font-weight: 600; font-size: 0.9rem; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .admin-role { font-size: 0.75rem; color: var(--text-muted); }
        .btn-logout { display: flex; align-items: center; gap: 0.6rem; padding: 0.6rem 1rem; background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.2); border-radius: 8px; color: var(--red); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; width: 100%; text-decoration: none; }
        .btn-logout:hover { background: rgba(248,113,113,0.15); border-color: rgba(248,113,113,0.4); }

        /* ===== MAIN ===== */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* ===== TOPBAR ===== */
        .topbar { height: var(--topbar-h); background: rgba(10,10,10,0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; position: sticky; top: 0; z-index: 50; }
        .topbar-left { display: flex; align-items: center; gap: 1rem; }
        .sidebar-toggle { display: none; width: 38px; height: 38px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text); cursor: pointer; align-items: center; justify-content: center; font-size: 1rem; }
        .breadcrumb { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--text-muted); }
        .breadcrumb a { color: var(--text-muted); }
        .breadcrumb a:hover { color: var(--orange); }
        .breadcrumb span { color: var(--text); font-weight: 600; }
        .topbar-right { display: flex; align-items: center; gap: 1rem; }
        .topbar-time { font-size: 0.82rem; color: var(--text-muted); }
        .topbar-view-site { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.82rem; color: var(--text-muted); transition: all 0.2s; }
        .topbar-view-site:hover { border-color: var(--orange); color: var(--orange); }

        /* ===== PAGE CONTENT ===== */
        .page-content { padding: 2rem; flex: 1; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .page-header-title h1 { font-family: var(--font-head); font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 0.25rem; }
        .page-header-title p { color: var(--text-muted); font-size: 0.9rem; }
        .btn-add { display: inline-flex; align-items: center; gap: 0.6rem; padding: 0.75rem 1.4rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-family: var(--font-head); font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
        .btn-add:hover { background: var(--orange-dark); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(242,113,33,0.35); }

        /* ===== ALERT ===== */
        .page-alert { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.9rem 1.1rem; border-radius: 10px; font-size: 0.9rem; margin-bottom: 1.75rem; animation: fadeIn 0.4s ease; }
        .page-alert i { margin-top: 2px; flex-shrink: 0; }
        .page-alert--success { background: rgba(74,222,128,0.08); border: 1px solid rgba(74,222,128,0.25); color: var(--green); }
        .page-alert--error   { background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.25); color: var(--red); }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

        /* ===== STATS & FILTER ===== */
        .top-widgets { display: grid; grid-template-columns: 300px 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        @media(max-width: 900px) { .top-widgets { grid-template-columns: 1fr; } }
        
        .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1.25rem; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(242,113,33,0.15); color: var(--orange); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-val { font-family: var(--font-price); font-size: 2rem; line-height: 1; }
        .stat-lbl { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        .filter-bar { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; }
        .filter-bar form { display: flex; align-items: center; gap: 0.75rem; width: 100%; }
        .search-group { position: relative; flex: 1; }
        .search-group i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
        .search-input { width: 100%; padding: 0.75rem 1rem 0.75rem 2.7rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.95rem; outline: none; transition: border-color 0.2s; }
        .search-input:focus { border-color: var(--orange); }
        .btn-filter { padding: 0.75rem 1.5rem; background: var(--bg-input); color: var(--text); border: 1px solid var(--border); border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
        .btn-filter:hover { border-color: var(--orange); color: var(--orange); }
        
        /* ===== TABLE CARD ===== */
        .table-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .table-card-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); }
        .table-card-header h3 { font-family: var(--font-head); font-size: 1rem; font-weight: 700; }
        .table-total { font-size: 0.82rem; color: var(--text-muted); }
        .table-wrapper { overflow-x: auto; }

        .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; min-width: 600px; }
        .data-table th { padding: 0.8rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border); }
        .data-table td { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); vertical-align: top; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: rgba(255,255,255,0.02); }

        .cat-name { font-weight: 600; color: var(--text); font-size: 1rem; margin-bottom: 0.2rem; display: flex; align-items: center; gap: 0.5rem; }
        .cat-desc { font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; }
        
        .badge { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; background: rgba(242,113,33,0.15); color: var(--orange); border: 1px solid rgba(242,113,33,0.3); }
        .badge--zero { background: rgba(161,161,170,0.1); color: var(--text-muted); border-color: rgba(161,161,170,0.2); }

        .action-group { display: flex; align-items: center; gap: 0.4rem; }
        .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-input); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; color: var(--text-muted); }
        .action-btn:hover { border-color: var(--orange); color: var(--orange); background: var(--orange-glow); }
        .action-btn--danger:hover { border-color: var(--red); color: var(--red); background: rgba(248,113,113,0.1); }

        .empty-state { text-align: center; padding: 4rem 1.5rem; color: var(--text-muted); }
        .empty-state-icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.2; }
        .empty-state h3 { font-family: var(--font-head); font-size: 1.2rem; margin-bottom: 0.5rem; color: var(--text); opacity: 0.5; }
        .empty-state p { font-size: 0.9rem; margin-bottom: 1.5rem; }

        /* ===== MODAL ===== */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 200; align-items: flex-start; justify-content: center; padding: 2rem 1rem; overflow-y: auto; backdrop-filter: blur(4px); }
        .modal-overlay.open { display: flex; }
        .modal { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 500px; margin: auto; animation: modalIn 0.3s ease; box-shadow: 0 24px 80px rgba(0,0,0,0.7); }
        @keyframes modalIn { from { opacity:0; transform: scale(0.96) translateY(20px); } to { opacity:1; transform:none; } }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 1.75rem; border-bottom: 1px solid var(--border); }
        .modal-title { font-family: var(--font-head); font-size: 1.15rem; font-weight: 800; }
        .modal-close { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: transparent; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .modal-close:hover { border-color: var(--red); color: var(--red); background: rgba(248,113,113,0.08); }
        .modal-body { padding: 1.75rem; }
        .modal-footer { padding: 1.25rem 1.75rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.75rem; }

        /* ===== FORM ===== */
        .form-group { margin-bottom: 1.25rem; }
        .form-group:last-child { margin-bottom: 0; }
        .form-label { display: block; font-weight: 600; font-size: 0.85rem; color: var(--text); margin-bottom: 0.45rem; }
        .form-label .req { color: var(--orange); }
        .form-input, .form-textarea { width: 100%; padding: 0.75rem 1rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; transition: all 0.2s; }
        .form-input:focus, .form-textarea:focus { border-color: var(--orange); box-shadow: 0 0 0 3px rgba(242,113,33,0.12); }
        .form-textarea { resize: vertical; min-height: 100px; }
        .form-input.is-error { border-color: var(--red); box-shadow: 0 0 0 3px rgba(248,113,113,0.1); }
        .field-error { font-size: 0.78rem; color: var(--red); margin-top: 0.3rem; display: block; }

        .btn-primary { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-family: var(--font-head); font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; }
        .btn-primary:hover { background: var(--orange-dark); }
        .btn-secondary { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: 8px; font-family: var(--font-body); font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; }
        .btn-secondary:hover { border-color: var(--text-muted); color: var(--text); }

        .confirm-modal { max-width: 400px; text-align: center; }
        .confirm-modal .modal-header { border-bottom: none; padding-bottom: 0; }
        .confirm-modal .modal-title { display: none; }
        .confirm-modal .modal-body { padding-top: 0.5rem; }
        .confirm-icon { font-size: 3.5rem; margin-bottom: 1rem; color: var(--red); opacity: 0.8; }
        .confirm-modal h3 { font-family: var(--font-head); font-size: 1.25rem; margin-bottom: 0.5rem; }
        .confirm-modal p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem; }
        .confirm-modal .modal-footer { justify-content: center; border-top: none; padding-top: 0; }
        .btn-danger { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: var(--red); color: #fff; border: none; border-radius: 8px; font-family: var(--font-head); font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; }
        .btn-danger:hover { background: #dc2626; }

        /* ===== MOBILE ===== */
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99; }
        .sidebar-overlay.visible { display: block; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-toggle { display: flex; }
            .main { margin-left: 0; }
            .topbar { padding: 0 1rem; }
            .page-content { padding: 1.25rem 1rem; }
            .topbar-time, .topbar-view-site { display: none; }
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="sidebar">
    <a href="dashboard.php" class="sidebar-brand">
        <div class="sidebar-brand-icon"><i class="fas fa-mug-hot"></i></div>
        <div>
            <div class="sidebar-brand-text">Mekarsa<span>.</span></div>
            <div class="sidebar-brand-sub">Admin Panel</div>
        </div>
    </a>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Utama</div>
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-gauge-high"></i> Dashboard
        </a>

        <div class="nav-section-label" style="margin-top:0.75rem;">Kelola Konten</div>
        <a href="products.php" class="nav-item">
            <i class="fas fa-mug-saucer"></i> Produk Coffee
        </a>
        <a href="product-categories.php" class="nav-item">
            <i class="fas fa-tags"></i> Kategori Produk
        </a>
        <a href="articles.php" class="nav-item">
            <i class="fas fa-newspaper"></i> Artikel
        </a>
        <a href="article-categories.php" class="nav-item active">
            <i class="fas fa-folder-open"></i> Kategori Artikel
        </a>
        <a href="gallery.php" class="nav-item">
            <i class="fas fa-images"></i> Galeri
        </a>
        <a href="testimonials.php" class="nav-item">
            <i class="fas fa-star"></i> Testimoni
        </a>

        <div class="nav-section-label" style="margin-top:0.75rem;">Operasional</div>
        <a href="orders.php" class="nav-item">
            <i class="fas fa-receipt"></i> Pesanan
        </a>
        <a href="support-services.php" class="nav-item">
            <i class="fas fa-shoe-prints"></i> Layanan Pendukung
        </a>
        <a href="settings.php" class="nav-item">
            <i class="fas fa-gear"></i> Pengaturan Website
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
            <i class="fas fa-right-from-bracket"></i> Logout
        </a>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===== MAIN ===== -->
<div class="main">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <nav class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-house" style="font-size:0.8rem;"></i></a>
                <i class="fas fa-chevron-right" style="font-size:0.65rem;"></i>
                <span>Kategori Artikel</span>
            </nav>
        </div>
        <div class="topbar-right">
            <span class="topbar-time" id="currentTime"></span>
            <a href="../articles.php" class="topbar-view-site" target="_blank">
                <i class="fas fa-arrow-up-right-from-square"></i> Lihat Artikel
            </a>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-title">
                <h1><i class="fas fa-folder-open" style="color:var(--orange);margin-right:0.5rem;font-size:1.4rem;"></i>Kategori Artikel</h1>
                <p>Kelola klasifikasi atau topik-topik artikel blog.</p>
            </div>
            <button class="btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Tambah Kategori
            </button>
        </div>

        <!-- Flash Alert -->
        <?php if (!empty($flashMsg)): ?>
            <div class="page-alert page-alert--<?= $flashType ?>" id="pageAlert">
                <i class="fas <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <span><?= $flashMsg ?></span>
            </div>
        <?php endif; ?>

        <!-- Stats & Filter -->
        <div class="top-widgets">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
                <div>
                    <div class="stat-val"><?= $totalCategoriesCount ?></div>
                    <div class="stat-lbl">Total Kategori</div>
                </div>
            </div>
            <div class="filter-bar">
                <form method="GET" action="article-categories.php">
                    <div class="search-group">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" name="search" class="search-input" placeholder="Cari nama kategori..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button type="submit" class="btn-filter">Cari</button>
                    <?php if ($search): ?>
                        <a href="article-categories.php" class="btn-secondary" style="border:none;background:transparent;"><i class="fas fa-xmark"></i> Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-card-header">
                <h3>Daftar Kategori Artikel</h3>
                <span class="table-total"><?= $totalRows ?> kategori ditemukan</span>
            </div>

            <div class="table-wrapper">
                <?php if (!empty($categories)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Informasi Kategori</th>
                                <th style="width: 150px; text-align: center;">Jumlah Artikel</th>
                                <th style="width: 150px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $i => $cat): ?>
                                <tr>
                                    <td style="color:var(--text-muted);font-size:0.8rem;"><?= $i + 1 ?></td>
                                    <td>
                                        <div class="cat-name">
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </div>
                                        <?php if (!empty($cat['description'])): ?>
                                            <div class="cat-desc"><?= nl2br(htmlspecialchars($cat['description'])) ?></div>
                                        <?php else: ?>
                                            <div class="cat-desc" style="font-style:italic;opacity:0.5;">Tidak ada deskripsi</div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge <?= $cat['article_count'] == 0 ? 'badge--zero' : '' ?>">
                                            <?= $cat['article_count'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group" style="justify-content: center;">
                                            <button class="action-btn" onclick="openEditModal(<?= htmlspecialchars(json_encode($cat)) ?>)" title="Edit">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button class="action-btn action-btn--danger" onclick="confirmDelete(<?= $cat['id'] ?>, '<?= addslashes(htmlspecialchars($cat['name'])) ?>', <?= $cat['article_count'] ?>)" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
                        <h3>Belum ada kategori artikel</h3>
                        <p><?= $search ? 'Tidak ada kategori yang cocok dengan pencarian.' : 'Silakan tambahkan kategori untuk mengelompokkan artikel Anda.' ?></p>
                        <?php if (!$search): ?>
                            <button class="btn-primary" onclick="openAddModal()">
                                <i class="fas fa-plus"></i> Tambah Kategori
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div><!-- /main -->

<!-- ===== ADD / EDIT MODAL ===== -->
<div class="modal-overlay" id="catModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Tambah Kategori</h2>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="catForm" method="POST" action="article-categories.php" novalidate>
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId" value="">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="name">Nama Kategori <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="form-input <?= isset($formErrors['name']) ? 'is-error' : '' ?>"
                           placeholder="Contoh: Tips & Trick" required
                           value="<?= htmlspecialchars($editData['name'] ?? $_POST['name'] ?? '') ?>">
                    <?php if (isset($formErrors['name'])): ?><span class="field-error"><?= $formErrors['name'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Deskripsi Singkat</label>
                    <textarea id="description" name="description" class="form-textarea"
                              placeholder="Penjelasan tentang kategori ini..."><?= htmlspecialchars($editData['description'] ?? $_POST['description'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary" id="formSubmitBtn">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== DELETE CONFIRM MODAL ===== -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal confirm-modal">
        <div class="modal-header">
            <button class="modal-close" onclick="closeDeleteModal()" style="position:absolute; right:1.5rem; top:1.5rem;"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="confirm-icon"><i class="fas fa-circle-exclamation"></i></div>
            <h3>Hapus Kategori?</h3>
            <p>Anda akan menghapus kategori <strong id="deleteCatName"></strong>.</p>
            
            <div id="deleteWarning" style="display:none; background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.3); color:var(--red); padding:0.75rem; border-radius:8px; font-size:0.85rem; margin-bottom:1.5rem;">
                <i class="fas fa-triangle-exclamation"></i> Kategori ini tidak bisa dihapus karena masih memiliki <strong id="deleteCount"></strong> artikel.
            </div>

        </div>
        <div class="modal-footer">
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteCatId" value="">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Batal</button>
                <button type="submit" class="btn-danger" id="btnConfirmDelete">Ya, Hapus</button>
            </form>
        </div>
    </div>
</div>

<?php
$autoOpenModal = !empty($formErrors) ? 'true' : 'false';
$autoOpenEdit  = ($editData !== null && !empty($formErrors) && ($_POST['action'] ?? '') === 'edit') ? 'true' : 'false';
$editDataJSON  = $editData ? json_encode($editData) : 'null';
?>

<script>
(function () {
    'use strict';

    /* ===== SIDEBAR MOBILE ===== */
    const sidebar = document.getElementById('sidebar'), overlay = document.getElementById('sidebarOverlay'), toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) toggleBtn.addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('visible'); });
    if (overlay) overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('visible'); });

    /* ===== LIVE CLOCK ===== */
    const timeEl = document.getElementById('currentTime');
    function updateClock() { if (timeEl) timeEl.textContent = new Date().toLocaleString('id-ID', { weekday:'short', day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' }); }
    updateClock(); setInterval(updateClock, 60000);

    /* ===== AUTO DISMISS ALERT ===== */
    const alertEl = document.getElementById('pageAlert');
    if (alertEl) { setTimeout(() => { alertEl.style.opacity = '0'; setTimeout(() => alertEl.remove(), 500); }, 4000); }

    /* ===== MODAL ===== */
    const catModalEl = document.getElementById('catModal');
    const deleteModalEl = document.getElementById('deleteModal');

    window.openAddModal = function () {
        document.getElementById('modalTitle').textContent = 'Tambah Kategori';
        document.getElementById('formAction').value = 'add';
        document.getElementById('formId').value = '';
        document.getElementById('catForm').reset();
        
        catModalEl.classList.add('open');
        setTimeout(() => document.getElementById('name').focus(), 100);
    };

    window.openEditModal = function (cat) {
        document.getElementById('modalTitle').textContent = 'Edit Kategori';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('formId').value = cat.id;
        document.getElementById('name').value = cat.name || '';
        document.getElementById('description').value = cat.description || '';
        
        catModalEl.classList.add('open');
        setTimeout(() => document.getElementById('name').focus(), 100);
    };

    window.closeModal = function () { catModalEl.classList.remove('open'); };

    window.confirmDelete = function (id, name, count) {
        document.getElementById('deleteCatId').value = id;
        document.getElementById('deleteCatName').textContent = name;
        
        const warningEl = document.getElementById('deleteWarning');
        const countEl = document.getElementById('deleteCount');
        const btnDelete = document.getElementById('btnConfirmDelete');
        
        if (count > 0) {
            countEl.textContent = count;
            warningEl.style.display = 'block';
            btnDelete.style.display = 'none';
        } else {
            warningEl.style.display = 'none';
            btnDelete.style.display = 'inline-flex';
        }

        deleteModalEl.classList.add('open');
    };

    window.closeDeleteModal = function () { deleteModalEl.classList.remove('open'); };

    catModalEl.addEventListener('click', function(e) { if(e.target===this) closeModal(); });
    deleteModalEl.addEventListener('click', function(e) { if(e.target===this) closeDeleteModal(); });
    document.addEventListener('keydown', function(e) { if(e.key==='Escape') { closeModal(); closeDeleteModal(); } });

    /* ===== VALIDATION ===== */
    const catForm = document.getElementById('catForm');
    if (catForm) {
        catForm.addEventListener('submit', function (e) {
            const nameEl = document.getElementById('name');
            nameEl.classList.remove('is-error');
            const prev = nameEl.parentElement.querySelector('.field-error');
            if (prev) prev.remove();

            if (!nameEl.value.trim()) {
                e.preventDefault();
                nameEl.classList.add('is-error');
                const span = document.createElement('span');
                span.className = 'field-error';
                span.textContent = 'Nama kategori wajib diisi.';
                nameEl.parentElement.appendChild(span);
            }
        });
    }

    /* ===== AUTO OPEN MODAL ON ERROR ===== */
    const autoOpen = <?= $autoOpenModal ?>;
    const autoEdit = <?= $autoOpenEdit ?>;
    const editData = <?= $editDataJSON ?>;

    if (autoOpen) {
        if (autoEdit && editData) openEditModal(editData);
        else openAddModal();
    }
})();
</script>

</body>
</html>
