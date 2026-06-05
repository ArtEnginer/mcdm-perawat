<?php
require_once __DIR__ . '/inc/bootstrap.php';

if (seed_ready()) {
    header('Location: login.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim((string) ($_POST['name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || $username === '' || $password === '') {
        $error = 'Semua field wajib diisi.';
    } else {
        $db = db();
        $stmt = $db->prepare('INSERT INTO users (name, username, password_hash, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), 'admin']);
        flash_set('success', 'Admin pertama berhasil dibuat. Silakan login.');
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Awal</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: grid;
            place-items: center
        }

        .card {
            width: min(520px, 92vw);
            background: #fff;
            border-radius: 22px;
            padding: 32px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, .1)
        }

        h1 {
            margin: 0 0 8px
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

        .error,
        .note {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 16px
        }

        .error {
            background: #fee2e2;
            color: #991b1b
        }

        .note {
            background: #eff6ff;
            color: #1d4ed8
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>Install awal</h1>
        <p>Buat admin pertama untuk mengaktifkan halaman management.</p>
        <div class="note">Pastikan <strong>schema.sql</strong> sudah di-import ke MySQL.</div>
        <?php if ($error): ?>
            <div class="error"><?php echo esc($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo esc(csrf_token()); ?>">
            <label>Nama</label>
            <input type="text" name="name" required>
            <label>Username</label>
            <input type="text" name="username" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Buat Admin</button>
        </form>
    </div>
</body>

</html>