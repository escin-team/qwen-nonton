<?php
/**
 * ==========================================
 * NONTONIN - ENTRY POINT (FINAL)
 * ==========================================
 * 
 * File utama yang pertama kali dieksekusi oleh browser
 * Struktur: FLAT (tanpa folder /public)
 * Compatible: PHP 5.6 - 8.3
 * 
 * PENTING: File ini berada di ROOT htdocs, bukan di /public
 */

// ==========================================
// 1. ERROR REPORTING (Development Mode)
// ==========================================
// Aktifkan saat development, matikan (0) saat production
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// ==========================================
// 2. LOAD CORE CONFIGURATION
// ==========================================
// PENTING: Pastikan ada tanda '/' sebelum nama folder!
// __DIR__ = /home/vol15_7/byethost17.com/b17_42158260/htdocs
// __DIR__ . '/config/config.php' = /home/.../htdocs/config/config.php ✅

require_once __DIR__ . '/config/config.php';

// ==========================================
// 3. LOAD CORE CLASSES
// ==========================================
// Load class-class inti aplikasi (Router, Database, Controller, ApiService)
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/core/ApiService.php';
require_once __DIR__ . '/app/core/Router.php';

// ==========================================
// 4. LOAD CONTROLLERS
// ==========================================
// Load semua controller yang digunakan dalam aplikasi
require_once __DIR__ . '/app/controllers/HomeController.php';
require_once __DIR__ . '/app/controllers/DramaController.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

// Uncomment jika sudah membuat controller ini:
// require_once __DIR__ . '/app/controllers/SearchController.php';
// require_once __DIR__ . '/app/controllers/GenreController.php';
// require_once __DIR__ . '/app/controllers/WatchlistController.php';

// ==========================================
// 5. INITIALIZE ROUTER
// ==========================================
$router = new Router();

// ==========================================
// 6. DEFINE ROUTES
// ==========================================

// --- HOMEPAGE ---
$router->get('/', 'HomeController@index');

// --- AUTHENTICATION ---
$router->get('/auth/login', 'AuthController@showLoginForm');
$router->post('/auth/login', 'AuthController@login');
$router->get('/auth/register', 'AuthController@showRegisterForm');
$router->post('/auth/register', 'AuthController@register');
$router->get('/auth/logout', 'AuthController@logout');

// --- DRAMA DETAIL & WATCH ---
$router->get('/drama/{provider}/{id}', 'DramaController@detail');
$router->get('/watch/{provider}/{id}/{ep}', 'DramaController@watch');

// --- SEARCH (Uncomment jika sudah buat SearchController) ---
// $router->get('/search', 'SearchController@index');
// $router->get('/search/results', 'SearchController@results');

// --- GENRE (Uncomment jika sudah buat GenreController) ---
// $router->get('/genre', 'GenreController@index');
// $router->get('/genre/{id}', 'GenreController@show');

// --- WATCHLIST (Uncomment jika sudah buat WatchlistController) ---
// $router->get('/watchlist', 'WatchlistController@index');
// $router->post('/watchlist/add', 'WatchlistController@add');
// $router->post('/watchlist/remove', 'WatchlistController@remove');

// ==========================================
// 7. DISPATCH ROUTER
// ==========================================
// Jalankan router untuk mencocokkan URL dengan route yang terdaftar
$router->dispatch();

// ==========================================
// END OF FILE
// ==========================================