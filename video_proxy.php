<?php
require_once __DIR__ . '/config/config.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Range, Origin, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$url = isset($_GET['url']) ? $_GET['url'] : '';
if (empty($url)) { http_response_code(400); exit('URL required'); }

$allowed = ['cdn.dramabos.live', 'stream.dramabos.live'];
$parsed = parse_url($url);
$host = isset($parsed['host']) ? $parsed['host'] : '';
$match = false;
foreach ($allowed as $d) { if (strpos($host, $d) !== false) { $match = true; break; } }
if (!$match) { http_response_code(403); exit('Domain not allowed'); }

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 45);
if (isset($_SERVER['HTTP_RANGE'])) curl_setopt($ch, CURLOPT_HTTPHEADER, ['Range: ' . $_SERVER['HTTP_RANGE']]);

curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) {
    $len = strlen($header);
    $h = explode(':', $header, 2);
    if (count($h) < 2) return $len;
    $k = strtolower(trim($h[0]));
    if (in_array($k, ['content-type','content-length','accept-ranges','content-range','cache-control','last-modified','etag'])) {
        header(trim($h[0]) . ': ' . trim($h[1]));
    }
    return $len;
});

header('Content-Type: application/vnd.apple.mpegurl');
curl_exec($ch);
curl_close($ch);
