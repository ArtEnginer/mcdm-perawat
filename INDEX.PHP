<?php
// ============================================================
// DATA & KALKULASI — SERVER SIDE (PHP)
// ============================================================

$kriteria = [
    'C1 Komp. Klinis',
    'C2 Profesionalisme',
    'C3 Kolaborasi Tim',
    'C4 Orientasi Hasil',
    'C5 Keselamatan',
    'C6 Mgmt Waktu',
    'C7 Komunikasi'
];
$kriteriaShort = ['C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7'];

$perawat = [
    ['no' => 1, 'nama' => 'Andi Setiawan',   'c' => [8, 7, 9, 8, 9, 7, 8], 'klaster' => 'K1', 'wp' => 7.913555, 'rankWP' => 4,  'ci' => 0.75897,  'rankT' => 4],
    ['no' => 2, 'nama' => 'Budi Santoso',    'c' => [5, 6, 5, 6, 5, 6, 5], 'klaster' => 'K3', 'wp' => 5.310986, 'rankWP' => 8,  'ci' => 0.253919, 'rankT' => 8],
    ['no' => 3, 'nama' => 'Citra Dewi',      'c' => [9, 9, 8, 9, 9, 8, 9], 'klaster' => 'K1', 'wp' => 8.828326, 'rankWP' => 1,  'ci' => 0.953567, 'rankT' => 1],
    ['no' => 4, 'nama' => 'Dian Pratiwi',    'c' => [6, 5, 6, 5, 6, 5, 6], 'klaster' => 'K2', 'wp' => 5.648669, 'rankWP' => 7,  'ci' => 0.359472, 'rankT' => 7],
    ['no' => 5, 'nama' => 'Eko Wahyudi',     'c' => [7, 8, 7, 8, 7, 8, 7], 'klaster' => 'K2', 'wp' => 7.316284, 'rankWP' => 5,  'ci' => 0.640528, 'rankT' => 5],
    ['no' => 6, 'nama' => 'Fitri Handayani', 'c' => [4, 5, 4, 5, 4, 5, 4], 'klaster' => 'K3', 'wp' => 4.30658,  'rankWP' => 10, 'ci' => 0.089833, 'rankT' => 10],
    ['no' => 7, 'nama' => 'Galih Nugroho',   'c' => [9, 8, 9, 9, 8, 9, 8], 'klaster' => 'K1', 'wp' => 8.618741, 'rankWP' => 2,  'ci' => 0.904541, 'rankT' => 2],
    ['no' => 8, 'nama' => 'Hana Putri',      'c' => [6, 6, 7, 6, 7, 6, 7], 'klaster' => 'K2', 'wp' => 6.236635, 'rankWP' => 6,  'ci' => 0.420531, 'rankT' => 6],
    ['no' => 9, 'nama' => 'Indra Kusuma',    'c' => [8, 9, 8, 8, 9, 9, 8], 'klaster' => 'K1', 'wp' => 8.378747, 'rankWP' => 3,  'ci' => 0.832798, 'rankT' => 3],
    ['no' => 10, 'nama' => 'Joko Purnomo',    'c' => [5, 4, 5, 4, 5, 4, 5], 'klaster' => 'K3', 'wp' => 4.644056, 'rankWP' => 9,  'ci' => 0.171882, 'rankT' => 9],
];


// ============================================================
// LANGKAH 2 — K-MEANS ITERATIF (Tidak Hardcode)
// ============================================================

/**
 * Hitung jarak Euclidean kuadrat antara satu data point dan satu centroid.
 */
function euclideanSq(array $point, array $cen): float
{
    $sum = 0.0;
    foreach ($point as $j => $v) $sum += ($v - $cen[$j]) ** 2;
    return $sum;
}

/**
 * Jalankan K-Means iteratif hingga konvergen.
 * Mengembalikan: centroids, labels (numerik), history tiap iterasi, jumlah iterasi.
 */
function runKMeans(array $data, int $k, array $initCentroids, int $maxIter = 100): array
{
    $n         = count($data);
    $centroids = $initCentroids;
    $labels    = array_fill(0, $n, -1);
    $history   = [];

    for ($iter = 1; $iter <= $maxIter; $iter++) {
        $iterDistances = [];
        $iterAssignments = [];

        // === Langkah E: Assign tiap titik ke centroid terdekat ===
        $newLabels = [];
        foreach ($data as $i => $point) {
            $minDist  = PHP_FLOAT_MAX;
            $minClust = 0;
            $pointDistances = [];
            foreach ($centroids as $ki => $cen) {
                $d = euclideanSq($point, $cen);
                $pointDistances[$ki] = [
                    'd2' => $d,
                    'd' => sqrt($d),
                ];
                if ($d < $minDist) {
                    $minDist = $d;
                    $minClust = $ki;
                }
            }
            $newLabels[$i] = $minClust;
            $iterDistances[$i] = $pointDistances;
            $iterAssignments[$i] = $minClust;
        }

        // === Langkah M: Hitung ulang centroid dari rata-rata anggota ===
        $sums   = array_fill(0, $k, array_fill(0, count($data[0]), 0.0));
        $counts = array_fill(0, $k, 0);
        foreach ($newLabels as $i => $ki) {
            $counts[$ki]++;
            foreach ($data[$i] as $j => $v) $sums[$ki][$j] += $v;
        }
        $newCentroids = [];
        $centroidMoves = [];
        for ($ki = 0; $ki < $k; $ki++) {
            $newCentroids[$ki] = $counts[$ki] > 0
                ? array_map(fn($s) => $s / $counts[$ki], $sums[$ki])
                : $centroids[$ki];
            $centroidMoves[$ki] = sqrt(euclideanSq($centroids[$ki], $newCentroids[$ki]));
        }

        // Simpan log iterasi ini
        $centLog = [];
        foreach ($newCentroids as $ki => $cen) {
            $centLog['K' . ($ki + 1)] = array_map(fn($v) => round($v, 4), $cen);
        }
        $history[] = [
            'iter'      => $iter,
            'centroids' => $centLog,
            'labels'    => $newLabels,
            'distances' => $iterDistances,
            'assignments' => $iterAssignments,
            'sums'      => $sums,
            'counts'    => $counts,
            'oldCentroids' => $centroids,
            'newCentroids' => $newCentroids,
            'centroidMoves' => $centroidMoves,
            'changed'   => ($newLabels !== $labels),
        ];

        // Konvergensi: label tidak berubah
        if ($newLabels === $labels) break;

        $labels    = $newLabels;
        $centroids = $newCentroids;
    }

    return [
        'centroids'  => $centroids,
        'labels'     => $labels,
        'history'    => $history,
        'iterations' => count($history),
    ];
}

// ---- Siapkan data mentah dari $perawat ----
$rawData = [];
foreach ($perawat as $p) $rawData[] = $p['c'];

// ---- Centroid Awal (Iterasi 0): pilih representatif high/mid/low berbasis domain ----
$initCentroids = [
    0 => $rawData[2],  // Citra Dewi      — calon K1 (nilai tinggi)
    1 => $rawData[7],  // Hana Putri      — calon K2 (nilai sedang)
    2 => $rawData[5],  // Fitri Handayani — calon K3 (nilai rendah)
];

// ---- Jalankan K-Means iteratif ----
$kmResult  = runKMeans($rawData, 3, $initCentroids, 100);
$kmHistory = $kmResult['history'];
$kmIter    = $kmResult['iterations'];
$kmLabels  = $kmResult['labels'];        // index numerik 0/1/2 per perawat

// ---- Mapping: urutkan centroid berdasarkan rata-rata (tinggi→K1, sedang→K2, rendah→K3) ----
$finalCentroids = $kmResult['centroids'];
$centMeans = [];
foreach ($finalCentroids as $ki => $cen) $centMeans[$ki] = array_sum($cen) / count($cen);
arsort($centMeans);   // index dengan rata-rata tertinggi lebih dahulu
$labelMap = [];
foreach (array_keys($centMeans) as $rank => $ki) $labelMap[$ki] = 'K' . ($rank + 1);

// ---- Terapkan klaster hasil iterasi ke setiap perawat ----
foreach ($perawat as $i => &$p) $p['klaster'] = $labelMap[$kmLabels[$i]];
unset($p);

// ---- Susun $centroid dalam urutan K1/K2/K3 ----
$centroid = [];
foreach ($labelMap as $ki => $kName) {
    $centroid[$kName] = array_map(fn($v) => round($v, 4), $finalCentroids[$ki]);
}
ksort($centroid);

// ---- Hitung $euclidData secara dinamis dari centroid hasil iterasi ----
$klNames  = ['K1', 'K2', 'K3'];
$klLabels = ['K1' => 'TINGGI', 'K2' => 'SEDANG', 'K3' => 'RENDAH'];
$euclidData = [];
foreach ($perawat as $p) {
    $d2 = [];
    $d = [];
    $bestKl = '';
    $bestDist = PHP_FLOAT_MAX;
    foreach ($klNames as $kn) {
        $sq   = euclideanSq($p['c'], $centroid[$kn]);
        $dist = sqrt($sq);
        $d2[] = round($sq, 4);
        $d[]  = round($dist, 4);
        if ($dist < $bestDist) {
            $bestDist = $dist;
            $bestKl = $kn;
        }
    }
    $euclidData[] = ['d2' => $d2, 'd' => $d, 'kl' => $bestKl, 'label' => $klLabels[$bestKl]];
}

// ---- Hitung Silhouette Score dari hasil klasterisasi ----
function silhouetteScore(array $data, array $labels): float
{
    $n = count($data);
    if ($n < 2) return 0.0;
    $scores = [];
    foreach ($data as $i => $point) {
        $myK = $labels[$i];
        $sameD = [];
        foreach ($data as $j => $q) {
            if ($j !== $i && $labels[$j] === $myK) $sameD[] = sqrt(euclideanSq($point, $q));
        }
        $a = count($sameD) > 0 ? array_sum($sameD) / count($sameD) : 0.0;
        $otherKs = array_unique($labels);
        $bVals = [];
        foreach ($otherKs as $ok) {
            if ($ok === $myK) continue;
            $od = [];
            foreach ($data as $j => $q) {
                if ($labels[$j] === $ok) $od[] = sqrt(euclideanSq($point, $q));
            }
            if (count($od) > 0) $bVals[] = array_sum($od) / count($od);
        }
        $b  = count($bVals) > 0 ? min($bVals) : 0.0;
        $mx = max($a, $b);
        $scores[] = $mx > 0 ? ($b - $a) / $mx : 0.0;
    }
    return round(array_sum($scores) / count($scores), 4);
}
$silhouette = silhouetteScore($rawData, $kmLabels);


$weights = [0.418116, 0.229216, 0.112646, 0.050868, 0.112646, 0.050868, 0.02564];

$pairwise = [
    [1,      3,       5,       7,       5,      7,      9],
    [1 / 3,    1,       3,       5,       3,      5,      7],
    [0.2,    1 / 3,     1,       3,       1,      3,      5],
    [1 / 7,    0.2,     1 / 3,     1,       1 / 3,    1,      3],
    [0.2,    1 / 3,     1,       3,       1,      3,      5],
    [1 / 7,    0.2,     1 / 3,     1,       1 / 3,    1,      3],
    [1 / 9,    1 / 7,     0.2,     1 / 3,     0.2,    1 / 3,    1],
];

$normMatrix = [
    [0.469449, 0.575868, 0.460123, 0.344262, 0.460123, 0.344262, 0.272727],
    [0.156483, 0.191956, 0.276074, 0.245902, 0.276074, 0.245902, 0.212121],
    [0.09389, 0.063985, 0.092025, 0.147541, 0.092025, 0.147541, 0.151515],
    [0.067064, 0.038391, 0.030675, 0.04918, 0.030675, 0.04918, 0.090909],
    [0.09389, 0.063985, 0.092025, 0.147541, 0.092025, 0.147541, 0.151515],
    [0.067064, 0.038391, 0.030675, 0.04918, 0.030675, 0.04918, 0.090909],
    [0.052161, 0.027422, 0.018405, 0.016393, 0.018405, 0.016393, 0.030303],
];

// \$euclidData dihitung secara dinamis oleh runKMeans() di atas

$topsisData = [
    ['dPos' => 0.028904, 'dNeg' => 0.091015],
    ['dPos' => 0.088253, 'dNeg' => 0.030036],
    ['dPos' => 0.005596, 'dNeg' => 0.114932],
    ['dPos' => 0.075516, 'dNeg' => 0.042380],
    ['dPos' => 0.042380, 'dNeg' => 0.075516],
    ['dPos' => 0.111372, 'dNeg' => 0.010992],
    ['dPos' => 0.011686, 'dNeg' => 0.110734],
    ['dPos' => 0.067788, 'dNeg' => 0.049195],
    ['dPos' => 0.019976, 'dNeg' => 0.099497],
    ['dPos' => 0.098599, 'dNeg' => 0.020465],
];

// ============================================================
// HELPER FUNCTIONS (PHP)
// ============================================================

function klasterClass(string $k): string
{
    return match ($k) {
        'K1' => 'k1',
        'K2' => 'k2',
        default => 'k3'
    };
}

function rankClass(int $r): string
{
    return match (true) {
        $r === 1 => 'rank-1',
        $r === 2 => 'rank-2',
        $r === 3 => 'rank-3',
        default => 'rank-other'
    };
}

function rekom(array $p): string
{
    return match ($p['klaster']) {
        'K1' => 'Promosi / Pengembangan Lanjut',
        'K2' => 'Pertahankan & Tingkatkan',
        default => 'Perlu Pembinaan Intensif',
    };
}

function rekomClass(array $p): string
{
    return match ($p['klaster']) {
        'K1' => 'rekom-promosi',
        'K2' => 'rekom-pertahankan',
        default => 'rekom-pembinaan',
    };
}

function initials(string $nama): string
{
    $parts = explode(' ', $nama);
    $ini = '';
    foreach ($parts as $w) $ini .= strtoupper($w[0]);
    return substr($ini, 0, 2);
}

function avClass(string $k): string
{
    return match ($k) {
        'K1' => 'av-k1',
        'K2' => 'av-k2',
        default => 'av-k3'
    };
}

function cardClass(string $k): string
{
    return match ($k) {
        'K1' => 'k1-card',
        'K2' => 'k2-card',
        default => 'k3-card'
    };
}

function ciClass(float $ci): string
{
    return $ci >= 0.7 ? 'ci-high' : ($ci >= 0.35 ? 'ci-mid' : 'ci-low');
}

// ============================================================
// SIMULATOR (PHP backend via POST)
// ============================================================

$simResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sim_submit'])) {
    $simNama = htmlspecialchars(trim($_POST['sim_nama'] ?? 'Perawat Baru'));
    $simVals = [];
    for ($i = 1; $i <= 7; $i++) {
        $v = floatval($_POST['s' . $i] ?? 5);
        $simVals[] = max(1, min(10, $v));
    }

    // K-Means classification
    $dists = [];
    foreach ($GLOBALS['centroid'] as $kl => $cen) {
        $d = 0;
        foreach ($simVals as $j => $v) $d += ($v - $cen[$j]) ** 2;
        $dists[$kl] = sqrt($d);
    }
    asort($dists);
    $simKlaster = array_key_first($dists);

    // WP Score
    $simWP = 1.0;
    foreach ($simVals as $j => $v) $simWP *= pow($v, $GLOBALS['weights'][$j]);

    // TOPSIS Ci (approx)
    $maxVals = array_fill(0, 7, 9);
    $minVals = array_fill(0, 7, 4);
    $sumSq = array_fill(0, 7, 0);
    foreach ($GLOBALS['perawat'] as $p) {
        foreach ($p['c'] as $j => $v) $sumSq[$j] += $v ** 2;
    }
    $vij = [];
    $vMax = [];
    $vMin = [];
    for ($j = 0; $j < 7; $j++) {
        $denom = sqrt($sumSq[$j] + $simVals[$j] ** 2);
        $vij[$j]  = ($simVals[$j]  * $GLOBALS['weights'][$j]) / $denom;
        $vMax[$j] = ($maxVals[$j]  * $GLOBALS['weights'][$j]) / sqrt($sumSq[$j] + $maxVals[$j] ** 2);
        $vMin[$j] = ($minVals[$j]  * $GLOBALS['weights'][$j]) / sqrt($sumSq[$j] + $minVals[$j] ** 2);
    }
    $dPos = 0;
    $dNeg = 0;
    for ($j = 0; $j < 7; $j++) {
        $dPos += ($vij[$j] - $vMax[$j]) ** 2;
        $dNeg += ($vij[$j] - $vMin[$j]) ** 2;
    }
    $dPos = sqrt($dPos);
    $dNeg = sqrt($dNeg);
    $simCi = ($dPos + $dNeg) > 0 ? $dNeg / ($dPos + $dNeg) : 0;

    $simResult = [
        'nama'    => $simNama,
        'vals'    => $simVals,
        'klaster' => $simKlaster,
        'wp'      => $simWP,
        'ci'      => $simCi,
    ];
}

// Sort perawat by rankT for final table
$perawatSortedT = $perawat;
usort($perawatSortedT, fn($a, $b) => $a['rankT'] - $b['rankT']);

$perawatSortedWP = $perawat;
usort($perawatSortedWP, fn($a, $b) => $a['rankWP'] - $b['rankWP']);

// Active tab from GET
$activeTab = $_GET['tab'] ?? 'overview';
$validTabs = ['overview', 'kmeans', 'ahp', 'wp', 'topsis', 'model', 'simulator'];
if (!in_array($activeTab, $validTabs)) $activeTab = 'overview';

// JSON for JS charts
$perawatJson      = json_encode($perawat);
$weightsJson      = json_encode($weights);
$centroidJson     = json_encode($centroid);
$kriteriaJson     = json_encode($kriteria);
$kriteriaShortJson = json_encode($kriteriaShort);
$topsisDataJson   = json_encode($topsisData);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluasi Kinerja Perawat — Hybrid MCDM (K-Means + AHP + WP + TOPSIS)</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
    <style>
        :root {
            --primary: #1a5276;
            --primary-light: #2e86c1;
            --primary-pale: #d6eaf8;
            --accent: #117a65;
            --accent-light: #1abc9c;
            --accent-pale: #d1f2eb;
            --warn: #b7770d;
            --warn-pale: #fef9e7;
            --danger: #922b21;
            --danger-pale: #fdedec;
            --surface: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #1a202c;
            --text2: #4a5568;
            --text3: #718096;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 4px 12px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.12);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--surface);
            color: var(--text);
            font-size: 14px;
            line-height: 1.6;
        }

        .app-header {
            background: linear-gradient(135deg, #1a2f4b 0%, #1a5276 60%, #117a65 100%);
            padding: 28px 32px 24px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.25);
        }

        .header-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .brand-text h1 {
            font-size: 18px;
            font-weight: 700;
            color: white;
            letter-spacing: -0.3px;
        }

        .brand-text p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 2px;
        }

        .header-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: rgba(255, 255, 255, 0.9);
            background: rgba(255, 255, 255, 0.12);
        }

        .app-nav {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 96px;
            z-index: 90;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .nav-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            overflow-x: auto;
        }

        .nav-tab {
            padding: 14px 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            background: none;
            color: var(--text3);
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
        }

        .nav-tab:hover {
            color: var(--primary-light);
            background: var(--primary-pale);
        }

        .nav-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            font-weight: 600;
        }

        .tab-num {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--border);
            color: var(--text3);
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-tab.active .tab-num {
            background: var(--primary);
            color: white;
        }

        .app-main {
            max-width: 1280px;
            margin: 0 auto;
            padding: 28px 24px 60px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
            letter-spacing: -0.4px;
        }

        .section-sub {
            font-size: 13px;
            color: var(--text3);
            margin-bottom: 24px;
        }

        .card {
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            padding: 20px 24px;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text2);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .dot-blue {
            background: #2e86c1;
        }

        .dot-green {
            background: #1abc9c;
        }

        .dot-amber {
            background: #f39c12;
        }

        .dot-red {
            background: #e74c3c;
        }

        .dot-purple {
            background: #8e44ad;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 20px;
            text-align: center;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -1px;
        }

        .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text3);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 4px;
        }

        .stat-sub {
            font-size: 12px;
            color: var(--text2);
            margin-top: 6px;
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead {
            background: #f0f4f8;
        }

        thead th {
            padding: 12px 14px;
            font-weight: 600;
            color: var(--text2);
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody td {
            padding: 11px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .klaster-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }

        .k1 {
            background: #d1f2eb;
            color: #0e6655;
        }

        .k2 {
            background: #d6eaf8;
            color: #1a5276;
        }

        .k3 {
            background: #fdedec;
            color: #922b21;
        }

        .rekom-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        .rekom-promosi {
            background: #d1f2eb;
            color: #0e6655;
        }

        .rekom-pertahankan {
            background: #d6eaf8;
            color: #1a5276;
        }

        .rekom-pembinaan {
            background: #fdedec;
            color: #922b21;
        }

        .rank-num {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            color: white;
        }

        .rank-1 {
            background: #f4d03f;
            color: #7d6608 !important;
        }

        .rank-2 {
            background: #bdc3c7;
            color: #555 !important;
        }

        .rank-3 {
            background: #ca6f1e;
            color: white;
        }

        .rank-other {
            background: var(--primary-pale);
            color: var(--primary) !important;
        }

        .score-bar-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .score-bar {
            height: 6px;
            border-radius: 3px;
            background: var(--primary-pale);
            flex: 1;
            overflow: hidden;
        }

        .score-bar-fill {
            height: 100%;
            border-radius: 3px;
        }

        .score-bar-fill.green {
            background: linear-gradient(90deg, #1abc9c, #117a65);
        }

        .score-bar-fill.blue {
            background: linear-gradient(90deg, #3498db, #1a5276);
        }

        .score-val {
            font-size: 12px;
            font-weight: 600;
            color: var(--text2);
            min-width: 40px;
            text-align: right;
        }

        .perawat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        .perawat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            box-shadow: var(--shadow);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .perawat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .perawat-card.k1-card {
            border-left: 4px solid #1abc9c;
        }

        .perawat-card.k2-card {
            border-left: 4px solid #2e86c1;
        }

        .perawat-card.k3-card {
            border-left: 4px solid #e74c3c;
        }

        .pc-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .pc-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .av-k1 {
            background: #d1f2eb;
            color: #0e6655;
        }

        .av-k2 {
            background: #d6eaf8;
            color: #1a5276;
        }

        .av-k3 {
            background: #fdedec;
            color: #922b21;
        }

        .pc-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
        }

        .pc-rank {
            font-size: 11px;
            color: var(--text3);
            margin-top: 2px;
        }

        .pc-scores {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }

        .pc-score-item {
            background: var(--surface);
            border-radius: 8px;
            padding: 8px 10px;
        }

        .pc-score-label {
            font-size: 10px;
            color: var(--text3);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pc-score-val {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            margin-top: 2px;
        }

        .pc-criteria {
            margin-top: 12px;
            border-top: 1px solid var(--border);
            padding-top: 12px;
        }

        .pc-crit-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 5px;
        }

        .pc-crit-label {
            font-size: 11px;
            color: var(--text3);
            width: 90px;
            flex-shrink: 0;
        }

        .pc-crit-bar {
            flex: 1;
            height: 5px;
            border-radius: 3px;
            background: var(--border);
            overflow: hidden;
        }

        .pc-crit-fill {
            height: 100%;
            border-radius: 3px;
        }

        .pc-crit-num {
            font-size: 11px;
            font-weight: 600;
            color: var(--text2);
            min-width: 18px;
            text-align: right;
        }

        .flow-steps {
            display: flex;
            gap: 0;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .flow-step {
            flex: 1;
            min-width: 140px;
            background: var(--card);
            border: 1px solid var(--border);
            padding: 16px;
            text-align: center;
            position: relative;
        }

        .flow-step:not(:last-child)::after {
            content: '→';
            position: absolute;
            right: -14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: var(--primary-light);
            z-index: 2;
            background: var(--card);
            padding: 0 4px;
        }

        .flow-step:first-child {
            border-radius: var(--radius) 0 0 var(--radius);
        }

        .flow-step:last-child {
            border-radius: 0 var(--radius) var(--radius) 0;
        }

        .flow-icon {
            font-size: 24px;
            margin-bottom: 6px;
        }

        .flow-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--primary);
        }

        .flow-desc {
            font-size: 11px;
            color: var(--text3);
            margin-top: 3px;
        }

        .ahp-table thead th,
        .ahp-table tbody td {
            text-align: center;
        }

        .ahp-table tbody td:first-child {
            text-align: left;
            font-weight: 600;
        }

        .cr-box {
            background: var(--accent-pale);
            border: 1px solid var(--accent-light);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .cr-item {
            text-align: center;
        }

        .cr-val {
            font-size: 22px;
            font-weight: 700;
            color: var(--accent);
            font-family: 'JetBrains Mono', monospace;
        }

        .cr-label {
            font-size: 11px;
            color: var(--text3);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cr-status {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            background: var(--accent);
            color: white;
            margin-left: auto;
        }

        .chart-container {
            position: relative;
            width: 100%;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        @media (max-width: 1024px) {
            .grid-3 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .grid-3 {
                grid-template-columns: 1fr;
            }
        }

        .formula-box {
            background: #0d1117;
            border-radius: 8px;
            padding: 14px 18px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: #c9d1d9;
            margin: 12px 0;
            overflow-x: auto;
        }

        .formula-box .kw {
            color: #ff7b72;
        }

        .formula-box .fn {
            color: #79c0ff;
        }

        .formula-box .num {
            color: #f0883e;
        }

        .formula-box .comment {
            color: #8b949e;
        }

        details {
            transition: all 0.2s;
        }

        details summary {
            user-select: none;
            transition: background 0.2s;
        }

        details summary:hover {
            background: var(--surface) !important;
        }

        details[open]>summary span:last-child {
            transform: rotate(180deg);
            transition: transform 0.2s;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text2);
        }

        .form-group input[type=number],
        .form-group input[type=text] {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 9px 12px;
            font-size: 13px;
            font-family: inherit;
            color: var(--text);
            background: var(--surface);
            outline: none;
            width: 100%;
        }

        .form-group input:focus {
            border-color: var(--primary-light);
            background: white;
        }

        .btn {
            padding: 10px 22px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-success {
            background: var(--accent);
            color: white;
        }

        .btn-success:hover {
            opacity: 0.9;
        }

        .centroid-row {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .centroid-card {
            flex: 1;
            min-width: 140px;
            border-radius: var(--radius);
            padding: 14px 18px;
            border: 1px solid;
        }

        .cent-k1 {
            background: #d1f2eb;
            border-color: #1abc9c;
        }

        .cent-k2 {
            background: #d6eaf8;
            border-color: #2e86c1;
        }

        .cent-k3 {
            background: #fdedec;
            border-color: #e74c3c;
        }

        .centroid-card h4 {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .centroid-card .vals {
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
            line-height: 1.8;
        }

        .legend {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text2);
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .topsis-ci {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 600;
        }

        .ci-high {
            color: #0e6655;
        }

        .ci-mid {
            color: #1a5276;
        }

        .ci-low {
            color: #922b21;
        }

        .overview-hero {
            background: linear-gradient(135deg, #1a2f4b 0%, #1a5276 100%);
            border-radius: 16px;
            padding: 32px;
            color: white;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .overview-hero::before {
            content: '';
            position: absolute;
            right: -40px;
            top: -40px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
        }

        .overview-hero h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .overview-hero p {
            font-size: 14px;
            opacity: 0.8;
            max-width: 600px;
            line-height: 1.6;
        }

        .hero-stats {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-num {
            font-size: 32px;
            font-weight: 700;
            line-height: 1;
        }

        .hero-stat-label {
            font-size: 11px;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 4px;
        }

        .best-row td {
            background: #f0fdf4 !important;
            font-weight: 600;
        }

        .best-row td:first-child {
            border-left: 3px solid #1abc9c;
        }

        .weight-slider-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .weight-slider-row label {
            min-width: 150px;
            font-size: 13px;
            color: var(--text2);
            font-weight: 500;
        }

        .weight-slider-row input[type=range] {
            flex: 1;
        }

        .weight-slider-row .w-val {
            min-width: 48px;
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
        }

        .weight-total {
            font-size: 14px;
            font-weight: 700;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid;
        }

        .wt-ok {
            background: var(--accent-pale);
            border-color: var(--accent-light);
            color: var(--accent);
        }

        .wt-warn {
            background: var(--warn-pale);
            border-color: #f4d03f;
            color: var(--warn);
        }

        .sim-result-box {
            background: var(--accent-pale);
            border: 1px solid var(--accent-light);
            border-radius: var(--radius);
            padding: 20px;
        }

        @media(max-width:700px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .flow-steps {
                flex-direction: column;
            }

            .flow-step:not(:last-child)::after {
                content: '↓';
                right: auto;
                top: auto;
                bottom: -16px;
                left: 50%;
                transform: translateX(-50%);
            }

            .flow-step {
                border-radius: var(--radius) !important;
            }
        }
    </style>
</head>

<body>

    <header class="app-header">
        <div class="header-inner">
            <div class="header-brand">
                <div class="brand-icon">🏥</div>
                <div class="brand-text">
                    <h1>Evaluasi Kinerja Perawat</h1>
                    <p>Model Konseptual Berbasis Klasterisasi &amp; Hybrid MCDM — Fatkhurrochman, S.Kom, M.Kom | 2026</p>
                </div>
            </div>
            <div class="header-badges">
                <span class="badge">K-Means</span>
                <span class="badge">AHP</span>
                <span class="badge">WP</span>
                <span class="badge">TOPSIS</span>
                <span class="badge">PHP</span>
            </div>
        </div>
    </header>

    <nav class="app-nav">
        <div class="nav-inner">
            <?php
            $tabs = [
                'overview'  => ['num' => '0',  'label' => 'Dashboard'],
                'kmeans'    => ['num' => '1',  'label' => 'K-Means Clustering'],
                'ahp'       => ['num' => '2',  'label' => 'AHP — Pembobotan'],
                'wp'        => ['num' => '3',  'label' => 'Weighted Product'],
                'topsis'    => ['num' => '4',  'label' => 'TOPSIS'],
                'model'     => ['num' => '5',  'label' => 'Hasil & Rekomendasi'],
                'simulator' => ['num' => '★',  'label' => 'Simulator'],
            ];
            foreach ($tabs as $key => $tab):
                $active = $activeTab === $key ? ' active' : '';
            ?>
                <a href="?tab=<?= $key ?>" class="nav-tab<?= $active ?>">
                    <span class="tab-num"><?= $tab['num'] ?></span> <?= $tab['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <main class="app-main">

        <?php if ($activeTab === 'overview'): ?>
            <!-- ==================== DASHBOARD ==================== -->
            <div class="overview-hero">
                <h2>Model Konseptual Evaluasi Kinerja Perawat</h2>
                <p>Sistem evaluasi berbasis data mengintegrasikan K-Means Clustering untuk segmentasi pola kinerja, AHP untuk pembobotan kriteria berbasis pakar, serta WP dan TOPSIS sebagai metode MCDM hibrida untuk pemeringkatan akhir.</p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-num">10</div>
                        <div class="hero-stat-label">Perawat</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-num">7</div>
                        <div class="hero-stat-label">Kriteria</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-num">3</div>
                        <div class="hero-stat-label">Klaster</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-num">0.036</div>
                        <div class="hero-stat-label">CR (AHP)</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-num">≥75%</div>
                        <div class="hero-stat-label">Konsistensi TOPSIS</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><span class="dot dot-blue"></span>Alur Model Konseptual</div>
                <div class="flow-steps">
                    <div class="flow-step" style="border-top:3px solid #2e86c1;">
                        <div class="flow-icon">📥</div>
                        <div class="flow-title">INPUT</div>
                        <div class="flow-desc">7 Kriteria, 10 Perawat (Skala 1–10)</div>
                    </div>
                    <div class="flow-step" style="border-top:3px solid #1abc9c;">
                        <div class="flow-icon">🔵</div>
                        <div class="flow-title">K-MEANS</div>
                        <div class="flow-desc">Segmentasi k=3 klaster kinerja</div>
                    </div>
                    <div class="flow-step" style="border-top:3px solid #8e44ad;">
                        <div class="flow-icon">⚖️</div>
                        <div class="flow-title">AHP</div>
                        <div class="flow-desc">Bobot prioritas kriteria, CR&lt;0.1</div>
                    </div>
                    <div class="flow-step" style="border-top:3px solid #f39c12;">
                        <div class="flow-icon">✖️</div>
                        <div class="flow-title">WP</div>
                        <div class="flow-desc">Skor preferensi Si = ∏(Xij^Wj)</div>
                    </div>
                    <div class="flow-step" style="border-top:3px solid #e74c3c;">
                        <div class="flow-icon">🎯</div>
                        <div class="flow-title">TOPSIS</div>
                        <div class="flow-desc">Validasi Ci = D−/(D++D−)</div>
                    </div>
                    <div class="flow-step" style="border-top:3px solid #2c3e50;">
                        <div class="flow-icon">📊</div>
                        <div class="flow-title">OUTPUT</div>
                        <div class="flow-desc">Ranking final + Rekomendasi manajerial</div>
                    </div>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-card" style="border-top:3px solid #1abc9c;">
                    <div class="stat-value" style="color:#0e6655;">4</div>
                    <div class="stat-label">K1 — Kinerja Tinggi</div>
                    <div class="stat-sub">Avg WP: 8.43 | Avg Ci: 0.862</div>
                </div>
                <div class="stat-card" style="border-top:3px solid #2e86c1;">
                    <div class="stat-value" style="color:#1a5276;">3</div>
                    <div class="stat-label">K2 — Kinerja Sedang</div>
                    <div class="stat-sub">Avg WP: 6.40 | Avg Ci: 0.474</div>
                </div>
                <div class="stat-card" style="border-top:3px solid #e74c3c;">
                    <div class="stat-value" style="color:#922b21;">3</div>
                    <div class="stat-label">K3 — Kinerja Rendah</div>
                    <div class="stat-sub">Avg WP: 4.75 | Avg Ci: 0.172</div>
                </div>
                <div class="stat-card" style="border-top:3px solid #8e44ad;">
                    <div class="stat-value" style="color:#6c3483;">41.8%</div>
                    <div class="stat-label">Bobot C1 (Komp. Klinis)</div>
                    <div class="stat-sub">Kriteria paling dominan — AHP</div>
                </div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-title"><span class="dot dot-green"></span>Top 5 Perawat — Rank Final</div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Nama</th>
                                    <th>WP Score</th>
                                    <th>TOPSIS Ci</th>
                                    <th>Klaster</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($perawatSortedT, 0, 5) as $p): ?>
                                    <tr<?= $p['rankT'] === 1 ? ' class="best-row"' : '' ?>>
                                        <td><span class="rank-num <?= rankClass($p['rankT']) ?>"><?= $p['rankT'] ?></span></td>
                                        <td><?= $p['nama'] ?></td>
                                        <td><?= number_format($p['wp'], 3) ?></td>
                                        <td class="topsis-ci <?= ciClass($p['ci']) ?>"><?= number_format($p['ci'], 3) ?></td>
                                        <td><span class="klaster-badge <?= klasterClass($p['klaster']) ?>"><?= $p['klaster'] ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-title"><span class="dot dot-purple"></span>Distribusi Klaster</div>
                    <div class="legend">
                        <span class="legend-item"><span class="legend-dot" style="background:#1abc9c"></span>K1 Tinggi (40%)</span>
                        <span class="legend-item"><span class="legend-dot" style="background:#2e86c1"></span>K2 Sedang (30%)</span>
                        <span class="legend-item"><span class="legend-dot" style="background:#e74c3c"></span>K3 Rendah (30%)</span>
                    </div>
                    <div class="chart-container" style="height:220px;"><canvas id="chartKlaster"></canvas></div>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><span class="dot dot-amber"></span>Perbandingan WP Score vs TOPSIS Ci — Seluruh Perawat</div>
                <div class="chart-container" style="height:320px;"><canvas id="chartComparison"></canvas></div>
            </div>

        <?php elseif ($activeTab === 'kmeans'): ?>
            <!-- ==================== K-MEANS ==================== -->
            <div class="section-title">Tahap 1 — K-Means Clustering</div>
            <div class="section-sub">Algoritma K-Means dengan k=3 klaster | 7 Kriteria | Silhouette Score ≥ 0.5</div>

            <div class="card">
                <div class="card-title"><span class="dot dot-blue"></span>Data Kinerja Perawat (Skala 1–10)</div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Perawat</th>
                                <th>C1 Klinis</th>
                                <th>C2 Prof.</th>
                                <th>C3 Kolab.</th>
                                <th>C4 Hasil</th>
                                <th>C5 Selamat.</th>
                                <th>C6 Waktu</th>
                                <th>C7 Komun.</th>
                                <th>Total</th>
                                <th>Rata-rata</th>
                                <th>Klaster</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perawat as $p):
                                $tot = array_sum($p['c']);
                                $avg = number_format($tot / 7, 6);
                            ?>
                                <tr>
                                    <td><?= $p['no'] ?></td>
                                    <td><b><?= $p['nama'] ?></b></td>
                                    <?php foreach ($p['c'] as $v): ?><td style="text-align:center;font-family:'JetBrains Mono',monospace;"><?= $v ?></td><?php endforeach; ?>
                                    <td style="text-align:center;font-weight:600;"><?= $tot ?></td>
                                    <td style="text-align:center;font-family:'JetBrains Mono',monospace;"><?= $avg ?></td>
                                    <td><span class="klaster-badge <?= klasterClass($p['klaster']) ?>"><?= $p['klaster'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-title"><span class="dot dot-green"></span>Centroid K-Means — Hasil Iterasi (<?= $kmIter ?> iterasi)</div>
                    <div class="centroid-row">
                        <div class="centroid-card cent-k1">
                            <h4 style="color:#0e6655;">K1 — Kinerja TINGGI</h4>
                            <div class="vals" style="color:#0e6655;">C1:8.5 C2:8.3 C3:8.5<br>C4:8.5 C5:8.7 C6:8.3 C7:8.3</div>
                        </div>
                        <div class="centroid-card cent-k2">
                            <h4 style="color:#1a5276;">K2 — Kinerja SEDANG</h4>
                            <div class="vals" style="color:#1a5276;">C1:6.5 C2:6.5 C3:6.7<br>C4:6.7 C5:6.7 C6:6.5 C7:6.7</div>
                        </div>
                        <div class="centroid-card cent-k3">
                            <h4 style="color:#922b21;">K3 — Kinerja RENDAH</h4>
                            <div class="vals" style="color:#922b21;">C1:4.7 C2:5.0 C3:4.7<br>C4:5.0 C5:4.7 C6:5.0 C7:4.7</div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-title"><span class="dot dot-purple"></span>Radar Profil per Klaster</div>
                    <div class="chart-container" style="height:220px;"><canvas id="chartRadar"></canvas></div>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><span class="dot dot-amber"></span>Jarak Euclidean ke Centroid &amp; Penugasan Klaster</div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Perawat</th>
                                <th>D² → K1</th>
                                <th>D² → K2</th>
                                <th>D² → K3</th>
                                <th>D → K1</th>
                                <th>D → K2</th>
                                <th>D → K3</th>
                                <th>Klaster</th>
                                <th>Label</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perawat as $i => $p):
                                $e = $euclidData[$i];
                            ?>
                                <tr>
                                    <td><?= $p['no'] ?></td>
                                    <td><b><?= $p['nama'] ?></b></td>
                                    <td><?= number_format($e['d2'][0], 2) ?></td>
                                    <td><?= number_format($e['d2'][1], 2) ?></td>
                                    <td><?= number_format($e['d2'][2], 2) ?></td>
                                    <td><?= number_format($e['d'][0], 3) ?></td>
                                    <td><?= number_format($e['d'][1], 3) ?></td>
                                    <td><?= number_format($e['d'][2], 3) ?></td>
                                    <td><span class="klaster-badge <?= klasterClass($e['kl']) ?>"><?= $e['kl'] ?></span></td>
                                    <td><b><?= $e['label'] ?></b></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="formula-box" style="margin-top:16px;">
                    <span class="comment">// Formula Jarak Euclidean</span><br>
                    d(x, c) = <span class="fn">√</span>( <span class="fn">Σ</span>(Xij − Ckj)² )<br>
                    <span class="comment">// Penugasan: min(d→K1, d→K2, d→K3)</span>
                </div>
            </div>

            <!-- ============ LOG ITERASI K-MEANS ============ -->
            <div class="card">
                <div class="card-title"><span class="dot dot-blue"></span>Log Iterasi K-Means — Ringkasan Centroid (Proses Konvergensi)</div>
                <p style="font-size:12px;color:var(--text3);margin-bottom:12px;">
                    Algoritma berjalan <strong><?= $kmIter ?> iterasi</strong> hingga penugasan klaster tidak berubah (konvergen).
                    Centroid dihitung ulang setiap iterasi dari rata-rata anggota klaster.
                </p>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Iterasi</th>
                                <th>K1 Centroid (C1–C7)</th>
                                <th>K2 Centroid (C1–C7)</th>
                                <th>K3 Centroid (C1–C7)</th>
                                <th>Label Berubah?</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Tampilkan centroid awal (Iterasi 0)
                            $krShort = ['C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7'];
                            function centStr(array $cen, array $krShort): string
                            {
                                $parts = [];
                                foreach ($cen as $j => $v) $parts[] = $krShort[$j] . ':' . number_format($v, 2);
                                return implode(' ', $parts);
                            }
                            ?>
                            <tr style="background:var(--primary-pale);">
                                <td><strong>0 (Awal)</strong></td>
                                <?php
                                $ic = [
                                    $initCentroids[0],
                                    $initCentroids[1],
                                    $initCentroids[2],
                                ];
                                // Map initial centroids to K1/K2/K3 by mean
                                $icMeans = array_map(fn($c) => array_sum($c) / count($c), $ic);
                                arsort($icMeans);
                                $icSorted = [];
                                foreach (array_keys($icMeans) as $ki) $icSorted[] = $ic[$ki];
                                foreach ($icSorted as $cen): ?>
                                    <td style="font-family:'JetBrains Mono',monospace;font-size:11px;"><?= centStr($cen, $krShort) ?></td>
                                <?php endforeach; ?>
                                <td>—</td>
                            </tr>
                            <?php foreach ($kmHistory as $h): ?>
                                <tr>
                                    <td><strong><?= $h['iter'] ?></strong></td>
                                    <?php
                                    // centroid sudah disimpan dalam log
                                    $centLog = $h['centroids'];
                                    // urutkan berdasarkan rata-rata
                                    $cMeans = [];
                                    foreach ($centLog as $ki => $cen) $cMeans[$ki] = array_sum($cen) / count($cen);
                                    arsort($cMeans);
                                    foreach (array_keys($cMeans) as $ki):
                                        $cen = $centLog[$ki];
                                    ?>
                                        <td style="font-family:'JetBrains Mono',monospace;font-size:11px;"><?= centStr($cen, $krShort) ?></td>
                                    <?php endforeach; ?>
                                    <td style="text-align:center;">
                                        <?php if ($h['changed']): ?>
                                            <span style="color:var(--warn);font-weight:600;">✔ Ya</span>
                                        <?php else: ?>
                                            <span style="color:var(--accent);font-weight:600;">✔ Konvergen</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="formula-box" style="margin-top:16px;">
                    <span class="comment">// Langkah E (Expectation): Assign xi → arg min_k d(xi, ck)</span><br>
                    <span class="comment">// Langkah M (Maximization): ck = (1/|Ck|) Σ xi , xi ∈ Ck</span><br>
                    <span class="comment">// Konvergen bila: label[t] === label[t-1] untuk semua i</span><br>
                    Silhouette Score = <?= $silhouette ?> &nbsp;→&nbsp;
                    <span style="color:var(--accent);">
                        <?= $silhouette >= 0.5 ? '✔ VALID (≥ 0.5)' : '⚠ Perlu ditinjau (< 0.5)' ?>
                    </span>
                </div>
            </div>

            <!-- ============ DETAIL PENUGASAN KLASTER PER ITERASI ============ -->
            <div class="card">
                <div class="card-title"><span class="dot dot-green"></span>Detail Penugasan Klaster — Setiap Iterasi</div>
                <p style="font-size:12px;color:var(--text3);margin-bottom:16px;">
                    Tabel berikut menunjukkan penugasan setiap perawat ke klaster di setiap tahap iterasi,
                    beserta jumlah anggota per klaster dan centroid yang digunakan untuk iterasi berikutnya.
                </p>

                <?php
                // Persiapan: Hitung penugasan iterasi 0 (Awal)
                $assignmentHistory = [];

                // Iterasi 0: Assign dengan centroid awal
                $ic = [
                    $initCentroids[0],
                    $initCentroids[1],
                    $initCentroids[2],
                ];
                $icMeans = array_map(fn($c) => array_sum($c) / count($c), $ic);
                arsort($icMeans);
                $icMap = [];
                $icRank = 0;
                foreach (array_keys($icMeans) as $ki) {
                    $icMap[$ki] = 'K' . (++$icRank);
                }
                $labels0 = [];
                foreach ($rawData as $i => $point) {
                    $minDist = PHP_FLOAT_MAX;
                    $minClust = 0;
                    foreach ($ic as $ki => $cen) {
                        $d = euclideanSq($point, $cen);
                        if ($d < $minDist) {
                            $minDist = $d;
                            $minClust = $ki;
                        }
                    }
                    $labels0[$i] = $icMap[$minClust];
                }

                $counts0 = ['K1' => 0, 'K2' => 0, 'K3' => 0];
                $members0 = ['K1' => [], 'K2' => [], 'K3' => []];
                $distances0 = [];
                foreach ($rawData as $i => $point) {
                    $row = [];
                    foreach ($ic as $ki => $cen) {
                        $d2 = euclideanSq($point, $cen);
                        $row[$icMap[$ki]] = ['d2' => $d2, 'd' => sqrt($d2)];
                    }
                    $distances0[$i] = $row;
                }
                foreach ($labels0 as $i => $k) {
                    $counts0[$k]++;
                    $members0[$k][] = $perawat[$i]['nama'];
                }

                $assignmentHistory[0] = [
                    'labels' => $labels0,
                    'counts' => $counts0,
                    'members' => $members0,
                    'distances' => $distances0,
                    'centroids' => [
                        'K1' => $icSorted[0],
                        'K2' => $icSorted[1],
                        'K3' => $icSorted[2],
                    ],
                    'oldCentroids' => [
                        'K1' => $icSorted[0],
                        'K2' => $icSorted[1],
                        'K3' => $icSorted[2],
                    ],
                    'newCentroids' => [
                        'K1' => $icSorted[0],
                        'K2' => $icSorted[1],
                        'K3' => $icSorted[2],
                    ],
                    'centroidMoves' => [
                        'K1' => 0.0,
                        'K2' => 0.0,
                        'K3' => 0.0,
                    ],
                ];

                // Iterasi 1 - N: Gunakan data dari history
                foreach ($kmHistory as $h) {
                    $iter = $h['iter'];
                    $labelsH = $h['labels'];

                    // Map label numerik ke K1/K2/K3
                    $centLog = $h['centroids'];
                    $cMeans = [];
                    foreach ($centLog as $ki => $cen) $cMeans[$ki] = array_sum($cen) / count($cen);
                    arsort($cMeans);
                    $labelMapH = [];
                    $rankH = 0;
                    foreach (array_keys($cMeans) as $ki) {
                        $labelMapH[$rankH] = 'K' . (++$rankH);
                    }

                    $labelsStr = [];
                    foreach ($labelsH as $i => $ki) {
                        $labelsStr[$i] = $labelMapH[$ki] ?? ('K' . ($ki + 1));
                    }

                    $countsH = ['K1' => 0, 'K2' => 0, 'K3' => 0];
                    $membersH = ['K1' => [], 'K2' => [], 'K3' => []];
                    foreach ($labelsStr as $i => $k) {
                        $countsH[$k]++;
                        $membersH[$k][] = $perawat[$i]['nama'];
                    }

                    $orderedDistances = [];
                    foreach ($labelsH as $i => $ki) {
                        $orderedDistances[$i] = [];
                        foreach (array_keys($cMeans) as $rank => $idx) {
                            $kName = 'K' . ($rank + 1);
                            $orderedDistances[$i][$kName] = $h['distances'][$i][$idx] ?? ['d2' => 0.0, 'd' => 0.0];
                        }
                    }

                    $orderedCentroids = [];
                    $orderedOldCentroids = [];
                    $orderedNewCentroids = [];
                    $orderedMoves = [];
                    $orderedSums = [];
                    $orderedCounts = [];
                    foreach (array_keys($cMeans) as $rank => $idx) {
                        $kName = 'K' . ($rank + 1);
                        $sourceIdx = (int) substr($idx, 1) - 1;
                        $orderedCentroids[$kName] = $centLog[$idx];
                        $orderedOldCentroids[$kName] = $h['oldCentroids'][$sourceIdx] ?? [];
                        $orderedNewCentroids[$kName] = $h['newCentroids'][$sourceIdx] ?? [];
                        $orderedMoves[$kName] = $h['centroidMoves'][$sourceIdx] ?? 0.0;
                        $orderedSums[$kName] = $h['sums'][$sourceIdx] ?? [];
                        $orderedCounts[$kName] = $h['counts'][$sourceIdx] ?? 0;
                    }

                    $assignmentHistory[$iter] = [
                        'labels' => $labelsStr,
                        'counts' => $countsH,
                        'members' => $membersH,
                        'distances' => $orderedDistances,
                        'centroids' => $orderedCentroids,
                        'oldCentroids' => $orderedOldCentroids,
                        'newCentroids' => $orderedNewCentroids,
                        'centroidMoves' => $orderedMoves,
                        'sums' => $orderedSums,
                        'rawCounts' => $orderedCounts,
                    ];
                }
                ?>

                <!-- Accordion atau Tab untuk setiap iterasi -->
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($assignmentHistory as $iterNum => $assign): ?>
                        <details style="border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden;">
                            <summary style="padding: 12px 16px; background: <?= $iterNum === 0 ? 'var(--primary-pale)' : 'var(--surface)' ?>; cursor: pointer; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
                                <span>
                                    Iterasi <?= $iterNum === 0 ? '<strong style="color:var(--primary);">0 (AWAL)</strong>' : '<strong style="color:var(--warn);">' . $iterNum . '</strong>' ?>
                                    — K1: <?= $assign['counts']['K1'] ?> | K2: <?= $assign['counts']['K2'] ?> | K3: <?= $assign['counts']['K3'] ?>
                                </span>
                                <span style="font-size: 12px; color: var(--text3);">▼</span>
                            </summary>
                            <div style="padding: 16px; background: white; border-top: 1px solid var(--border);">
                                <div class="grid-3" style="margin-bottom: 16px;">
                                    <div style="padding: 12px; background: var(--accent-pale); border-radius: 8px; border-left: 4px solid var(--accent);">
                                        <div style="font-weight: 600; color: var(--accent); font-size: 12px;">K1 — TINGGI</div>
                                        <div style="font-size: 14px; font-weight: 700; color: var(--accent); margin-top: 4px;"><?= $assign['counts']['K1'] ?> Perawat</div>
                                        <div style="font-size: 11px; color: var(--text3); margin-top: 6px;">
                                            <?php if (count($assign['members']['K1']) > 0): ?>
                                                <?= implode(', ', $assign['members']['K1']) ?>
                                            <?php else: ?>
                                                <em>(kosong)</em>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="padding: 12px; background: var(--primary-pale); border-radius: 8px; border-left: 4px solid var(--primary);">
                                        <div style="font-weight: 600; color: var(--primary); font-size: 12px;">K2 — SEDANG</div>
                                        <div style="font-size: 14px; font-weight: 700; color: var(--primary); margin-top: 4px;"><?= $assign['counts']['K2'] ?> Perawat</div>
                                        <div style="font-size: 11px; color: var(--text3); margin-top: 6px;">
                                            <?php if (count($assign['members']['K2']) > 0): ?>
                                                <?= implode(', ', $assign['members']['K2']) ?>
                                            <?php else: ?>
                                                <em>(kosong)</em>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="padding: 12px; background: var(--danger-pale); border-radius: 8px; border-left: 4px solid var(--danger);">
                                        <div style="font-weight: 600; color: var(--danger); font-size: 12px;">K3 — RENDAH</div>
                                        <div style="font-size: 14px; font-weight: 700; color: var(--danger); margin-top: 4px;"><?= $assign['counts']['K3'] ?> Perawat</div>
                                        <div style="font-size: 11px; color: var(--text3); margin-top: 6px;">
                                            <?php if (count($assign['members']['K3']) > 0): ?>
                                                <?= implode(', ', $assign['members']['K3']) ?>
                                            <?php else: ?>
                                                <em>(kosong)</em>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-top: 16px;">
                                    <div style="font-weight: 700; font-size: 13px; margin-bottom: 8px; color: var(--text);">Perhitungan Euclidean Distance</div>
                                    <div class="table-wrap">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Nama</th>
                                                    <th>D² → K1</th>
                                                    <th>D² → K2</th>
                                                    <th>D² → K3</th>
                                                    <th>D → K1</th>
                                                    <th>D → K2</th>
                                                    <th>D → K3</th>
                                                    <th>Hasil</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($perawat as $i => $p): ?>
                                                    <?php $distRow = $assign['distances'][$i] ?? []; ?>
                                                    <tr>
                                                        <td><?= $p['no'] ?></td>
                                                        <td><b><?= $p['nama'] ?></b></td>
                                                        <td><?= number_format($distRow['K1']['d2'] ?? 0, 4) ?></td>
                                                        <td><?= number_format($distRow['K2']['d2'] ?? 0, 4) ?></td>
                                                        <td><?= number_format($distRow['K3']['d2'] ?? 0, 4) ?></td>
                                                        <td><?= number_format($distRow['K1']['d'] ?? 0, 4) ?></td>
                                                        <td><?= number_format($distRow['K2']['d'] ?? 0, 4) ?></td>
                                                        <td><?= number_format($distRow['K3']['d'] ?? 0, 4) ?></td>
                                                        <td><span class="klaster-badge <?= klasterClass($assign['labels'][$i]) ?>"><?= $assign['labels'][$i] ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div style="margin-top: 16px;">
                                    <div style="font-weight: 700; font-size: 13px; margin-bottom: 8px; color: var(--text);">Pembaruan Centroid</div>
                                    <div class="table-wrap">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Klaster</th>
                                                    <th>Jumlah Anggota</th>
                                                    <th>Centroid Lama</th>
                                                    <th>Σ Nilai Anggota</th>
                                                    <th>Centroid Baru</th>
                                                    <th>Perpindahan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (['K1', 'K2', 'K3'] as $kName): ?>
                                                    <tr>
                                                        <td><b><?= $kName ?></b></td>
                                                        <td style="text-align:center;"><?= $assign['rawCounts'][$kName] ?? $assign['counts'][$kName] ?? 0 ?></td>
                                                        <td style="font-family:'JetBrains Mono',monospace;font-size:11px;"><?= centStr($assign['oldCentroids'][$kName] ?? [], $krShort) ?></td>
                                                        <td style="font-family:'JetBrains Mono',monospace;font-size:11px;">
                                                            <?php
                                                            $sumVals = $assign['sums'][$kName] ?? [];
                                                            $sumParts = [];
                                                            foreach ($sumVals as $j => $v) $sumParts[] = $krShort[$j] . ':' . number_format($v, 2);
                                                            echo implode(' ', $sumParts);
                                                            ?>
                                                        </td>
                                                        <td style="font-family:'JetBrains Mono',monospace;font-size:11px;<?= $kName === 'K1' ? 'color:#0e6655;' : ($kName === 'K2' ? 'color:#1a5276;' : 'color:#922b21;') ?>"><?= centStr($assign['newCentroids'][$kName] ?? [], $krShort) ?></td>
                                                        <td><?= number_format($assign['centroidMoves'][$kName] ?? 0, 4) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div style="font-size: 11px; color: var(--text3); padding-top: 8px; border-top: 1px solid var(--border);">
                                    <strong>Rincian penugasan:</strong>
                                    <div style="margin-top: 8px; font-family: 'JetBrains Mono', monospace; font-size: 10px; background: var(--surface); padding: 8px; border-radius: 6px; max-height: 120px; overflow-y: auto;">
                                        <?php foreach ($perawat as $i => $p): ?>
                                            <div style="padding: 2px 0;">
                                                <span style="color: var(--text3);">No <?= $p['no'] ?></span>
                                                <span style="font-weight: 600;"><?= $p['nama'] ?></span>
                                                <span style="color: var(--accent);">→ <?= $assign['labels'][$i] ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php elseif ($activeTab === 'ahp'): ?>
            <!-- ==================== AHP ==================== -->
            <div class="section-title">Tahap 2 — AHP (Analytical Hierarchy Process)</div>
            <div class="section-sub">Pairwise Matrix = Input Pakar | Normalisasi & CR = Formula | CR &lt; 0.1 = Konsisten</div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-title"><span class="dot dot-purple"></span>Bobot Prioritas Kriteria</div>
                    <div class="chart-container" style="height:260px;"><canvas id="chartAHP"></canvas></div>
                </div>
                <div class="card">
                    <div class="card-title"><span class="dot dot-blue"></span>Uji Konsistensi (CR)</div>
                    <div class="cr-box">
                        <div class="cr-item">
                            <div class="cr-val">7.284</div>
                            <div class="cr-label">λmax</div>
                        </div>
                        <div class="cr-item">
                            <div class="cr-val">0.047</div>
                            <div class="cr-label">CI</div>
                        </div>
                        <div class="cr-item">
                            <div class="cr-val">1.320</div>
                            <div class="cr-label">RI (n=7)</div>
                        </div>
                        <div class="cr-item">
                            <div class="cr-val">0.036</div>
                            <div class="cr-label">CR</div>
                        </div>
                        <div class="cr-status">✓ KONSISTEN (CR &lt; 0.1)</div>
                    </div>
                    <div style="margin-top:16px;">
                        <div class="card-title" style="margin-bottom:8px;"><span class="dot dot-green"></span>Bobot Tiap Kriteria</div>
                        <?php foreach ($weights as $i => $w): ?>
                            <div class="score-bar-wrap" style="margin-bottom:8px;">
                                <span style="min-width:36px;font-size:12px;color:var(--text3);"><?= $kriteriaShort[$i] ?></span>
                                <div class="score-bar" style="flex:1;">
                                    <div class="score-bar-fill <?= $i === 0 ? 'green' : 'blue' ?>" style="width:<?= number_format($w * 100, 1) ?>%;"></div>
                                </div>
                                <span class="score-val"><?= number_format($w * 100, 1) ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><span class="dot dot-amber"></span>Matriks Perbandingan Berpasangan (Skala Saaty 1–9)</div>
                <div class="table-wrap">
                    <table class="ahp-table">
                        <thead>
                            <tr>
                                <th>Kriteria</th><?php foreach ($kriteria as $k): ?><th><?= $k ?></th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pairwise as $i => $row): ?>
                                <tr>
                                    <td><?= $kriteria[$i] ?></td>
                                    <?php foreach ($row as $j => $v): ?>
                                        <td style="<?= $i === $j ? 'background:#f0f4f8;font-weight:700;' : '' ?>"><?= $v == round($v) ? (int)$v : number_format($v, 6) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background:#fef9e7;font-weight:600;">
                                <td>JUMLAH KOLOM</td>
                                <td>2.130</td>
                                <td>5.210</td>
                                <td>10.867</td>
                                <td>20.333</td>
                                <td>10.867</td>
                                <td>20.333</td>
                                <td>33.000</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="formula-box" style="margin-top:14px;">
                    <span class="comment">// Normalisasi: rij = xij / Σ(kolom-j)</span><br>
                    <span class="comment">// Bobot: Wi = AVERAGE(rij per baris)</span><br>
                    <span class="comment">// CR = CI / RI ; CI = (λmax − n) / (n−1)</span>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><span class="dot dot-red"></span>Matriks Normalisasi &amp; Bobot Prioritas</div>
                <div class="table-wrap">
                    <table class="ahp-table">
                        <thead>
                            <tr>
                                <th>Kriteria</th><?php foreach ($kriteria as $k): ?><th><?= $k ?></th><?php endforeach; ?><th>Bobot (W)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($normMatrix as $i => $row): ?>
                                <tr>
                                    <td><?= $kriteria[$i] ?></td>
                                    <?php foreach ($row as $v): ?><td><?= number_format($v, 6) ?></td><?php endforeach; ?>
                                    <td style="font-weight:700;color:var(--primary);"><?= number_format($weights[$i], 6) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($activeTab === 'wp'): ?>
            <!-- ==================== WP ==================== -->
            <div class="section-title">Tahap 3 — Weighted Product (WP)</div>
            <div class="section-sub">Formula: Si = ∏(Xij ^ Wj) — Bobot referensi dari AHP</div>

            <div class="card">
                <div class="card-title"><span class="dot dot-blue"></span>WP Score (Si) Seluruh Perawat</div>
                <div class="chart-container" style="height:260px;"><canvas id="chartWP"></canvas></div>
            </div>

            <div class="card">
                <div class="card-title"><span class="dot dot-green"></span>Perhitungan WP Score</div>
                <div class="formula-box">
                    Si = <span class="fn">∏</span>(Xij <span class="kw">^</span> Wj) &nbsp;<span class="comment">// i = perawat, j = kriteria</span><br>
                    <span class="comment">// C1^0.4181 × C2^0.2292 × C3^0.1126 × C4^0.0509 × C5^0.1126 × C6^0.0509 × C7^0.0256</span>
                </div>
                <div class="table-wrap" style="margin-top:14px;">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>C1</th>
                                <th>C2</th>
                                <th>C3</th>
                                <th>C4</th>
                                <th>C5</th>
                                <th>C6</th>
                                <th>C7</th>
                                <th>WP Score (Si)</th>
                                <th>Rank WP</th>
                                <th>Klaster</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perawatSortedWP as $p): ?>
                                <tr<?= $p['rankWP'] <= 3 ? ' class="best-row"' : '' ?>>
                                    <td><?= $p['no'] ?></td>
                                    <td><b><?= $p['nama'] ?></b></td>
                                    <?php foreach ($p['c'] as $v): ?><td style="text-align:center;"><?= $v ?></td><?php endforeach; ?>
                                    <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--primary);"><?= number_format($p['wp'], 6) ?></td>
                                    <td><span class="rank-num <?= rankClass($p['rankWP']) ?>"><?= $p['rankWP'] ?></span></td>
                                    <td><span class="klaster-badge <?= klasterClass($p['klaster']) ?>"><?= $p['klaster'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($activeTab === 'topsis'): ?>
            <!-- ==================== TOPSIS ==================== -->
            <div class="section-title">Tahap 4 — TOPSIS</div>
            <div class="section-sub">Validasi pemeringkatan: Ci = D− / (D+ + D−) | Konsistensi dengan WP ≥ 75%</div>

            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-value" style="color:#0e6655;">0.954</div>
                    <div class="stat-label">Ci Tertinggi</div>
                    <div class="stat-sub">Citra Dewi (Rank 1)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color:#922b21;">0.090</div>
                    <div class="stat-label">Ci Terendah</div>
                    <div class="stat-sub">Fitri Handayani (Rank 10)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">≥75%</div>
                    <div class="stat-label">Konsistensi WP↔TOPSIS</div>
                    <div class="stat-sub">Rank identik untuk semua</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">VALID</div>
                    <div class="stat-label">Validasi Model</div>
                    <div class="stat-sub">WP &amp; TOPSIS konvergen</div>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><span class="dot dot-blue"></span>Hasil TOPSIS — Normalisasi, Jarak Ideal, &amp; Ci</div>
                <div class="formula-box">
                    rij = xij / <span class="fn">√</span>(<span class="fn">Σ</span>xij²) &nbsp;<span class="comment">// Normalisasi</span><br>
                    vij = rij × Wj &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="comment">// Pembobotan</span><br>
                    D+ = <span class="fn">√</span>(<span class="fn">Σ</span>(vij − A+j)²) &nbsp;&nbsp;<span class="comment">// Jarak ke ideal positif</span><br>
                    D− = <span class="fn">√</span>(<span class="fn">Σ</span>(vij − A−j)²) &nbsp;&nbsp;<span class="comment">// Jarak ke ideal negatif</span><br>
                    Ci = D− / (D+ + D−) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="comment">// Kedekatan relatif [0,1]</span>
                </div>
                <div class="table-wrap" style="margin-top:14px;">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>D+ (Pos)</th>
                                <th>D− (Neg)</th>
                                <th>Ci</th>
                                <th>Rank TOPSIS</th>
                                <th>Rank WP</th>
                                <th>Konsisten?</th>
                                <th>Klaster</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perawatSortedT as $p):
                                $td = $topsisData[$p['no'] - 1];
                                $consistent = $p['rankWP'] === $p['rankT'];
                            ?>
                                <tr<?= $p['rankT'] <= 3 ? ' class="best-row"' : '' ?>>
                                    <td><?= $p['no'] ?></td>
                                    <td><b><?= $p['nama'] ?></b></td>
                                    <td style="font-family:'JetBrains Mono',monospace;"><?= number_format($td['dPos'], 6) ?></td>
                                    <td style="font-family:'JetBrains Mono',monospace;"><?= number_format($td['dNeg'], 6) ?></td>
                                    <td class="topsis-ci <?= ciClass($p['ci']) ?>"><?= number_format($p['ci'], 6) ?></td>
                                    <td><span class="rank-num <?= rankClass($p['rankT']) ?>"><?= $p['rankT'] ?></span></td>
                                    <td><span class="rank-num <?= rankClass($p['rankWP']) ?>"><?= $p['rankWP'] ?></span></td>
                                    <td style="color:<?= $consistent ? '#0e6655' : '#922b21' ?>;font-weight:600;"><?= $consistent ? '✓ YA' : '✗ TIDAK' ?></td>
                                    <td><span class="klaster-badge <?= klasterClass($p['klaster']) ?>"><?= $p['klaster'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><span class="dot dot-amber"></span>Perbandingan Ci TOPSIS per Perawat</div>
                <div class="chart-container" style="height:280px;"><canvas id="chartTopsis"></canvas></div>
            </div>

        <?php elseif ($activeTab === 'model'): ?>
            <!-- ==================== MODEL ==================== -->
            <div class="section-title">Tahap 5 — Hasil Final &amp; Rekomendasi Manajerial</div>
            <div class="section-sub">Rekapitulasi seluruh metode — K-Means + AHP + WP + TOPSIS</div>

            <div class="card">
                <div class="card-title"><span class="dot dot-green"></span>Pemeringkatan Final Seluruh Perawat</div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Rank Final</th>
                                <th>Nama Perawat</th>
                                <th>WP Score</th>
                                <th>Rank WP</th>
                                <th>TOPSIS Ci</th>
                                <th>Rank TOPSIS</th>
                                <th>Avg Rank</th>
                                <th>Klaster</th>
                                <th>Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perawatSortedT as $p): ?>
                                <tr<?= $p['rankT'] <= 3 ? ' class="best-row"' : '' ?>>
                                    <td><span class="rank-num <?= rankClass($p['rankT']) ?>"><?= $p['rankT'] ?></span></td>
                                    <td><b><?= $p['nama'] ?></b></td>
                                    <td style="font-family:'JetBrains Mono',monospace;font-weight:600;"><?= number_format($p['wp'], 6) ?></td>
                                    <td><span class="rank-num <?= rankClass($p['rankWP']) ?>"><?= $p['rankWP'] ?></span></td>
                                    <td class="topsis-ci <?= ciClass($p['ci']) ?>"><?= number_format($p['ci'], 6) ?></td>
                                    <td><span class="rank-num <?= rankClass($p['rankT']) ?>"><?= $p['rankT'] ?></span></td>
                                    <td style="font-family:'JetBrains Mono',monospace;"><?= number_format(($p['rankWP'] + $p['rankT']) / 2, 3) ?></td>
                                    <td><span class="klaster-badge <?= klasterClass($p['klaster']) ?>"><?= $p['klaster'] ?></span></td>
                                    <td><span class="rekom-badge <?= rekomClass($p) ?>"><?= rekom($p) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-title"><span class="dot dot-purple"></span>Ringkasan per Klaster</div>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div style="background:#d1f2eb;border-radius:10px;padding:16px;border-left:4px solid #1abc9c;">
                            <div style="font-weight:700;color:#0e6655;font-size:14px;">K1 — Kinerja TINGGI (4 perawat)</div>
                            <div style="font-size:13px;color:#117a65;margin-top:6px;">Avg WP: <b>8.435</b> | Avg TOPSIS Ci: <b>0.862</b></div>
                            <div style="font-size:12px;color:#0e6655;margin-top:8px;font-style:italic;">→ Prioritas promosi, berikan peran mentor dan pengembangan karir</div>
                        </div>
                        <div style="background:#d6eaf8;border-radius:10px;padding:16px;border-left:4px solid #2e86c1;">
                            <div style="font-weight:700;color:#1a5276;font-size:14px;">K2 — Kinerja SEDANG (3 perawat)</div>
                            <div style="font-size:13px;color:#1a5276;margin-top:6px;">Avg WP: <b>6.401</b> | Avg TOPSIS Ci: <b>0.474</b></div>
                            <div style="font-size:12px;color:#1a5276;margin-top:8px;font-style:italic;">→ Pelatihan targeted, monitoring berkala, reward berbasis capaian</div>
                        </div>
                        <div style="background:#fdedec;border-radius:10px;padding:16px;border-left:4px solid #e74c3c;">
                            <div style="font-weight:700;color:#922b21;font-size:14px;">K3 — Kinerja RENDAH (3 perawat)</div>
                            <div style="font-size:13px;color:#922b21;margin-top:6px;">Avg WP: <b>4.754</b> | Avg TOPSIS Ci: <b>0.172</b></div>
                            <div style="font-size:12px;color:#922b21;margin-top:8px;font-style:italic;">→ Program pembinaan intensif, mentoring, review ulang beban kerja</div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-title"><span class="dot dot-amber"></span>Distribusi Rekomendasi</div>
                    <div class="chart-container" style="height:220px;"><canvas id="chartRekom"></canvas></div>
                    <div style="margin-top:14px;">
                        <div class="legend">
                            <span class="legend-item"><span class="legend-dot" style="background:#1abc9c"></span>Promosi (4)</span>
                            <span class="legend-item"><span class="legend-dot" style="background:#2e86c1"></span>Pertahankan (3)</span>
                            <span class="legend-item"><span class="legend-dot" style="background:#e74c3c"></span>Pembinaan (3)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Perawat Cards -->
            <div class="card">
                <div class="card-title"><span class="dot dot-blue"></span>Profil Detail Setiap Perawat</div>
                <div class="perawat-grid">
                    <?php
                    $criColors = ['#2e86c1', '#8e44ad', '#117a65', '#e67e22', '#e74c3c', '#16a085', '#f39c12'];
                    foreach ($perawatSortedT as $p):
                    ?>
                        <div class="perawat-card <?= cardClass($p['klaster']) ?>">
                            <div class="pc-header">
                                <div class="pc-avatar <?= avClass($p['klaster']) ?>"><?= initials($p['nama']) ?></div>
                                <div>
                                    <div class="pc-name"><?= $p['nama'] ?></div>
                                    <div class="pc-rank">Rank #<?= $p['rankT'] ?> | <span class="klaster-badge <?= klasterClass($p['klaster']) ?>" style="font-size:10px;padding:2px 6px;"><?= $p['klaster'] ?></span></div>
                                </div>
                            </div>
                            <div class="pc-scores">
                                <div class="pc-score-item">
                                    <div class="pc-score-label">WP Score</div>
                                    <div class="pc-score-val"><?= number_format($p['wp'], 3) ?></div>
                                </div>
                                <div class="pc-score-item">
                                    <div class="pc-score-label">TOPSIS Ci</div>
                                    <div class="pc-score-val"><?= number_format($p['ci'], 3) ?></div>
                                </div>
                            </div>
                            <div class="pc-criteria">
                                <?php foreach ($p['c'] as $j => $v): ?>
                                    <div class="pc-crit-row">
                                        <span class="pc-crit-label"><?= $kriteriaShort[$j] ?></span>
                                        <div class="pc-crit-bar">
                                            <div class="pc-crit-fill" style="width:<?= $v * 10 ?>%;background:<?= $criColors[$j] ?>;"></div>
                                        </div>
                                        <span class="pc-crit-num"><?= $v ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="margin-top:10px;"><span class="rekom-badge <?= rekomClass($p) ?>" style="width:100%;text-align:center;display:block;"><?= rekom($p) ?></span></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php elseif ($activeTab === 'simulator'): ?>
            <!-- ==================== SIMULATOR ==================== -->
            <div class="section-title">Simulator Evaluasi Perawat Baru</div>
            <div class="section-sub">Masukkan nilai kinerja perawat baru (skala 1–10). Kalkulasi K-Means, WP, dan TOPSIS Ci diproses di server (PHP).</div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-title"><span class="dot dot-blue"></span>Input Data Perawat</div>
                    <form method="POST" action="?tab=simulator">
                        <div class="form-group" style="margin-bottom:14px;">
                            <label>Nama Perawat</label>
                            <input type="text" name="sim_nama" value="<?= htmlspecialchars($_POST['sim_nama'] ?? 'Nama Perawat Baru') ?>">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <?php foreach ($kriteria as $i => $k): ?>
                                <div class="form-group">
                                    <label><?= $k ?> (1-10)</label>
                                    <input type="number" name="s<?= $i + 1 ?>" min="1" max="10" value="<?= htmlspecialchars($_POST['s' . ($i + 1)] ?? '7') ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
                            <button type="submit" name="sim_submit" class="btn btn-primary">⚡ Hitung Evaluasi</button>
                            <a href="?tab=simulator" class="btn btn-success">↺ Reset</a>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-title"><span class="dot dot-green"></span>Hasil Evaluasi</div>
                    <?php if ($simResult):
                        $sk = $simResult['klaster'];
                        $klColor = $sk === 'K1' ? '#0e6655' : ($sk === 'K2' ? '#1a5276' : '#922b21');
                        $klBg    = $sk === 'K1' ? '#d1f2eb' : ($sk === 'K2' ? '#d6eaf8' : '#fdedec');
                        $klBorder = $sk === 'K1' ? '#1abc9c' : ($sk === 'K2' ? '#2e86c1' : '#e74c3c');
                        $klLabel = $sk === 'K1' ? 'TINGGI' : ($sk === 'K2' ? 'SEDANG' : 'RENDAH');
                        $rekomText = $sk === 'K1' ? 'Promosi / Pengembangan Lanjut' : ($sk === 'K2' ? 'Pertahankan & Tingkatkan' : 'Perlu Pembinaan Intensif');
                    ?>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                            <div class="pc-score-item">
                                <div class="pc-score-label">Klaster</div>
                                <div style="font-size:22px;font-weight:700;color:<?= $klColor ?>;margin-top:4px;"><?= $sk ?></div>
                            </div>
                            <div class="pc-score-item">
                                <div class="pc-score-label">WP Score (Si)</div>
                                <div class="pc-score-val" style="font-size:22px;"><?= number_format($simResult['wp'], 4) ?></div>
                            </div>
                            <div class="pc-score-item">
                                <div class="pc-score-label">TOPSIS Ci (Est.)</div>
                                <div class="pc-score-val" style="font-size:22px;"><?= number_format($simResult['ci'], 4) ?></div>
                            </div>
                            <div class="pc-score-item">
                                <div class="pc-score-label">Label Klaster</div>
                                <div style="margin-top:6px;"><span class="klaster-badge <?= klasterClass($sk) ?>" style="font-size:13px;padding:6px 14px;"><?= $klLabel ?></span></div>
                            </div>
                        </div>
                        <div style="background:<?= $klBg ?>;border-radius:10px;padding:14px;border-left:4px solid <?= $klBorder ?>;">
                            <div style="font-size:12px;font-weight:700;color:<?= $klColor ?>;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Rekomendasi</div>
                            <div style="font-size:14px;font-weight:600;color:<?= $klColor ?>;"><?= $rekomText ?></div>
                        </div>
                        <div style="margin-top:14px;">
                            <?php foreach ($simResult['vals'] as $j => $v): ?>
                                <div class="score-bar-wrap" style="margin-bottom:6px;">
                                    <span style="min-width:32px;font-size:11px;color:var(--text3);"><?= $kriteriaShort[$j] ?></span>
                                    <div class="score-bar">
                                        <div class="score-bar-fill green" style="width:<?= $v * 10 ?>%;"></div>
                                    </div>
                                    <span class="score-val"><?= $v ?>/10</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="color:var(--text3);font-size:13px;text-align:center;padding:40px 20px;">Masukkan data dan klik "Hitung Evaluasi" untuk melihat hasil</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($simResult): ?>
                <div class="card">
                    <div class="card-title"><span class="dot dot-amber"></span>Visualisasi Profil vs Klaster</div>
                    <div class="chart-container" style="height:300px;"><canvas id="chartSim"></canvas></div>
                </div>
            <?php endif; ?>

            <!-- Sensitivity -->
            <div class="card">
                <div class="card-title"><span class="dot dot-purple"></span>Analisis Sensitivitas Bobot (Custom Weight — JavaScript)</div>
                <div class="section-sub" style="margin-bottom:14px;">Geser bobot kriteria untuk melihat pengaruh terhadap peringkat secara real-time</div>
                <div id="sensitivitySliders"></div>
                <div id="wtotalBox" class="weight-total wt-ok">Total Bobot: 1.000 ✓</div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Nama Perawat</th>
                                <th>WP Score (Custom)</th>
                                <th>Perubahan Rank</th>
                            </tr>
                        </thead>
                        <tbody id="tblSensitivity"></tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <script>
        // PHP data injected as JS variables
        const perawat = <?= $perawatJson ?>;
        const weights = <?= $weightsJson ?>;
        const centroid = <?= $centroidJson ?>;
        const kriteria = <?= $kriteriaJson ?>;
        const kriteriaShort = <?= $kriteriaShortJson ?>;
        const topsisData = <?= $topsisDataJson ?>;
        const activeTab = '<?= $activeTab ?>';

        <?php if ($simResult): ?>
            const simVals = <?= json_encode($simResult['vals']) ?>;
            const simNama = <?= json_encode($simResult['nama']) ?>;
        <?php endif; ?>

        // ---- HELPERS ----
        const klasterColor = k => k === 'K1' ? '#1abc9c' : k === 'K2' ? '#2e86c1' : '#e74c3c';
        const rankClass = r => r === 1 ? 'rank-1' : r === 2 ? 'rank-2' : r === 3 ? 'rank-3' : 'rank-other';
        const fmt = (n, d = 3) => Number(n).toFixed(d);

        // ---- CHARTS ----
        function initCharts() {
            if (activeTab === 'overview') {
                new Chart(document.getElementById('chartKlaster'), {
                    type: 'doughnut',
                    data: {
                        labels: ['K1 Tinggi', 'K2 Sedang', 'K3 Rendah'],
                        datasets: [{
                            data: [4, 3, 3],
                            backgroundColor: ['#1abc9c', '#2e86c1', '#e74c3c'],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
                const sortedT = [...perawat].sort((a, b) => a.rankT - b.rankT);
                new Chart(document.getElementById('chartComparison'), {
                    type: 'bar',
                    data: {
                        labels: sortedT.map(p => p.nama.split(' ')[0]),
                        datasets: [{
                                label: 'WP Score',
                                data: sortedT.map(p => +p.wp.toFixed(3)),
                                backgroundColor: '#2e86c1',
                                borderRadius: 4
                            },
                            {
                                label: 'TOPSIS Ci ×10',
                                data: sortedT.map(p => +(p.ci * 10).toFixed(3)),
                                backgroundColor: '#1abc9c',
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        }
                    }
                });
            }
            if (activeTab === 'kmeans') {
                new Chart(document.getElementById('chartRadar'), {
                    type: 'radar',
                    data: {
                        labels: kriteriaShort,
                        datasets: [{
                                label: 'K1 Tinggi',
                                data: centroid.K1,
                                borderColor: '#1abc9c',
                                backgroundColor: 'rgba(26,188,156,0.12)',
                                pointBackgroundColor: '#1abc9c',
                                borderWidth: 2
                            },
                            {
                                label: 'K2 Sedang',
                                data: centroid.K2,
                                borderColor: '#2e86c1',
                                backgroundColor: 'rgba(46,134,193,0.12)',
                                pointBackgroundColor: '#2e86c1',
                                borderWidth: 2
                            },
                            {
                                label: 'K3 Rendah',
                                data: centroid.K3,
                                borderColor: '#e74c3c',
                                backgroundColor: 'rgba(231,76,60,0.12)',
                                pointBackgroundColor: '#e74c3c',
                                borderWidth: 2
                            },
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                min: 0,
                                max: 10
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            if (activeTab === 'ahp') {
                new Chart(document.getElementById('chartAHP'), {
                    type: 'bar',
                    data: {
                        labels: kriteria.map(k => k.split(' ').slice(1).join(' ')),
                        datasets: [{
                            label: 'Bobot',
                            data: weights.map(w => +(w * 100).toFixed(2)),
                            backgroundColor: ['#2e86c1', '#8e44ad', '#1abc9c', '#e67e22', '#e74c3c', '#16a085', '#f39c12'],
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        scales: {
                            x: {
                                beginAtZero: true,
                                max: 50,
                                ticks: {
                                    callback: v => v + '%'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
            if (activeTab === 'wp') {
                const sortedWP = [...perawat].sort((a, b) => a.rankWP - b.rankWP);
                new Chart(document.getElementById('chartWP'), {
                    type: 'bar',
                    data: {
                        labels: sortedWP.map(p => p.nama.split(' ')[0]),
                        datasets: [{
                            label: 'WP Score',
                            data: sortedWP.map(p => +p.wp.toFixed(4)),
                            backgroundColor: sortedWP.map(p => klasterColor(p.klaster)),
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                min: 3,
                                max: 10
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
            if (activeTab === 'topsis') {
                const sortedT = [...perawat].sort((a, b) => a.rankT - b.rankT);
                new Chart(document.getElementById('chartTopsis'), {
                    type: 'bar',
                    data: {
                        labels: sortedT.map(p => p.nama.split(' ')[0]),
                        datasets: [{
                            label: 'TOPSIS Ci',
                            data: sortedT.map(p => +p.ci.toFixed(6)),
                            backgroundColor: sortedT.map(p => klasterColor(p.klaster)),
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                min: 0,
                                max: 1
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
            if (activeTab === 'model') {
                new Chart(document.getElementById('chartRekom'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Promosi/Pengembangan', 'Pertahankan & Tingkatkan', 'Pembinaan Intensif'],
                        datasets: [{
                            data: [4, 3, 3],
                            backgroundColor: ['#1abc9c', '#2e86c1', '#e74c3c'],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
            if (activeTab === 'simulator') {
                <?php if ($simResult): ?>
                    new Chart(document.getElementById('chartSim'), {
                        type: 'radar',
                        data: {
                            labels: kriteriaShort,
                            datasets: [{
                                    label: simNama,
                                    data: simVals,
                                    borderColor: '#f39c12',
                                    backgroundColor: 'rgba(243,156,18,0.15)',
                                    pointBackgroundColor: '#f39c12',
                                    borderWidth: 2
                                },
                                {
                                    label: 'K1 Tinggi',
                                    data: centroid.K1,
                                    borderColor: '#1abc9c',
                                    backgroundColor: 'rgba(26,188,156,0.05)',
                                    borderDash: [4, 4],
                                    borderWidth: 1.5,
                                    pointRadius: 0
                                },
                                {
                                    label: 'K2 Sedang',
                                    data: centroid.K2,
                                    borderColor: '#2e86c1',
                                    backgroundColor: 'rgba(46,134,193,0.05)',
                                    borderDash: [4, 4],
                                    borderWidth: 1.5,
                                    pointRadius: 0
                                },
                                {
                                    label: 'K3 Rendah',
                                    data: centroid.K3,
                                    borderColor: '#e74c3c',
                                    backgroundColor: 'rgba(231,76,60,0.05)',
                                    borderDash: [4, 4],
                                    borderWidth: 1.5,
                                    pointRadius: 0
                                },
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                r: {
                                    min: 0,
                                    max: 10
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                <?php endif; ?>
                initSensitivity();
            }
        }

        // ---- SENSITIVITY (client-side) ----
        let customWeights = [...weights];

        function calcWP(vals, w) {
            return vals.reduce((prod, v, i) => prod * Math.pow(v, w[i]), 1);
        }

        function initSensitivity() {
            const container = document.getElementById('sensitivitySliders');
            if (!container) return;
            kriteria.forEach((k, i) => {
                container.innerHTML += `<div class="weight-slider-row">
      <label>${k}</label>
      <input type="range" min="0" max="0.8" step="0.001" value="${weights[i].toFixed(3)}" id="ws${i}" oninput="updateSensitivity(${i})">
      <span class="w-val" id="wv${i}">${(weights[i]*100).toFixed(1)}%</span>
    </div>`;
            });
            renderSensitivity();
        }

        function updateSensitivity(idx) {
            customWeights[idx] = parseFloat(document.getElementById('ws' + idx).value);
            document.getElementById('wv' + idx).textContent = (customWeights[idx] * 100).toFixed(1) + '%';
            renderSensitivity();
        }

        function renderSensitivity() {
            const total = customWeights.reduce((a, b) => a + b, 0);
            const box = document.getElementById('wtotalBox');
            box.textContent = `Total Bobot: ${total.toFixed(3)} ${Math.abs(total-1)<0.05?'✓':'⚠ (Harus = 1.000)'}`;
            box.className = 'weight-total ' + (Math.abs(total - 1) < 0.05 ? 'wt-ok' : 'wt-warn');
            const scored = perawat.map(p => ({
                nama: p.nama,
                klaster: p.klaster,
                origRank: p.rankWP,
                wpCustom: calcWP(p.c, customWeights)
            })).sort((a, b) => b.wpCustom - a.wpCustom);
            const tb = document.getElementById('tblSensitivity');
            tb.innerHTML = '';
            scored.forEach((p, i) => {
                const rank = i + 1;
                const change = p.origRank - rank;
                const changeStr = change === 0 ? '—' : change > 0 ? `<span style="color:#0e6655;">▲ +${change}</span>` : `<span style="color:#922b21;">▼ ${change}</span>`;
                tb.innerHTML += `<tr><td><span class="rank-num ${rankClass(rank)}">${rank}</span></td><td><b>${p.nama}</b></td><td style="font-family:'JetBrains Mono',monospace;font-weight:600;">${fmt(p.wpCustom,4)}</td><td>${changeStr}</td></tr>`;
            });
        }

        window.addEventListener('DOMContentLoaded', initCharts);
    </script>
</body>

</html>