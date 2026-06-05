<?php
require_once __DIR__ . '/inc/analytics.php';

$db = db();
$criteria = $db->query('SELECT * FROM criteria WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$perawatRaw = $db->query('SELECT * FROM perawat WHERE is_active = 1 ORDER BY name ASC, id ASC')->fetchAll();
$dashboard = dashboard_data($criteria, $perawatRaw);
$perawat = $dashboard['perawatByCi'];
$currentUser = current_user();
$kmeansHistory = $dashboard['kmeansHistory'] ?? [];
$scoreFields = $dashboard['criteria'];

$criteriaLabels = array_map(static fn(array $row): string => $row['code'] . ' - ' . $row['name'], $dashboard['criteria']);
$criteriaWeights = $dashboard['weights'];
$clusterCounts = ['K1' => 0, 'K2' => 0, 'K3' => 0];
foreach ($perawat as $row) {
    $clusterCounts[$row['cluster']] = ($clusterCounts[$row['cluster']] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Evaluasi Perawat</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #fff;
            --text: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --accent: #0f766e;
            --accent2: #1d4ed8;
            --shadow: 0 18px 50px rgba(15, 23, 42, .08)
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at top left, #e0f2fe 0, #f5f7fb 38%, #eef2ff 100%);
            color: var(--text)
        }

        .hero {
            padding: 28px 20px 0
        }

        .wrap {
            max-width: 1280px;
            margin: 0 auto
        }

        .banner {
            background: linear-gradient(135deg, #0f172a, #0f766e 55%, #1d4ed8);
            color: #fff;
            border-radius: 28px;
            padding: 28px;
            box-shadow: var(--shadow);
            margin-bottom: 32px;
        }

        .banner-top {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap
        }

        .banner h1 {
            margin: 8px 0 10px;
            font-size: clamp(28px, 4vw, 48px);
            line-height: 1.05;
            letter-spacing: -.04em
        }

        .banner p {
            max-width: 720px;
            margin: 0;
            color: rgba(255, 255, 255, .84);
            font-size: 15px;
            line-height: 1.6
        }

        .nav a {
            display: inline-block;
            margin-right: 10px;
            margin-top: 12px;
            padding: 10px 14px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 700;
            background: rgba(255, 255, 255, .12);
            color: #fff
        }

        .nav a.primary {
            background: #fff;
            color: #0f172a
        }

        .main {
            max-width: 1280px;
            margin: 0 auto;
            padding: 22px 20px 60px
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-top: -20px
        }

        .stat,
        .card {
            background: var(--card);
            border: 1px solid rgba(226, 232, 240, .85);
            border-radius: 22px;
            box-shadow: var(--shadow)
        }

        .stat {
            padding: 18px 20px
        }

        .stat span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .08em
        }

        .stat b {
            display: block;
            font-size: 30px;
            margin-top: 8px
        }

        .grid {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 16px;
            margin-top: 18px
        }

        .card {
            padding: 20px
        }

        .card h2 {
            margin: 0 0 14px;
            font-size: 18px
        }

        .table-wrap {
            overflow: auto
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px
        }

        .table th,
        .table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            white-space: nowrap
        }

        .table th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
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

        .chart {
            height: 320px
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
        }

        .mini {
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px;
            background: #f8fafc;
        }

        .mini b {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted)
        }

        details.calc {
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px 16px;
            background: #fff
        }

        details.calc+details.calc {
            margin-top: 12px
        }

        details.calc summary {
            cursor: pointer;
            font-weight: 800;
            color: var(--text);
            list-style: none
        }

        details.calc summary::-webkit-details-marker {
            display: none
        }

        .step-list {
            margin: 10px 0 0 18px;
            color: var(--text)
        }

        .matrix {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 12px
        }

        .matrix th,
        .matrix td {
            border-bottom: 1px solid var(--line);
            padding: 8px 10px;
            white-space: nowrap;
            text-align: left
        }

        .matrix th {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .04em
        }

        .muted {
            color: var(--muted)
        }

        @media (max-width:960px) {
            .grid {
                grid-template-columns: 1fr
            }

            .banner {
                padding: 22px
            }

            .main {
                padding-top: 18px
            }
        }
    </style>
</head>

<body>
    <div class="hero">
        <div class="wrap">
            <div class="banner">
                <div class="banner-top">
                    <div>

                        <h1>Evaluasi Kinerja Perawat</h1>
                        <p>
                            Implementasi desain metode MCDM (Multi-Criteria Decision Making) untuk membantu manajemen rumah sakit dalam mengevaluasi dan meranking kinerja perawat berdasarkan berbagai kriteria yang relevan. Sistem ini dirancang untuk memberikan wawasan yang lebih mendalam tentang kinerja perawat, sehingga dapat mendukung pengambilan keputusan yang lebih baik dalam manajemen sumber daya manusia di lingkungan rumah sakit.
                            Menggunakan metode K-Means, AHP, WP dan TOPSIS.
                        </p>
                    </div>
                    <div>
                        <div class="nav">
                            <a class="primary" href="admin/index.php">Admin Panel</a>
                            <?php if ($currentUser): ?>
                                <a href="logout.php">Logout</a>
                            <?php else: ?>
                                <a href="login.php">Login Admin</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="main">
        <div class="stats">
            <div class="stat"><span>Kriteria Aktif</span><b><?php echo count($dashboard['criteria']); ?></b></div>
            <div class="stat"><span>Data Perawat</span><b><?php echo count($perawat); ?></b></div>
            <div class="stat"><span>Silhouette</span><b><?php echo esc($dashboard['silhouette']); ?></b></div>
            <div class="stat"><span>Iterasi K-Means</span><b><?php echo (int) ($dashboard['kmeans']['iterations'] ?? 0); ?></b></div>
        </div>

        <div class="grid">
            <div class="card">
                <h2>Ranking TOPSIS</h2>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Cluster</th>
                                <th>TOPSIS</th>
                                <th>Rank</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perawat as $row): ?>
                                <tr>
                                    <td><?php echo esc($row['name']); ?></td>
                                    <td><span class="pill <?php echo strtolower($row['cluster']); ?>"><?php echo esc($row['cluster']); ?></span></td>
                                    <td><?php echo number_format((float) $row['ci'], 4, '.', ''); ?></td>
                                    <td><?php echo (int) $row['rankT']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                <h2>Bobot Kriteria</h2>
                <div class="chart"><canvas id="criteriaChart"></canvas></div>
                <p class="muted">Bobot diambil langsung dari tabel kriteria.</p>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h2>Top 5 Perawat</h2>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>WP</th>
                                <th>TOPSIS</th>
                                <th>Cluster</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perawat as $row): ?>
                                <tr>
                                    <td><?php echo esc($row['name']); ?></td>
                                    <td><?php echo number_format((float) $row['wp'], 4, '.', ''); ?></td>
                                    <td><?php echo number_format((float) $row['ci'], 4, '.', ''); ?></td>
                                    <td><span class="pill <?php echo strtolower($row['cluster']); ?>"><?php echo esc($row['cluster']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                <h2>Distribusi Cluster</h2>
                <div class="chart"><canvas id="clusterChart"></canvas></div>
            </div>
        </div>
    </div>

    <script>
        const criteriaLabels = <?php echo json_encode($criteriaLabels, JSON_UNESCAPED_UNICODE); ?>;
        const criteriaWeights = <?php echo json_encode($criteriaWeights, JSON_UNESCAPED_UNICODE); ?>;
        const clusterCounts = <?php echo json_encode($clusterCounts, JSON_UNESCAPED_UNICODE); ?>;

        new Chart(document.getElementById('criteriaChart'), {
            type: 'bar',
            data: {
                labels: criteriaLabels,
                datasets: [{
                    label: 'Bobot',
                    data: criteriaWeights,
                    backgroundColor: '#0f766e'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        new Chart(document.getElementById('clusterChart'), {
            type: 'doughnut',
            data: {
                labels: ['K1', 'K2', 'K3'],
                datasets: [{
                    data: [clusterCounts.K1 || 0, clusterCounts.K2 || 0, clusterCounts.K3 || 0],
                    backgroundColor: ['#10b981', '#3b82f6', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>

</html>