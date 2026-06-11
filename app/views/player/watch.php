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
document.addEventListener('DOMContentLoaded', function() {
    const videoUrl = '<?php echo e($videoUrl); ?>';
    
    if (videoUrl) {
        const dp = new DPlayer({
            container: document.getElementById('dplayer'),
            theme: '#bb86fc',
            autoplay: false,
            video: {
                url: videoUrl,
                type: 'hls',
                customType: {
                    hls: function (video, player) {
                        if (Hls.isSupported()) {
                            const hls = new Hls();
                            hls.loadSource(video.src);
                            hls.attachMedia(video);
                            hls.on(Hls.Events.ERROR, function (event, data) {
                                console.error('HLS Error:', data);
                                if (data.fatal) {
                                    // Bisa tambahkan UI error di sini jika HLS gagal total
                                }
                            });
                        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                            // Fallback native untuk Safari/iOS
                            video.src = video.src;
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>

</body>
</html>
