<?php
/**
 * Mekarsa Coffee Bar - Admin Kelola Layanan Pendukung
 * CRUD Layanan (misal: Shoe Clean, Cuci Helm, dll)
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once dirname(__DIR__) . '/src/config/database.php';

// =====================================================================
// UPLOAD HELPER
// =====================================================================
define('UPLOAD_DIR',  dirname(__DIR__) . '/public/uploads/services/');
define('UPLOAD_URL',  '../public/uploads/services/');
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
        return ['success' => false, 'error' => 'Format file harus JPG, PNG, atau WEBP.'];
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

    $newFilename = 'srv_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newFilename)) {
        return ['success' => false, 'error' => 'Gagal menyimpan file ke server.'];
    }

    if ($oldImage && file_exists(UPLOAD_DIR . $oldImage)) {
        @unlink(UPLOAD_DIR . $oldImage);
    }

    return ['success' => true, 'filename' => $newFilename];
}

// =====================================================================
// BACKEND LOGIC
// =====================================================================
$flashMsg   = '';
$flashType  = 'success';
$formErrors = [];
$editData   = null;

try {
    $pdo = getDBConnection();

    // Auto-create table
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) DEFAULT 0.00,
        image VARCHAR(255) DEFAULT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // ----- ACTION: ADD SERVICE -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
        $title  = trim($_POST['title'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $price  = str_replace(['.', ','], ['', '.'], $_POST['price'] ?? '0');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if (empty($title)) $formErrors['title'] = 'Nama layanan wajib diisi.';

        if (empty($formErrors)) {
            $uploadResult = handleImageUpload($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            if (!$uploadResult['success']) {
                $formErrors['image'] = $uploadResult['error'];
            } else {
                $imageName = $uploadResult['filename'];
                $stmt = $pdo->prepare("INSERT INTO support_services (title, description, price, image, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $desc, $price, $imageName, $status]);
                header('Location: support-services.php?msg=added');
                exit;
            }
        }
        if (!empty($formErrors)) {
            $flashMsg  = 'Terdapat kesalahan input. Periksa kembali.';
            $flashType = 'error';
        }
    }

    // ----- ACTION: EDIT SERVICE -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
        $id     = (int)($_POST['id'] ?? 0);
        $title  = trim($_POST['title'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $price  = str_replace(['.', ','], ['', '.'], $_POST['price'] ?? '0');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if ($id <= 0)      $formErrors['id']    = 'ID Layanan tidak valid.';
        if (empty($title)) $formErrors['title'] = 'Nama layanan wajib diisi.';

        if (empty($formErrors)) {
            $existingStmt = $pdo->prepare("SELECT image FROM support_services WHERE id = ?");
            $existingStmt->execute([$id]);
            $oldImage  = $existingStmt->fetchColumn();

            $uploadResult = handleImageUpload($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], $oldImage);
            if (!$uploadResult['success']) {
                $formErrors['image'] = $uploadResult['error'];
            } else {
                $imageName = $uploadResult['filename'];
                $stmt = $pdo->prepare("UPDATE support_services SET title=?, description=?, price=?, image=?, status=? WHERE id=?");
                $stmt->execute([$title, $desc, $price, $imageName, $status, $id]);
                header('Location: support-services.php?msg=updated');
                exit;
            }
        }
        if (!empty($formErrors)) {
            $flashType = 'error';
            $flashMsg  = 'Terdapat kesalahan input.';
            $existingStmt = $pdo->prepare("SELECT image FROM support_services WHERE id = ?");
            $existingStmt->execute([$id]);
            $editData = [
                'id'          => $id,
                'title'       => $title,
                'description' => $desc,
                'price'       => $price,
                'status'      => $status,
                'image'       => $existingStmt->fetchColumn()
            ];
        }
    }

    // ----- ACTION: DELETE SERVICE -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT image FROM support_services WHERE id = ?");
            $stmt->execute([$id]);
            $img = $stmt->fetchColumn();
            $pdo->prepare("DELETE FROM support_services WHERE id = ?")->execute([$id]);
            if ($img && file_exists(UPLOAD_DIR . $img)) {
                @unlink(UPLOAD_DIR . $img);
            }
            header('Location: support-services.php?msg=deleted');
            exit;
        }
    }

    // ----- Messages -----
    $msgMap = [
        'added'   => ['Layanan pendukung berhasil ditambahkan!', 'success'],
        'updated' => ['Data layanan berhasil diperbarui!', 'success'],
        'deleted' => ['Layanan berhasil dihapus dari daftar.', 'success'],
    ];
    if (isset($_GET['msg']) && isset($msgMap[$_GET['msg']])) {
        [$flashMsg, $flashType] = $msgMap[$_GET['msg']];
    }

    // ----- FETCH: Services -----
    $search = trim($_GET['search'] ?? '');
    $where  = [];
    $params = [];
    if (!empty($search)) {
        $where[]  = "title LIKE ?";
        $params[] = "%$search%";
    }
    $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $listStmt = $pdo->prepare("SELECT * FROM support_services $whereSQL ORDER BY id DESC");
    $listStmt->execute($params);
    $services = $listStmt->fetchAll();

    // Stats
    $statsStmt = $pdo->query("SELECT COUNT(*) AS total, SUM(status='active') AS active_count FROM support_services");
    $stats = $statsStmt->fetch();

} catch (PDOException $e) {
    $services = [];
    $stats = ['total'=>0, 'active_count'=>0];
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
    <title>Kelola Layanan — Mekarsa Admin</title>
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

        .page-content { padding: 2rem; flex: 1; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .page-header-title h1 { font-family: var(--font-head); font-size: 1.75rem; font-weight: 800; margin-bottom: 0.25rem; }
        .page-header-title p { color: var(--text-muted); font-size: 0.9rem; }
        .btn-add { display: inline-flex; align-items: center; gap: 0.6rem; padding: 0.75rem 1.4rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; white-space: nowrap; }
        .btn-add:hover { background: var(--orange-dark); }

        .page-alert { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.9rem 1.1rem; border-radius: 10px; font-size: 0.9rem; margin-bottom: 1.75rem; }
        .page-alert--success { background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.25); color: var(--green); }
        .page-alert--error   { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); color: var(--red); }

        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; }
        .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; }
        .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink:0; }
        .stat-icon--orange { background: rgba(242,113,33,0.15); color: var(--orange); }
        .stat-icon--green { background: rgba(34,197,94,0.15); color: var(--green); }
        .stat-val { font-family: var(--font-price); font-size: 1.6rem; line-height: 1; margin-bottom: 0.25rem; }
        .stat-lbl { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        .filter-bar { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; }
        .search-group { position: relative; flex: 1; max-width: 400px; }
        .search-group i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .search-input { width: 100%; padding: 0.75rem 1rem 0.75rem 2.7rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; }
        .btn-filter { padding: 0.75rem 1.5rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); cursor: pointer; transition: 0.2s; }
        .btn-filter:hover { border-color: var(--orange); color: var(--orange); }

        /* Card List */
        .service-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .service-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s; position: relative; }
        .service-card:hover { transform: translateY(-4px); border-color: rgba(242,113,33,0.3); box-shadow: 0 10px 20px rgba(0,0,0,0.4); }
        
        .srv-img-wrap { aspect-ratio: 16/9; background: var(--bg-input); position: relative; overflow: hidden; border-bottom: 1px solid var(--border); }
        .srv-img { width: 100%; height: 100%; object-fit: cover; }
        .srv-img-placeholder { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--border); }
        
        .srv-status { position: absolute; top: 1rem; left: 1rem; z-index: 10; padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1); }
        .srv-status--active { color: var(--green); }
        .srv-status--inactive { color: var(--red); }

        .srv-content { padding: 1.25rem; flex: 1; display: flex; flex-direction: column; }
        .srv-title { font-family: var(--font-head); font-size: 1.1rem; font-weight: 700; margin-bottom: 0.4rem; color: var(--text); }
        .srv-desc { font-size: 0.85rem; color: var(--text-muted); flex: 1; margin-bottom: 1rem; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        
        .srv-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 1rem; }
        .srv-price { font-family: var(--font-price); font-size: 1.25rem; color: var(--orange); }
        
        .action-group { display: flex; gap: 0.4rem; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-input); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; cursor: pointer; transition: 0.2s; color: var(--text-muted); }
        .action-btn:hover { border-color: var(--orange); color: var(--orange); background: var(--orange-glow); }
        .action-btn--danger:hover { border-color: var(--red); color: var(--red); background: rgba(239,68,68,0.1); }

        .empty-state { text-align: center; padding: 4rem 1.5rem; color: var(--text-muted); grid-column: 1 / -1; border: 1px dashed var(--border); border-radius: 12px; }
        .empty-state i { font-size: 3rem; opacity: 0.2; margin-bottom: 1rem; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 200; align-items: flex-start; justify-content: center; padding: 2rem 1rem; overflow-y: auto; backdrop-filter: blur(4px); }
        .modal-overlay.open { display: flex; }
        .modal { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 500px; margin: auto; animation: modalIn 0.3s ease; }
        @keyframes modalIn { from { opacity:0; transform: scale(0.96) translateY(20px); } to { opacity:1; transform:none; } }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.5rem; border-bottom: 1px solid var(--border); }
        .modal-title { font-family: var(--font-head); font-size: 1.15rem; font-weight: 800; }
        .modal-close { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1.25rem 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.75rem; }

        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-weight: 600; font-size: 0.85rem; color: var(--text); margin-bottom: 0.5rem; }
        .form-input, .form-textarea, .form-select { width: 100%; padding: 0.75rem 1rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; transition: 0.2s; }
        .form-input:focus, .form-textarea:focus, .form-select:focus { border-color: var(--orange); }
        .form-textarea { resize: vertical; min-height: 100px; }
        .field-error { font-size: 0.78rem; color: var(--red); display: block; margin-top: 0.3rem; }

        .btn-primary { padding: 0.75rem 1.5rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-secondary { padding: 0.75rem 1.5rem; background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: 8px; cursor: pointer; }

        .upload-zone { border: 2px dashed var(--border); border-radius: 10px; padding: 2rem 1.5rem; text-align: center; cursor: pointer; position: relative; transition: 0.2s; }
        .upload-zone:hover { border-color: var(--orange); background: rgba(242,113,33,0.05); }
        .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .upload-icon { font-size: 2rem; color: var(--text-muted); margin-bottom: 0.5rem; opacity: 0.5; }
        .upload-preview { display: none; margin-top: 1rem; position: relative; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); }
        .upload-preview img { width: 100%; display: block; }
        .upload-preview-remove { position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(0,0,0,0.7); color: #fff; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-toggle { display: flex; }
            .main { margin-left: 0; }
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
        <a href="support-services.php" class="nav-item active"><i class="fas fa-shoe-prints"></i> Layanan</a>
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
            <nav class="breadcrumb"><span>Layanan Pendukung</span></nav>
        </div>
    </header>

    <main class="page-content">
        <div class="page-header">
            <div class="page-header-title">
                <h1><i class="fas fa-shoe-prints" style="color:var(--orange);margin-right:0.5rem;font-size:1.4rem;"></i>Layanan Pendukung</h1>
                <p>Kelola layanan jasa tambahan seperti Shoe Clean, Cuci Helm, dll.</p>
            </div>
            <button class="btn-add" onclick="openAddModal()"><i class="fas fa-plus"></i> Tambah Layanan</button>
        </div>

        <?php if (!empty($flashMsg)): ?>
            <div class="page-alert page-alert--<?= $flashType ?>" id="pageAlert">
                <i class="fas <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <span><?= $flashMsg ?></span>
            </div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon stat-icon--orange"><i class="fas fa-shoe-prints"></i></div>
                <div>
                    <div class="stat-val"><?= $stats['total'] ?? 0 ?></div>
                    <div class="stat-lbl">Total Layanan</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon--green"><i class="fas fa-circle-check"></i></div>
                <div>
                    <div class="stat-val"><?= $stats['active_count'] ?? 0 ?></div>
                    <div class="stat-lbl">Aktif Ditawarkan</div>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <form method="GET" action="support-services.php" style="display:flex; gap:1rem; width:100%; flex-wrap:wrap;">
                <div class="search-group">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" name="search" class="search-input" placeholder="Cari nama layanan..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="btn-filter">Cari</button>
                <?php if ($search): ?>
                    <a href="support-services.php" class="btn-filter" style="background:transparent;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="service-list">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $srv): ?>
                    <div class="service-card">
                        <div class="srv-img-wrap">
                            <?php if ($srv['image']): ?>
                                <img src="<?= UPLOAD_URL . $srv['image'] ?>" class="srv-img" alt="Layanan">
                            <?php else: ?>
                                <div class="srv-img-placeholder"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                            
                            <?php if ($srv['status'] === 'active'): ?>
                                <div class="srv-status srv-status--active"><i class="fas fa-check"></i> Aktif</div>
                            <?php else: ?>
                                <div class="srv-status srv-status--inactive"><i class="fas fa-xmark"></i> Nonaktif</div>
                            <?php endif; ?>
                        </div>
                        <div class="srv-content">
                            <h3 class="srv-title"><?= htmlspecialchars($srv['title']) ?></h3>
                            <div class="srv-desc"><?= nl2br(htmlspecialchars($srv['description'])) ?></div>
                        </div>
                        <div class="srv-footer">
                            <div class="srv-price">Rp<?= number_format($srv['price'], 0, ',', '.') ?></div>
                            <div class="action-group">
                                <button class="action-btn" onclick="openEditModal(<?= htmlspecialchars(json_encode($srv)) ?>)"><i class="fas fa-pen"></i></button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus layanan ini secara permanen?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $srv['id'] ?>">
                                    <button type="submit" class="action-btn action-btn--danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>Belum ada layanan</h3>
                    <p><?= $search ? 'Layanan tidak ditemukan.' : 'Silakan tambahkan layanan pendukung pertama.' ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal Form -->
<div class="modal-overlay" id="srvModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Tambah Layanan</h2>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="srvForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Layanan <span style="color:var(--orange);">*</span></label>
                    <input type="text" name="title" id="title" class="form-input" required placeholder="Contoh: Premium Shoe Clean">
                    <?php if (isset($formErrors['title'])): ?><span class="field-error"><?= $formErrors['title'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Harga (Rp)</label>
                    <input type="number" name="price" id="price" class="form-input" placeholder="0" min="0" step="1000">
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi Layanan</label>
                    <textarea name="description" id="description" class="form-textarea" placeholder="Jelaskan detail layanan yang diberikan..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Status Tampil</label>
                    <select name="status" id="status" class="form-select">
                        <option value="active">Aktif (Tampil di website)</option>
                        <option value="inactive">Nonaktif (Sembunyikan)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Gambar Ilustrasi</label>
                    <div id="existingImgWrap" style="display:none; margin-bottom:0.75rem;">
                        <img id="existingImg" src="" style="height:60px; border-radius:6px; border:1px solid var(--border);">
                    </div>
                    <div class="upload-zone" id="uploadZone">
                        <input type="file" name="image" id="imageInput" accept=".jpg,.jpeg,.png,.webp">
                        <div id="uploadPlaceholder">
                            <div class="upload-icon"><i class="fas fa-cloud-arrow-up"></i></div>
                            <div style="font-size:0.85rem; color:var(--text-muted);">Klik untuk upload (Opsional, maks 5MB)</div>
                        </div>
                    </div>
                    <div class="upload-preview" id="uploadPreview">
                        <img id="previewImg" src="">
                        <button type="button" class="upload-preview-remove" onclick="removePreview()"><i class="fas fa-xmark"></i></button>
                    </div>
                    <?php if (isset($formErrors['image'])): ?><span class="field-error"><?= $formErrors['image'] ?></span><?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Layanan</button>
            </div>
        </form>
    </div>
</div>

<?php
$autoOpen = !empty($formErrors) ? 'true' : 'false';
$editDataJSON = $editData ? json_encode($editData) : 'null';
?>
<script>
    const sidebar = document.getElementById('sidebar'), overlay = document.getElementById('sidebarOverlay'), toggleBtn = document.getElementById('sidebarToggle');
    if(toggleBtn) toggleBtn.addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('visible'); });
    if(overlay) overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('visible'); });

    const alertEl = document.getElementById('pageAlert');
    if(alertEl) { setTimeout(() => { alertEl.style.opacity = '0'; setTimeout(() => alertEl.remove(), 500); }, 4000); }

    const modal = document.getElementById('srvModal');

    window.openAddModal = function() {
        document.getElementById('modalTitle').textContent = 'Tambah Layanan Baru';
        document.getElementById('formAction').value = 'add';
        document.getElementById('formId').value = '';
        document.getElementById('srvForm').reset();
        document.getElementById('existingImgWrap').style.display = 'none';
        removePreview();
        modal.classList.add('open');
    };

    window.openEditModal = function(data) {
        document.getElementById('modalTitle').textContent = 'Edit Layanan';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('formId').value = data.id;
        document.getElementById('title').value = data.title || '';
        document.getElementById('description').value = data.description || '';
        document.getElementById('price').value = parseInt(data.price) || 0;
        document.getElementById('status').value = data.status || 'active';
        
        if (data.image) {
            document.getElementById('existingImg').src = '../public/uploads/services/' + data.image;
            document.getElementById('existingImgWrap').style.display = 'block';
        } else {
            document.getElementById('existingImgWrap').style.display = 'none';
        }
        removePreview();
        modal.classList.add('open');
    };

    window.closeModal = function() { modal.classList.remove('open'); };

    // Image Preview
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

    if (<?= $autoOpen ?>) {
        const ed = <?= $editDataJSON ?>;
        if (ed) openEditModal(ed);
        else openAddModal();
    }
</script>
</body>
</html>
