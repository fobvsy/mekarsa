<?php
/**
 * Mekarsa Coffee Bar - Admin Kelola Testimoni
 * CRUD testimoni: tambah, edit, hapus, ubah status
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

    // ----- ACTION: ADD TESTIMONI -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
        $name    = trim($_POST['customer_name'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $rating  = (int)($_POST['rating'] ?? 5);
        $status  = in_array($_POST['status'] ?? '', ['show', 'hide']) ? $_POST['status'] : 'show';

        if (empty($name))    $formErrors['customer_name'] = 'Nama pelanggan wajib diisi.';
        if (empty($message)) $formErrors['message']       = 'Pesan testimoni wajib diisi.';
        if ($rating < 1 || $rating > 5) $rating = 5;

        if (empty($formErrors)) {
            $stmt = $pdo->prepare("INSERT INTO testimonials (customer_name, message, rating, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $message, $rating, $status]);
            header('Location: testimonials.php?msg=added');
            exit;
        } else {
            $flashMsg  = 'Terdapat kesalahan pada form. Periksa kembali.';
            $flashType = 'error';
        }
    }

    // ----- ACTION: EDIT TESTIMONI -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $name    = trim($_POST['customer_name'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $rating  = (int)($_POST['rating'] ?? 5);
        $status  = in_array($_POST['status'] ?? '', ['show', 'hide']) ? $_POST['status'] : 'show';

        if ($id <= 0)        $formErrors['id']            = 'ID testimoni tidak valid.';
        if (empty($name))    $formErrors['customer_name'] = 'Nama pelanggan wajib diisi.';
        if (empty($message)) $formErrors['message']       = 'Pesan testimoni wajib diisi.';
        if ($rating < 1 || $rating > 5) $rating = 5;

        if (empty($formErrors)) {
            $stmt = $pdo->prepare("UPDATE testimonials SET customer_name=?, message=?, rating=?, status=? WHERE id=?");
            $stmt->execute([$name, $message, $rating, $status, $id]);
            header('Location: testimonials.php?msg=updated');
            exit;
        } else {
            $flashType = 'error';
            $flashMsg  = 'Terdapat kesalahan pada form. Periksa kembali.';
            $editData  = ['id' => $id, 'customer_name' => $name, 'message' => $message, 'rating' => $rating, 'status' => $status];
        }
    }

    // ----- ACTION: DELETE TESTIMONI -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM testimonials WHERE id = ?")->execute([$id]);
            header('Location: testimonials.php?msg=deleted');
            exit;
        }
        header('Location: testimonials.php?msg=error');
        exit;
    }

    // ----- ACTION: TOGGLE STATUS -----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT status FROM testimonials WHERE id = ?");
            $stmt->execute([$id]);
            $cur = $stmt->fetchColumn();
            $newStatus = ($cur === 'show') ? 'hide' : 'show';
            $pdo->prepare("UPDATE testimonials SET status=? WHERE id=?")->execute([$newStatus, $id]);
        }
        header('Location: testimonials.php?msg=status_updated');
        exit;
    }

    // ----- GET: Load edit data -----
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
        $editId = (int)$_GET['edit'];
        if ($editId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
            $stmt->execute([$editId]);
            $editData = $stmt->fetch();
            if (!$editData) $editData = null;
        }
    }

    // ----- Flash messages -----
    $msgMap = [
        'added'          => ['Testimoni berhasil ditambahkan!', 'success'],
        'updated'        => ['Testimoni berhasil diperbarui!', 'success'],
        'deleted'        => ['Testimoni berhasil dihapus.', 'success'],
        'status_updated' => ['Status tampilan testimoni berhasil diubah.', 'success'],
        'error'          => ['Terjadi kesalahan. Silakan coba lagi.', 'error'],
    ];
    if (isset($_GET['msg']) && isset($msgMap[$_GET['msg']])) {
        [$flashMsg, $flashType] = $msgMap[$_GET['msg']];
    }

    // ----- FETCH: Semua testimoni -----
    $search       = trim($_GET['search'] ?? '');
    $filterStatus = $_GET['status'] ?? '';
    
    $where  = [];
    $params = [];
    if (!empty($search)) {
        $where[]  = "(customer_name LIKE ? OR message LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if (in_array($filterStatus, ['show', 'hide'])) {
        $where[]  = "status = ?";
        $params[] = $filterStatus;
    }

    $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $listStmt = $pdo->prepare("SELECT * FROM testimonials $whereSQL ORDER BY created_at DESC");
    $listStmt->execute($params);
    $testimonials = $listStmt->fetchAll();

    $totalRows = count($testimonials);

    // Stats
    $statsStmt = $pdo->query("SELECT COUNT(*) AS total, SUM(status='show') AS show_count FROM testimonials");
    $stats = $statsStmt->fetch();

} catch (PDOException $e) {
    $testimonials = [];
    $totalRows = 0;
    $stats = ['total' => 0, 'show_count' => 0];
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
    <title>Kelola Testimoni — Mekarsa Admin</title>
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
        .topbar { height: var(--topbar-h); background: rgba(10,10,10,0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; position: sticky; top: 0; z-index: 50; }
        .topbar-left { display: flex; align-items: center; gap: 1rem; }
        .sidebar-toggle { display: none; width: 38px; height: 38px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text); cursor: pointer; align-items: center; justify-content: center; font-size: 1rem; }
        .breadcrumb { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--text-muted); }
        .breadcrumb a { color: var(--text-muted); }
        .breadcrumb a:hover { color: var(--orange); }
        .breadcrumb span { color: var(--text); font-weight: 600; }
        .topbar-right { display: flex; align-items: center; gap: 1rem; }
        .topbar-time { font-size: 0.82rem; color: var(--text-muted); }

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
        .top-widgets { display: grid; grid-template-columns: 240px 240px 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        @media(max-width: 1024px) { .top-widgets { grid-template-columns: 1fr 1fr; } .filter-bar { grid-column: 1 / -1; } }
        @media(max-width: 600px) { .top-widgets { grid-template-columns: 1fr; } }
        
        .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; }
        .stat-icon { width: 44px; height: 44px; border-radius: 10px; background: rgba(242,113,33,0.15); color: var(--orange); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .stat-icon--green { background: rgba(34,197,94,0.15); color: var(--green); }
        .stat-val { font-family: var(--font-price); font-size: 1.8rem; line-height: 1; margin-bottom: 0.2rem; }
        .stat-lbl { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        .filter-bar { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem 1.5rem; display: flex; align-items: center; }
        .filter-bar form { display: flex; align-items: center; gap: 0.75rem; width: 100%; flex-wrap: wrap; }
        .search-group { position: relative; flex: 1; min-width: 200px; }
        .search-group i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
        .search-input { width: 100%; padding: 0.75rem 1rem 0.75rem 2.7rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; transition: border-color 0.2s; }
        .search-input:focus { border-color: var(--orange); }
        .filter-select { padding: 0.75rem 1rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; cursor: pointer; }
        .filter-select option { background: var(--bg-card); }
        .btn-filter { padding: 0.75rem 1.5rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-filter:hover { background: var(--orange-dark); }
        .btn-reset { padding: 0.75rem 1rem; background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: 8px; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; }
        .btn-reset:hover { color: var(--text); border-color: var(--text-muted); }

        /* ===== TESTIMONIAL GRID ===== */
        .testi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
        .testi-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; display: flex; flex-direction: column; position: relative; transition: transform 0.2s; }
        .testi-card:hover { transform: translateY(-4px); border-color: rgba(242,113,33,0.3); }
        
        .testi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .testi-user { display: flex; align-items: center; gap: 0.75rem; }
        .testi-avatar { width: 44px; height: 44px; border-radius: 50%; background: var(--bg-input); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-family: var(--font-head); font-weight: 800; font-size: 1.2rem; color: var(--orange); }
        .testi-name { font-weight: 700; color: var(--text); font-size: 1rem; line-height: 1.2; }
        .testi-date { font-size: 0.75rem; color: var(--text-muted); }
        
        .testi-rating { color: var(--yellow); font-size: 0.85rem; display: flex; gap: 0.15rem; margin-top: 0.2rem; }
        .testi-rating .fa-star.empty { color: #3f3f46; }

        .testi-msg { font-size: 0.95rem; color: #d4d4d8; line-height: 1.6; font-style: italic; flex: 1; margin-bottom: 1.5rem; }
        .testi-msg::before { content: '"'; font-size: 1.2rem; color: var(--orange); font-family: var(--font-head); }
        .testi-msg::after { content: '"'; font-size: 1.2rem; color: var(--orange); font-family: var(--font-head); }

        .testi-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 1rem; }
        
        .badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .badge--show { background: rgba(34,197,94,0.12); color: var(--green); border: 1px solid rgba(34,197,94,0.3); }
        .badge--hide { background: rgba(161,161,170,0.1); color: var(--text-muted); border: 1px solid rgba(161,161,170,0.2); }

        .action-group { display: flex; align-items: center; gap: 0.4rem; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-input); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; color: var(--text-muted); }
        .action-btn:hover { border-color: var(--orange); color: var(--orange); background: var(--orange-glow); }
        .action-btn--danger:hover { border-color: var(--red); color: var(--red); background: rgba(248,113,113,0.1); }
        .action-btn--success:hover { border-color: var(--green); color: var(--green); background: rgba(34,197,94,0.08); }

        .empty-state { grid-column: 1 / -1; text-align: center; padding: 5rem 1.5rem; background: var(--bg-card); border: 1px dashed var(--border); border-radius: 16px; color: var(--text-muted); }
        .empty-state-icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.2; }
        .empty-state h3 { font-family: var(--font-head); font-size: 1.2rem; margin-bottom: 0.5rem; color: var(--text); opacity: 0.5; }

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

        .form-group { margin-bottom: 1.25rem; }
        .form-group:last-child { margin-bottom: 0; }
        .form-label { display: block; font-weight: 600; font-size: 0.85rem; color: var(--text); margin-bottom: 0.45rem; }
        .form-label .req { color: var(--orange); }
        .form-input, .form-textarea, .form-select { width: 100%; padding: 0.75rem 1rem; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem; outline: none; transition: all 0.2s; }
        .form-select option { background: var(--bg-card); }
        .form-input:focus, .form-textarea:focus, .form-select:focus { border-color: var(--orange); box-shadow: 0 0 0 3px rgba(242,113,33,0.12); }
        .form-textarea { resize: vertical; min-height: 120px; }
        .form-input.is-error, .form-textarea.is-error { border-color: var(--red); box-shadow: 0 0 0 3px rgba(248,113,113,0.1); }
        .field-error { font-size: 0.78rem; color: var(--red); margin-top: 0.3rem; display: block; }

        .rating-select { display: flex; gap: 0.5rem; flex-direction: row-reverse; justify-content: flex-end; }
        .rating-select input { display: none; }
        .rating-select label { cursor: pointer; color: #3f3f46; font-size: 1.5rem; transition: 0.2s; }
        .rating-select label:hover, .rating-select label:hover ~ label, .rating-select input:checked ~ label { color: var(--yellow); }

        .btn-primary { padding: 0.75rem 1.5rem; background: var(--orange); color: #fff; border: none; border-radius: 8px; font-family: var(--font-head); font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-primary:hover { background: var(--orange-dark); }
        .btn-secondary { padding: 0.75rem 1.5rem; background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: 8px; cursor: pointer; transition: 0.2s; }
        .btn-secondary:hover { color: var(--text); border-color: var(--text); }
        .btn-danger { padding: 0.75rem 1.5rem; background: var(--red); color: #fff; border: none; border-radius: 8px; font-family: var(--font-head); font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-danger:hover { background: #dc2626; }

        /* Mobile */
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99; }
        .sidebar-overlay.visible { display: block; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-toggle { display: flex; }
            .main { margin-left: 0; }
            .topbar { padding: 0 1rem; }
            .page-content { padding: 1.25rem 1rem; }
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
        <a href="products.php" class="nav-item"><i class="fas fa-mug-saucer"></i> Produk</a>
        <a href="product-categories.php" class="nav-item"><i class="fas fa-tags"></i> Kategori Produk</a>
        <a href="articles.php" class="nav-item"><i class="fas fa-newspaper"></i> Artikel</a>
        <a href="article-categories.php" class="nav-item"><i class="fas fa-folder-open"></i> Kategori Artikel</a>
        <a href="gallery.php" class="nav-item"><i class="fas fa-images"></i> Galeri</a>
        <a href="testimonials.php" class="nav-item active"><i class="fas fa-star"></i> Testimoni</a>
        <div class="nav-section-label" style="margin-top:0.75rem;">Operasional</div>
        <a href="orders.php" class="nav-item"><i class="fas fa-receipt"></i> Pesanan</a>
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
            <nav class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-house" style="font-size:0.8rem;"></i></a>
                <i class="fas fa-chevron-right" style="font-size:0.65rem;"></i>
                <span>Testimoni</span>
            </nav>
        </div>
        <div class="topbar-right">
            <span class="topbar-time" id="currentTime"></span>
        </div>
    </header>

    <main class="page-content">
        <div class="page-header">
            <div class="page-header-title">
                <h1><i class="fas fa-star" style="color:var(--orange);margin-right:0.5rem;font-size:1.4rem;"></i>Testimoni Pelanggan</h1>
                <p>Kelola ulasan dan feedback dari pelanggan untuk ditampilkan di website.</p>
            </div>
            <button class="btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Tambah Testimoni
            </button>
        </div>

        <?php if (!empty($flashMsg)): ?>
            <div class="page-alert page-alert--<?= $flashType ?>" id="pageAlert">
                <i class="fas <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <span><?= $flashMsg ?></span>
            </div>
        <?php endif; ?>

        <div class="top-widgets">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-comments"></i></div>
                <div>
                    <div class="stat-val"><?= $stats['total'] ?? 0 ?></div>
                    <div class="stat-lbl">Total Testimoni</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon--green"><i class="fas fa-eye"></i></div>
                <div>
                    <div class="stat-val"><?= $stats['show_count'] ?? 0 ?></div>
                    <div class="stat-lbl">Ditampilkan</div>
                </div>
            </div>
            <div class="filter-bar">
                <form method="GET" action="testimonials.php">
                    <div class="search-group">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" name="search" class="search-input" placeholder="Cari nama atau isi testimoni..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <select name="status" class="filter-select">
                        <option value="">Semua Status</option>
                        <option value="show" <?= $filterStatus === 'show' ? 'selected' : '' ?>>Tampil</option>
                        <option value="hide" <?= $filterStatus === 'hide' ? 'selected' : '' ?>>Sembunyi</option>
                    </select>
                    <button type="submit" class="btn-filter">Filter</button>
                    <?php if ($search || $filterStatus): ?>
                        <a href="testimonials.php" class="btn-reset">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="testi-grid">
            <?php if (!empty($testimonials)): ?>
                <?php foreach ($testimonials as $t): ?>
                    <div class="testi-card">
                        <div class="testi-header">
                            <div class="testi-user">
                                <div class="testi-avatar"><?= strtoupper(substr($t['customer_name'], 0, 1)) ?></div>
                                <div>
                                    <div class="testi-name"><?= htmlspecialchars($t['customer_name']) ?></div>
                                    <div class="testi-date"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></div>
                                    <div class="testi-rating">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $t['rating'] ? '' : 'empty' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="testi-msg">
                            <?= nl2br(htmlspecialchars($t['message'])) ?>
                        </div>
                        <div class="testi-footer">
                            <div>
                                <?php if ($t['status'] === 'show'): ?>
                                    <span class="badge badge--show"><i class="fas fa-eye"></i> Ditampilkan</span>
                                <?php else: ?>
                                    <span class="badge badge--hide"><i class="fas fa-eye-slash"></i> Sembunyi</span>
                                <?php endif; ?>
                            </div>
                            <div class="action-group">
                                <button class="action-btn" onclick="openEditModal(<?= htmlspecialchars(json_encode($t)) ?>)" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="action-btn action-btn--success" title="<?= $t['status'] === 'show' ? 'Sembunyikan' : 'Tampilkan' ?>">
                                        <i class="fas <?= $t['status'] === 'show' ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                    </button>
                                </form>
                                <button class="action-btn action-btn--danger" onclick="confirmDelete(<?= $t['id'] ?>, '<?= addslashes(htmlspecialchars($t['customer_name'])) ?>')" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-comment-dots"></i></div>
                    <h3>Belum ada testimoni</h3>
                    <p><?= $search ? 'Tidak ditemukan testimoni yang cocok.' : 'Tambahkan ulasan pelanggan pertama Anda.' ?></p>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- Modal ADD/EDIT -->
<div class="modal-overlay" id="testiModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Tambah Testimoni</h2>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <form id="testiForm" method="POST" action="testimonials.php" novalidate>
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId" value="">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="customer_name">Nama Pelanggan <span class="req">*</span></label>
                    <input type="text" id="customer_name" name="customer_name" class="form-input <?= isset($formErrors['customer_name']) ? 'is-error' : '' ?>"
                           placeholder="Contoh: Budi Santoso" required
                           value="<?= htmlspecialchars($editData['customer_name'] ?? $_POST['customer_name'] ?? '') ?>">
                    <?php if (isset($formErrors['customer_name'])): ?><span class="field-error"><?= $formErrors['customer_name'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Rating</label>
                    <div class="rating-select">
                        <?php $currRating = (int)($editData['rating'] ?? $_POST['rating'] ?? 5); ?>
                        <input type="radio" name="rating" id="r5" value="5" <?= $currRating===5 ? 'checked' : '' ?>><label for="r5"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="r4" value="4" <?= $currRating===4 ? 'checked' : '' ?>><label for="r4"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="r3" value="3" <?= $currRating===3 ? 'checked' : '' ?>><label for="r3"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="r2" value="2" <?= $currRating===2 ? 'checked' : '' ?>><label for="r2"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="r1" value="1" <?= $currRating===1 ? 'checked' : '' ?>><label for="r1"><i class="fas fa-star"></i></label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="message">Pesan / Ulasan <span class="req">*</span></label>
                    <textarea id="message" name="message" class="form-textarea <?= isset($formErrors['message']) ? 'is-error' : '' ?>"
                              placeholder="Tulis ulasan pelanggan di sini..." required><?= htmlspecialchars($editData['message'] ?? $_POST['message'] ?? '') ?></textarea>
                    <?php if (isset($formErrors['message'])): ?><span class="field-error"><?= $formErrors['message'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Status Tampil</label>
                    <select id="status" name="status" class="form-select">
                        <option value="show" <?= ($editData['status'] ?? $_POST['status'] ?? 'show') === 'show' ? 'selected' : '' ?>>Tampilkan di Website</option>
                        <option value="hide" <?= ($editData['status'] ?? $_POST['status'] ?? '') === 'hide' ? 'selected' : '' ?>>Sembunyikan</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Testimoni</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Delete -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width: 400px; text-align: center;">
        <div class="modal-header" style="border:none; padding-bottom:0;">
            <button class="modal-close" onclick="closeDeleteModal()" style="position:absolute; right:1.5rem; top:1.5rem;"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body" style="padding-top:0.5rem;">
            <i class="fas fa-circle-exclamation" style="font-size:3.5rem; color:var(--red); margin-bottom:1rem; opacity:0.8;"></i>
            <h3 style="font-family:var(--font-head); font-size:1.25rem; margin-bottom:0.5rem;">Hapus Testimoni?</h3>
            <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">Anda akan menghapus testimoni dari <strong id="delName"></strong> secara permanen.</p>
        </div>
        <div class="modal-footer" style="justify-content:center; border:none; padding-top:0;">
            <form method="POST" action="testimonials.php">
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
(function () {
    'use strict';

    /* Side & Time */
    const sidebar = document.getElementById('sidebar'), overlay = document.getElementById('sidebarOverlay'), toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) toggleBtn.addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('visible'); });
    if (overlay) overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('visible'); });

    const timeEl = document.getElementById('currentTime');
    function updateClock() { if (timeEl) timeEl.textContent = new Date().toLocaleString('id-ID', { weekday:'short', day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' }); }
    updateClock(); setInterval(updateClock, 60000);

    /* Alert */
    const alertEl = document.getElementById('pageAlert');
    if (alertEl) { setTimeout(() => { alertEl.style.opacity = '0'; setTimeout(() => alertEl.remove(), 500); }, 4000); }

    /* Modal */
    const modal = document.getElementById('testiModal');
    const delModal = document.getElementById('deleteModal');

    window.openAddModal = function () {
        document.getElementById('modalTitle').textContent = 'Tambah Testimoni';
        document.getElementById('formAction').value = 'add';
        document.getElementById('formId').value = '';
        document.getElementById('testiForm').reset();
        document.getElementById('r5').checked = true;
        modal.classList.add('open');
        setTimeout(() => document.getElementById('customer_name').focus(), 100);
    };

    window.openEditModal = function (t) {
        document.getElementById('modalTitle').textContent = 'Edit Testimoni';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('formId').value = t.id;
        document.getElementById('customer_name').value = t.customer_name || '';
        document.getElementById('message').value = t.message || '';
        document.getElementById('status').value = t.status || 'show';
        
        if(t.rating) {
            const rBtn = document.getElementById('r' + t.rating);
            if(rBtn) rBtn.checked = true;
        }

        modal.classList.add('open');
        setTimeout(() => document.getElementById('customer_name').focus(), 100);
    };

    window.closeModal = function () { modal.classList.remove('open'); };

    window.confirmDelete = function (id, name) {
        document.getElementById('delId').value = id;
        document.getElementById('delName').textContent = name;
        delModal.classList.add('open');
    };
    window.closeDeleteModal = function () { delModal.classList.remove('open'); };

    modal.addEventListener('click', function(e) { if(e.target===this) closeModal(); });
    delModal.addEventListener('click', function(e) { if(e.target===this) closeDeleteModal(); });

    /* Form Validation */
    const form = document.getElementById('testiForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const nameEl = document.getElementById('customer_name');
            const msgEl = document.getElementById('message');
            let valid = true;
            
            [nameEl, msgEl].forEach(el => {
                el.classList.remove('is-error');
                const prev = el.parentElement.querySelector('.field-error');
                if(prev) prev.remove();
            });

            if (!nameEl.value.trim()) {
                nameEl.classList.add('is-error');
                const s = document.createElement('span'); s.className = 'field-error'; s.textContent = 'Nama wajib diisi.';
                nameEl.parentElement.appendChild(s);
                valid = false;
            }
            if (!msgEl.value.trim()) {
                msgEl.classList.add('is-error');
                const s = document.createElement('span'); s.className = 'field-error'; s.textContent = 'Pesan testimoni wajib diisi.';
                msgEl.parentElement.appendChild(s);
                valid = false;
            }

            if (!valid) e.preventDefault();
        });
    }

    if (<?= $autoOpenModal ?>) {
        if (<?= $autoOpenEdit ?> && <?= $editDataJSON ?>) openEditModal(<?= $editDataJSON ?>);
        else openAddModal();
    }
})();
</script>

</body>
</html>
