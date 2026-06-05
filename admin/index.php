<?php
require_once __DIR__ . '/../inc/analytics.php';

app_require_login('../login.php');
$db = db();
$tab = $_GET['tab'] ?? 'overview';
$allowedTabs = ['overview', 'criteria', 'ahp', 'perawat', 'users'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'overview';
}

function admin_active(string $tab, string $needle): string
{
    return $tab === $needle ? 'active' : '';
}

function admin_build_ahp_matrix(array $criteria, array $pairs): array
{
    $matrix = [];
    $weightMap = [];
    foreach ($criteria as $criterion) {
        $weightMap[(int) $criterion['id']] = max(0.000001, (float) $criterion['weight']);
    }

    $pairMap = [];
    foreach ($pairs as $pair) {
        $left = (int) $pair['criteria_id_left'];
        $right = (int) $pair['criteria_id_right'];
        $value = max(0.000001, (float) $pair['value']);
        $pairMap[$left][$right] = $value;
    }

    foreach ($criteria as $leftCriterion) {
        $leftId = (int) $leftCriterion['id'];
        foreach ($criteria as $rightCriterion) {
            $rightId = (int) $rightCriterion['id'];
            if ($leftId === $rightId) {
                $matrix[$leftId][$rightId] = 1.0;
                continue;
            }

            if (isset($pairMap[$leftId][$rightId])) {
                $matrix[$leftId][$rightId] = $pairMap[$leftId][$rightId];
                continue;
            }

            if (isset($pairMap[$rightId][$leftId])) {
                $matrix[$leftId][$rightId] = 1 / max(0.000001, $pairMap[$rightId][$leftId]);
                continue;
            }

            $matrix[$leftId][$rightId] = $weightMap[$rightId] > 0 ? $weightMap[$leftId] / $weightMap[$rightId] : 1.0;
        }
    }

    return $matrix;
}

function admin_ahp_weights(array $criteria, array $matrix): array
{
    $scores = [];
    $count = max(1, count($criteria));

    foreach ($criteria as $criterion) {
        $id = (int) $criterion['id'];
        $product = 1.0;
        foreach ($criteria as $other) {
            $otherId = (int) $other['id'];
            $product *= max(0.000001, (float) ($matrix[$id][$otherId] ?? 1.0));
        }
        $scores[$id] = $product ** (1 / $count);
    }

    $sum = array_sum($scores);
    if ($sum <= 0) {
        $fallback = 1 / max(1, count($criteria));
        foreach ($criteria as $criterion) {
            $scores[(int) $criterion['id']] = $fallback;
        }
        return $scores;
    }

    foreach ($scores as $id => $score) {
        $scores[$id] = $score / $sum;
    }

    return $scores;
}

function admin_ensure_ahp_table(PDO $db): void
{
    if (table_exists('ahp_pairs')) {
        return;
    }

    $db->exec(
        'CREATE TABLE IF NOT EXISTS ahp_pairs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            criteria_id_left INT UNSIGNED NOT NULL,
            criteria_id_right INT UNSIGNED NOT NULL,
            value DECIMAL(12,6) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY ahp_pair_unique (criteria_id_left, criteria_id_right),
            CONSTRAINT fk_ahp_left FOREIGN KEY (criteria_id_left) REFERENCES criteria (id) ON DELETE CASCADE,
            CONSTRAINT fk_ahp_right FOREIGN KEY (criteria_id_right) REFERENCES criteria (id) ON DELETE CASCADE
        ) ENGINE=InnoDB'
    );
}

function admin_saaty_scale(): array
{
    return [
        '9' => '9 - Sangat mutlak lebih penting',
        '8' => '8 - Di antara kuat dan sangat kuat',
        '7' => '7 - Sangat kuat lebih penting',
        '6' => '6 - Di antara sedang dan kuat',
        '5' => '5 - Kuat lebih penting',
        '4' => '4 - Di antara sedikit dan kuat',
        '3' => '3 - Sedikit lebih penting',
        '2' => '2 - Di antara sama penting dan sedikit',
        '1' => '1 - Sama penting',
        '0.5' => '1/2 - Kebalikan 2',
        '0.333333' => '1/3 - Kebalikan 3',
        '0.25' => '1/4 - Kebalikan 4',
        '0.2' => '1/5 - Kebalikan 5',
        '0.166667' => '1/6 - Kebalikan 6',
        '0.142857' => '1/7 - Kebalikan 7',
        '0.125' => '1/8 - Kebalikan 8',
        '0.111111' => '1/9 - Kebalikan 9',
    ];
}

function admin_normalize_saaty_value(float $value): float
{
    $allowed = [9, 8, 7, 6, 5, 4, 3, 2, 1, 0.5, 0.333333, 0.25, 0.2, 0.166667, 0.142857, 0.125, 0.111111];
    $best = 1.0;
    $smallest = PHP_FLOAT_MAX;

    foreach ($allowed as $candidate) {
        $distance = abs($value - $candidate);
        if ($distance < $smallest) {
            $smallest = $distance;
            $best = $candidate;
        }
    }

    return $best;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_ahp') {
        admin_ensure_ahp_table($db);
        $criteria = $db->query('SELECT * FROM criteria WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
        $pairs = [];

        $db->beginTransaction();
        $db->exec('DELETE FROM ahp_pairs');

        try {
            foreach ($criteria as $leftIndex => $leftCriterion) {
                for ($rightIndex = $leftIndex + 1; $rightIndex < count($criteria); $rightIndex++) {
                    $rightCriterion = $criteria[$rightIndex];
                    $field = 'pair_' . $leftCriterion['id'] . '_' . $rightCriterion['id'];
                    $value = admin_normalize_saaty_value((float) ($_POST[$field] ?? 1));
                    $pairs[] = [
                        'criteria_id_left' => (int) $leftCriterion['id'],
                        'criteria_id_right' => (int) $rightCriterion['id'],
                        'value' => $value,
                    ];
                    $stmt = $db->prepare('INSERT INTO ahp_pairs (criteria_id_left, criteria_id_right, value) VALUES (?, ?, ?)');
                    $stmt->execute([(int) $leftCriterion['id'], (int) $rightCriterion['id'], $value]);
                }
            }

            $matrix = admin_build_ahp_matrix($criteria, $pairs);
            $weights = admin_ahp_weights($criteria, $matrix);

            $stmt = $db->prepare('UPDATE criteria SET weight = ? WHERE id = ?');
            foreach ($criteria as $criterion) {
                $id = (int) $criterion['id'];
                $stmt->execute([round($weights[$id] ?? 0, 6), $id]);
            }

            $db->commit();
            flash_set('success', 'Bobot AHP berhasil diperbarui dari matrix pairwise.');
        } catch (Throwable $throwable) {
            $db->rollBack();
            throw $throwable;
        }

        header('Location: index.php?tab=ahp');
        exit;
    }

    if ($action === 'save_criterion') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $code = trim((string) ($_POST['code'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $weight = (float) ($_POST['weight'] ?? 0);
        $sortOrder = (int) ($_POST['sort_order'] ?? 1);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id === '') {
            $stmt = $db->prepare('INSERT INTO criteria (code, name, weight, sort_order, is_active) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$code, $name, $weight, $sortOrder, $isActive]);
            flash_set('success', 'Kriteria berhasil ditambahkan.');
        } else {
            $stmt = $db->prepare('UPDATE criteria SET code = ?, name = ?, weight = ?, sort_order = ?, is_active = ? WHERE id = ?');
            $stmt->execute([$code, $name, $weight, $sortOrder, $isActive, (int) $id]);
            flash_set('success', 'Kriteria berhasil diperbarui.');
        }
        header('Location: index.php?tab=criteria');
        exit;
    }

    if ($action === 'delete_criterion') {
        $stmt = $db->prepare('DELETE FROM criteria WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Kriteria dihapus.');
        header('Location: index.php?tab=criteria');
        exit;
    }

    if ($action === 'save_perawat') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $employeeCode = trim((string) ($_POST['employee_code'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $scores = [];
        for ($i = 1; $i <= 7; $i++) {
            $scores[] = (float) ($_POST['c' . $i] ?? 0);
        }
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id === '') {
            $stmt = $db->prepare('INSERT INTO perawat (employee_code, name, c1, c2, c3, c4, c5, c6, c7, notes, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$employeeCode !== '' ? $employeeCode : null, $name, ...$scores, $notes, $isActive]);
            flash_set('success', 'Data perawat berhasil ditambahkan.');
        } else {
            $stmt = $db->prepare('UPDATE perawat SET employee_code = ?, name = ?, c1 = ?, c2 = ?, c3 = ?, c4 = ?, c5 = ?, c6 = ?, c7 = ?, notes = ?, is_active = ? WHERE id = ?');
            $stmt->execute([$employeeCode !== '' ? $employeeCode : null, $name, ...$scores, $notes, $isActive, (int) $id]);
            flash_set('success', 'Data perawat berhasil diperbarui.');
        }
        header('Location: index.php?tab=perawat');
        exit;
    }

    if ($action === 'delete_perawat') {
        $stmt = $db->prepare('DELETE FROM perawat WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Data perawat dihapus.');
        header('Location: index.php?tab=perawat');
        exit;
    }

    if ($action === 'save_user') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'admin');

        if ($id === '') {
            $stmt = $db->prepare('INSERT INTO users (name, username, password_hash, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $role === 'operator' ? 'operator' : 'admin']);
            flash_set('success', 'Admin baru berhasil ditambahkan.');
        } else {
            $sql = 'UPDATE users SET name = ?, username = ?, role = ?';
            $params = [$name, $username, $role === 'operator' ? 'operator' : 'admin'];
            if ($password !== '') {
                $sql .= ', password_hash = ?';
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = ?';
            $params[] = (int) $id;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            flash_set('success', 'Data admin berhasil diperbarui.');
        }

        header('Location: index.php?tab=users');
        exit;
    }

    if ($action === 'delete_user') {
        $id = (int) ($_POST['id'] ?? 0);
        $user = current_user();
        if ($user && (int) $user['id'] === $id) {
            flash_set('error', 'Tidak bisa menghapus akun yang sedang dipakai.');
        } else {
            $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            flash_set('success', 'Admin dihapus.');
        }
        header('Location: index.php?tab=users');
        exit;
    }
}

$criteria = $db->query('SELECT * FROM criteria ORDER BY sort_order ASC, id ASC')->fetchAll();
admin_ensure_ahp_table($db);
$ahpPairs = table_exists('ahp_pairs')
    ? $db->query('SELECT criteria_id_left, criteria_id_right, value FROM ahp_pairs')->fetchAll()
    : [];
$perawat = $db->query('SELECT * FROM perawat ORDER BY name ASC, id ASC')->fetchAll();
$users = $db->query('SELECT id, name, username, role, created_at FROM users ORDER BY id ASC')->fetchAll();
$dashboard = dashboard_data($criteria, $perawat);
$ahpMatrix = admin_build_ahp_matrix($criteria, $ahpPairs);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
    <style>
        :root {
            --bg: #f6f7fb;
            --card: #fff;
            --text: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --accent: #0f766e;
            --danger: #b91c1c;
            --shadow: 0 16px 40px rgba(15, 23, 42, .08)
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text)
        }

        .top {
            background: linear-gradient(135deg, #0f172a, #0f766e);
            color: #fff;
            padding: 24px 28px
        }

        .wrap {
            max-width: 1280px;
            margin: 0 auto
        }

        .head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap
        }

        .nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px
        }

        .nav a {
            color: #fff;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .12)
        }

        .nav a.active {
            background: #fff;
            color: #0f172a
        }

        .main {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px 20px 60px
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 20px
        }

        .stat,
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 20px;
            box-shadow: var(--shadow)
        }

        .stat {
            padding: 18px 20px
        }

        .stat b {
            font-size: 30px;
            display: block;
            margin-top: 6px
        }

        .stat span {
            color: var(--muted);
            font-size: 13px
        }

        .card {
            padding: 20px;
            margin-bottom: 18px
        }

        .card h2 {
            margin: 0 0 14px;
            font-size: 18px
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px
        }

        .table th,
        .table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: top
        }

        .table th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted)
        }

        .tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px
        }

        .tabs a {
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid var(--line);
            text-decoration: none;
            color: var(--text);
            background: #fff
        }

        .tabs a.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent)
        }

        .flash {
            padding: 12px 14px;
            border-radius: 14px;
            margin-bottom: 16px
        }

        .flash.success {
            background: #dcfce7;
            color: #166534
        }

        .flash.error {
            background: #fee2e2;
            color: #991b1b
        }

        .form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px
        }

        .form .full {
            grid-column: 1/-1
        }

        .form label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px
        }

        .form input,
        .form textarea,
        .form select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            font: inherit;
            background: #fff
        }

        .form textarea {
            min-height: 96px;
            resize: vertical
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap
        }

        .btn {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 10px;
            border: 0;
            text-decoration: none;
            cursor: pointer;
            font-weight: 700
        }

        .btn.primary {
            background: var(--accent);
            color: #fff
        }

        .btn.ghost {
            background: #e2e8f0;
            color: #0f172a
        }

        .btn.danger {
            background: var(--danger);
            color: #fff
        }

        .muted {
            color: var(--muted)
        }

        .pill {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700
        }

        .k1 {
            background: #d1fae5;
            color: #065f46
        }

        .k2 {
            background: #dbeafe;
            color: #1e3a8a
        }

        .k3 {
            background: #fee2e2;
            color: #991b1b
        }

        @media (max-width:720px) {
            .main {
                padding: 18px 14px 40px
            }
        }
    </style>
</head>

<body>
    <div class="top">
        <div class="wrap">
            <div class="head">
                <div>
                    <div style="color:rgba(255,255,255,.75);font-size:12px;letter-spacing:.12em;text-transform:uppercase">Admin Panel</div>
                    <h1 style="margin:6px 0 0">Management Data Aplikasi</h1>
                </div>
                <div class="actions">
                    <a class="btn ghost" href="../index.php">Lihat Dashboard</a>
                    <a class="btn danger" href="../logout.php">Logout</a>
                </div>
            </div>
            <div class="nav">
                <a class="<?php echo admin_active($tab, 'overview'); ?>" href="?tab=overview">Overview</a>
                <a class="<?php echo admin_active($tab, 'criteria'); ?>" href="?tab=criteria">Kriteria</a>
                <a class="<?php echo admin_active($tab, 'ahp'); ?>" href="?tab=ahp">AHP</a>
                <a class="<?php echo admin_active($tab, 'perawat'); ?>" href="?tab=perawat">Perawat</a>
                <a class="<?php echo admin_active($tab, 'users'); ?>" href="?tab=users">Admin</a>
            </div>
        </div>
    </div>

    <div class="main">
        <?php if ($flash = flash_pull()): ?>
            <div class="flash <?php echo esc($flash['type'] ?? 'success'); ?>"><?php echo esc($flash['message'] ?? ''); ?></div>
        <?php endif; ?>

        <?php if ($tab === 'overview'): ?>
            <div class="grid">
                <div class="stat"><span>Total Kriteria</span><b><?php echo count($criteria); ?></b></div>
                <div class="stat"><span>Total Perawat</span><b><?php echo count($perawat); ?></b></div>
                <div class="stat"><span>Total Admin</span><b><?php echo count($users); ?></b></div>
                <div class="stat"><span>Silhouette</span><b><?php echo esc($dashboard['silhouette']); ?></b></div>
            </div>

            <div class="card">
                <h2>Top 5 Perawat</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Cluster</th>
                            <th>TOPSIS</th>
                            <th>WP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($dashboard['perawatByCi'], 0, 5) as $row): ?>
                            <tr>
                                <td><?php echo esc($row['name']); ?></td>
                                <td><span class="pill <?php echo strtolower(esc($row['cluster'])); ?>"><?php echo esc($row['cluster']); ?></span></td>
                                <td><?php echo number_format((float) $row['ci'], 4, '.', ''); ?></td>
                                <td><?php echo number_format((float) $row['wp'], 4, '.', ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'ahp'): ?>
            <div class="card">
                <h2>AHP Pairwise Matrix</h2>
                <p class="muted">Pilih skala Saaty 1-9 untuk perbandingan di sisi atas. Nilai sisi bawah dihitung otomatis sebagai kebalikan, lalu bobot disimpan kembali ke tabel kriteria.</p>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo esc(csrf_token()); ?>">
                    <input type="hidden" name="action" value="save_ahp">
                    <div style="overflow:auto;border:1px solid var(--line);border-radius:16px">
                        <table class="table" style="min-width:980px;margin:0">
                            <thead>
                                <tr>
                                    <th>Kriteria</th>
                                    <?php foreach ($criteria as $column): ?>
                                        <th><?php echo esc($column['code']); ?></th>
                                    <?php endforeach; ?>
                                    <th>Bobot Baru</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($criteria as $rowIndex => $rowCriterion): ?>
                                    <tr>
                                        <th style="text-transform:none;color:var(--text)"><?php echo esc($rowCriterion['code'] . ' - ' . $rowCriterion['name']); ?></th>
                                        <?php foreach ($criteria as $colIndex => $colCriterion): ?>
                                            <?php if ((int) $rowCriterion['id'] === (int) $colCriterion['id']): ?>
                                                <td><strong>1</strong></td>
                                            <?php elseif ($rowIndex < $colIndex): ?>
                                                <td>
                                                    <select name="pair_<?php echo (int) $rowCriterion['id']; ?>_<?php echo (int) $colCriterion['id']; ?>">
                                                        <?php foreach (admin_saaty_scale() as $scaleValue => $scaleLabel): ?>
                                                            <?php $selected = admin_normalize_saaty_value((float) ($ahpMatrix[$rowCriterion['id']][$colCriterion['id']] ?? 1)) === (float) $scaleValue; ?>
                                                            <option value="<?php echo esc($scaleValue); ?>" <?php echo $selected ? 'selected' : ''; ?>><?php echo esc($scaleLabel); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                            <?php else: ?>
                                                <td><?php echo number_format((float) ($ahpMatrix[$rowCriterion['id']][$colCriterion['id']] ?? 1), 3, '.', ''); ?></td>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <td><strong><?php echo number_format((float) $rowCriterion['weight'], 6, '.', ''); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="actions" style="margin-top:14px">
                        <button class="btn primary" type="submit">Hitung & Simpan Bobot AHP</button>
                    </div>
                </form>
            </div>
            <div class="card">
                <h2>Bobot Aktif</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Bobot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($criteria as $row): ?>
                            <tr>
                                <td><?php echo esc($row['code']); ?></td>
                                <td><?php echo esc($row['name']); ?></td>
                                <td><?php echo number_format((float) $row['weight'], 6, '.', ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'criteria'): ?>
            <div class="card">
                <h2>Tambah / Ubah Kriteria</h2>
                <form class="form" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo esc(csrf_token()); ?>">
                    <input type="hidden" name="action" value="save_criterion">
                    <input type="hidden" name="id" value="">
                    <div><label>Kode</label><input name="code" placeholder="C1" required></div>
                    <div class="full"><label>Nama Kriteria</label><input name="name" placeholder="Komp. Klinis" required></div>
                    <div><label>Bobot</label><input type="number" step="0.000001" name="weight" value="0.1" required></div>
                    <div><label>Urutan</label><input type="number" name="sort_order" value="1" required></div>
                    <div><label>Status</label><select name="is_active">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select></div>
                    <div class="full"><button class="btn primary" type="submit">Simpan Kriteria</button></div>
                </form>
            </div>
            <div class="card">
                <h2>Data Kriteria</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Bobot</th>
                            <th>Urutan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($criteria as $row): ?>
                            <tr>
                                <td><?php echo esc($row['code']); ?></td>
                                <td><?php echo esc($row['name']); ?></td>
                                <td><?php echo esc($row['weight']); ?></td>
                                <td><?php echo esc($row['sort_order']); ?></td>
                                <td><?php echo (int) $row['is_active'] === 1 ? 'Aktif' : 'Nonaktif'; ?></td>
                                <td>
                                    <form method="post" class="actions" style="margin:0">
                                        <input type="hidden" name="csrf_token" value="<?php echo esc(csrf_token()); ?>">
                                        <input type="hidden" name="action" value="delete_criterion">
                                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                        <button class="btn danger" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'perawat'): ?>
            <div class="card">
                <h2>Tambah / Ubah Perawat</h2>
                <form class="form" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo esc(csrf_token()); ?>">
                    <input type="hidden" name="action" value="save_perawat">
                    <input type="hidden" name="id" value="">
                    <div><label>Kode Pegawai</label><input name="employee_code"></div>
                    <div class="full"><label>Nama</label><input name="name" required></div>
                    <?php for ($i = 1; $i <= 7; $i++): ?>
                        <div><label>C<?php echo $i; ?></label><input type="number" step="0.01" name="c<?php echo $i; ?>" value="0" required></div>
                    <?php endfor; ?>
                    <div class="full"><label>Catatan</label><textarea name="notes"></textarea></div>
                    <div><label>Status</label><select name="is_active">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select></div>
                    <div class="full"><button class="btn primary" type="submit">Simpan Perawat</button></div>
                </form>
            </div>
            <div class="card">
                <h2>Data Perawat</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kode</th>
                            <th>C1</th>
                            <th>C2</th>
                            <th>C3</th>
                            <th>C4</th>
                            <th>C5</th>
                            <th>C6</th>
                            <th>C7</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($perawat as $row): ?>
                            <tr>
                                <td><?php echo esc($row['name']); ?></td>
                                <td><?php echo esc($row['employee_code']); ?></td>
                                <?php for ($i = 1; $i <= 7; $i++): ?>
                                    <td><?php echo esc($row['c' . $i]); ?></td>
                                <?php endfor; ?>
                                <td>
                                    <form method="post" class="actions" style="margin:0">
                                        <input type="hidden" name="csrf_token" value="<?php echo esc(csrf_token()); ?>">
                                        <input type="hidden" name="action" value="delete_perawat">
                                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                        <button class="btn danger" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'users'): ?>
            <div class="card">
                <h2>Tambah Admin</h2>
                <form class="form" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo esc(csrf_token()); ?>">
                    <input type="hidden" name="action" value="save_user">
                    <input type="hidden" name="id" value="">
                    <div><label>Nama</label><input name="name" required></div>
                    <div><label>Username</label><input name="username" required></div>
                    <div><label>Password</label><input type="password" name="password" required></div>
                    <div><label>Role</label><select name="role">
                            <option value="admin">Admin</option>
                            <option value="operator">Operator</option>
                        </select></div>
                    <div class="full"><button class="btn primary" type="submit">Simpan Admin</button></div>
                </form>
            </div>
            <div class="card">
                <h2>Daftar Admin</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $row): ?>
                            <tr>
                                <td><?php echo esc($row['name']); ?></td>
                                <td><?php echo esc($row['username']); ?></td>
                                <td><?php echo esc($row['role']); ?></td>
                                <td><?php echo esc($row['created_at']); ?></td>
                                <td>
                                    <form method="post" class="actions" style="margin:0">
                                        <input type="hidden" name="csrf_token" value="<?php echo esc(csrf_token()); ?>">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                        <button class="btn danger" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>