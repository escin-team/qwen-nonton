<?php
/**
 * ==========================================
 * LINK TESTER & DOUBLE-SLASH AUDITOR
 * ==========================================
 * Scanner otomatis untuk menemukan penggunaan BASE_URL yang salah
 * dan URL dengan double-slash (//) yang diblokir oleh ByetHost.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load config jika ada
if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
}

$scan_dirs = array(__DIR__ . '/app', __DIR__ . '/public');
$exclude_patterns = array('link_tester.php', 'test_url.php', 'debug_', '.git', 'vendor', 'storage/cache');

$issues = array(
    'echo_base' => array(),
    'concat_base' => array(),
    'header_base' => array(),
    'double_slash' => array()
);

$stats = array('files' => 0, 'issues' => 0);

function scan_directory($dir, &$issues, &$stats, $exclude_patterns) {
    if (!is_dir($dir)) return;
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filepath = $dir . '/' . $file;
        
        $skip = false;
        foreach ($exclude_patterns as $pattern) {
            if (strpos($filepath, $pattern) !== false) {
                $skip = true;
                break;
            }
        }
        if ($skip) continue;
        
        if (is_dir($filepath)) {
            scan_directory($filepath, $issues, $stats, $exclude_patterns);
            continue;
        }
        
        if (pathinfo($filepath, PATHINFO_EXTENSION) !== 'php') continue;
        
        $stats['files']++;
        $lines = file($filepath);
        $relative_path = str_replace(__DIR__ . '/', '', $filepath);
        
        foreach ($lines as $num => $line) {
            $line_num = $num + 1;
            $code = trim($line);
            
            // Abaikan baris komentar atau definisi helper
            if (strpos($code, '//') === 0 || strpos($code, '*') === 0) continue;
            if (strpos($code, 'function url') !== false || strpos($code, 'rtrim') !== false) continue;

            // 1. Cek: <?php echo BASE_URL; ?>
            if (preg_match('/<\?php\s*echo\s+BASE_URL/i', $line)) {
                $issues['echo_base'][] = array('file' => $relative_path, 'line' => $line_num, 'code' => $code, 'fix' => "Ganti dengan: <?php echo url('path'); ?>");
            }
            
            // 2. Cek: BASE_URL . '/...'
            if (preg_match('/BASE_URL\s*\.\s*[\'"]\//i', $line)) {
                $issues['concat_base'][] = array('file' => $relative_path, 'line' => $line_num, 'code' => $code, 'fix' => "Ganti dengan: url('path') atau redirect('path')");
            }
            
            // 3. Cek: header('Location: ' . BASE_URL ...)
            if (preg_match('/header\s*\(\s*[\'"]Location:.*BASE_URL/i', $line)) {
                $issues['header_base'][] = array('file' => $relative_path, 'line' => $line_num, 'code' => $code, 'fix' => "Ganti dengan: redirect('path');");
            }
            
            // 4. Cek: Double Slash pada URL (Contoh: "https://domain.com//auth")
            // Regex mencari http(s)://domain.com// (slash ganda setelah domain)
            if (preg_match('/https?:\/\/[a-zA-Z0-9.-]+\/\//', $line)) {
                $issues['double_slash'][] = array('file' => $relative_path, 'line' => $line_num, 'code' => $code, 'fix' => "Hapus salah satu slash '/' pada URL agar tidak 404");
            }
        }
    }
}

foreach ($scan_dirs as $d) {
    scan_directory($d, $issues, $stats, $exclude_patterns);
}

$total_issues = 0;
foreach ($issues as $cat) $total_issues += count($cat);
$stats['issues'] = $total_issues;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Tester - Nontonin Auditor</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #0d1117; color: #c9d1d9; padding: 20px; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #58a6ff; border-bottom: 2px solid #30363d; padding-bottom: 10px; }
        .stats { display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap; }
        .stat-box { background: #161b22; border: 1px solid #30363d; padding: 15px 25px; border-radius: 8px; text-align: center; flex: 1; min-width: 150px; }
        .stat-box.danger { border-left: 5px solid #f85149; }
        .stat-box.success { border-left: 5px solid #3fb950; }
        .stat-val { font-size: 2em; font-weight: bold; color: #fff; }
        .category { background: #161b22; border: 1px solid #30363d; border-radius: 8px; margin-top: 25px; overflow: hidden; }
        .cat-header { background: #21262d; padding: 12px 20px; font-weight: bold; color: #f85149; display: flex; justify-content: space-between; }
        .cat-header.ok { color: #3fb950; }
        .item { padding: 15px 20px; border-bottom: 1px solid #21262d; }
        .item:last-child { border-bottom: none; }
        .file-path { color: #58a6ff; font-size: 0.9em; margin-bottom: 5px; }
        .code-box { background: #0d1117; padding: 10px; border-radius: 4px; font-family: monospace; color: #ff7b72; border-left: 3px solid #f85149; overflow-x: auto; margin: 8px 0; }
        .fix-tip { color: #3fb950; font-size: 0.9em; }
        .badge { background: #f85149; color: #fff; padding: 2px 8px; border-radius: 12px; font-size: 0.8em; }
        .badge.ok { background: #3fb950; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Nontonin Link & URL Auditor</h1>
    <p>Scanner ini mencari penggunaan <code>BASE_URL</code> yang salah dan URL <code>//</code> (Double-Slash) yang menyebabkan Error 404 di ByetHost.</p>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-val"><?php echo $stats['files']; ?></div>
            <div>Files Discan</div>
        </div>
        <div class="stat-box <?php echo $stats['issues'] > 0 ? 'danger' : 'success'; ?>">
            <div class="stat-val"><?php echo $stats['issues']; ?></div>
            <div>Total Masalah</div>
        </div>
    </div>

    <?php if ($stats['issues'] === 0): ?>
        <div class="category">
            <div class="cat-header ok">🎉 SEMPURNA! APLIKASI AMAN DARI ERROR 404</div>
            <div class="item">Tidak ditemukan penggunaan BASE_URL yang salah maupun URL Double-Slash. Seluruh link di aplikasi Anda sudah menggunakan helper <code>url()</code> dan <code>redirect()</code> dengan benar.</div>
        </div>
    <?php else: ?>
        
        <?php if (!empty($issues['double_slash'])): ?>
        <div class="category">
            <div class="cat-header">❌ URL Double-Slash (Penyebab Utama 404 ByetHost) <span class="badge"><?php echo count($issues['double_slash']); ?></span></div>
            <?php foreach ($issues['double_slash'] as $i): ?>
            <div class="item">
                <div class="file-path">📄 <?php echo htmlspecialchars($i['file']); ?> : Baris <?php echo $i['line']; ?></div>
                <div class="code-box"><?php echo htmlspecialchars($i['code']); ?></div>
                <div class="fix-tip">💡 <strong>Solusi:</strong> <?php echo htmlspecialchars($i['fix']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($issues['echo_base'])): ?>
        <div class="category">
            <div class="cat-header">⚠️ Penggunaan &lt;?php echo BASE_URL; ?&gt; <span class="badge"><?php echo count($issues['echo_base']); ?></span></div>
            <?php foreach ($issues['echo_base'] as $i): ?>
            <div class="item">
                <div class="file-path">📄 <?php echo htmlspecialchars($i['file']); ?> : Baris <?php echo $i['line']; ?></div>
                <div class="code-box"><?php echo htmlspecialchars($i['code']); ?></div>
                <div class="fix-tip">💡 <strong>Solusi:</strong> <?php echo htmlspecialchars($i['fix']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($issues['concat_base'])): ?>
        <div class="category">
            <div class="cat-header">⚠️ Concatenation BASE_URL . '/...' <span class="badge"><?php echo count($issues['concat_base']); ?></span></div>
            <?php foreach ($issues['concat_base'] as $i): ?>
            <div class="item">
                <div class="file-path">📄 <?php echo htmlspecialchars($i['file']); ?> : Baris <?php echo $i['line']; ?></div>
                <div class="code-box"><?php echo htmlspecialchars($i['code']); ?></div>
                <div class="fix-tip">💡 <strong>Solusi:</strong> <?php echo htmlspecialchars($i['fix']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($issues['header_base'])): ?>
        <div class="category">
            <div class="cat-header">⚠️ Header Location dengan BASE_URL <span class="badge"><?php echo count($issues['header_base']); ?></span></div>
            <?php foreach ($issues['header_base'] as $i): ?>
            <div class="item">
                <div class="file-path">📄 <?php echo htmlspecialchars($i['file']); ?> : Baris <?php echo $i['line']; ?></div>
                <div class="code-box"><?php echo htmlspecialchars($i['code']); ?></div>
                <div class="fix-tip">💡 <strong>Solusi:</strong> <?php echo htmlspecialchars($i['fix']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>

    <div style="margin-top: 30px; padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
        <h3 style="color: #79c0ff; margin-top:0;">📋 Aturan Emas (Berikan ke Junior Programmer / AI)</h3>
        <ul>
            <li>❌ <strong>DILARANG:</strong> <code>&lt;?php echo BASE_URL; ?&gt;/auth/login</code></li>
            <li>❌ <strong>DILARANG:</strong> <code>BASE_URL . '/auth/login'</code></li>
            <li>❌ <strong>DILARANG:</strong> <code>header('Location: ' . BASE_URL . '/home')</code></li>
            <li>✅ <strong>WAJIB:</strong> Gunakan <code>&lt;?php echo url('auth/login'); ?&gt;</code></li>
            <li>✅ <strong>WAJIB:</strong> Gunakan <code>redirect('home');</code> di dalam Controller</li>
        </ul>
    </div>
</div>
</body>
</html>