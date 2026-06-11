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
                    <?php 
                        // FIX: Handle jika 'episodes' dikembalikan sebagai Array, bukan Integer
                        $ep_count = 0;
                        if (isset($detail['episodes'])) {
                            if (is_array($detail['episodes'])) {
                                $ep_count = count($detail['episodes']);
                            } elseif (is_numeric($detail['episodes'])) {
                                $ep_count = (int)$detail['episodes'];
                            }
                        }
                    ?>
                    <?php if ($ep_count > 0): ?>
                        <span>🎬 <?php echo $ep_count; ?> Episodes</span>
                    <?php endif; ?>
                </div>

                <hr class="bg-secondary">
                
                <h5>Sinopsis:</h5>
                <p class="drama-synopsis">
                    <?php echo e(isset($detail['desc']) ? $detail['desc'] : (isset($detail['synopsis']) ? $detail['synopsis'] : 'Tidak ada sinopsis tersedia.')); ?>
                </p>

                <hr class="bg-secondary">
                
                <h4 class="mb-3">Daftar Episode</h4>
                <?php if (empty($episodes) || !is_array($episodes)): ?>
                    <div class="alert alert-warning">
                        Daftar episode belum tersedia atau sedang dalam proses pembaruan.
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-wrap">
                        <?php 
                        $ep_counter = 1; 
                        foreach ($episodes as $index => $ep): 
                            
                            // FIX: Jika $ep bukan array (misal hanya string ID), bungkus jadi array
                            if (!is_array($ep)) {
                                $ep = array('id' => $ep);
                            }

                            // 1. Ambil ID Episode dengan aman - FLICKREELS MENGGUNAKAN 'chapterId'
                            $ep_id = '';
                            if (isset($ep['chapterId']) && !is_array($ep['chapterId'])) {
                                // FlickReels uses chapterId as episode ID
                                $ep_id = $ep['chapterId'];
                            } elseif (isset($ep['id']) && !is_array($ep['id'])) {
                                $ep_id = $ep['id'];
                            } elseif (isset($ep['episode_id'])) {
                                $ep_id = $ep['episode_id'];
                            } elseif (isset($ep['ep'])) {
                                $ep_id = $ep['ep'];
                            }
                            
                            // Fallback ID: Gunakan counter jika index berupa string (mencegah error string + int)
                            if (empty($ep_id)) {
                                $ep_id = is_numeric($index) ? $index : $ep_counter;
                            }

                            // 2. Ambil Nomor Episode dengan aman
                            $ep_num = $ep_counter;
                            if (isset($ep['number']) && is_numeric($ep['number'])) {
                                $ep_num = (int)$ep['number'];
                            } elseif (isset($ep['ep']) && is_numeric($ep['ep'])) {
                                $ep_num = (int)$ep['ep'];
                            } elseif (is_numeric($index)) {
                                $ep_num = (int)$index + 1;
                            }
                            
                            $ep_counter++;
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
