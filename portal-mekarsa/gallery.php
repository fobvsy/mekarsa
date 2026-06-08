<?php
/**
 * Mekarsa Coffee Bar - Admin Kelola Galeri
 * CRUD Galeri: tambah foto, edit, hapus, filter kategori
 */

session_start();

// Auth check
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once dirname(__DIR__) . '/src/config/database.php';

// =====================================================================
// UPLOAD HELPER
// =====================================================================
define('UPLOAD_DIR',  dirname(__DIR__) . '/public/uploads/gallery/');
define('UPLOAD_URL',  '../public/uploads/gallery/');
define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'webp']);
define('MAX_SIZE',    5 * 1024 * 1024); // 5 MB

function handleImageUpload(array $file, ?string $oldImage = null): array
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => $oldImage];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Gagal mengunggah file.'];
    }
    if ($file['size'] > MAX_SIZE) {
        return ['success' => false, 'error' => 'Ukuran file maksimal 5 MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXT)) {
        return ['success' => false, 'error' => 'Format file harus JPG, JPEG, PNG, atau WEBP.'];
    }

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
        return ['success' => false, 'error' => 'File harus berupa gambar.'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $newFilename = 'gal_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newFilename)) {
        return ['success' => false, 'error' => 'Gagal menyimpan file ke server.'];
    }

    if ($oldImage && file_exists(UPLOAD_DIR . $oldImage)) {
        @unlink(UPLOAD_DIR . $oldImage);
    }

    return ['success' => true, 'filename' => $newFilename];
}

// =====================================================================
// BACKEND: Proses POST Requests
// =====================================================================
$flashMsg   = '';
$flashType  = 'success';
$formErrors = [];
$editData   = null;

try {
    $pdo = getDBConnection();

    // Auto-create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NULL,
        image VARCHAR(255) NOT NULL,
        category ENUM('interior', 'exterior', 'events', 'products', 'others') DEFAULT 'others',
        status ENUM('show', 'hide') DEFAULT 'show',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // ----- ACTION: ADD FOTO -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
        $title    = trim($_POST['title'] ?? '');
        $category = in_array($_POST['category'] ?? '', ['interior', 'exterior', 'events', 'products', 'others']) ? $_POST['category'] : 'others';
        $status   = in_array($_POST['status'] ?? '', ['show', 'hide']) ? $_POST['status'] : 'show';

        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            $formErrors['image'] = 'Gambar wajib diunggah.';
        }

        if (empty($formErrors)) {
            $uploadResult = handleImageUpload($_FILES['image']);
            if (!$uploadResult['success']) {
                $formErrors['image'] = $uploadResult['error'];
            } else {
                $imageName = $uploadResult['filename'];
                $stmt = $pdo->prepare("INSERT INTO gallery (title, image, category, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $imageName, $category, $status]);
                header('Location: gallery.php?msg=added');
                exit;
            }
        }
        if (!empty($formErrors)) {
            $flashMsg  = 'Terdapat kesalahan pada form. Periksa kembali.';
            $flashType = 'error';
        }
    }

    // ----- ACTION: EDIT FOTO -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $category = in_array($_POST['category'] ?? '', ['interior', 'exterior', 'events', 'products', 'others']) ? $_POST['category'] : 'others';
        $status   = in_array($_POST['status'] ?? '', ['show', 'hide']) ? $_POST['status'] : 'show';

        if ($id <= 0) $formErrors['id'] = 'ID galeri tidak valid.';

        if (empty($formErrors)) {
            $existingStmt = $pdo->prepare("SELECT image FROM gallery WHERE id = ?");
            $existingStmt->execute([$id]);
            $oldImage  = $existingStmt->fetchColumn();

            $uploadResult = handleImageUpload($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], $oldImage);
            if (!$uploadResult['success']) {
                $formErrors['image'] = $uploadResult['error'];
            } else {
                $imageName = $uploadResult['filename'];
                $stmt = $pdo->prepare("UPDATE gallery SET title=?, image=?, category=?, status=? WHERE id=?");
                $stmt->execute([$title, $imageName, $category, $status, $id]);
                header('Location: gallery.php?msg=updated');
                exit;
            }
        }
        if (!empty($formErrors)) {
            $flashType = 'error';
            $flashMsg  = 'Terdapat kesalahan. Periksa kembali.';
            $existingStmt = $pdo->prepare("SELECT image FROM gallery WHERE id = ?");
            $existingStmt->execute([$id]);
            $editData = [
                'id'       => $id,
                'title'    => $title,
                'category' => $category,
                'status'   => $status,
                'image'    => $existingStmt->fetchColumn()
            ];
        }
    }

    // ----- ACTION: DELETE FOTO -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT image FROM gallery WHERE id = ?");
            $stmt->execute([$id]);
            $img = $stmt->fetchColumn();
            if ($img) {
                $pdo->prepare("DELETE FROM gallery WHERE id = ?")->execute([$id]);
                if (file_exists(UPLOAD_DIR . $img)) {
                    @unlink(UPLOAD_DIR . $img);
                }
                header('Location: gallery.php?msg=deleted');
                exit;
            }
        }
        header('Location: gallery.php?msg=error');
        exit;
    }

    // ----- GET: Load edit data -----
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
        $editId = (int)$_GET['edit'];
        if ($editId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM gallery WHERE id = ?");
            $stmt->execute([$editId]);
            $editData = $stmt->fetch();
            if (!$editData) $editData = null;
        }
    }

    // ----- Flash messages -----
    $msgMap = [
        'added'   => ['Foto berhasil ditambahkan ke galeri!', 'success'],
        'updated' => ['Informasi foto berhasil diperbarui!', 'success'],
        'deleted' => ['Foto berhasil dihapus dari galeri.', 'success'],
        'error'   => ['Terjadi kesalahan. Silakan coba lagi.', 'error'],
    ];
    if (isset($_GET['msg']) && isset($msgMap[$_GET['msg']])) {
        [$flashMsg, $flashType] = $msgMap[$_GET['msg']];
    }

    // ----- FETCH: Semua galeri -----
    $filterCat = $_GET['category'] ?? '';
    $where  = [];
    $params = [];

    if (in_array($filterCat, ['interior', 'exterior', 'events', 'products', 'others'])) {
        $where[]  = "category = ?";
        $params[] = $filterCat;
    }

    $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $listStmt = $pdo->prepare("SELECT * FROM gallery $whereSQL ORDER BY created_at DESC");
    $listStmt->execute($params);
    $galleries = $listStmt->fetchAll();

    $statsStmt = $pdo->query("SELECT COUNT(*) AS total, SUM(status='show') AS show_count FROM gallery");
    $stats = $statsStmt->fetch();

} catch (PDOException $e) {
    $galleries = [];
    $stats = ['total' => 0, 'show_count' => 0];
    if (empty($flashMsg)) {
        $flashMsg  = 'Database error: ' . $e->getMessage();
        $flashType = 'error';
    }
}

$categoryLabels = [
    'interior' => 'Interior',
    'exterior' => 'Eksterior',
    'events'   => 'Event / Acara',
    'products' => 'Produk',
    'others'   => 'Lainnya'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri — Mekarsa Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700;800&family=Inter:wght@400;500;600&family=Anton&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS reset & variables sama seperti sebelumnya */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #0a0a0a; --bg-sidebar: #0d0d0d; --bg-card: #111111; --bg-input: #1a1a1a;
            --border: #27272a; --orange: #F27121; --orange-dark: #e06010; --orange-glow: rgba(242, 113, 33, 0.15);
            --text: #ffffff; --text-muted: #a1a1aa; --green: #22c55e; --blue: #3b82f6; --red: #f87171;
            --sidebar-w: 260px; --topbar-h: 65px;
            --font-head: 'Poppins', sans-serif; --font-body: 'Inter', sans-serif;
        }
        html, body { height: 100%; }
        body { font-family: var(--font-body); background: var(--bg); color: var(--text); display: flex; min-height: 100vh; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }
        button { font-family: var(--font-body); }

        /* Sidebar & Topbar */
        .sidebar { width: var(--sidebar-w); background: var(--bg-sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-brand { padding: 1.5rem; display: flex; align-items: center; gap: 0.75rem; border-bottom: 1px solid var(--border); }
        .sidebar-brand-icon { width: 38px; height: 38px; background: var(--orange); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff; box-shadow: 0 0 12px rgba(242,113,33,0.3); }
        .sidebar-brand-text { font-family: var(--font-head); font-weight: 800; font-size: 1.2rem; }
        .sidebar-brand-text span { color: var(--orange); }
        .sidebar-nav { flex: 1; padding: 1.25rem 0; overflow-y: auto; }
        .nav-section-label { padding: 0.5rem 1.5rem 0.3rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); opacity: 0.6; }
        .nav-item { display: flex; align-items: center; gap: 0.8rem; padding: 0.7rem 1.5rem; color: var(--text-muted); font-size: 0.9rem; font-weight: 500; transition: all 0.2s; position: relative; }
        .nav-item:hover { color: var(--text); background: rgba(255,255,255,0.04); }
        .nav-item.active { color: var(--orange); background: var(--orange-glow); }
        .nav-item.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--orange); border-radius: 0 2px 2px 0; }
        .nav-item i { width: 18px; text-align: center; font-size: 0.95rem; }
        .sidebar-footer { padding: 1.25rem 1.5rem; border-top: 1px solid var(--border); }
        .btn-logout { display: flex; align-items: center; gap: 0.6rem; padding: 0.6rem 1rem; background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.2); border-radius: 8px; color: var(--red); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; width: 100%; justify-content: center; }

        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar { height: var(--topbar-h); background: rgba(10,10,10,0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; position: sticky; top: 0; z-index: 50; }
        .topbar-left { display: flex; align-items: center; gap: 1rem; }
        .sidebar-toggle { display: none; width: 38px; height: 38px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text); cursor: pointer; align-items: center; justify-content: center; }
        .breadcrumb { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--text-muted); }
        .breadcrumb span { color: var(--text); font-weight: 600; }
        .topbar-view-site { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.82rem; color: var(--text-muted); transition: all 0.2s; }
        .topbar-view-site:hover { border-color: var(--orange); color: var(--orange); }

        /* Page specific */
        .page-content { padding: 2rem; flex: 1; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .page-header-title h1 { font-family: var(--font-head); font-size: 1.75rem; font-weight: 800; margin-bottom: 0.25rem; }
        .page-header-title p { color: var(--text-muted); font-size: 0.9rem; }
        .btn-add { display: inline-flex; align-items: center; gap: 0.6rem; padding: 0.75rem 1.4rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .btn-add:hover { background: var(--orange-dark); }

        .page-alert { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.9rem 1.1rem; border-radius: 10px; font-size: 0.9rem; margin-bottom: 1.75rem; }
        .page-alert--success { background: rgba(74,222,128,0.08); border: 1px solid rgba(74,222,128,0.25); color: var(--green); }
        .page-alert--error   { background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.25); color: var(--red); }

        .filter-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 0.5rem; }
        .filter-tab { padding: 0.6rem 1.25rem; border-radius: 999px; background: var(--bg-card); border: 1px solid var(--border); color: var(--text-muted); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
        .filter-tab:hover { color: var(--text); border-color: var(--text); }
        .filter-tab.active { background: var(--orange); color: #fff; border-color: var(--orange); }

        /* Gallery Grid */
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .gallery-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; position: relative; group; transition: transform 0.2s; }
        .gallery-card:hover { transform: translateY(-4px); border-color: var(--orange); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .gallery-img-wrap { aspect-ratio: 4/3; overflow: hidden; position: relative; background: var(--bg-input); }
        .gallery-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
        .gallery-card:hover .gallery-img { transform: scale(1.05); }
        .gallery-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent 50%); opacity: 0.8; }
        
        .gallery-status { position: absolute; top: 1rem; left: 1rem; z-index: 10; padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; backdrop-filter: blur(4px); }
        .gallery-status--show { background: rgba(34,197,94,0.2); color: var(--green); border: 1px solid rgba(34,197,94,0.3); }
        .gallery-status--hide { background: rgba(248,113,113,0.2); color: var(--red); border: 1px solid rgba(248,113,113,0.3); }
        
        .gallery-cat { position: absolute; top: 1rem; right: 1rem; z-index: 10; padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(4px); }

        .gallery-info { position: absolute; bottom: 0; left: 0; right: 0; padding: 1.25rem; z-index: 10; display: flex; justify-content: space-between; align-items: flex-end; }
        .gallery-title { font-family: var(--font-head); font-weight: 700; font-size: 1rem; color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.5); margin-bottom: 0.25rem; }
        .gallery-date { font-size: 0.75rem; color: rgba(255,255,255,0.7); }
        
        .gallery-actions { display: flex; gap: 0.4rem; opacity: 0; transform: translateY(10px); transition: all 0.2s; }
        .gallery-card:hover .gallery-actions { opacity: 1; transform: translateY(0); }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; border: none; background: rgba(255,255,255,0.1); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; color: #fff; }
        .action-btn:hover { background: var(--orange); }
        .action-btn--danger:hover { background: var(--red); }

        .empty-state { text-align: center; padding: 5rem 1.5rem; background: var(--bg-card); border: 1px dashed var(--border); border-radius: 12px; grid-column: 1 / -1; }
        .empty-state i { font-size: 3rem; color: var(--text-muted); opacity: 0.3; margin-bottom: 1rem; }
        .empty-state h3 { margin-bottom: 0.5rem; font-family: var(--font-head); }
        .empty-state p { color: var(--text-muted); font-size: 0.9rem; }

        /* Modal & Form */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 200; align-items: flex-start; justify-content: center; padding: 2rem 1rem; overflow-y: auto; backdrop-filter: blur(4px); }
        .modal-overlay.open { display: flex; }
        .modal { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 500px; margin: auto; animation: modalIn 0.3s ease; }
        @keyframes modalIn { from { opacity:0; transform: scale(0.96) translateY(20px); } to { opacity:1; transform:none; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid var(--border); }
        .modal-title { font-family: var(--font-head); font-size: 1.15rem; font-weight: 800; }
        .modal-close { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1.25rem 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.75rem; }

        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; }
        .form-input, .form-select { width: 100%; padding: 0.75rem 1rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); }
        .form-input:focus, .form-select:focus { border-color: var(--orange); outline: none; }
        .field-error { font-size: 0.78rem; color: var(--red); display: block; margin-top: 0.3rem; }
        
        .btn-primary { padding: 0.75rem 1.5rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-secondary { padding: 0.75rem 1.5rem; background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: 8px; cursor: pointer; }

        /* Upload */
        .upload-zone { border: 2px dashed var(--border); border-radius: 10px; padding: 2rem 1.5rem; text-align: center; cursor: pointer; position: relative; transition: 0.2s; }
        .upload-zone:hover { border-color: var(--orange); background: rgba(242,113,33,0.05); }
        .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .upload-icon { font-size: 2rem; color: var(--text-muted); margin-bottom: 0.5rem; opacity: 0.5; }
        .upload-text { font-size: 0.85rem; color: var(--text-muted); }
        .upload-preview { display: none; margin-top: 1rem; position: relative; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); }
        .upload-preview img { width: 100%; display: block; }
        .upload-preview-remove { position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(0,0,0,0.7); color: #fff; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-toggle { display: flex; }
            .main { margin-left: 0; }
            .gallery-actions { opacity: 1; transform: none; }
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
        <a href="gallery.php" class="nav-item active"><i class="fas fa-images"></i> Galeri</a>
        <a href="testimonials.php" class="nav-item"><i class="fas fa-star"></i> Testimoni</a>
        <div class="nav-section-label" style="margin-top:0.75rem;">Operasional</div>
        <a href="orders.php" class="nav-item"><i class="fas fa-receipt"></i> Pesanan</a>
        <a href="support-services.php" class="nav-item"><i class="fas fa-shoe-prints"></i> Layanan</a>
        <a href="settings.php" class="nav-item"><i class="fas fa-gear"></i> Pengaturan</a>
    </nav>
</aside>

<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <nav class="breadcrumb">
                <span>Galeri</span>
            </nav>
        </div>
    </header>

    <main class="page-content">
        <div class="page-header">
            <div class="page-header-title">
                <h1><i class="fas fa-images" style="color:var(--orange);margin-right:0.5rem;font-size:1.4rem;"></i>Galeri</h1>
                <p>Kelola foto suasana cafe, event, dan portfolio Mekarsa.</p>
            </div>
            <button class="btn-add" onclick="openAddModal()"><i class="fas fa-plus"></i> Upload Foto</button>
        </div>

        <?php if (!empty($flashMsg)): ?>
            <div class="page-alert page-alert--<?= $flashType ?>" id="pageAlert">
                <i class="fas <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <span><?= $flashMsg ?></span>
            </div>
        <?php endif; ?>

        <div class="filter-tabs">
            <a href="gallery.php" class="filter-tab <?= $filterCat === '' ? 'active' : '' ?>">Semua (<?= $stats['total'] ?>)</a>
            <?php foreach ($categoryLabels as $key => $label): ?>
                <a href="gallery.php?category=<?= $key ?>" class="filter-tab <?= $filterCat === $key ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

        <div class="gallery-grid">
            <?php if (!empty($galleries)): ?>
                <?php foreach ($galleries as $gal): ?>
                    <div class="gallery-card">
                        <div class="gallery-img-wrap">
                            <img src="<?= UPLOAD_URL . htmlspecialchars($gal['image']) ?>" class="gallery-img" alt="<?= htmlspecialchars($gal['title'] ?? 'Galeri') ?>">
                            <div class="gallery-overlay"></div>
                        </div>
                        
                        <?php if ($gal['status'] === 'hide'): ?>
                            <div class="gallery-status gallery-status--hide"><i class="fas fa-eye-slash"></i> Sembunyi</div>
                        <?php else: ?>
                            <div class="gallery-status gallery-status--show"><i class="fas fa-eye"></i> Tampil</div>
                        <?php endif; ?>

                        <div class="gallery-cat"><?= $categoryLabels[$gal['category']] ?? 'Lainnya' ?></div>

                        <div class="gallery-info">
                            <div>
                                <div class="gallery-title"><?= htmlspecialchars($gal['title'] ?: 'Tanpa Judul') ?></div>
                                <div class="gallery-date"><?= date('d M Y', strtotime($gal['created_at'])) ?></div>
                            </div>
                            <div class="gallery-actions">
                                <button class="action-btn" onclick="openEditModal(<?= htmlspecialchars(json_encode($gal)) ?>)"><i class="fas fa-pen"></i></button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus foto ini dari galeri?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $gal['id'] ?>">
                                    <button type="submit" class="action-btn action-btn--danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-images"></i>
                    <h3>Galeri Kosong</h3>
                    <p><?= $filterCat ? 'Tidak ada foto di kategori ini.' : 'Belum ada foto yang diunggah.' ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal ADD/EDIT -->
<div class="modal-overlay" id="galleryModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Upload Foto</h2>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="galleryForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Judul Foto <span style="color:var(--text-muted);font-weight:normal;">(Opsional)</span></label>
                    <input type="text" id="title" name="title" class="form-input" placeholder="Contoh: Suasana sore di Mekarsa">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <select id="category" name="category" class="form-select">
                        <?php foreach ($categoryLabels as $key => $label): ?>
                            <option value="<?= $key ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status Tampil</label>
                    <select id="status" name="status" class="form-select">
                        <option value="show">Tampilkan di Website</option>
                        <option value="hide">Sembunyikan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">File Gambar <span style="color:var(--orange);">*</span></label>
                    <div id="existingImgWrap" style="display:none; margin-bottom:0.75rem;">
                        <img id="existingImg" src="" style="height:60px; border-radius:6px; border:1px solid var(--border);">
                        <div style="font-size:0.75rem; color:var(--text-muted);">Gambar saat ini.</div>
                    </div>
                    <div class="upload-zone" id="uploadZone">
                        <input type="file" name="image" id="imageInput" accept=".jpg,.jpeg,.png,.webp">
                        <div id="uploadPlaceholder">
                            <div class="upload-icon"><i class="fas fa-cloud-arrow-up"></i></div>
                            <div class="upload-text"><strong>Klik untuk upload</strong> (JPG, PNG, WEBP max 5MB)</div>
                        </div>
                    </div>
                    <div class="upload-preview" id="uploadPreview">
                        <img id="previewImg" src="">
                        <button type="button" class="upload-preview-remove" onclick="removePreview()"><i class="fas fa-xmark"></i></button>
                    </div>
                    <?php if (isset($formErrors['image'])): ?>
                        <span class="field-error"><?= $formErrors['image'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Foto</button>
            </div>
        </form>
    </div>
</div>

<?php
$autoOpen = !empty($formErrors) ? 'true' : 'false';
$editDataJSON = $editData ? json_encode($editData) : 'null';
?>
<script>
    const modal = document.getElementById('galleryModal');
    
    window.openAddModal = function() {
        document.getElementById('modalTitle').textContent = 'Upload Foto Baru';
        document.getElementById('formAction').value = 'add';
        document.getElementById('formId').value = '';
        document.getElementById('galleryForm').reset();
        document.getElementById('existingImgWrap').style.display = 'none';
        removePreview();
        modal.classList.add('open');
    };

    window.openEditModal = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Informasi Foto';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('formId').value = data.id;
        document.getElementById('title').value = data.title || '';
        document.getElementById('category').value = data.category || 'others';
        document.getElementById('status').value = data.status || 'show';
        
        if (data.image) {
            document.getElementById('existingImg').src = '../public/uploads/gallery/' + data.image;
            document.getElementById('existingImgWrap').style.display = 'block';
        } else {
            document.getElementById('existingImgWrap').style.display = 'none';
        }
        removePreview();
        modal.classList.add('open');
    };

    window.closeModal = function() { modal.classList.remove('open'); };

    // Image Upload Preview
    const imgInput = document.getElementById('imageInput'),
          upPreview = document.getElementById('uploadPreview'),
          upPlaceholder = document.getElementById('uploadPlaceholder'),
          previewImg = document.getElementById('previewImg');

    imgInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            upPreview.style.display = 'block';
            upPlaceholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });

    window.removePreview = function() {
        imgInput.value = '';
        upPreview.style.display = 'none';
        upPlaceholder.style.display = 'block';
    };

    // Auto dismiss alert
    const alertEl = document.getElementById('pageAlert');
    if (alertEl) { setTimeout(() => { alertEl.style.opacity = '0'; setTimeout(() => alertEl.remove(), 500); }, 4000); }

    if (<?= $autoOpen ?>) {
        const editData = <?= $editDataJSON ?>;
        if (editData) openEditModal(editData);
        else openAddModal();
    }
</script>
</body>
</html>
