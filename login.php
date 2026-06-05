<?php
require_once __DIR__ . '/inc/bootstrap.php';

if (is_logged_in()) {
    header('Location: admin/index.php');
    exit;
}

$error = null;
$flash = flash_pull();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (app_login($username, $password)) {
        flash_set('success', 'Login berhasil.');
        header('Location: admin/index.php');
        exit;
    } else {
        $error = 'Login gagal. Periksa username atau password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a, #0f766e);
            min-height: 100vh;
            display: grid;
            place-items: center;
            color: #0f172a
        }

        .card {
            width: min(440px, 92vw);
            background: #fff;
            border-radius: 22px;
            padding: 32px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .22)
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px
        }

        p {
            margin: 0 0 18px;
            color: #475569
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin: 14px 0 8px
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 15px
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 14px 16px;
            border: 0;
            border-radius: 12px;
            background: #0f172a;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer
        }

        .message,
        .error {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 16px
        }

        .message {
            background: #dcfce7;
            color: #166534
        }

        .error {
            background: #fee2e2;
            color: #991b1b
        }

        .hint {
            margin-top: 16px;
            font-size: 12px;
            color: #64748b
        }

        a {
            color: #0f172a
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>Login Admin</h1>
        <p>Masuk untuk mengelola data kriteria, perawat, dan admin.</p>
        <?php if ($flash && ($flash['message'] ?? null)): ?>
            <div class="message"><?php echo esc($flash['message']); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo esc($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo esc(csrf_token()); ?>">
            <label>Username</label>
            <input type="text" name="username" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Masuk</button>
        </form>
        <div class="hint">Jika belum ada akun admin, buka <a href="/install.php">install.php</a> untuk membuat admin pertama.</div>
    </div>
</body>

</html>