<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title><?php echo e($title); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .error-container { background: #1e293b; border-radius: 16px; padding: 40px; max-width: 500px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .error-icon { font-size: 64px; margin-bottom: 20px; }
        .error-title { font-size: 24px; font-weight: 600; margin-bottom: 15px; color: #f87171; }
        .error-message { font-size: 16px; line-height: 1.6; color: #94a3b8; margin-bottom: 30px; }
        .error-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s; display: inline-block; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-2px); }
        .btn-secondary { background: #334155; color: #e2e8f0; }
        .btn-secondary:hover { background: #475569; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">📺</div>
        <h1 class="error-title"><?php echo e($title); ?></h1>
        <p class="error-message"><?php echo e($message); ?></p>
        <div class="error-actions">
            <a href="javascript:history.back()" class="btn btn-secondary">← Kembali</a>
            <a href="/" class="btn btn-primary">Homepage</a>
        </div>
    </div>
</body>
</html>
