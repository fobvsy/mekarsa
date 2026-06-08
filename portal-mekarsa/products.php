<?php
/**
 * Mekarsa Coffee Bar - Admin Kelola Produk Coffee
 * CRUD produk: tambah, edit, hapus, ubah status, ubah featured
 * Upload gambar JPG/PNG disimpan di public/uploads/products/
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
define('UPLOAD_DIR',  dirname(__DIR__) . '/public/uploads/products/');
define('UPLOAD_URL',  '../public/uploads/products/');
define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'webp']);
define('MAX_SIZE',    5 * 1024 * 1024); // 5 MB

function handleImageUpload(array $file, ?string $oldImage = null): array
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => $oldImage]; // tidak ada file baru
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Gagal mengunggah file. Kode error: ' . $file['error']];
    }

    if ($file['size'] > MAX_SIZE) {
        return ['success' => false, 'error' => 'Ukuran file maksimal 5 MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXT)) {
        return ['success' => false, 'error' => 'Format file harus JPG, JPEG, PNG, atau WEBP.'];
    }

    // Validasi MIME type tambahan
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes)) {
        return ['success' => false, 'error' => 'File harus berupa gambar yang valid.'];
    }

    // Buat folder jika belum ada
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $newFilename = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = UPLOAD_DIR . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Gagal menyimpan file ke server.'];
    }

    // Hapus gambar lama jika ada
    if ($oldImage && file_exists(UPLOAD_DIR . $oldImage)) {
        @unlink(UPLOAD_DIR . $oldImage);
    }

    return ['success' => true, 'filename' => $newFilename];
}

// =====================================================================
// BACKEND: Proses POST Requests
// =====================================================================
$flashMsg   = '';
$flashType  = 'success'; // success | error
$formErrors = [];
$editData   = null;

try {
    $pdo = getDBConnection();

    // --- Ambil semua kategori untuk dropdown ---
    $categories = $pdo->query("SELECT id, name FROM product_categories ORDER BY name ASC")->fetchAll();

    // ----- ACTION: ADD PRODUCT -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
        $name        = trim($_POST['name'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price       = (float)str_replace(['.', ','], ['', '.'], $_POST['price'] ?? '0');
        $stock       = (int)($_POST['stock'] ?? 0);
        $isFeatured  = isset($_POST['is_featured']) ? 1 : 0;
        $status      = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        // Validasi
        if (empty($name))       $formErrors['name']        = 'Nama produk wajib diisi.';
        if ($categoryId <= 0)   $formErrors['category_id'] = 'Kategori wajib dipilih.';
        if ($price <= 0)        $formErrors['price']        = 'Harga harus lebih dari 0.';

        if (empty($formErrors)) {
            // Upload gambar
            $uploadResult = handleImageUpload($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            if (!$uploadResult['success']) {
                $formErrors['image'] = $uploadResult['error'];
            } else {
                $imageName = $uploadResult['filename'];
                $stmt = $pdo->prepare("
                    INSERT INTO products (category_id, name, description, price, stock, image, is_featured, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$categoryId, $name, $description, $price, $stock, $imageName, $isFeatured, $status]);
                $flashMsg  = "Produk <strong>" . htmlspecialchars($name) . "</strong> berhasil ditambahkan!";
                $flashType = 'success';
                header('Location: products.php?msg=added');
                exit;
            }
        }
        if (!empty($formErrors)) {
            $flashMsg  = 'Terdapat ' . count($formErrors) . ' kesalahan pada form. Periksa kembali.';
            $flashType = 'error';
        }
    }

    // ----- ACTION: EDIT PRODUCT -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
        $id          = (int)($_POST['id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price       = (float)str_replace(['.', ','], ['', '.'], $_POST['price'] ?? '0');
        $stock       = (int)($_POST['stock'] ?? 0);
        $isFeatured  = isset($_POST['is_featured']) ? 1 : 0;
        $status      = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if ($id <= 0)           $formErrors['id']          = 'ID produk tidak valid.';
        if (empty($name))       $formErrors['name']        = 'Nama produk wajib diisi.';
        if ($categoryId <= 0)   $formErrors['category_id'] = 'Kategori wajib dipilih.';
        if ($price <= 0)        $formErrors['price']        = 'Harga harus lebih dari 0.';

        if (empty($formErrors)) {
            // Ambil gambar lama
            $existingStmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $existingStmt->execute([$id]);
            $existing  = $existingStmt->fetch();
            $oldImage  = $existing['image'] ?? null;

            $uploadResult = handleImageUpload($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], $oldImage);
            if (!$uploadResult['success']) {
                $formErrors['image'] = $uploadResult['error'];
            } else {
                $imageName = $uploadResult['filename'];
                $stmt = $pdo->prepare("
                    UPDATE products
                    SET category_id=?, name=?, description=?, price=?, stock=?, image=?, is_featured=?, status=?, updated_at=NOW()
                    WHERE id=?
                ");
                $stmt->execute([$categoryId, $name, $description, $price, $stock, $imageName, $isFeatured, $status, $id]);
                $flashMsg  = "Produk <strong>" . htmlspecialchars($name) . "</strong> berhasil diperbarui!";
                $flashType = 'success';
                header('Location: products.php?msg=updated');
                exit;
            }
        }
        if (!empty($formErrors)) {
            $flashType = 'error';
            $flashMsg  = 'Terdapat ' . count($formErrors) . ' kesalahan. Periksa kembali.';
            // Kembalikan data ke form edit
            $editData = [
                'id'          => $id,
                'name'        => $name,
                'category_id' => $categoryId,
                'description' => $description,
                'price'       => $price,
                'stock'       => $stock,
                'is_featured' => $isFeatured,
                'status'      => $status,
                'image'       => $existing['image'] ?? null,
            ];
        }
    }

    // ----- ACTION: DELETE PRODUCT -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Ambil nama & gambar dulu
            $stmt = $pdo->prepare("SELECT name, image FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $prod = $stmt->fetch();
            if ($prod) {
                $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
                // Hapus file gambar
                if ($prod['image'] && file_exists(UPLOAD_DIR . $prod['image'])) {
                    @unlink(UPLOAD_DIR . $prod['image']);
                }
                header('Location: products.php?msg=deleted');
                exit;
            }
        }
        header('Location: products.php?msg=error');
        exit;
    }

    // ----- ACTION: TOGGLE STATUS -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT status FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $cur = $stmt->fetchColumn();
            $newStatus = ($cur === 'active') ? 'inactive' : 'active';
            $pdo->prepare("UPDATE products SET status=?, updated_at=NOW() WHERE id=?")->execute([$newStatus, $id]);
        }
        header('Location: products.php?msg=status_updated');
        exit;
    }

    // ----- ACTION: TOGGLE FEATURED -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_featured') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT is_featured FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $cur = (int)$stmt->fetchColumn();
            $pdo->prepare("UPDATE products SET is_featured=?, updated_at=NOW() WHERE id=?")->execute([($cur ? 0 : 1), $id]);
        }
        header('Location: products.php?msg=featured_updated');
        exit;
    }

    // ----- GET: Load edit data -----
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
        $editId = (int)$_GET['edit'];
        if ($editId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$editId]);
            $editData = $stmt->fetch();
            if (!$editData) $editData = null;
        }
    }

    // ----- Flash messages dari redirect -----
    $msgMap = [
        'added'           => ['Produk berhasil ditambahkan!', 'success'],
        'updated'         => ['Produk berhasil diperbarui!', 'success'],
        'deleted'         => ['Produk berhasil dihapus.', 'success'],
        'status_updated'  => ['Status produk berhasil diubah.', 'success'],
        'featured_updated'=> ['Status unggulan produk berhasil diubah.', 'success'],
        'error'           => ['Terjadi kesalahan. Silakan coba lagi.', 'error'],
    ];
    if (isset($_GET['msg']) && isset($msgMap[$_GET['msg']])) {
        [$flashMsg, $flashType] = $msgMap[$_GET['msg']];
    }

    // ----- FETCH: Semua produk (dengan JOIN kategori + filter/search) -----
    $search      = trim($_GET['search'] ?? '');
    $filterCat   = (int)($_GET['category'] ?? 0);
    $filterStatus = $_GET['status'] ?? '';
    $page        = max(1, (int)($_GET['page'] ?? 1));
    $perPage     = 10;
    $offset      = ($page - 1) * $perPage;

    $where  = [];
    $params = [];

    if (!empty($search)) {
        $where[]  = "(p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($filterCat > 0) {
        $where[]  = "p.category_id = ?";
        $params[] = $filterCat;
    }
    if (in_array($filterStatus, ['active', 'inactive'])) {
        $where[]  = "p.status = ?";
        $params[] = $filterStatus;
    }

    $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total rows
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSQL");
    $countStmt->execute($params);
    $totalRows  = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $perPage));

    // Products rows
    $listStmt = $pdo->prepare("
        SELECT p.*, pc.name AS category_name
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        $whereSQL
        ORDER BY p.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $listStmt->execute($params);
    $products = $listStmt->fetchAll();

    // Stats cards
    $statsStmt = $pdo->query("
        SELECT
            COUNT(*) AS total,
            SUM(status='active') AS active_count,
            SUM(status='inactive') AS inactive_count,
            SUM(is_featured=1) AS featured_count
        FROM products
    ");
    $productStats = $statsStmt->fetch();

} catch (PDOException $e) {
    $dbError      = $e->getMessage();
    $products     = [];
    $categories   = [];
    $totalPages   = 1;
    $totalRows    = 0;
    $productStats = ['total'=>0,'active_count'=>0,'inactive_count'=>0,'featured_count'=>0];
    if (empty($flashMsg)) {
        $flashMsg  = 'Database error: ' . $e->getMessage();
        $flashType = 'error';
    }
}

// Helper: format Rupiah
function formatRupiah(float $amount): string {
    return 'Rp' . number_format($amount, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk Coffee — Mekarsa Admin</title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700;800&family=Inter:wght@400;500;600&family=Anton&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ===== DESIGN TOKENS ===== */
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
        button { font-family: var(--font-body); }

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
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 1.5rem 1.5rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
            flex-shrink: 0;
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

        .sidebar-brand-text { font-family: var(--font-head); font-weight: 800; font-size: 1.2rem; color: var(--text); }
        .sidebar-brand-text span { color: var(--orange); }
        .sidebar-brand-sub { font-size: 0.7rem; color: var(--text-muted); }

        .sidebar-nav { flex: 1; padding: 1.25rem 0; }
        .nav-section-label {
            padding: 0.5rem 1.5rem 0.3rem;
            font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1.5px; color: var(--text-muted); opacity: 0.6;
        }

        .nav-item {
            display: flex; align-items: center; gap: 0.8rem;
            padding: 0.7rem 1.5rem;
            color: var(--text-muted); font-size: 0.9rem; font-weight: 500;
            transition: all 0.2s; position: relative; cursor: pointer;
        }
        .nav-item:hover { color: var(--text); background: rgba(255,255,255,0.04); }
        .nav-item.active { color: var(--orange); background: var(--orange-glow); }
        .nav-item.active::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 3px; background: var(--orange); border-radius: 0 2px 2px 0;
        }
        .nav-item i { width: 18px; text-align: center; font-size: 0.95rem; flex-shrink: 0; }

        .nav-badge {
            margin-left: auto; background: var(--orange); color: #fff;
            font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.45rem;
            border-radius: 999px; font-family: var(--font-head);
        }
        .nav-badge--yellow { background: var(--yellow); }

        .sidebar-footer { padding: 1.25rem 1.5rem; border-top: 1px solid var(--border); flex-shrink: 0; }
        .admin-info { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
        .admin-avatar {
            width: 36px; height: 36px; border-radius: 10px; background: var(--orange);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.9rem; color: #fff; font-family: var(--font-head); flex-shrink: 0;
        }
        .admin-name { font-weight: 600; font-size: 0.9rem; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .admin-role { font-size: 0.75rem; color: var(--text-muted); }
        .btn-logout {
            display: flex; align-items: center; gap: 0.6rem; padding: 0.6rem 1rem;
            background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.2);
            border-radius: 8px; color: var(--red); font-size: 0.85rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s; width: 100%; text-decoration: none;
        }
        .btn-logout:hover { background: rgba(248,113,113,0.15); border-color: rgba(248,113,113,0.4); }

        /* ===== MAIN ===== */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* ===== TOPBAR ===== */
        .topbar {
            height: var(--topbar-h); background: rgba(10,10,10,0.95); backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border); display: flex; align-items: center;
            justify-content: space-between; padding: 0 2rem; position: sticky; top: 0; z-index: 50;
        }
        .topbar-left { display: flex; align-items: center; gap: 1rem; }
        .sidebar-toggle {
            display: none; width: 38px; height: 38px; border-radius: 8px;
            border: 1px solid var(--border); background: var(--bg-card); color: var(--text);
            cursor: pointer; align-items: center; justify-content: center; font-size: 1rem;
        }
        .breadcrumb { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--text-muted); }
        .breadcrumb a { color: var(--text-muted); }
        .breadcrumb a:hover { color: var(--orange); }
        .breadcrumb span { color: var(--text); font-weight: 600; }
        .topbar-right { display: flex; align-items: center; gap: 1rem; }
        .topbar-time { font-size: 0.82rem; color: var(--text-muted); }
        .topbar-view-site {
            display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem;
            border: 1px solid var(--border); border-radius: 8px; font-size: 0.82rem; color: var(--text-muted); transition: all 0.2s;
        }
        .topbar-view-site:hover { border-color: var(--orange); color: var(--orange); }

        /* ===== PAGE CONTENT ===== */
        .page-content { padding: 2rem; flex: 1; }

        /* ===== PAGE HEADER ===== */
        .page-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;
        }
        .page-header-title h1 {
            font-family: var(--font-head); font-size: 1.75rem; font-weight: 800;
            letter-spacing: -0.5px; margin-bottom: 0.25rem;
        }
        .page-header-title p { color: var(--text-muted); font-size: 0.9rem; }
        .btn-add {
            display: inline-flex; align-items: center; gap: 0.6rem;
            padding: 0.75rem 1.4rem; background: var(--orange); color: #fff;
            border: none; border-radius: 8px; font-family: var(--font-head);
            font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; white-space: nowrap;
        }
        .btn-add:hover { background: var(--orange-dark); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(242,113,33,0.35); }

        /* ===== ALERT ===== */
        .page-alert {
            display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.9rem 1.1rem;
            border-radius: 10px; font-size: 0.9rem; margin-bottom: 1.75rem; animation: fadeIn 0.4s ease;
        }
        .page-alert i { margin-top: 2px; flex-shrink: 0; }
        .page-alert--success { background: rgba(74,222,128,0.08); border: 1px solid rgba(74,222,128,0.25); color: var(--green); }
        .page-alert--error   { background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.25); color: var(--red); }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

        /* ===== STATS CARDS ===== */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.75rem;
        }
        .stat-mini {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: 12px; padding: 1.25rem;
            display: flex; align-items: center; gap: 1rem;
            transition: border-color 0.2s, transform 0.2s;
        }
        .stat-mini:hover { transform: translateY(-2px); }
        .stat-mini--orange:hover { border-color: var(--orange); }
        .stat-mini--green:hover  { border-color: var(--green); }
        .stat-mini--yellow:hover { border-color: var(--yellow); }
        .stat-mini--red:hover    { border-color: var(--red); }

        .stat-mini-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
        }
        .stat-mini--orange .stat-mini-icon { background: rgba(242,113,33,0.15); color: var(--orange); }
        .stat-mini--green .stat-mini-icon  { background: rgba(34,197,94,0.12);  color: var(--green); }
        .stat-mini--yellow .stat-mini-icon { background: rgba(245,158,11,0.12); color: var(--yellow); }
        .stat-mini--red .stat-mini-icon    { background: rgba(248,113,113,0.1); color: var(--red); }

        .stat-mini-value { font-family: var(--font-price); font-size: 1.8rem; line-height: 1; }
        .stat-mini-label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }

        /* ===== FILTER BAR ===== */
        .filter-bar {
            background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px;
            padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
        }
        .filter-bar form { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; width: 100%; }

        .search-group {
            position: relative; flex: 1; min-width: 200px;
        }
        .search-group i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.9rem; }
        .search-input {
            width: 100%; padding: 0.7rem 1rem 0.7rem 2.7rem;
            background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px;
            color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; transition: border-color 0.2s;
        }
        .search-input:focus { border-color: var(--orange); }
        .search-input::placeholder { color: var(--text-muted); opacity: 0.7; }

        .filter-select {
            padding: 0.7rem 1rem; background: var(--bg-input); border: 1px solid var(--border);
            border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; cursor: pointer;
        }
        .filter-select:focus { border-color: var(--orange); }
        .filter-select option { background: var(--bg-card); }

        .btn-filter {
            padding: 0.7rem 1.2rem; background: var(--orange); color: #fff; border: none;
            border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: background 0.2s; white-space: nowrap;
        }
        .btn-filter:hover { background: var(--orange-dark); }

        .btn-reset {
            padding: 0.7rem 1rem; background: transparent; color: var(--text-muted);
            border: 1px solid var(--border); border-radius: 8px; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; white-space: nowrap;
        }
        .btn-reset:hover { color: var(--text); border-color: var(--text-muted); }

        /* ===== TABLE CARD ===== */
        .table-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: 12px; overflow: hidden;
        }
        .table-card-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);
        }
        .table-card-header h3 { font-family: var(--font-head); font-size: 1rem; font-weight: 700; }
        .table-total { font-size: 0.82rem; color: var(--text-muted); }

        .table-wrapper { overflow-x: auto; }

        .data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; min-width: 900px; }
        .data-table th {
            padding: 0.75rem 1rem; text-align: left; font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted);
            background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border); white-space: nowrap;
        }
        .data-table td { padding: 0.9rem 1rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: rgba(255,255,255,0.02); }

        /* Product cell */
        .product-cell { display: flex; align-items: center; gap: 0.9rem; }
        .product-thumb {
            width: 52px; height: 52px; border-radius: 8px; object-fit: cover;
            border: 1px solid var(--border); flex-shrink: 0; background: var(--bg-input);
        }
        .product-thumb-placeholder {
            width: 52px; height: 52px; border-radius: 8px; border: 1px solid var(--border);
            background: var(--bg-input); display: flex; align-items: center; justify-content: center;
            color: var(--text-muted); font-size: 1.3rem; flex-shrink: 0;
        }
        .product-name { font-weight: 600; color: var(--text); font-size: 0.9rem; }
        .product-cat  { font-size: 0.75rem; color: var(--orange); font-weight: 600; text-transform: uppercase; margin-top: 2px; }

        /* Price */
        .price-text { font-family: var(--font-price); color: var(--orange); font-size: 1.05rem; white-space: nowrap; }

        /* Badges */
        .badge {
            display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.65rem;
            border-radius: 999px; font-size: 0.7rem; font-weight: 700; font-family: var(--font-head);
            text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap;
        }
        .badge--active   { background: rgba(34,197,94,0.12);  color: var(--green);  border: 1px solid rgba(34,197,94,0.3); }
        .badge--inactive { background: rgba(161,161,170,0.1); color: var(--text-muted); border: 1px solid rgba(161,161,170,0.2); }
        .badge--featured { background: rgba(245,158,11,0.12); color: var(--yellow); border: 1px solid rgba(245,158,11,0.3); }
        .badge--normal   { background: rgba(99,102,241,0.1);  color: #818cf8; border: 1px solid rgba(99,102,241,0.25); }

        /* Action buttons */
        .action-group { display: flex; align-items: center; gap: 0.4rem; }
        .action-btn {
            width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--border);
            background: transparent; display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; cursor: pointer; transition: all 0.2s; color: var(--text-muted);
        }
        .action-btn:hover { border-color: var(--orange); color: var(--orange); background: var(--orange-glow); }
        .action-btn--danger:hover  { border-color: var(--red);    color: var(--red);    background: rgba(248,113,113,0.1); }
        .action-btn--success:hover { border-color: var(--green);  color: var(--green);  background: rgba(34,197,94,0.08); }
        .action-btn--star:hover    { border-color: var(--yellow); color: var(--yellow); background: rgba(245,158,11,0.1); }
        .action-btn--star.starred  { color: var(--yellow); border-color: rgba(245,158,11,0.4); background: rgba(245,158,11,0.1); }

        /* ===== EMPTY STATE ===== */
        .empty-state { text-align: center; padding: 4rem 1.5rem; color: var(--text-muted); }
        .empty-state-icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.2; }
        .empty-state h3 { font-family: var(--font-head); font-size: 1.2rem; margin-bottom: 0.5rem; color: var(--text); opacity: 0.5; }
        .empty-state p { font-size: 0.9rem; margin-bottom: 1.5rem; }

        /* ===== PAGINATION ===== */
        .pagination {
            display: flex; justify-content: center; align-items: center; gap: 0.5rem;
            padding: 1.25rem; border-top: 1px solid var(--border);
        }
        .page-btn {
            width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border);
            background: transparent; color: var(--text-muted); cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 0.875rem;
            transition: all 0.2s; font-family: var(--font-body);
        }
        .page-btn:hover    { border-color: var(--orange); color: var(--orange); }
        .page-btn.active   { background: var(--orange); border-color: var(--orange); color: #fff; font-weight: 700; }
        .page-btn:disabled { opacity: 0.3; cursor: not-allowed; }

        /* ===== MODAL ===== */
        .modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75);
            z-index: 200; align-items: flex-start; justify-content: center;
            padding: 2rem 1rem; overflow-y: auto; backdrop-filter: blur(4px);
        }
        .modal-overlay.open { display: flex; }

        .modal {
            background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px;
            width: 100%; max-width: 680px; position: relative; margin: auto;
            animation: modalIn 0.3s ease;
            box-shadow: 0 24px 80px rgba(0,0,0,0.7);
        }
        @keyframes modalIn { from { opacity:0; transform: scale(0.96) translateY(20px); } to { opacity:1; transform:none; } }

        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.5rem 1.75rem; border-bottom: 1px solid var(--border);
        }
        .modal-title { font-family: var(--font-head); font-size: 1.15rem; font-weight: 800; }
        .modal-close {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border);
            background: transparent; color: var(--text-muted); cursor: pointer;
            display: flex; align-items: center; justify-content: center; transition: all 0.2s;
        }
        .modal-close:hover { border-color: var(--red); color: var(--red); background: rgba(248,113,113,0.08); }

        .modal-body { padding: 1.75rem; }

        .modal-footer {
            padding: 1.25rem 1.75rem; border-top: 1px solid var(--border);
            display: flex; justify-content: flex-end; gap: 0.75rem;
        }

        /* ===== FORM ELEMENTS ===== */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .form-grid--full { grid-column: 1 / -1; }

        .form-group { margin-bottom: 0; }
        .form-label {
            display: block; font-weight: 600; font-size: 0.85rem; color: var(--text);
            margin-bottom: 0.45rem; letter-spacing: 0.1px;
        }
        .form-label .req { color: var(--orange); margin-left: 0.2rem; }

        .form-input, .form-select, .form-textarea {
            width: 100%; padding: 0.75rem 1rem;
            background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px;
            color: var(--text); font-family: var(--font-body); font-size: 0.9rem;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--orange); box-shadow: 0 0 0 3px rgba(242,113,33,0.12);
        }
        .form-input::placeholder, .form-textarea::placeholder { color: var(--text-muted); opacity: 0.7; }
        .form-select option { background: var(--bg-card); }
        .form-textarea { resize: vertical; min-height: 90px; }

        .form-input.is-error, .form-select.is-error, .form-textarea.is-error {
            border-color: var(--red); box-shadow: 0 0 0 3px rgba(248,113,113,0.1);
        }
        .field-error { font-size: 0.78rem; color: var(--red); margin-top: 0.3rem; display: block; }

        /* Toggle switches */
        .toggle-row { display: flex; align-items: center; gap: 0.75rem; }
        .toggle-switch { position: relative; width: 44px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
        .toggle-slider {
            position: absolute; cursor: pointer; inset: 0;
            background: var(--border); border-radius: 999px; transition: 0.3s;
        }
        .toggle-slider::before {
            content: ''; position: absolute;
            height: 18px; width: 18px; left: 3px; bottom: 3px;
            background: #fff; border-radius: 50%; transition: 0.3s;
        }
        .toggle-switch input:checked + .toggle-slider { background: var(--orange); }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }
        .toggle-label { font-size: 0.875rem; font-weight: 500; }

        /* Image upload zone */
        .upload-zone {
            border: 2px dashed var(--border); border-radius: 10px; padding: 1.5rem;
            text-align: center; cursor: pointer; transition: all 0.2s; position: relative;
        }
        .upload-zone:hover, .upload-zone.drag-over { border-color: var(--orange); background: rgba(242,113,33,0.04); }
        .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .upload-icon { font-size: 2rem; color: var(--text-muted); margin-bottom: 0.5rem; opacity: 0.5; }
        .upload-text { font-size: 0.875rem; color: var(--text-muted); }
        .upload-text strong { color: var(--orange); }
        .upload-hint { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; opacity: 0.7; }

        .upload-preview {
            margin-top: 0.75rem; display: none;
            border: 1px solid var(--border); border-radius: 8px; overflow: hidden;
            position: relative;
        }
        .upload-preview img { width: 100%; max-height: 160px; object-fit: cover; display: block; }
        .upload-preview-remove {
            position: absolute; top: 0.5rem; right: 0.5rem;
            width: 28px; height: 28px; border-radius: 6px;
            background: rgba(0,0,0,0.7); border: none; color: #fff; cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 0.8rem;
        }

        /* Existing image preview */
        .existing-img-wrap { position: relative; display: inline-block; }
        .existing-img-wrap img { height: 80px; border-radius: 8px; border: 1px solid var(--border); object-fit: cover; }
        .existing-img-label { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem; display: block; }

        /* Buttons */
        .btn-primary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.8rem 1.5rem; background: var(--orange); color: #fff;
            border: none; border-radius: 8px; font-family: var(--font-head);
            font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s;
        }
        .btn-primary:hover { background: var(--orange-dark); }
        .btn-secondary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.8rem 1.5rem; background: transparent; color: var(--text-muted);
            border: 1px solid var(--border); border-radius: 8px; font-family: var(--font-body);
            font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s;
        }
        .btn-secondary:hover { border-color: var(--text-muted); color: var(--text); }

        /* Delete confirm modal */
        .confirm-modal { max-width: 420px; }
        .confirm-icon { font-size: 3rem; margin-bottom: 1rem; text-align: center; }
        .confirm-modal .modal-body { text-align: center; }
        .confirm-modal h3 { font-family: var(--font-head); font-size: 1.2rem; margin-bottom: 0.5rem; }
        .confirm-modal p { color: var(--text-muted); font-size: 0.9rem; }
        .btn-danger {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.8rem 1.5rem; background: rgba(248,113,113,0.15); color: var(--red);
            border: 1px solid rgba(248,113,113,0.3); border-radius: 8px; font-family: var(--font-head);
            font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s;
        }
        .btn-danger:hover { background: rgba(248,113,113,0.25); border-color: var(--red); }

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
            .stats-row { grid-template-columns: 1fr 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; align-items: flex-start; }
        }
        @media (max-width: 480px) {
            .stats-row { grid-template-columns: 1fr; }
        }
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
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-gauge-high"></i> Dashboard
        </a>

        <div class="nav-section-label" style="margin-top:0.75rem;">Kelola Konten</div>
        <a href="products.php" class="nav-item active">
            <i class="fas fa-mug-saucer"></i> Produk Coffee
            <?php if (!empty($productStats['total'])): ?>
                <span class="nav-badge"><?= $productStats['total'] ?></span>
            <?php endif; ?>
        </a>
        <a href="product-categories.php" class="nav-item">
            <i class="fas fa-tags"></i> Kategori Produk
        </a>
        <a href="articles.php" class="nav-item">
            <i class="fas fa-newspaper"></i> Artikel
        </a>
        <a href="article-categories.php" class="nav-item">
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
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="breadcrumb" aria-label="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-house" style="font-size:0.8rem;"></i></a>
                <i class="fas fa-chevron-right" style="font-size:0.65rem;"></i>
                <span>Produk Coffee</span>
            </nav>
        </div>
        <div class="topbar-right">
            <span class="topbar-time" id="currentTime"></span>
            <a href="../menu.php" class="topbar-view-site" target="_blank" rel="noopener">
                <i class="fas fa-arrow-up-right-from-square"></i> Lihat Menu
            </a>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-title">
                <h1><i class="fas fa-mug-saucer" style="color:var(--orange);margin-right:0.5rem;font-size:1.4rem;"></i>Produk Coffee</h1>
                <p>Kelola daftar menu coffee, harga, ketersediaan, dan status unggulan produk.</p>
            </div>
            <button class="btn-add" id="btnAddProduct" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Tambah Produk
            </button>
        </div>

        <!-- Flash Alert -->
        <?php if (!empty($flashMsg)): ?>
            <div class="page-alert page-alert--<?= $flashType ?>" role="alert" id="pageAlert">
                <i class="fas <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <span><?= $flashMsg ?></span>
            </div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-mini stat-mini--orange">
                <div class="stat-mini-icon"><i class="fas fa-mug-saucer"></i></div>
                <div>
                    <div class="stat-mini-value"><?= $productStats['total'] ?? 0 ?></div>
                    <div class="stat-mini-label">Total Produk</div>
                </div>
            </div>
            <div class="stat-mini stat-mini--green">
                <div class="stat-mini-icon"><i class="fas fa-circle-check"></i></div>
                <div>
                    <div class="stat-mini-value"><?= $productStats['active_count'] ?? 0 ?></div>
                    <div class="stat-mini-label">Produk Aktif</div>
                </div>
            </div>
            <div class="stat-mini stat-mini--red">
                <div class="stat-mini-icon"><i class="fas fa-circle-xmark"></i></div>
                <div>
                    <div class="stat-mini-value"><?= $productStats['inactive_count'] ?? 0 ?></div>
                    <div class="stat-mini-label">Tidak Tersedia</div>
                </div>
            </div>
            <div class="stat-mini stat-mini--yellow">
                <div class="stat-mini-icon"><i class="fas fa-star"></i></div>
                <div>
                    <div class="stat-mini-value"><?= $productStats['featured_count'] ?? 0 ?></div>
                    <div class="stat-mini-label">Menu Unggulan</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" action="products.php">
                <div class="search-group">
                    <i class="fas fa-magnifying-glass"></i>
                    <input
                        type="text" name="search" class="search-input"
                        placeholder="Cari nama atau deskripsi produk..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>
                <select name="category" class="filter-select" aria-label="Filter kategori">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filterCat == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="filter-select" aria-label="Filter status">
                    <option value="">Semua Status</option>
                    <option value="active"   <?= $filterStatus === 'active'   ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
                <?php if ($search || $filterCat || $filterStatus): ?>
                    <a href="products.php" class="btn-reset"><i class="fas fa-xmark"></i> Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-card-header">
                <h3><i class="fas fa-table" style="color:var(--orange);margin-right:0.5rem;"></i> Daftar Produk</h3>
                <span class="table-total">
                    <?= $totalRows ?> produk ditemukan
                    <?= (!empty($search) || $filterCat || $filterStatus) ? '(difilter)' : '' ?>
                </span>
            </div>

            <div class="table-wrapper">
                <?php if (!empty($products)): ?>
                    <table class="data-table" role="table" aria-label="Daftar produk coffee">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Unggulan</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $i => $p): ?>
                                <tr>
                                    <td style="color:var(--text-muted);font-size:0.8rem;"><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <div class="product-cell">
                                            <?php if (!empty($p['image']) && file_exists(UPLOAD_DIR . $p['image'])): ?>
                                                <img src="<?= UPLOAD_URL . htmlspecialchars($p['image']) ?>"
                                                     alt="<?= htmlspecialchars($p['name']) ?>"
                                                     class="product-thumb">
                                            <?php else: ?>
                                                <div class="product-thumb-placeholder">
                                                    <i class="fas fa-mug-saucer"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                                                <div class="product-cat"><?= htmlspecialchars($p['category_name'] ?? '—') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="price-text"><?= formatRupiah((float)$p['price']) ?></td>
                                    <td style="color:var(--text-muted);"><?= (int)$p['stock'] ?></td>
                                    <td>
                                        <?php if ($p['status'] === 'active'): ?>
                                            <span class="badge badge--active"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Aktif</span>
                                        <?php else: ?>
                                            <span class="badge badge--inactive"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['is_featured']): ?>
                                            <span class="badge badge--featured"><i class="fas fa-star" style="font-size:0.6rem;"></i> Unggulan</span>
                                        <?php else: ?>
                                            <span class="badge badge--normal"><i class="far fa-star" style="font-size:0.6rem;"></i> Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--text-muted);font-size:0.8rem;white-space:nowrap;">
                                        <?= date('d M Y', strtotime($p['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <!-- Edit -->
                                            <button
                                                class="action-btn"
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($p)) ?>)"
                                                title="Edit produk"
                                                aria-label="Edit <?= htmlspecialchars($p['name']) ?>"
                                            ><i class="fas fa-pen"></i></button>

                                            <!-- Toggle Status -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button
                                                    type="submit"
                                                    class="action-btn action-btn--success"
                                                    title="<?= $p['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                                    aria-label="Ubah status <?= htmlspecialchars($p['name']) ?>"
                                                >
                                                    <i class="fas <?= $p['status'] === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                                </button>
                                            </form>

                                            <!-- Toggle Featured -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_featured">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button
                                                    type="submit"
                                                    class="action-btn action-btn--star <?= $p['is_featured'] ? 'starred' : '' ?>"
                                                    title="<?= $p['is_featured'] ? 'Hapus dari unggulan' : 'Jadikan unggulan' ?>"
                                                    aria-label="Toggle unggulan <?= htmlspecialchars($p['name']) ?>"
                                                ><i class="<?= $p['is_featured'] ? 'fas' : 'far' ?> fa-star"></i></button>
                                            </form>

                                            <!-- Delete -->
                                            <button
                                                class="action-btn action-btn--danger"
                                                onclick="confirmDelete(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'])) ?>')"
                                                title="Hapus produk"
                                                aria-label="Hapus <?= htmlspecialchars($p['name']) ?>"
                                            ><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-mug-saucer"></i></div>
                        <h3>Belum ada produk</h3>
                        <p><?= ($search || $filterCat || $filterStatus) ? 'Tidak ada produk yang cocok dengan filter.' : 'Mulai tambahkan menu coffee pertamamu.' ?></p>
                        <?php if (!$search && !$filterCat && !$filterStatus): ?>
                            <button class="btn-primary" onclick="openAddModal()">
                                <i class="fas fa-plus"></i> Tambah Produk Pertama
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" role="navigation" aria-label="Pagination">
                    <?php
                    $buildPageUrl = function(int $p) use ($search, $filterCat, $filterStatus): string {
                        $params = array_filter(['page' => $p, 'search' => $search, 'category' => $filterCat ?: '', 'status' => $filterStatus]);
                        return 'products.php?' . http_build_query($params);
                    };
                    ?>
                    <a href="<?= $page > 1 ? $buildPageUrl($page - 1) : '#' ?>" class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>" aria-label="Previous">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="<?= $buildPageUrl($i) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="<?= $page < $totalPages ? $buildPageUrl($page + 1) : '#' ?>" class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" aria-label="Next">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div><!-- /main -->

<!-- ===== ADD / EDIT MODAL ===== -->
<div class="modal-overlay" id="productModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Tambah Produk</h2>
            <button class="modal-close" onclick="closeModal()" aria-label="Tutup modal"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="productForm" method="POST" action="products.php" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId" value="">

            <div class="modal-body">
                <div class="form-grid">
                    <!-- Nama Produk -->
                    <div class="form-group form-grid--full">
                        <label class="form-label" for="name">Nama Produk <span class="req">*</span></label>
                        <input type="text" id="name" name="name" class="form-input <?= isset($formErrors['name']) ? 'is-error' : '' ?>"
                               placeholder="Contoh: KopSu Mekarsa" maxlength="100" required
                               value="<?= htmlspecialchars($editData['name'] ?? $_POST['name'] ?? '') ?>">
                        <?php if (isset($formErrors['name'])): ?><span class="field-error"><?= $formErrors['name'] ?></span><?php endif; ?>
                    </div>

                    <!-- Kategori -->
                    <div class="form-group">
                        <label class="form-label" for="category_id">Kategori <span class="req">*</span></label>
                        <select id="category_id" name="category_id" class="form-select <?= isset($formErrors['category_id']) ? 'is-error' : '' ?>" required>
                            <option value="">— Pilih Kategori —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"
                                    <?= (int)($editData['category_id'] ?? $_POST['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($formErrors['category_id'])): ?><span class="field-error"><?= $formErrors['category_id'] ?></span><?php endif; ?>
                    </div>

                    <!-- Harga -->
                    <div class="form-group">
                        <label class="form-label" for="price">Harga (Rp) <span class="req">*</span></label>
                        <input type="number" id="price" name="price" class="form-input <?= isset($formErrors['price']) ? 'is-error' : '' ?>"
                               placeholder="Contoh: 18000" min="0" step="500" required
                               value="<?= htmlspecialchars((string)($editData['price'] ?? $_POST['price'] ?? '')) ?>">
                        <?php if (isset($formErrors['price'])): ?><span class="field-error"><?= $formErrors['price'] ?></span><?php endif; ?>
                    </div>

                    <!-- Stok -->
                    <div class="form-group">
                        <label class="form-label" for="stock">Stok</label>
                        <input type="number" id="stock" name="stock" class="form-input"
                               placeholder="0" min="0"
                               value="<?= htmlspecialchars((string)(int)($editData['stock'] ?? $_POST['stock'] ?? 0)) ?>">
                    </div>

                    <!-- Deskripsi -->
                    <div class="form-group form-grid--full">
                        <label class="form-label" for="description">Deskripsi</label>
                        <textarea id="description" name="description" class="form-textarea" rows="3"
                                  placeholder="Deskripsi singkat produk..."><?= htmlspecialchars($editData['description'] ?? $_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Upload Gambar -->
                    <div class="form-group form-grid--full">
                        <label class="form-label">Gambar Produk</label>

                        <!-- Existing image (edit mode) -->
                        <div id="existingImgWrap" style="margin-bottom:0.75rem; display:none;">
                            <div class="existing-img-wrap">
                                <img id="existingImg" src="" alt="Gambar saat ini">
                            </div>
                            <span class="existing-img-label">
                                <i class="fas fa-circle-info" style="color:var(--orange);"></i>
                                Biarkan kosong untuk mempertahankan gambar yang ada. Upload gambar baru untuk mengganti.
                            </span>
                        </div>

                        <div class="upload-zone" id="uploadZone">
                            <input type="file" name="image" id="imageInput" accept=".jpg,.jpeg,.png,.webp" aria-label="Upload gambar produk">
                            <div id="uploadPlaceholder">
                                <div class="upload-icon"><i class="fas fa-cloud-arrow-up"></i></div>
                                <div class="upload-text"><strong>Klik untuk upload</strong> atau drag &amp; drop</div>
                                <div class="upload-hint">Format: JPG, JPEG, PNG, WEBP — Maks. 5 MB</div>
                            </div>
                        </div>
                        <div class="upload-preview" id="uploadPreview">
                            <img id="previewImg" src="" alt="Preview gambar">
                            <button type="button" class="upload-preview-remove" onclick="removePreview()" title="Hapus gambar">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </div>
                        <?php if (isset($formErrors['image'])): ?><span class="field-error"><?= $formErrors['image'] ?></span><?php endif; ?>
                    </div>

                    <!-- Status & Featured Toggles -->
                    <div class="form-group">
                        <label class="form-label">Status Produk</label>
                        <div class="toggle-row">
                            <label class="toggle-switch">
                                <input type="checkbox" name="status" id="statusToggle" value="active"
                                    <?= (($editData['status'] ?? $_POST['status'] ?? 'active') === 'active') ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-label" id="statusLabel">
                                <?= (($editData['status'] ?? 'active') === 'active') ? 'Aktif (tersedia)' : 'Nonaktif (tidak tersedia)' ?>
                            </span>
                        </div>
                        <!-- hidden field for status value -->
                        <input type="hidden" name="status" id="statusHidden" value="<?= ($editData['status'] ?? 'active') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Menu Unggulan</label>
                        <div class="toggle-row">
                            <label class="toggle-switch">
                                <input type="checkbox" name="is_featured" id="featuredToggle" value="1"
                                    <?= (($editData['is_featured'] ?? $_POST['is_featured'] ?? 0) ? 'checked' : '') ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-label" id="featuredLabel">
                                <?= ($editData['is_featured'] ?? 0) ? 'Ya (tampil di beranda)' : 'Tidak' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">
                    <i class="fas fa-xmark"></i> Batal
                </button>
                <button type="submit" class="btn-primary" id="formSubmitBtn">
                    <i class="fas fa-floppy-disk"></i> <span id="submitBtnText">Simpan Produk</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== DELETE CONFIRM MODAL ===== -->
<div class="modal-overlay" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="deleteTitle">
    <div class="modal confirm-modal">
        <div class="modal-header">
            <h2 class="modal-title" id="deleteTitle">Konfirmasi Hapus</h2>
            <button class="modal-close" onclick="closeDeleteModal()" aria-label="Tutup modal"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="confirm-icon">🗑️</div>
            <h3>Hapus Produk?</h3>
            <p>Anda akan menghapus produk <strong id="deleteProductName"></strong>. Tindakan ini tidak dapat dibatalkan dan gambar produk juga akan dihapus.</p>
        </div>
        <div class="modal-footer">
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteProductId" value="">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-xmark"></i> Batal
                </button>
                <button type="submit" class="btn-danger">
                    <i class="fas fa-trash"></i> Ya, Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<?php
// Jika ada error form (add/edit), buka modal otomatis
$autoOpenModal = !empty($formErrors) ? 'true' : 'false';
$autoOpenEdit  = ($editData !== null && !empty($formErrors) && ($_POST['action'] ?? '') === 'edit') ? 'true' : 'false';
$editDataJSON  = $editData ? json_encode($editData) : 'null';
?>

<script>
(function () {
    'use strict';

    /* ===== SIDEBAR MOBILE ===== */
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');

    function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('visible'); document.body.style.overflow = 'hidden'; }
    function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('visible'); document.body.style.overflow = ''; }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (overlay)   overlay.addEventListener('click', closeSidebar);

    /* ===== LIVE CLOCK ===== */
    const timeEl = document.getElementById('currentTime');
    function updateClock() {
        if (!timeEl) return;
        timeEl.textContent = new Date().toLocaleString('id-ID', { weekday:'short', day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' });
    }
    updateClock();
    setInterval(updateClock, 60000);

    /* ===== AUTO DISMISS ALERT ===== */
    const alertEl = document.getElementById('pageAlert');
    if (alertEl) {
        setTimeout(() => {
            alertEl.style.transition = 'opacity 0.5s';
            alertEl.style.opacity = '0';
            setTimeout(() => alertEl.remove(), 500);
        }, 5000);
    }

    /* ===== MODAL OPEN/CLOSE ===== */
    const productModalEl = document.getElementById('productModal');
    const deleteModalEl  = document.getElementById('deleteModal');

    window.openAddModal = function () {
        document.getElementById('modalTitle').textContent = 'Tambah Produk Baru';
        document.getElementById('submitBtnText').textContent = 'Simpan Produk';
        document.getElementById('formAction').value = 'add';
        document.getElementById('formId').value = '';
        document.getElementById('productForm').reset();
        document.getElementById('existingImgWrap').style.display = 'none';
        document.getElementById('uploadPreview').style.display = 'none';
        document.getElementById('uploadPlaceholder').style.display = '';

        // Reset toggles
        setStatusToggle('active');
        setFeaturedToggle(false);

        productModalEl.classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('name').focus(), 100);
    };

    window.openEditModal = function (product) {
        document.getElementById('modalTitle').textContent = 'Edit Produk';
        document.getElementById('submitBtnText').textContent = 'Simpan Perubahan';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('formId').value = product.id;

        // Isi form
        document.getElementById('name').value        = product.name || '';
        document.getElementById('category_id').value = product.category_id || '';
        document.getElementById('price').value       = product.price || '';
        document.getElementById('stock').value       = product.stock || 0;
        document.getElementById('description').value = product.description || '';

        setStatusToggle(product.status || 'active');
        setFeaturedToggle(!!parseInt(product.is_featured));

        // Gambar yang ada
        if (product.image) {
            const existingWrap = document.getElementById('existingImgWrap');
            const existingImg  = document.getElementById('existingImg');
            existingImg.src    = '../public/uploads/products/' + product.image;
            existingWrap.style.display = '';
        } else {
            document.getElementById('existingImgWrap').style.display = 'none';
        }
        document.getElementById('uploadPreview').style.display = 'none';
        document.getElementById('uploadPlaceholder').style.display = '';
        document.getElementById('imageInput').value = '';

        productModalEl.classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('name').focus(), 100);
    };

    window.closeModal = function () {
        productModalEl.classList.remove('open');
        document.body.style.overflow = '';
    };

    window.confirmDelete = function (id, name) {
        document.getElementById('deleteProductId').value = id;
        document.getElementById('deleteProductName').textContent = name;
        deleteModalEl.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeDeleteModal = function () {
        deleteModalEl.classList.remove('open');
        document.body.style.overflow = '';
    };

    // Close on overlay click
    productModalEl.addEventListener('click', function (e) { if (e.target === this) closeModal(); });
    deleteModalEl.addEventListener('click', function (e)  { if (e.target === this) closeDeleteModal(); });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeModal(); closeDeleteModal(); }
    });

    /* ===== STATUS TOGGLE ===== */
    const statusToggle = document.getElementById('statusToggle');
    const statusLabel  = document.getElementById('statusLabel');
    const statusHidden = document.getElementById('statusHidden');

    function setStatusToggle(val) {
        const isActive    = val === 'active';
        statusToggle.checked    = isActive;
        statusHidden.value      = isActive ? 'active' : 'inactive';
        statusLabel.textContent = isActive ? 'Aktif (tersedia)' : 'Nonaktif (tidak tersedia)';
    }

    if (statusToggle) {
        statusToggle.addEventListener('change', function () {
            setStatusToggle(this.checked ? 'active' : 'inactive');
        });
    }

    /* ===== FEATURED TOGGLE ===== */
    const featuredToggle = document.getElementById('featuredToggle');
    const featuredLabel  = document.getElementById('featuredLabel');

    function setFeaturedToggle(val) {
        featuredToggle.checked  = !!val;
        featuredLabel.textContent = val ? 'Ya (tampil di beranda)' : 'Tidak';
    }

    if (featuredToggle) {
        featuredToggle.addEventListener('change', function () {
            featuredLabel.textContent = this.checked ? 'Ya (tampil di beranda)' : 'Tidak';
        });
    }

    /* ===== IMAGE UPLOAD PREVIEW ===== */
    const imageInput    = document.getElementById('imageInput');
    const uploadPreview = document.getElementById('uploadPreview');
    const previewImg    = document.getElementById('previewImg');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const uploadZone    = document.getElementById('uploadZone');

    if (imageInput) {
        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                alert('Ukuran file maksimal 5 MB.');
                this.value = '';
                return;
            }

            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowed.includes(file.type)) {
                alert('Format file harus JPG, JPEG, PNG, atau WEBP.');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                uploadPreview.style.display = '';
                uploadPlaceholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    window.removePreview = function () {
        imageInput.value = '';
        uploadPreview.style.display = 'none';
        uploadPlaceholder.style.display = '';
        previewImg.src = '';
    };

    // Drag & drop
    if (uploadZone) {
        uploadZone.addEventListener('dragover', (e) => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
        uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                imageInput.files = files;
                imageInput.dispatchEvent(new Event('change'));
            }
        });
    }

    /* ===== FORM VALIDATION ===== */
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', function (e) {
            let valid = true;

            const nameEl  = document.getElementById('name');
            const catEl   = document.getElementById('category_id');
            const priceEl = document.getElementById('price');

            // Clear previous errors
            [nameEl, catEl, priceEl].forEach(el => {
                el.classList.remove('is-error');
                const prev = el.parentElement.querySelector('.field-error');
                if (prev) prev.remove();
            });

            if (!nameEl.value.trim()) {
                showFieldError(nameEl, 'Nama produk wajib diisi.');
                valid = false;
            }
            if (!catEl.value) {
                showFieldError(catEl, 'Kategori wajib dipilih.');
                valid = false;
            }
            if (!priceEl.value || parseFloat(priceEl.value) <= 0) {
                showFieldError(priceEl, 'Harga harus lebih dari 0.');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });
    }

    function showFieldError(el, msg) {
        el.classList.add('is-error');
        const span = document.createElement('span');
        span.className = 'field-error';
        span.textContent = msg;
        el.parentElement.appendChild(span);
    }

    /* ===== AUTO OPEN MODAL (jika ada error dari server) ===== */
    const autoOpen = <?= $autoOpenModal ?>;
    const autoEdit = <?= $autoOpenEdit ?>;
    const editData = <?= $editDataJSON ?>;

    if (autoOpen) {
        if (autoEdit && editData) {
            openEditModal(editData);
        } else {
            openAddModal();
        }
    }

    /* ===== NUMBER formatting (price input) ===== */
    // Show formatted value beside price input
    const priceInput = document.getElementById('price');
    if (priceInput) {
        function formatNumber(n) {
            return 'Rp' + parseInt(n || 0).toLocaleString('id-ID');
        }
        let priceHint = document.createElement('small');
        priceHint.style.cssText = 'color:var(--orange);font-size:0.78rem;margin-top:0.3rem;display:block;';
        priceInput.parentElement.appendChild(priceHint);

        priceInput.addEventListener('input', function () {
            priceHint.textContent = this.value ? formatNumber(this.value) : '';
        });
    }

})();
</script>

</body>
</html>
