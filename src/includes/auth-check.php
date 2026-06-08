<?php
/**
 * Mekarsa Coffee Bar - Auth Check Middleware
 * Digunakan di semua halaman admin untuk memastikan user sudah login.
 * Redirect ke halaman login jika belum autentikasi.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    // Simpan URL yang ingin diakses untuk redirect setelah login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . BASE_URL . 'portal-mekarsa/login.php');
    exit;
}
