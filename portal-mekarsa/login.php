<?php
/**
 * Mekarsa Coffee Bar - Admin Login Page
 * Halaman login untuk admin panel
 *
 * Frontend: Neo-Minimalist dark, konsisten dengan desain publik
 * Backend : Validasi session + password_verify() + PDO
 */

// --- BACKEND LOGIC ---
session_start();

// Jika admin sudah login, arahkan ke dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Koneksi DB (admin/ berada satu level di bawah root project)
require_once dirname(__DIR__) . '/src/config/database.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validasi input dasar
    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $pdo  = getDBConnection();
            $stmt = $pdo->prepare('SELECT id, name, username, password FROM admins WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Login berhasil — set session
                session_regenerate_id(true); // Cegah session fixation
                $_SESSION['admin_id']       = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name']     = $admin['name'];
                $_SESSION['login_time']     = time();

                // Redirect ke halaman yang ingin diakses, atau dashboard
                $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'Username atau password salah. Periksa kembali kredensial Anda.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — Mekarsa Coffee Bar</title>
    <meta name="description" content="Halaman login admin panel Mekarsa Coffee Bar.">
    <meta name="robots" content="noindex, nofollow">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700;800&family=Inter:wght@400;500;600&family=Anton&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ===== RESET & BASE ===== */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg-main:     #0a0a0a;
            --bg-card:     #111111;
            --bg-input:    #1a1a1a;
            --border:      #27272a;
            --orange:      #F27121;
            --orange-dark: #e06010;
            --orange-glow: rgba(242, 113, 33, 0.18);
            --text:        #ffffff;
            --text-muted:  #a1a1aa;
            --error:       #f87171;
            --success:     #4ade80;
            --font-head:   'Poppins', sans-serif;
            --font-body:   'Inter', sans-serif;
            --font-price:  'Anton', sans-serif;
        }

        html { height: 100%; }

        body {
            min-height: 100%;
            font-family: var(--font-body);
            background-color: var(--bg-main);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }

        /* ===== ANIMATED BACKGROUND ===== */
        .bg-orbs {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.12;
            animation: orbFloat 8s ease-in-out infinite;
        }

        .bg-orb--1 {
            width: 500px; height: 500px;
            background: var(--orange);
            top: -150px; left: -150px;
            animation-delay: 0s;
        }

        .bg-orb--2 {
            width: 400px; height: 400px;
            background: #f59e0b;
            bottom: -100px; right: -100px;
            animation-delay: -4s;
        }

        .bg-orb--3 {
            width: 300px; height: 300px;
            background: var(--orange);
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -2s;
            opacity: 0.06;
        }

        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%       { transform: translate(30px, -20px) scale(1.05); }
            66%       { transform: translate(-20px, 15px) scale(0.95); }
        }

        /* Grid pattern overlay */
        .bg-grid {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background-image:
                linear-gradient(rgba(242,113,33,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(242,113,33,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* ===== MAIN LAYOUT ===== */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
            animation: fadeInUp 0.6s ease both;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ===== BRAND HEADER ===== */
        .brand-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.75rem;
            text-decoration: none;
        }

        .brand-logo-icon {
            width: 48px; height: 48px;
            background: var(--orange);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            color: #fff;
            box-shadow: 0 0 20px rgba(242,113,33,0.4);
            flex-shrink: 0;
        }

        .brand-logo-text {
            font-family: var(--font-head);
            font-weight: 800;
            font-size: 1.8rem;
            color: var(--text);
            letter-spacing: -0.5px;
        }

        .brand-logo-text span {
            color: var(--orange);
        }

        .brand-tagline {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-family: var(--font-body);
            letter-spacing: 0.5px;
        }

        /* ===== CARD ===== */
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem 2.5rem 2rem;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.03) inset,
                0 20px 60px rgba(0,0,0,0.5);
            position: relative;
            overflow: hidden;
        }

        /* Orange top accent line */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--orange), transparent);
        }

        .card-title {
            font-family: var(--font-head);
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 0.4rem;
            letter-spacing: -0.5px;
        }

        .card-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        /* ===== ALERT MESSAGES ===== */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
            animation: alertSlide 0.3s ease;
        }

        @keyframes alertSlide {
            from { opacity: 0; transform: translateX(-10px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .alert i { margin-top: 2px; flex-shrink: 0; }

        .alert--error {
            background: rgba(248, 113, 113, 0.1);
            border: 1px solid rgba(248, 113, 113, 0.3);
            color: var(--error);
        }

        .alert--success {
            background: rgba(74, 222, 128, 0.1);
            border: 1px solid rgba(74, 222, 128, 0.3);
            color: var(--success);
        }

        /* ===== FORM ELEMENTS ===== */
        .form-group {
            margin-bottom: 1.4rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--text);
            margin-bottom: 0.5rem;
            letter-spacing: 0.2px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.95rem;
            pointer-events: none;
            transition: color 0.3s;
        }

        .form-input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: var(--font-body);
            font-size: 0.95rem;
            transition: border-color 0.3s, box-shadow 0.3s;
            outline: none;
            -webkit-appearance: none;
        }

        .form-input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px var(--orange-glow);
        }

        .form-input:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: var(--orange);
        }

        .form-input::placeholder { color: var(--text-muted); opacity: 0.7; }

        /* Password toggle button */
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.2rem;
            font-size: 0.95rem;
            transition: color 0.3s;
            display: flex;
            align-items: center;
        }

        .password-toggle:hover { color: var(--orange); }

        /* Password field needs extra right padding */
        .form-input--password { padding-right: 3rem; }

        /* ===== SUBMIT BUTTON ===== */
        .btn-login {
            width: 100%;
            padding: 0.95rem;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: var(--font-head);
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s, box-shadow 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            margin-top: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .btn-login::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, transparent 50%);
            pointer-events: none;
        }

        .btn-login:hover:not(:disabled) {
            background: var(--orange-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(242, 113, 33, 0.4);
        }

        .btn-login:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Loading spinner */
        .spinner {
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        .btn-login.loading .spinner { display: block; }
        .btn-login.loading .btn-text { display: none; }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ===== DIVIDER ===== */
        .form-divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .form-divider::before,
        .form-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ===== INFO SECTION ===== */
        .card-info {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: center;
        }

        .back-to-site {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-to-site:hover { color: var(--orange); }

        /* ===== SECURITY BADGES ===== */
        .security-row {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1.75rem;
        }

        .security-badge {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .security-badge i { color: var(--orange); font-size: 0.8rem; }

        /* ===== FOOTER ===== */
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 520px) {
            body { padding: 1rem; align-items: flex-start; padding-top: 2rem; }
            .login-card { padding: 2rem 1.5rem 1.5rem; }
            .card-title { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

    <!-- Animated background -->
    <div class="bg-orbs" aria-hidden="true">
        <div class="bg-orb bg-orb--1"></div>
        <div class="bg-orb bg-orb--2"></div>
        <div class="bg-orb bg-orb--3"></div>
    </div>
    <div class="bg-grid" aria-hidden="true"></div>

    <!-- Login container -->
    <div class="login-wrapper">

        <!-- Brand Header -->
        <div class="brand-header">
            <a href="../index.php" class="brand-logo" title="Kembali ke halaman utama">
                <div class="brand-logo-icon">
                    <i class="fas fa-mug-hot"></i>
                </div>
                <span class="brand-logo-text">Mekarsa<span>.</span></span>
            </a>
            <p class="brand-tagline">Admin Panel — Coffee First, Clean Vibes Always.</p>
        </div>

        <!-- Login Card -->
        <div class="login-card" role="main">

            <h1 class="card-title">Selamat Datang 👋</h1>
            <p class="card-subtitle">Masuk ke panel pengelolaan Mekarsa Coffee Bar</p>

            <!-- Alert Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert--error" role="alert" id="alertError">
                    <i class="fas fa-triangle-exclamation"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert--success" role="status" id="alertSuccess">
                    <i class="fas fa-circle-check"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="loginForm" method="POST" action="login.php" novalidate autocomplete="on">

                <!-- Username Field -->
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fas fa-user" style="color:var(--orange);margin-right:0.4rem;font-size:0.8rem;"></i>
                        Username
                    </label>
                    <div class="input-wrapper">
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-input"
                            placeholder="Masukkan username admin"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            autocomplete="username"
                            required
                            autofocus
                            aria-describedby="usernameError"
                        >
                        <i class="fas fa-at input-icon" aria-hidden="true"></i>
                    </div>
                    <div class="form-error" id="usernameError" role="alert"></div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-lock" style="color:var(--orange);margin-right:0.4rem;font-size:0.8rem;"></i>
                        Password
                    </label>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input form-input--password"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                            aria-describedby="passwordError"
                        >
                        <i class="fas fa-key input-icon" aria-hidden="true"></i>
                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Tampilkan atau sembunyikan password"
                            title="Tampilkan/sembunyikan password"
                        >
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    <div class="form-error" id="passwordError" role="alert"></div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="submitBtn">
                    <span class="btn-text">
                        <i class="fas fa-right-to-bracket"></i>
                        Masuk ke Panel Admin
                    </span>
                    <div class="spinner" aria-hidden="true"></div>
                </button>

            </form>

            <!-- Back to Site -->
            <div class="card-info">
                <a href="../index.php" class="back-to-site">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Halaman Utama
                </a>
            </div>
        </div>

        <!-- Security Badges -->
        <div class="security-row" aria-label="Fitur keamanan">
            <div class="security-badge">
                <i class="fas fa-shield-halved"></i>
                <span>Session Protected</span>
            </div>
            <div class="security-badge">
                <i class="fas fa-lock"></i>
                <span>Password Hashed</span>
            </div>
            <div class="security-badge">
                <i class="fas fa-user-shield"></i>
                <span>Admin Only</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            &copy; <?= date('Y') ?> Mekarsa Shoe Clean &amp; Coffee Bar. All Rights Reserved.
        </div>

    </div><!-- /login-wrapper -->

    <script>
    (function () {
        'use strict';

        /* ===== Password Toggle ===== */
        const toggleBtn  = document.getElementById('togglePassword');
        const passInput  = document.getElementById('password');
        const eyeIcon    = document.getElementById('eyeIcon');

        if (toggleBtn && passInput) {
            toggleBtn.addEventListener('click', function () {
                const isHidden = passInput.type === 'password';
                passInput.type = isHidden ? 'text' : 'password';
                eyeIcon.classList.toggle('fa-eye', !isHidden);
                eyeIcon.classList.toggle('fa-eye-slash', isHidden);
                this.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
                passInput.focus();
            });
        }

        /* ===== Form Client-Side Validation ===== */
        const form       = document.getElementById('loginForm');
        const submitBtn  = document.getElementById('submitBtn');
        const userInput  = document.getElementById('username');
        const passField  = document.getElementById('password');
        const userError  = document.getElementById('usernameError');
        const passError  = document.getElementById('passwordError');

        function showError(el, msg) {
            el.textContent = msg;
            el.style.display = 'block';
            el.style.color = 'var(--error)';
            el.style.fontSize = '0.82rem';
            el.style.marginTop = '0.35rem';
        }

        function clearError(el) {
            el.textContent = '';
            el.style.display = 'none';
        }

        if (form) {
            // Clear errors on input
            userInput.addEventListener('input', () => clearError(userError));
            passField.addEventListener('input', () => clearError(passError));

            form.addEventListener('submit', function (e) {
                let valid = true;

                if (!userInput.value.trim()) {
                    e.preventDefault();
                    showError(userError, 'Username wajib diisi.');
                    userInput.focus();
                    valid = false;
                }

                if (!passField.value.trim()) {
                    e.preventDefault();
                    showError(passError, 'Password wajib diisi.');
                    if (valid) passField.focus();
                    valid = false;
                }

                if (valid) {
                    // Show loading state
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                }
            });
        }

        /* ===== Auto-dismiss alert after 6s ===== */
        const alertEl = document.getElementById('alertError') || document.getElementById('alertSuccess');
        if (alertEl) {
            setTimeout(() => {
                alertEl.style.transition = 'opacity 0.5s ease';
                alertEl.style.opacity = '0';
                setTimeout(() => alertEl.remove(), 500);
            }, 6000);
        }

        /* ===== Input focus ring animation ===== */
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function () {
                this.closest('.input-wrapper').style.transform = 'scale(1.01)';
                this.closest('.input-wrapper').style.transition = 'transform 0.2s ease';
            });
            input.addEventListener('blur', function () {
                this.closest('.input-wrapper').style.transform = '';
            });
        });

    })();
    </script>

</body>
</html>
