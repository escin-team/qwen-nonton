<?php
/**
 * Home Controller
 * Menampilkan halaman home dengan drama trending dari multiple provider DramaBos
 * PHP 5.6 - 8.3 Compatible
 * 
 * PENTING: Setiap provider punya endpoint unik! Tidak lagi pakai pattern generik.
 * Menggunakan try-catch per provider agar error tidak menyebar ke provider lain.
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/ApiService.php';

class HomeController extends Controller {
    private $apiService;
    
    // Daftar provider utama yang endpoint-nya sudah terverifikasi berdasarkan dokumentasi resmi
    private $verifiedProviders = array(
        'dramabox',   // /dramabox/api/v1/discover
        'shortmax',   // /shortmax/api/v1/popular
        'reelshort',  // /reelshort/api/v1/featured
        'starshort',  // /starshort/api/v1/trending
        'dramabite',  // /dramabite/api/v1/recommend
        'flickreels'  // /flickreels/api/flickreels/trending?lang=en
    );
    
    public function __construct() {
        parent::__construct();
        // FIX BUG #6: Gunakan $this->api dari parent daripada membuat ApiService baru
        $this->apiService = $this->api;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function index() {
        $title = 'Nontonin - Streaming Drama Asia Terbaru';
        $allDramas = array();
        $cacheFile = __DIR__ . '/../../storage/cache/global_feed.json';
        
        if (file_exists($cacheFile) && is_readable($cacheFile)) {
            $cacheAge = time() - filemtime($cacheFile);
            if ($cacheAge < 21600) {
                $jsonContent = file_get_contents($cacheFile);
                $cachedData = json_decode($jsonContent, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($cachedData['data'])) {
                    $allDramas = $cachedData['data'];
                    error_log('Homepage: Loaded ' . count($allDramas) . ' items from cache (age: ' . round($cacheAge/3600, 1) . 'h)');
                    shuffle($allDramas);
                    $allDramas = array_slice($allDramas, 0, 60);
                    view('home/index', array(
                        'title' => $title,
                        'dramas' => $allDramas,
                        'cache_info' => array('source' => 'file_cache', 'age' => round($cacheAge/3600, 1))
                    ));
                    return;
                }
            }
        }
        
        error_log('Homepage: Cache miss, fallback to API');
        $successful = array(); $failed = array();
        foreach ($this->verifiedProviders as $provider) {
            try {
                $result = $this->apiService->getTrending($provider, 21600);
                if (!empty($result) && isset($result['data']) && is_array($result['data'])) {
                    $dramas = $result['data'];
                    foreach ($dramas as &$d) {
                        if (is_array($d)) {
                            $d['source_provider'] = $provider;
                            if (!isset($d['id']) && isset($d['drama_id'])) $d['id'] = $d['drama_id'];
                            if (!isset($d['title']) && isset($d['name'])) $d['title'] = $d['name'];
                            if (!isset($d['cover']) && isset($d['poster'])) $d['cover'] = $d['poster'];
                            if (isset($d['cover'])) $d['cover'] = trim($d['cover']);
                        }
                    } unset($d);
                    $allDramas = array_merge($allDramas, $dramas);
                    $successful[] = $provider;
                } else { $failed[] = $provider . '(empty)'; }
            } catch (Exception $e) { error_log('API Error: ' . $provider . ': ' . $e->getMessage()); $failed[] = $provider . '(error)'; }
        }
        shuffle($allDramas);
        $allDramas = array_slice($allDramas, 0, 60);
        view('home/index', array('title' => $title, 'dramas' => $allDramas, 'cache_info' => array('source' => 'api_fallback', 'ok' => $successful, 'err' => $failed)));
    }
}
