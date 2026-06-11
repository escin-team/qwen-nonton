<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="referrer" content="no-referrer">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>

<nav class="navbar navbar-dark mb-4">
    <a class="navbar-brand" href="<?php echo url(''); ?>">🎬 <?php echo defined('SITE_NAME') ? SITE_NAME : 'Nontonin'; ?></a>
    <a href="<?php echo url(''); ?>" class="btn btn-outline-light btn-sm">Kembali ke Home</a>
</nav>

<div class="container fade-in">
    <?php if (empty($detail) || isset($detail['error'])): ?>
        <div class="alert alert-danger text-center">
            <h4>⚠️ Data Drama Tidak Ditemukan</h4>
            <p>Maaf, informasi untuk drama ini tidak tersedia atau telah dihapus dari provider.</p>
            <a href="<?php echo url(''); ?>" class="btn btn-primary">Kembali ke Beranda</a>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Cover Image -->
            <div class="col-md-4 mb-4">
                <?php 
                    $cover = isset($detail['cover']) ? $detail['cover'] : '';
                    if (empty($cover)) $cover = url('assets/img/no-poster.svg');
                ?>
                <img src="<?php echo e($cover); ?>" 
                     onerror="this.onerror=null; this.src='<?php echo url('assets/img/no-poster.svg'); ?>';" 
                     class="img-fluid drama-cover" alt="<?php echo e(isset($detail['title']) ? $detail['title'] : 'Cover'); ?>">
            </div>
            
            <!-- Info & Episodes -->
            <div class="col-md-8">
                <h1 class="drama-title"><?php echo e(isset($detail['title']) ? $detail['title'] : 'Unknown Title'); ?></h1>
                
                <div class="drama-meta">
                    <span>📺 <?php echo e(strtoupper($provider)); ?></span>
                    <?php if (isset($detail['genre']) && !empty($detail['genre'])): ?>
                        <span>🎭 <?php echo e($detail['genre']); ?></span>
                    <?php endif; ?>
                    <?php if (isset($detail['rating']) && !empty($detail['rating'])): ?>
                        <span>⭐ <?php echo e($detail['rating']); ?></span>
                    <?php endif; ?>
                    <?php if (isset($detail['episodes']) && !empty($detail['episodes'])): ?>
                        <span>🎬 <?php echo e($detail['episodes']); ?> Episodes</span>
                    <?php endif; ?>
                </div>

                <hr class="bg-secondary">
                
                <h5>Sinopsis:</h5>
                <p class="drama-synopsis">
                    <?php echo e(isset($detail['desc']) ? $detail['desc'] : (isset($detail['synopsis']) ? $detail['synopsis'] : 'Tidak ada sinopsis tersedia.')); ?>
                </p>

                <hr class="bg-secondary">
                
                <h4 class="mb-3">Daftar Episode</h4>
                <?php if (empty($episodes)): ?>
                    <div class="alert alert-warning">
                        Daftar episode belum tersedia atau sedang dalam proses pembaruan.
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-wrap">
                        <?php foreach ($episodes as $index => $ep): ?>
                            <?php 
                                // Coba ambil ID episode, fallback ke index+1 jika tidak ada
                                $ep_id = isset($ep['id']) ? $ep['id'] : ($index + 1);
                                $ep_num = isset($ep['number']) ? $ep['number'] : ($index + 1);
                                $ep_title = isset($ep['title']) ? ' - ' . $ep['title'] : '';
                            ?>
                            <a href="<?php echo url('watch/' . e($provider) . '/' . e($drama_id) . '/' . e($ep_id)); ?>" 
                               class="btn episode-btn">
                                Ep <?php echo e($ep_num); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<footer class="footer mt-5">
    <p>&copy; <?php echo date('Y'); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : 'Nontonin'; ?>. All rights reserved.</p>
</footer>

</body>
</html>
