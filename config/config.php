<?php
/**
 * ==========================================
 * NONTONIN - CONFIGURATION FILE (FINAL FIX)
 * ==========================================
 * 
 * File konfigurasi utama untuk aplikasi Nontonin
 * Dioptimalkan untuk: ByetHost/AeonFree + PHP 8.3.19
 * Struktur: FLAT (tanpa folder /public)
 * 
 * Compatible: PHP 5.6 - 8.3
 * 
 * PERBAIKAN BUG:
 * - Bug #2: Helper url() anti double-slash
 * - Bug #3: Helper e() untuk XSS dan null safety
 * - Bug #12: SITE_NAME bukan APP_NAME
 */

// ==========================================
// 1. ERROR REPORTING (DEBUG MODE)
// ==========================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/storage/logs/php_error.log');

// ==========================================
// 2. SESSION MANAGEMENT
// ==========================================
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    ini_set('session.gc_maxlifetime', 3600);
    session_start();
}

// ==========================================
// 3. PATH DEFINITIONS
// ==========================================
define('BASE_PATH', dirname(__DIR__));
define('VIEW_PATH', BASE_PATH . '/app/views/');
define('CACHE_PATH', BASE_PATH . '/storage/cache/');
define('LOGS_PATH', BASE_PATH . '/storage/logs/');

// ==========================================
// 4. DATABASE CONFIGURATION
// ==========================================
define('DB_HOST', 'sql200.byethost17.com');
define('DB_NAME', 'b17_42158260_data');
define('DB_USER', 'b17_42158260');
define('DB_PASS', 'lukman112');
define('DB_CHARSET', 'utf8mb4');

// ==========================================
// 5. DRAMABOS API CONFIGURATION
// ==========================================
define('API_BASE_URL', 'https://prod-api.dramabos.live');
define('API_TOKEN', 'dbk_live_5f9955d229af1fc9fed1bc037a733ac0a36601bd9b9b8ca6');

// ==========================================
// 6. BASE URL DEFINITION (ANTI DOUBLE-SLASH)
// ==========================================
$protocol = 'http';
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '') {
    $protocol = 'https';
}

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$host = rtrim($host, '/');

define('BASE_URL', $protocol . '://' . $host);

// ==========================================
// 7. AUTO-CREATE FOLDERS
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
 * Bug #2 Fix: Menggunakan rtrim + ltrim + preg_replace
 * 
 * @param string $path Path URL
 * @return string URL lengkap yang bersih
 */
if (!function_exists('url')) {
    function url($path = '') {
        $base = defined('BASE_URL') ? BASE_URL : '';
        $base = rtrim($base, '/');
        
        if ($path === '' || $path === null) {
            return $base . '/';
        }
        
        $path = ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path);
        
        return $base . '/' . $path;
    }
}

/**
 * Helper untuk redirect yang aman
 * Bug #2 Fix: Menggunakan url() untuk mencegah double-slash
 * 
 * @param string $path Path tujuan redirect
 * @param int $code HTTP status code
 */
if (!function_exists('redirect')) {
    function redirect($path = '', $code = 302) {
        if (!headers_sent()) {
            header('Location: ' . url($path), true, $code);
            exit;
        } else {
            echo '<script>window.location.href="' . url($path) . '";</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . url($path) . '"></noscript>';
            exit;
        }
    }
}

/**
 * Helper untuk output HTML yang aman dari XSS dan crash PHP 8.3
 * Bug #3 Fix: Wrap dengan isset() check untuk null safety
 * 
 * @param mixed $string String yang akan di-escape
 * @return string String yang sudah di-htmlspecialchars
 */
if (!function_exists('e')) {
    function e($string) {
        // FIX: Jika data adalah Array atau Object, kembalikan string kosong
        if (is_array($string) || is_object($string)) {
            return ''; 
        }
        
        // FIX: Jika null atau false, kembalikan string kosong
        if ($string === null || $string === false) {
            return '';
        }
        
        // Escape string biasa
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Helper untuk mengecek apakah user sudah login
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

/**
 * Helper untuk mendapatkan user ID yang sedang login
 */
if (!function_exists('get_user_id')) {
    function get_user_id() {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
}

/**
 * Helper untuk mendapatkan username yang sedang login
 */
if (!function_exists('get_username')) {
    function get_username() {
        return isset($_SESSION['username']) ? $_SESSION['username'] : null;
    }
}

/**
 * Helper untuk flash message
 */
if (!function_exists('set_flash')) {
    function set_flash($key, $message) {
        $_SESSION['flash_' . $key] = $message;
    }
}

/**
 * Helper untuk mendapatkan flash message
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
 */
if (!function_exists('verify_csrf')) {
    function verify_csrf($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Helper untuk debug
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
// 10. CONSTANTS (SITE_NAME bukan APP_NAME)
// Bug #12 Fix
// ==========================================
define('SITE_NAME', 'Nontonin');
define('SITE_DESCRIPTION', 'Platform Streaming Drama China & Short Drama');
define('ITEMS_PER_PAGE', 30);
define('CACHE_DURATION_FEED', 21600);
define('CACHE_DURATION_DETAIL', 43200);
define('CACHE_DURATION_EPISODES', 21600);
define('CACHE_DURATION_STREAM', 900);

// ==========================================
// 11. MAINTENANCE MODE
// ==========================================
define('MAINTENANCE_MODE', false);

if (MAINTENANCE_MODE && !isset($_GET['admin'])) {
    http_response_code(503);
    echo '<h1>🚧 Website Sedang Dalam Perbaikan</h1>';
    echo '<p>Kami akan segera kembali.</p>';
    exit;
}