<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="referrer" content="no-referrer">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dplayer@latest/dist/DPlayer.min.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>

<nav class="navbar navbar-dark mb-4">
    <a class="navbar-brand" href="<?php echo url(''); ?>">🎬 <?php echo defined('SITE_NAME') ? SITE_NAME : 'Nontonin'; ?></a>
    <a href="<?php echo url('drama/' . e($provider) . '/' . e($drama_id)); ?>" class="btn btn-outline-light btn-sm">Kembali ke Detail</a>
</nav>

<div class="container fade-in">
    <?php if (empty($videoUrl)): ?>
        <div class="alert alert-danger text-center" style="max-width: 800px; margin: 50px auto;">
            <h3>⚠️ Video Tidak Dapat Diputar</h3>
            <p>Link streaming untuk episode ini tidak ditemukan, kadaluarsa, atau sedang dalam perbaikan oleh provider.</p>
            <p><small>Silakan coba episode lain atau kembali ke halaman detail.</small></p>
            <a href="<?php echo url('drama/' . e($provider) . '/' . e($drama_id)); ?>" class="btn btn-primary mt-3">Lihat Daftar Episode</a>
        </div>
    <?php else: ?>
        <div class="player-container">
            <div id="dplayer"></div>
        </div>
        
        <!-- Navigasi Episode Sederhana -->
        <div class="text-center mt-4">
            <a href="<?php echo url('watch/' . e($provider) . '/' . e($drama_id) . '/' . max(1, $episode - 1)); ?>" class="btn btn-outline-light">
                ⏮️ Episode Sebelumnya
            </a>
            <span class="mx-3 text-muted-custom">Episode <?php echo e($episode); ?></span>
            <a href="<?php echo url('watch/' . e($provider) . '/' . e($drama_id) . '/' . ($episode + 1)); ?>" class="btn btn-outline-light">
                Episode Selanjutnya ⏭️
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($videoUrl)): ?>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/dplayer@latest/dist/DPlayer.min.js"></script>
<script>
// Fungsi deteksi kualitas jaringan menggunakan Network Information API
function getNetworkQuality() {
    if ('connection' in navigator) {
        var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (conn) {
            // downlink dalam Mbps, rtt dalam ms
            var downlink = conn.downlink || 0;
            var rtt = conn.rtt || 0;
            var effectiveType = conn.effectiveType || '';
            
            // Klasifikasi kualitas jaringan
            if (downlink >= 5 && rtt <= 100) {
                return 'good'; // HD Quality
            } else if (downlink >= 2 && rtt <= 200) {
                return 'medium'; // SD Quality
            } else {
                return 'poor'; // Low Quality
            }
        }
    }
    // Default: asumsikan jaringan bagus jika tidak terdeteksi
    return 'good';
}

// Fungsi adaptasi kualitas HLS berdasarkan jaringan
function adaptHLSQuality(hls, networkQuality) {
    if (!hls.levels || hls.levels.length === 0) {
        return;
    }
    
    var levels = hls.levels;
    var selectedLevel = -1;
    
    if (networkQuality === 'good') {
        // Pilih kualitas tertinggi (HD)
        selectedLevel = hls.autoLevelCapping = -1; // Auto select highest
        console.log('[Network] Jaringan BAGUS - Menggunakan HD Quality');
    } else if (networkQuality === 'medium') {
        // Pilih kualitas medium (SD)
        for (var i = 0; i < levels.length; i++) {
            if (levels[i].bitrate < 2000000) { // < 2 Mbps
                selectedLevel = i;
                break;
            }
        }
        if (selectedLevel === -1) {
            selectedLevel = Math.floor(levels.length / 2);
        }
        hls.autoLevelCapping = selectedLevel;
        console.log('[Network] Jaringan SEDANG - Menggunakan SD Quality (Level ' + selectedLevel + ')');
    } else {
        // Pilih kualitas terendah (Low)
        selectedLevel = 0;
        hls.autoLevelCapping = 0;
        console.log('[Network] Jaringan BURUK - Menggunakan Low Quality (Level 0)');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const videoUrl = '<?php echo e($videoUrl); ?>';
    
    console.log('[DEBUG] Video URL:', videoUrl);
    console.log('[DEBUG] Hls.isSupported():', typeof Hls !== 'undefined' ? Hls.isSupported() : 'Hls not loaded');
    
    if (videoUrl) {
        // Deteksi kualitas jaringan sebelum load video
        var networkQuality = getNetworkQuality();
        console.log('[Network] Kualitas jaringan terdeteksi: ' + networkQuality);
        
        // Cek apakah URL valid (harus mengandung .m3u8 atau merupakan URL http/https)
        if (!videoUrl.match(/^https?:\/\//i)) {
            console.error('[ERROR] Video URL tidak valid! Harus dimulai dengan http:// atau https://');
            document.getElementById('dplayer').innerHTML = '<div class="alert alert-danger" style="padding:20px;text-align:center;"><h3>⚠️ Error: URL Video Tidak Valid</h3><p>URL yang diterima: ' + videoUrl + '</p><p>Silakan hubungi administrator.</p></div>';
            return;
        }
        
        const dp = new DPlayer({
            container: document.getElementById('dplayer'),
            theme: '#bb86fc',
            autoplay: false,
            video: {
                url: videoUrl,
                type: 'hls',
                customType: {
                    hls: function (video, player) {
                        console.log('[HLS] Initializing HLS player...');
                        
                        if (Hls.isSupported()) {
                            const hls = new Hls({
                                enableWorker: true,
                                lowLatencyMode: false,
                                backBufferLength: 90,
                                debug: false // Set true untuk debugging detail
                            });
                            
                            hls.loadSource(video.src);
                            hls.attachMedia(video);
                            
                            // Adaptasi kualitas berdasarkan jaringan
                            adaptHLSQuality(hls, networkQuality);
                            
                            // Monitor perubahan jaringan secara real-time
                            if ('connection' in navigator) {
                                var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                                if (conn && conn.addEventListener) {
                                    conn.addEventListener('change', function() {
                                        var newQuality = getNetworkQuality();
                                        console.log('[Network] Perubahan jaringan terdeteksi: ' + newQuality);
                                        adaptHLSQuality(hls, newQuality);
                                    });
                                }
                            }
                            
                            hls.on(Hls.Events.ERROR, function (event, data) {
                                console.error('[HLS ERROR]', data);
                                
                                // Log detail error
                                if (data.details) {
                                    console.error('[HLS] Details:', data.details);
                                }
                                if (data.reason) {
                                    console.error('[HLS] Reason:', data.reason);
                                }
                                
                                if (data.fatal) {
                                    // Auto-recover dari fatal error
                                    switch (data.type) {
                                        case Hls.ErrorTypes.NETWORK_ERROR:
                                            console.log('[HLS] Network error, attempting recovery...');
                                            hls.startLoad();
                                            break;
                                        case Hls.ErrorTypes.MEDIA_ERROR:
                                            console.log('[HLS] Media error, attempting recovery...');
                                            hls.recoverMediaError();
                                            break;
                                        default:
                                            console.log('[HLS] Unrecoverable error, destroying player...');
                                            hls.destroy();
                                            document.getElementById('dplayer').innerHTML = '<div class="alert alert-danger" style="padding:20px;text-align:center;"><h3>⚠️ Video Gagal Dimuat</h3><p>Error: ' + (data.details || 'Unknown error') + '</p><p>Mungkin penyebab:</p><ul style="text-align:left;max-width:400px;margin:10px auto;"><li>Link streaming kadaluarsa/rusak</li><li>Provider sedang maintenance</li><li>Pemblokiran CORS oleh CDN</li><li>Koneksi internet bermasalah</li></ul><p><a href="<?php echo url("drama/" . e($provider) . "/" . e($drama_id)); ?>" class="btn btn-primary">Kembali ke Daftar Episode</a></p></div>';
                                            break;
                                    }
                                }
                            });
                            
                            // Log level yang tersedia
                            hls.on(Hls.Events.MANIFEST_PARSED, function(event, data) {
                                console.log('[HLS] Manifest parsed. Available levels:', data.levels.length);
                                for (var i = 0; i < data.levels.length; i++) {
                                    console.log('[HLS] Level ' + i + ': ' + (data.levels[i].bitrate / 1000).toFixed(0) + ' kbps');
                                }
                            });
                            
                            // Event ketika video mulai diputar
                            hls.on(Hls.Events.FRAG_LOADED, function(event, data) {
                                console.log('[HLS] Fragment loaded successfully');
                            });
                            
                        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                            // Fallback native untuk Safari/iOS
                            video.src = video.src;
                            console.log('[HLS] Using native HLS playback (Safari/iOS)');
                        } else {
                            console.error('[HLS] Browser tidak mendukung HLS!');
                            document.getElementById('dplayer').innerHTML = '<div class="alert alert-warning" style="padding:20px;text-align:center;"><h3>⚠️ Browser Tidak Didukung</h3><p>Browser Anda tidak mendukung pemutaran HLS.</p><p>Silakan gunakan Chrome, Firefox, atau Safari versi terbaru.</p></div>';
                        }
                    }
                }
            }
        });
        
        // Tampilkan info kualitas jaringan di console
        console.log('[Network] Network-based quality adaptation enabled');
    }
});
</script>
<?php endif; ?>

</body>
</html>
