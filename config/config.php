<?php
/**
 * ==========================================
 * NONTONIN - CONFIGURATION FILE (FINAL)
 * ==========================================
 * 
 * File konfigurasi utama untuk aplikasi Nontonin
 * Dioptimalkan untuk: ByetHost/AeonFree + PHP 8.3.19
 * Struktur: FLAT (tanpa folder /public)
 * 
 * Compatible: PHP 5.6 - 8.3
 */

// ==========================================
// 1. ERROR REPORTING (DEBUG MODE)
// ==========================================
// Aktifkan saat development, matikan (0) saat production
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/storage/logs/php_error.log');

// ==========================================
// 2. SESSION MANAGEMENT
// ==========================================
if (session_status() == PHP_SESSION_NONE) {
    // Konfigurasi session yang aman
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set ke 1 jika pakai HTTPS
    ini_set('session.gc_maxlifetime', 3600); // 1 jam
    
    session_start();
}

// ==========================================
// 3. PATH DEFINITIONS
// ==========================================
// BASE_PATH: Root folder proyek (htdocs)
// dirname(__DIR__) dari /config akan mundur 1 langkah ke root
define('BASE_PATH', dirname(__DIR__));

// VIEW_PATH: Lokasi folder view
define('VIEW_PATH', BASE_PATH . '/app/views/');

// CACHE_PATH: Lokasi folder cache API
define('CACHE_PATH', BASE_PATH . '/storage/cache/');

// LOGS_PATH: Lokasi folder log error
define('LOGS_PATH', BASE_PATH . '/storage/logs/');

// ==========================================
// 4. DATABASE CONFIGURATION (ByetHost/AeonFree)
// ==========================================
// PENTING: 
// - DB_HOST seringkali BUKAN 'localhost' di ByetHost
// - Cek VistaPanel → MySQL Databases → MySQL Hostname
// - DB_NAME dan DB_USER biasanya ada prefix b17_
// Database credentials
define('DB_HOST', 'sql200.byethost17.com');
define('DB_NAME', 'b17_42158260_data');
define('DB_USER', 'b17_42158260');
define('DB_PASS', 'lukman112');
define('DB_CHARSET', 'utf8');
// ==========================================
// 5. DRAMABOS API CONFIGURATION
// ==========================================
define('API_BASE_URL', 'https://prod-api.dramabos.live');
define('API_TOKEN', 'dbk_live_5f9955d229af1fc9fed1bc037a733ac0a36601bd9b9b8ca6'); // Token Anda

// ==========================================
// 6. BASE URL DEFINITION (ANTI DOUBLE-SLASH)
// ==========================================
// Deteksi protocol (http/https)
$protocol = 'http';
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '') {
    $protocol = 'https';
}

// Deteksi domain/host
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// PENTING: Hapus SEMUA trailing slash dari host
$host = rtrim($host, '/');

// Define BASE_URL TANPA trailing slash
// Hasil: https://tontonin.byethost17.com (TANPA / di belakang)
define('BASE_URL', $protocol . '://' . $host);

// ==========================================
// 7. AUTO-CREATE FOLDERS (Jika belum ada)
// ==========================================
$folders_to_check = array(
    CACHE_PATH,
    LOGS_PATH,
    BASE_PATH . '/storage/cache/images',
);

foreach ($folders_to_check as $folder) {
    if (!is_dir($folder)) {
        @mkdir($folder, 0777, true);
    }
}

// ==========================================
// 8. HELPER FUNCTIONS (GLOBAL)
// ==========================================

/**
 * Helper untuk generate URL yang aman dari double-slash (//)
 * 
 * @param string $path Path URL (boleh pakai / di depan atau tidak)
 * @return string URL lengkap yang bersih
 * 
 * Contoh:
 * url('auth/login')      -> https://domain.com/auth/login
 * url('/auth/login')     -> https://domain.com/auth/login
 * url('')                -> https://domain.com/
 * url()                  -> https://domain.com/
 */
if (!function_exists('url')) {
    function url($path = '') {
        $base = defined('BASE_URL') ? BASE_URL : '';
        
        // Hapus trailing slash dari base (jaga-jaga)
        $base = rtrim($base, '/');
        
        // Jika path kosong, return base dengan 1 slash
        if ($path === '' || $path === null) {
            return $base . '/';
        }
        
        // Hapus SEMUA leading slash dari path
        $path = ltrim($path, '/');
        
        // Hapus juga slash ganda di tengah path (misal: 'auth//login' -> 'auth/login')
        $path = preg_replace('#/+#', '/', $path);
        
        // Gabungkan dengan tepat 1 slash
        return $base . '/' . $path;
    }
}

/**
 * Helper untuk redirect yang aman
 * 
 * @param string $path Path tujuan redirect
 * @param int $code HTTP status code (default: 302)
 */
if (!function_exists('redirect')) {
    function redirect($path = '', $code = 302) {
        if (!headers_sent()) {
            header('Location: ' . url($path), true, $code);
            exit;
        } else {
            // Fallback jika header sudah terkirim
            echo '<script>window.location.href="' . url($path) . '";</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . url($path) . '"></noscript>';
            exit;
        }
    }
}

/**
 * Helper untuk output HTML yang aman dari XSS dan crash PHP 8.3
 * 
 * @param mixed $string String yang akan di-escape
 * @return string String yang sudah di-htmlspecialchars
 * 
 * Contoh:
 * e($drama['title']) -> Aman meskipun $drama['title'] null/undefined
 */
if (!function_exists('e')) {
    function e($string) {
        if ($string === null || $string === false) {
            return '';
        }
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Helper untuk mengecek apakah user sudah login
 * 
 * @return bool True jika sudah login, false jika belum
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

/**
 * Helper untuk mendapatkan user ID yang sedang login
 * 
 * @return int|null User ID atau null jika belum login
 */
if (!function_exists('get_user_id')) {
    function get_user_id() {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
}

/**
 * Helper untuk mendapatkan username yang sedang login
 * 
 * @return string|null Username atau null jika belum login
 */
if (!function_exists('get_username')) {
    function get_username() {
        return isset($_SESSION['username']) ? $_SESSION['username'] : null;
    }
}

/**
 * Helper untuk flash message (pesan sekali pakai)
 * 
 * @param string $key Key pesan (success, error, warning, info)
 * @param string $message Pesan yang akan ditampilkan
 */
if (!function_exists('set_flash')) {
    function set_flash($key, $message) {
        $_SESSION['flash_' . $key] = $message;
    }
}

/**
 * Helper untuk mendapatkan flash message
 * 
 * @param string $key Key pesan
 * @return string|null Pesan atau null jika tidak ada
 */
if (!function_exists('get_flash')) {
    function get_flash($key) {
        $flash_key = 'flash_' . $key;
        if (isset($_SESSION[$flash_key])) {
            $message = $_SESSION[$flash_key];
            unset($_SESSION[$flash_key]);
            return $message;
        }
        return null;
    }
}

/**
 * Helper untuk generate CSRF token
 * 
 * @return string CSRF token
 */
if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Helper untuk validasi CSRF token
 * 
 * @param string $token Token yang akan divalidasi
 * @return bool True jika valid, false jika tidak
 */
if (!function_exists('verify_csrf')) {
    function verify_csrf($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Helper untuk debug (hanya muncul saat development)
 * 
 * @param mixed $data Data yang akan di-debug
 * @param bool $die Apakah harus exit setelah debug
 */
if (!function_exists('dd')) {
    function dd($data, $die = true) {
        echo '<pre style="background:#1e1e1e;color:#0f0;padding:15px;border-radius:5px;overflow:auto;">';
        var_dump($data);
        echo '</pre>';
        if ($die) {
            exit;
        }
    }
}

// ==========================================
// 9. TIMEZONE SETTING
// ==========================================
date_default_timezone_set('Asia/Jakarta');

// ==========================================
// 10. CONSTANT TAMBAHAN (Opsional)
// ==========================================
define('SITE_NAME', 'Nontonin');
define('SITE_DESCRIPTION', 'Platform Streaming Drama China & Short Drama');
define('ITEMS_PER_PAGE', 30);
define('CACHE_DURATION_FEED', 21600); // 6 jam
define('CACHE_DURATION_DETAIL', 43200); // 12 jam
define('CACHE_DURATION_EPISODES', 21600); // 6 jam
define('CACHE_DURATION_STREAM', 900); // 15 menit

// ==========================================
// 11. AUTOLOAD CORE CLASSES (Opsional)
// ==========================================
// Jika Anda ingin menggunakan autoloader, uncomment baris di bawah
// spl_autoload_register(function ($class) {
//     $file = BASE_PATH . '/app/core/' . $class . '.php';
//     if (file_exists($file)) {
//         require_once $file;
//     }
// });

// ==========================================
// 12. MAINTENANCE MODE (Opsional)
// ==========================================
// Set ke true jika ingin menampilkan halaman maintenance
define('MAINTENANCE_MODE', false);

if (MAINTENANCE_MODE && !isset($_GET['admin'])) {
    http_response_code(503);
    echo '<h1>🚧 Website Sedang Dalam Perbaikan</h1>';
    echo '<p>Kami akan segera kembali. Terima kasih atas kesabarannya.</p>';
    exit;
}

// ==========================================
// END OF CONFIG FILE
// ==========================================