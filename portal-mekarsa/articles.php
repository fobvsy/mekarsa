<?php
/**
 * Mekarsa Coffee Bar - Admin Kelola Artikel
 * CRUD artikel: tambah, edit, hapus, ubah status
 * Upload gambar (thumbnail artikel) JPG/PNG disimpan di public/uploads/articles/
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
define('UPLOAD_DIR',  dirname(__DIR__) . '/public/uploads/articles/');
define('UPLOAD_URL',  '../public/uploads/articles/');
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

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes)) {
        return ['success' => false, 'error' => 'File harus berupa gambar yang valid.'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $newFilename = 'article_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = UPLOAD_DIR . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Gagal menyimpan file ke server.'];
    }

    if ($oldImage && file_exists(UPLOAD_DIR . $oldImage)) {
        @unlink(UPLOAD_DIR . $oldImage);
    }

    return ['success' => true, 'filename' => $newFilename];
}

// Generate Slug
function createSlug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return trim($slug, '-');
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

    // Ambil kategori artikel
    $categories = $pdo->query("SELECT id, name FROM article_categories ORDER BY name ASC")->fetchAll();

    // ----- ACTION: ADD ARTICLE -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
        $title       = trim($_POST['title'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $content     = trim($_POST['content'] ?? '');
        $status      = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';
        $slug        = createSlug($title);

        if (empty($title))      $formErrors['title']       = 'Judul artikel wajib diisi.';
        if ($categoryId <= 0)   $formErrors['category_id'] = 'Kategori wajib dipilih.';
        if (empty($content))    $formErrors['content']     = 'Konten artikel wajib diisi.';

        // Cek duplicate slug
        if (empty($formErrors)) {
            $check = $pdo->prepare("SELECT id FROM articles WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetch()) {
                $slug = $slug . '-' . time(); // Append time to make it unique
            }
        }

        if (empty($formErrors)) {
            $uploadResult = handleImageUpload($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            if (!$uploadResult['success']) {
                $formErrors['image'] = $uploadResult['error'];
            } else {
                $imageName = $uploadResult['filename'];
                $stmt = $pdo->prepare("
                    INSERT INTO articles (category_id, title, slug, content, image, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$categoryId, $title, $slug, $content, $imageName, $status]);
                header('Location: articles.php?msg=added');
                exit;
            }
        }
        if (!empty($formErrors)) {
            $flashMsg  = 'Terdapat kesalahan pada form. Periksa kembali.';
            $flashType = 'error';
        }
    }

    // ----- ACTION: EDIT ARTICLE -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $content     = trim($_POST['content'] ?? '');
        $status      = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';
        $slug        = createSlug($title);

        if ($id <= 0)           $formErrors['id']          = 'ID artikel tidak valid.';
        if (empty($title))      $formErrors['title']       = 'Judul artikel wajib diisi.';
        if ($categoryId <= 0)   $formErrors['category_id'] = 'Kategori wajib dipilih.';
        if (empty($content))    $formErrors['content']     = 'Konten artikel wajib diisi.';

        // Cek duplicate slug
        if (empty($formErrors)) {
            $check = $pdo->prepare("SELECT id FROM articles WHERE slug = ? AND id != ?");
            $check->execute([$slug, $id]);
            if ($check->fetch()) {
                $slug = $slug . '-' . time();
            }
        }

        if (empty($formErrors)) {
            $existingStmt = $pdo->prepare("SELECT image FROM articles WHERE id = ?");
            $existingStmt->execute([$id]);
            $existing  = $existingStmt->fetch();
            $oldImage  = $existing['image'] ?? null;

            $uploadResult = handleImageUpload($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], $oldImage);
            if (!$uploadResult['success']) {
                $formErrors['image'] = $uploadResult['error'];
            } else {
                $imageName = $uploadResult['filename'];
                $stmt = $pdo->prepare("
                    UPDATE articles
                    SET category_id=?, title=?, slug=?, content=?, image=?, status=?, updated_at=NOW()
                    WHERE id=?
                ");
                $stmt->execute([$categoryId, $title, $slug, $content, $imageName, $status, $id]);
                header('Location: articles.php?msg=updated');
                exit;
            }
        }
        if (!empty($formErrors)) {
            $flashType = 'error';
            $flashMsg  = 'Terdapat kesalahan. Periksa kembali.';
            $editData = [
                'id'          => $id,
                'title'       => $title,
                'category_id' => $categoryId,
                'content'     => $content,
                'status'      => $status,
                'image'       => $existing['image'] ?? null,
            ];
        }
    }

    // ----- ACTION: DELETE ARTICLE -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT title, image FROM articles WHERE id = ?");
            $stmt->execute([$id]);
            $art = $stmt->fetch();
            if ($art) {
                $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$id]);
                if ($art['image'] && file_exists(UPLOAD_DIR . $art['image'])) {
                    @unlink(UPLOAD_DIR . $art['image']);
                }
                header('Location: articles.php?msg=deleted');
                exit;
            }
        }
        header('Location: articles.php?msg=error');
        exit;
    }

    // ----- ACTION: TOGGLE STATUS -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT status FROM articles WHERE id = ?");
            $stmt->execute([$id]);
            $cur = $stmt->fetchColumn();
            $newStatus = ($cur === 'published') ? 'draft' : 'published';
            $pdo->prepare("UPDATE articles SET status=?, updated_at=NOW() WHERE id=?")->execute([$newStatus, $id]);
        }
        header('Location: articles.php?msg=status_updated');
        exit;
    }

    // ----- GET: Load edit data -----
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
        $editId = (int)$_GET['edit'];
        if ($editId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
            $stmt->execute([$editId]);
            $editData = $stmt->fetch();
            if (!$editData) $editData = null;
        }
    }

    // ----- Flash messages -----
    $msgMap = [
        'added'          => ['Artikel berhasil dipublikasikan!', 'success'],
        'updated'        => ['Artikel berhasil diperbarui!', 'success'],
        'deleted'        => ['Artikel berhasil dihapus.', 'success'],
        'status_updated' => ['Status artikel berhasil diubah.', 'success'],
        'error'          => ['Terjadi kesalahan. Silakan coba lagi.', 'error'],
    ];
    if (isset($_GET['msg']) && isset($msgMap[$_GET['msg']])) {
        [$flashMsg, $flashType] = $msgMap[$_GET['msg']];
    }

    // ----- FETCH: Semua artikel -----
    $search      = trim($_GET['search'] ?? '');
    $filterCat   = (int)($_GET['category'] ?? 0);
    $filterStatus = $_GET['status'] ?? '';
    $page        = max(1, (int)($_GET['page'] ?? 1));
    $perPage     = 10;
    $offset      = ($page - 1) * $perPage;

    $where  = [];
    $params = [];

    if (!empty($search)) {
        $where[]  = "(a.title LIKE ? OR a.content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($filterCat > 0) {
        $where[]  = "a.category_id = ?";
        $params[] = $filterCat;
    }
    if (in_array($filterStatus, ['published', 'draft'])) {
        $where[]  = "a.status = ?";
        $params[] = $filterStatus;
    }

    $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM articles a $whereSQL");
    $countStmt->execute($params);
    $totalRows  = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $perPage));

    $listStmt = $pdo->prepare("
        SELECT a.*, ac.name AS category_name
        FROM articles a
        LEFT JOIN article_categories ac ON a.category_id = ac.id
        $whereSQL
        ORDER BY a.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $listStmt->execute($params);
    $articles = $listStmt->fetchAll();

    $statsStmt = $pdo->query("
        SELECT
            COUNT(*) AS total,
            SUM(status='published') AS published_count,
            SUM(status='draft') AS draft_count
        FROM articles
    ");
    $articleStats = $statsStmt->fetch();

} catch (PDOException $e) {
    $articles     = [];
    $categories   = [];
    $totalPages   = 1;
    $totalRows    = 0;
    $articleStats = ['total'=>0,'published_count'=>0,'draft_count'=>0];
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
    <title>Kelola Artikel — Mekarsa Admin</title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700;800&family=Inter:wght@400;500;600&family=Anton&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ===== DESIGN TOKENS & RESET ===== */
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

        /* ===== STATS CARDS ===== */
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.75rem; }
        .stat-mini { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; transition: transform 0.2s; }
        .stat-mini:hover { transform: translateY(-2px); border-color: var(--orange); }
        .stat-mini-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
        .stat-mini--orange .stat-mini-icon { background: rgba(242,113,33,0.15); color: var(--orange); }
        .stat-mini--green .stat-mini-icon  { background: rgba(34,197,94,0.12);  color: var(--green); }
        .stat-mini--red .stat-mini-icon    { background: rgba(248,113,113,0.1); color: var(--red); }
        .stat-mini-value { font-family: var(--font-price); font-size: 1.8rem; line-height: 1; }
        .stat-mini-label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }

        /* ===== FILTER BAR ===== */
        .filter-bar { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; flex-wrap: wrap; }
        .filter-bar form { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; width: 100%; }
        .search-group { position: relative; flex: 1; min-width: 200px; }
        .search-group i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
        .search-input { width: 100%; padding: 0.7rem 1rem 0.7rem 2.7rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; transition: border-color 0.2s; }
        .search-input:focus { border-color: var(--orange); }
        .filter-select { padding: 0.7rem 1rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; cursor: pointer; }
        .filter-select:focus { border-color: var(--orange); }
        .filter-select option { background: var(--bg-card); }
        .btn-filter { padding: 0.7rem 1.2rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-filter:hover { background: var(--orange-dark); }
        .btn-reset { padding: 0.7rem 1rem; background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: 8px; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; }
        .btn-reset:hover { color: var(--text); border-color: var(--text-muted); }

        /* ===== TABLE ===== */
        .table-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .table-card-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); }
        .table-card-header h3 { font-family: var(--font-head); font-size: 1rem; font-weight: 700; }
        .table-total { font-size: 0.82rem; color: var(--text-muted); }
        .table-wrapper { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; min-width: 900px; }
        .data-table th { padding: 0.75rem 1rem; text-align: left; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border); white-space: nowrap; }
        .data-table td { padding: 0.9rem 1rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .data-table tr:hover td { background: rgba(255,255,255,0.02); }
        
        .article-cell { display: flex; align-items: flex-start; gap: 0.9rem; }
        .article-thumb { width: 80px; height: 55px; border-radius: 6px; object-fit: cover; border: 1px solid var(--border); flex-shrink: 0; background: var(--bg-input); }
        .article-thumb-placeholder { width: 80px; height: 55px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-input); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 1.3rem; flex-shrink: 0; }
        .article-title { font-weight: 600; color: var(--text); font-size: 0.95rem; margin-bottom: 0.2rem; line-height: 1.3; }
        .article-cat { font-size: 0.75rem; color: var(--orange); font-weight: 600; text-transform: uppercase; }

        .badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; font-family: var(--font-head); text-transform: uppercase; }
        .badge--active { background: rgba(34,197,94,0.12); color: var(--green); border: 1px solid rgba(34,197,94,0.3); }
        .badge--inactive { background: rgba(161,161,170,0.1); color: var(--text-muted); border: 1px solid rgba(161,161,170,0.2); }

        .action-group { display: flex; align-items: center; gap: 0.4rem; }
        .action-btn { width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--border); background: transparent; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; color: var(--text-muted); }
        .action-btn:hover { border-color: var(--orange); color: var(--orange); background: var(--orange-glow); }
        .action-btn--danger:hover { border-color: var(--red); color: var(--red); background: rgba(248,113,113,0.1); }
        .action-btn--success:hover { border-color: var(--green); color: var(--green); background: rgba(34,197,94,0.08); }

        .empty-state { text-align: center; padding: 4rem 1.5rem; color: var(--text-muted); }
        .empty-state-icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.2; }
        .empty-state h3 { font-family: var(--font-head); font-size: 1.2rem; margin-bottom: 0.5rem; color: var(--text); opacity: 0.5; }

        .pagination { display: flex; justify-content: center; gap: 0.5rem; padding: 1.25rem; border-top: 1px solid var(--border); }
        .page-btn { width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border); background: transparent; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; transition: all 0.2s; }
        .page-btn:hover { border-color: var(--orange); color: var(--orange); }
        .page-btn.active { background: var(--orange); border-color: var(--orange); color: #fff; font-weight: 700; }

        /* ===== MODAL ===== */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 200; align-items: flex-start; justify-content: center; padding: 2rem 1rem; overflow-y: auto; backdrop-filter: blur(4px); }
        .modal-overlay.open { display: flex; }
        .modal { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 800px; margin: auto; animation: modalIn 0.3s ease; box-shadow: 0 24px 80px rgba(0,0,0,0.7); }
        @keyframes modalIn { from { opacity:0; transform: scale(0.96) translateY(20px); } to { opacity:1; transform:none; } }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 1.75rem; border-bottom: 1px solid var(--border); }
        .modal-title { font-family: var(--font-head); font-size: 1.15rem; font-weight: 800; }
        .modal-close { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: transparent; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .modal-close:hover { border-color: var(--red); color: var(--red); background: rgba(248,113,113,0.08); }
        .modal-body { padding: 1.75rem; }
        .modal-footer { padding: 1.25rem 1.75rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.75rem; }

        /* ===== FORM ===== */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .form-grid--full { grid-column: 1 / -1; }
        .form-group { margin-bottom: 0; }
        .form-label { display: block; font-weight: 600; font-size: 0.85rem; color: var(--text); margin-bottom: 0.45rem; }
        .form-label .req { color: var(--orange); }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 0.75rem 1rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; transition: border-color 0.2s; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--orange); box-shadow: 0 0 0 3px rgba(242,113,33,0.12); }
        .form-textarea { resize: vertical; min-height: 200px; font-family: monospace; font-size: 0.85rem; }
        .form-select option { background: var(--bg-card); }
        .form-input.is-error, .form-select.is-error, .form-textarea.is-error { border-color: var(--red); box-shadow: 0 0 0 3px rgba(248,113,113,0.1); }
        .field-error { font-size: 0.78rem; color: var(--red); margin-top: 0.3rem; display: block; }

        /* Toggle */
        .toggle-row { display: flex; align-items: center; gap: 0.75rem; }
        .toggle-switch { position: relative; width: 44px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
        .toggle-slider { position: absolute; cursor: pointer; inset: 0; background: var(--border); border-radius: 999px; transition: 0.3s; }
        .toggle-slider::before { content: ''; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s; }
        .toggle-switch input:checked + .toggle-slider { background: var(--orange); }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }
        .toggle-label { font-size: 0.875rem; font-weight: 500; }

        /* Upload */
        .upload-zone { border: 2px dashed var(--border); border-radius: 10px; padding: 1.5rem; text-align: center; cursor: pointer; transition: all 0.2s; position: relative; }
        .upload-zone:hover, .upload-zone.drag-over { border-color: var(--orange); background: rgba(242,113,33,0.04); }
        .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .upload-icon { font-size: 2rem; color: var(--text-muted); margin-bottom: 0.5rem; opacity: 0.5; }
        .upload-text { font-size: 0.875rem; color: var(--text-muted); }
        .upload-preview { margin-top: 0.75rem; display: none; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; position: relative; }
        .upload-preview img { width: 100%; max-height: 200px; object-fit: cover; display: block; }
        .upload-preview-remove { position: absolute; top: 0.5rem; right: 0.5rem; width: 28px; height: 28px; border-radius: 6px; background: rgba(0,0,0,0.7); border: none; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; }

        /* Buttons */
        .btn-primary { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.8rem 1.5rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-family: var(--font-head); font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; }
        .btn-primary:hover { background: var(--orange-dark); }
        .btn-secondary { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.8rem 1.5rem; background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: 8px; font-family: var(--font-body); font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; }
        .btn-secondary:hover { border-color: var(--text-muted); color: var(--text); }
        .btn-danger { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.8rem 1.5rem; background: rgba(248,113,113,0.15); color: var(--red); border: 1px solid rgba(248,113,113,0.3); border-radius: 8px; font-family: var(--font-head); font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .btn-danger:hover { background: rgba(248,113,113,0.25); border-color: var(--red); }

        /* Confirm Modal */
        .confirm-modal { max-width: 420px; text-align: center; }
        .confirm-icon { font-size: 3rem; margin-bottom: 1rem; }
        .confirm-modal h3 { font-family: var(--font-head); font-size: 1.2rem; margin-bottom: 0.5rem; }
        .confirm-modal p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;}

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-toggle { display: flex; }
            .main { margin-left: 0; }
            .topbar { padding: 0 1rem; }
            .page-content { padding: 1.25rem 1rem; }
            .stats-row { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

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
        <a href="dashboard.php" class="nav-item"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <div class="nav-section-label" style="margin-top:0.75rem;">Kelola Konten</div>
        <a href="products.php" class="nav-item"><i class="fas fa-mug-saucer"></i> Produk Coffee</a>
        <a href="product-categories.php" class="nav-item"><i class="fas fa-tags"></i> Kategori Produk</a>
        <a href="articles.php" class="nav-item active"><i class="fas fa-newspaper"></i> Artikel</a>
        <a href="article-categories.php" class="nav-item"><i class="fas fa-folder-open"></i> Kategori Artikel</a>
        <a href="gallery.php" class="nav-item"><i class="fas fa-images"></i> Galeri</a>
        <a href="testimonials.php" class="nav-item"><i class="fas fa-star"></i> Testimoni</a>
        <div class="nav-section-label" style="margin-top:0.75rem;">Operasional</div>
        <a href="orders.php" class="nav-item"><i class="fas fa-receipt"></i> Pesanan</a>
        <a href="support-services.php" class="nav-item"><i class="fas fa-shoe-prints"></i> Layanan Pendukung</a>
        <a href="settings.php" class="nav-item"><i class="fas fa-gear"></i> Pengaturan Website</a>
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
            <nav class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-house" style="font-size:0.8rem;"></i></a>
                <i class="fas fa-chevron-right" style="font-size:0.65rem;"></i>
                <span>Artikel</span>
            </nav>
        </div>
        <div class="topbar-right">
            <span class="topbar-time" id="currentTime"></span>
            <a href="../articles.php" class="topbar-view-site" target="_blank">
                <i class="fas fa-arrow-up-right-from-square"></i> Lihat Artikel
            </a>
        </div>
    </header>

    <main class="page-content">
        <div class="page-header">
            <div class="page-header-title">
                <h1><i class="fas fa-newspaper" style="color:var(--orange);margin-right:0.5rem;font-size:1.4rem;"></i>Artikel & Blog</h1>
                <p>Kelola konten artikel, berita, atau promo Mekarsa Coffee Bar.</p>
            </div>
            <button class="btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Buat Artikel
            </button>
        </div>

        <?php if (!empty($flashMsg)): ?>
            <div class="page-alert page-alert--<?= $flashType ?>" id="pageAlert">
                <i class="fas <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <span><?= $flashMsg ?></span>
            </div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-mini stat-mini--orange">
                <div class="stat-mini-icon"><i class="fas fa-newspaper"></i></div>
                <div>
                    <div class="stat-mini-value"><?= $articleStats['total'] ?? 0 ?></div>
                    <div class="stat-mini-label">Total Artikel</div>
                </div>
            </div>
            <div class="stat-mini stat-mini--green">
                <div class="stat-mini-icon"><i class="fas fa-check-double"></i></div>
                <div>
                    <div class="stat-mini-value"><?= $articleStats['published_count'] ?? 0 ?></div>
                    <div class="stat-mini-label">Dipublikasi</div>
                </div>
            </div>
            <div class="stat-mini stat-mini--red">
                <div class="stat-mini-icon"><i class="fas fa-pen-ruler"></i></div>
                <div>
                    <div class="stat-mini-value"><?= $articleStats['draft_count'] ?? 0 ?></div>
                    <div class="stat-mini-label">Draft</div>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <form method="GET" action="articles.php">
                <div class="search-group">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" name="search" class="search-input" placeholder="Cari judul artikel..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <select name="category" class="filter-select">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filterCat == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="filter-select">
                    <option value="">Semua Status</option>
                    <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
                <button type="submit" class="btn-filter">Filter</button>
                <?php if ($search || $filterCat || $filterStatus): ?>
                    <a href="articles.php" class="btn-reset">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-card">
            <div class="table-card-header">
                <h3>Daftar Artikel</h3>
                <span class="table-total"><?= $totalRows ?> artikel ditemukan</span>
            </div>
            <div class="table-wrapper">
                <?php if (!empty($articles)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Artikel</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $art): ?>
                                <tr>
                                    <td>
                                        <div class="article-cell">
                                            <?php if (!empty($art['image']) && file_exists(UPLOAD_DIR . $art['image'])): ?>
                                                <img src="<?= UPLOAD_URL . htmlspecialchars($art['image']) ?>" class="article-thumb" alt="Thumbnail">
                                            <?php else: ?>
                                                <div class="article-thumb-placeholder"><i class="fas fa-image"></i></div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="article-title"><?= htmlspecialchars($art['title']) ?></div>
                                                <div class="article-cat"><?= htmlspecialchars($art['category_name'] ?? '—') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($art['status'] === 'published'): ?>
                                            <span class="badge badge--active"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Published</span>
                                        <?php else: ?>
                                            <span class="badge badge--inactive"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--text-muted);font-size:0.85rem;">
                                        <?= date('d M Y', strtotime($art['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <button class="action-btn" onclick="openEditModal(<?= htmlspecialchars(json_encode($art)) ?>)" title="Edit">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $art['id'] ?>">
                                                <button type="submit" class="action-btn action-btn--success" title="<?= $art['status'] === 'published' ? 'Ubah ke Draft' : 'Publish' ?>">
                                                    <i class="fas <?= $art['status'] === 'published' ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                                </button>
                                            </form>
                                            <button class="action-btn action-btn--danger" onclick="confirmDelete(<?= $art['id'] ?>, '<?= addslashes(htmlspecialchars($art['title'])) ?>')" title="Hapus">
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
                        <div class="empty-state-icon"><i class="fas fa-newspaper"></i></div>
                        <h3>Belum ada artikel</h3>
                        <p>Mulai tulis artikel atau promo pertama Anda.</p>
                        <?php if (!$search && !$filterCat && !$filterStatus): ?>
                            <button class="btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Buat Artikel</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $filterCat ?>&status=<?= $filterStatus ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal ADD/EDIT -->
<div class="modal-overlay" id="articleModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Buat Artikel</h2>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="articleForm" method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId" value="">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group form-grid--full">
                        <label class="form-label" for="title">Judul Artikel <span class="req">*</span></label>
                        <input type="text" id="title" name="title" class="form-input <?= isset($formErrors['title']) ? 'is-error' : '' ?>" required value="<?= htmlspecialchars($editData['title'] ?? $_POST['title'] ?? '') ?>">
                        <?php if (isset($formErrors['title'])): ?><span class="field-error"><?= $formErrors['title'] ?></span><?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="category_id">Kategori <span class="req">*</span></label>
                        <select id="category_id" name="category_id" class="form-select <?= isset($formErrors['category_id']) ? 'is-error' : '' ?>" required>
                            <option value="">— Pilih Kategori —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (int)($editData['category_id'] ?? $_POST['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($formErrors['category_id'])): ?><span class="field-error"><?= $formErrors['category_id'] ?></span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <div class="toggle-row">
                            <label class="toggle-switch">
                                <input type="checkbox" name="status" id="statusToggle" value="published" <?= (($editData['status'] ?? $_POST['status'] ?? 'draft') === 'published') ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-label" id="statusLabel"><?= (($editData['status'] ?? 'draft') === 'published') ? 'Published' : 'Draft' ?></span>
                        </div>
                        <input type="hidden" name="status" id="statusHidden" value="<?= ($editData['status'] ?? 'draft') ?>">
                    </div>

                    <div class="form-group form-grid--full">
                        <label class="form-label" for="content">Isi Artikel <span class="req">*</span> <small style="color:var(--text-muted); font-weight:normal;">(Mendukung tag HTML sederhana seperti &lt;p&gt;, &lt;b&gt;, &lt;strong&gt;, &lt;br&gt;)</small></label>
                        <textarea id="content" name="content" class="form-textarea <?= isset($formErrors['content']) ? 'is-error' : '' ?>" required placeholder="<p>Tulis paragraf pertama di sini...</p>"><?= htmlspecialchars($editData['content'] ?? $_POST['content'] ?? '') ?></textarea>
                        <?php if (isset($formErrors['content'])): ?><span class="field-error"><?= $formErrors['content'] ?></span><?php endif; ?>
                    </div>

                    <div class="form-group form-grid--full">
                        <label class="form-label">Thumbnail Artikel</label>
                        <div id="existingImgWrap" style="display:none; margin-bottom:0.75rem;">
                            <img id="existingImg" src="" style="height:60px; border-radius:6px; border:1px solid var(--border);">
                            <div style="font-size:0.75rem; color:var(--text-muted);">Gambar saat ini. Upload baru untuk mengganti.</div>
                        </div>
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" name="image" id="imageInput" accept=".jpg,.jpeg,.png,.webp">
                            <div id="uploadPlaceholder">
                                <div class="upload-icon"><i class="fas fa-image"></i></div>
                                <div class="upload-text"><strong>Klik untuk upload</strong> atau drag & drop</div>
                            </div>
                        </div>
                        <div class="upload-preview" id="uploadPreview">
                            <img id="previewImg" src="">
                            <button type="button" class="upload-preview-remove" onclick="removePreview()"><i class="fas fa-xmark"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary" id="btnSubmit">Simpan Artikel</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Delete -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal confirm-modal">
        <div class="modal-body">
            <div class="confirm-icon">🗑️</div>
            <h3>Hapus Artikel?</h3>
            <p>Anda akan menghapus artikel <strong id="delName"></strong>. Gambar thumbnail juga akan dihapus.</p>
        </div>
        <div class="modal-footer" style="justify-content:center; border-top:none;">
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delId" value="">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Batal</button>
                <button type="submit" class="btn-danger">Ya, Hapus</button>
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
(function() {
    'use strict';
    // Sidebar
    const sidebar = document.getElementById('sidebar'), overlay = document.getElementById('sidebarOverlay'), toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) toggleBtn.addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('visible'); });
    if (overlay) overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('visible'); });

    // Clock
    const timeEl = document.getElementById('currentTime');
    function updateClock() { if (timeEl) timeEl.textContent = new Date().toLocaleString('id-ID', { weekday:'short', day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' }); }
    updateClock(); setInterval(updateClock, 60000);

    // Alert
    const alertEl = document.getElementById('pageAlert');
    if (alertEl) { setTimeout(() => { alertEl.style.opacity = '0'; setTimeout(() => alertEl.remove(), 500); }, 4000); }

    // Modals
    const artModal = document.getElementById('articleModal');
    const delModal = document.getElementById('deleteModal');

    window.openAddModal = function() {
        document.getElementById('modalTitle').textContent = 'Buat Artikel';
        document.getElementById('formAction').value = 'add';
        document.getElementById('formId').value = '';
        document.getElementById('articleForm').reset();
        document.getElementById('existingImgWrap').style.display = 'none';
        removePreview();
        setStatusToggle('draft');
        artModal.classList.add('open');
    };

    window.openEditModal = function(art) {
        document.getElementById('modalTitle').textContent = 'Edit Artikel';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('formId').value = art.id;
        document.getElementById('title').value = art.title || '';
        document.getElementById('category_id').value = art.category_id || '';
        document.getElementById('content').value = art.content || '';
        setStatusToggle(art.status || 'draft');

        if (art.image) {
            document.getElementById('existingImg').src = '../public/uploads/articles/' + art.image;
            document.getElementById('existingImgWrap').style.display = 'block';
        } else {
            document.getElementById('existingImgWrap').style.display = 'none';
        }
        removePreview();
        artModal.classList.add('open');
    };

    window.closeModal = function() { artModal.classList.remove('open'); };

    window.confirmDelete = function(id, title) {
        document.getElementById('delId').value = id;
        document.getElementById('delName').textContent = title;
        delModal.classList.add('open');
    };
    window.closeDeleteModal = function() { delModal.classList.remove('open'); };

    // Toggle Status
    const statusToggle = document.getElementById('statusToggle');
    const statusLabel = document.getElementById('statusLabel');
    const statusHidden = document.getElementById('statusHidden');
    function setStatusToggle(val) {
        const isPub = val === 'published';
        statusToggle.checked = isPub;
        statusHidden.value = val;
        statusLabel.textContent = isPub ? 'Published' : 'Draft';
    }
    if (statusToggle) {
        statusToggle.addEventListener('change', function() {
            setStatusToggle(this.checked ? 'published' : 'draft');
        });
    }

    // Image Upload
    const imgInput = document.getElementById('imageInput'),
          upZone = document.getElementById('uploadZone'),
          upPreview = document.getElementById('uploadPreview'),
          upPlaceholder = document.getElementById('uploadPlaceholder'),
          previewImg = document.getElementById('previewImg');

    if (imgInput) {
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
    }
    window.removePreview = function() {
        if(imgInput) imgInput.value = '';
        if(upPreview) upPreview.style.display = 'none';
        if(upPlaceholder) upPlaceholder.style.display = 'block';
    };

    // Validation
    const form = document.getElementById('articleForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const title = document.getElementById('title');
            const cat = document.getElementById('category_id');
            const content = document.getElementById('content');
            let valid = true;
            [title, cat, content].forEach(el => el.classList.remove('is-error'));

            if (!title.value.trim()) { title.classList.add('is-error'); valid = false; }
            if (!cat.value) { cat.classList.add('is-error'); valid = false; }
            if (!content.value.trim()) { content.classList.add('is-error'); valid = false; }

            if (!valid) e.preventDefault();
        });
    }

    // Auto open on error
    if (<?= $autoOpenModal ?>) {
        if (<?= $autoOpenEdit ?> && <?= $editDataJSON ?>) {
            openEditModal(<?= $editDataJSON ?>);
        } else {
            openAddModal();
        }
    }
})();
</script>
</body>
</html>
