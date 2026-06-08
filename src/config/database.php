<?php
/**
 * Mekarsa Coffee Bar - Database Configuration
 * Koneksi database menggunakan PDO (PHP Data Objects)
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'mekarsa_db2');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Tampilkan pesan error yang aman (jangan expose credential di production)
            http_response_code(500);
            die('<div style="font-family:sans-serif;padding:2rem;color:#f87171;background:#1a1a1a;min-height:100vh;">
                <h2>⚠️ Database Error</h2>
                <p>Tidak dapat terhubung ke database. Pastikan MySQL sudah aktif dan konfigurasi database sudah benar.</p>
                <small>' . htmlspecialchars($e->getMessage()) . '</small>
            </div>');
        }
    }

    return $pdo;
}
