<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function euclidean_sq(array $point, array $center): float
{
    $sum = 0.0;
    foreach ($point as $index => $value) {
        $diff = (float) $value - (float) ($center[$index] ?? 0);
        $sum += $diff * $diff;
    }

    return $sum;
}

function normalize_weights(array $criteria): array
{
    $weights = [];
    $total = 0.0;

    foreach ($criteria as $criterion) {
        $weight = max(0.0, (float) ($criterion['weight'] ?? 0));
        $weights[] = $weight;
        $total += $weight;
    }

    if ($total <= 0) {
        $count = max(1, count($weights));
        return array_fill(0, $count, 1 / $count);
    }

    return array_map(static fn(float $weight): float => $weight / $total, $weights);
}

function vector_average(array $vectors): array
{
    if ($vectors === []) {
        return [];
    }

    $dimension = count($vectors[0]);
    $sums = array_fill(0, $dimension, 0.0);

    foreach ($vectors as $vector) {
        foreach ($vector as $index => $value) {
            $sums[$index] += (float) $value;
        }
    }

    $count = max(1, count($vectors));
    return array_map(static fn(float $sum): float => $sum / $count, $sums);
}

function initial_centroids(array $vectors, int $k): array
{
    $ranked = $vectors;
    usort($ranked, static function (array $left, array $right): int {
        $leftAverage = array_sum($left) / max(1, count($left));
        $rightAverage = array_sum($right) / max(1, count($right));
        return $rightAverage <=> $leftAverage;
    });

    $count = count($ranked);
    if ($count === 0) {
        return [];
    }

    if ($k <= 1) {
        return [$ranked[0]];
    }

    if ($k === 2 || $count === 2) {
        return [$ranked[0], $ranked[$count - 1]];
    }

    $middleIndex = intdiv(max(0, $count - 1), 2);
    return [$ranked[0], $ranked[$middleIndex], $ranked[$count - 1]];
}

function run_kmeans(array $vectors, int $k, array $centroids, int $maxIterations = 50): array
{
    $total = count($vectors);
    if ($total === 0) {
        return [
            'centroids' => [],
            'labels' => [],
            'history' => [],
            'iterations' => 0,
        ];
    }

    $labels = array_fill(0, $total, -1);
    $history = [];

    for ($iteration = 1; $iteration <= $maxIterations; $iteration++) {
        $newLabels = [];
        $distanceLog = [];
        $assignmentLog = [];

        foreach ($vectors as $rowIndex => $point) {
            $bestCluster = 0;
            $bestDistance = PHP_FLOAT_MAX;
            $clusterDistances = [];

            foreach ($centroids as $clusterIndex => $centroid) {
                $distance = euclidean_sq($point, $centroid);
                $clusterDistances[$clusterIndex] = [
                    'd2' => $distance,
                    'd' => sqrt($distance),
                ];

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestCluster = $clusterIndex;
                }
            }

            $newLabels[$rowIndex] = $bestCluster;
            $distanceLog[$rowIndex] = $clusterDistances;
            $assignmentLog[$rowIndex] = $bestCluster;
        }

        $sums = array_fill(0, $k, array_fill(0, count($vectors[0]), 0.0));
        $counts = array_fill(0, $k, 0);

        foreach ($newLabels as $rowIndex => $clusterIndex) {
            $counts[$clusterIndex]++;
            foreach ($vectors[$rowIndex] as $dimension => $value) {
                $sums[$clusterIndex][$dimension] += (float) $value;
            }
        }

        $newCentroids = [];
        $centroidMoves = [];

        for ($clusterIndex = 0; $clusterIndex < $k; $clusterIndex++) {
            $newCentroids[$clusterIndex] = $counts[$clusterIndex] > 0
                ? array_map(static fn(float $sum): float => $sum / $counts[$clusterIndex], $sums[$clusterIndex])
                : $centroids[$clusterIndex];
            $centroidMoves[$clusterIndex] = sqrt(euclidean_sq($centroids[$clusterIndex], $newCentroids[$clusterIndex]));
        }

        $history[] = [
            'iter' => $iteration,
            'centroids' => array_map(static fn(array $centroid): array => array_map(static fn(float $value): float => round($value, 4), $centroid), $newCentroids),
            'labels' => $newLabels,
            'distances' => $distanceLog,
            'assignments' => $assignmentLog,
            'sums' => $sums,
            'counts' => $counts,
            'oldCentroids' => $centroids,
            'newCentroids' => $newCentroids,
            'centroidMoves' => $centroidMoves,
            'changed' => $newLabels !== $labels,
        ];

        if ($newLabels === $labels) {
            $centroids = $newCentroids;
            break;
        }

        $labels = $newLabels;
        $centroids = $newCentroids;
    }

    return [
        'centroids' => $centroids,
        'labels' => $labels,
        'history' => $history,
        'iterations' => count($history),
    ];
}

function silhouette_score(array $vectors, array $labels): float
{
    $total = count($vectors);
    if ($total < 2) {
        return 0.0;
    }

    $scores = [];

    foreach ($vectors as $index => $point) {
        $myCluster = $labels[$index] ?? 0;
        $sameClusterDistances = [];

        foreach ($vectors as $otherIndex => $candidate) {
            if ($index === $otherIndex || ($labels[$otherIndex] ?? null) !== $myCluster) {
                continue;
            }
            $sameClusterDistances[] = sqrt(euclidean_sq($point, $candidate));
        }

        $a = $sameClusterDistances === [] ? 0.0 : array_sum($sameClusterDistances) / count($sameClusterDistances);
        $clusterIds = array_values(array_unique($labels));
        $bCandidates = [];

        foreach ($clusterIds as $clusterId) {
            if ($clusterId === $myCluster) {
                continue;
            }

            $distances = [];
            foreach ($vectors as $otherIndex => $candidate) {
                if (($labels[$otherIndex] ?? null) === $clusterId) {
                    $distances[] = sqrt(euclidean_sq($point, $candidate));
                }
            }

            if ($distances !== []) {
                $bCandidates[] = array_sum($distances) / count($distances);
            }
        }

        $b = $bCandidates === [] ? 0.0 : min($bCandidates);
        $denominator = max($a, $b);
        $scores[] = $denominator > 0 ? ($b - $a) / $denominator : 0.0;
    }

    return round(array_sum($scores) / count($scores), 4);
}

function dashboard_data(array $criteria, array $perawat): array
{
    $criteria = array_values(array_filter($criteria, static fn(array $criterion): bool => (int) ($criterion['is_active'] ?? 1) === 1));
    usort($criteria, static fn(array $left, array $right): int => (int) ($left['sort_order'] ?? 0) <=> (int) ($right['sort_order'] ?? 0));

    $scoreFields = [];
    foreach ($criteria as $criterion) {
        $sortOrder = (int) ($criterion['sort_order'] ?? 0);
        $scoreFields[] = [
            'field' => 'c' . $sortOrder,
            'code' => (string) ($criterion['code'] ?? ('C' . $sortOrder)),
            'name' => (string) ($criterion['name'] ?? ''),
            'weight' => (float) ($criterion['weight'] ?? 0),
        ];
    }

    $weights = normalize_weights($scoreFields);
    $vectors = [];

    foreach ($perawat as $row) {
        $vector = [];
        foreach ($scoreFields as $field) {
            $vector[] = (float) ($row[$field['field']] ?? 0);
        }
        $vectors[] = $vector;
    }

    $k = min(3, max(1, count($vectors)));
    $initial = initial_centroids($vectors, $k);
    $kmeans = run_kmeans($vectors, $k, $initial);

    $clusterNames = [];
    if ($kmeans['centroids'] !== []) {
        $centroidMeans = [];
        foreach ($kmeans['centroids'] as $clusterIndex => $centroid) {
            $centroidMeans[$clusterIndex] = array_sum($centroid) / max(1, count($centroid));
        }
        arsort($centroidMeans);
        foreach (array_keys($centroidMeans) as $rank => $clusterIndex) {
            $clusterNames[$clusterIndex] = 'K' . ($rank + 1);
        }
    }

    $processed = [];
    foreach ($perawat as $index => $row) {
        $scores = [];
        foreach ($scoreFields as $field) {
            $scores[] = (float) ($row[$field['field']] ?? 0);
        }

        $clusterIndex = $kmeans['labels'][$index] ?? 0;
        $clusterName = $clusterNames[$clusterIndex] ?? 'K' . ($clusterIndex + 1);
        $processed[] = array_merge($row, [
            'scores' => $scores,
            'clusterIndex' => $clusterIndex,
            'cluster' => $clusterName,
        ]);
    }

    $scoreColumns = count($scoreFields);
    $sumSquares = array_fill(0, $scoreColumns, 0.0);
    $columnMax = array_fill(0, $scoreColumns, null);
    $columnMin = array_fill(0, $scoreColumns, null);

    foreach ($processed as $row) {
        foreach ($row['scores'] as $columnIndex => $value) {
            $sumSquares[$columnIndex] += $value ** 2;
            $columnMax[$columnIndex] = $columnMax[$columnIndex] === null ? $value : max($columnMax[$columnIndex], $value);
            $columnMin[$columnIndex] = $columnMin[$columnIndex] === null ? $value : min($columnMin[$columnIndex], $value);
        }
    }

    foreach ($processed as $rowIndex => &$row) {
        $weighted = [];
        foreach ($row['scores'] as $columnIndex => $value) {
            $denominator = sqrt(max(1e-9, $sumSquares[$columnIndex]));
            $weighted[] = ($value / $denominator) * $weights[$columnIndex];
        }

        $dPos = 0.0;
        $dNeg = 0.0;
        foreach ($weighted as $columnIndex => $value) {
            $positive = (($columnMax[$columnIndex] ?? 0) / sqrt(max(1e-9, $sumSquares[$columnIndex]))) * $weights[$columnIndex];
            $negative = (($columnMin[$columnIndex] ?? 0) / sqrt(max(1e-9, $sumSquares[$columnIndex]))) * $weights[$columnIndex];
            $dPos += ($value - $positive) ** 2;
            $dNeg += ($value - $negative) ** 2;
        }

        $ci = sqrt($dPos) + sqrt($dNeg) > 0 ? sqrt($dNeg) / (sqrt($dPos) + sqrt($dNeg)) : 0.0;
        $wp = 1.0;
        foreach ($row['scores'] as $columnIndex => $value) {
            $wp *= pow(max($value, 0.0001), $weights[$columnIndex]);
        }

        $row['wp'] = $wp;
        $row['ci'] = $ci;
    }
    unset($row);

    $clusterCounts = [];
    foreach ($processed as $row) {
        $clusterCounts[$row['cluster']] = ($clusterCounts[$row['cluster']] ?? 0) + 1;
    }

    $perawatByCi = $processed;
    usort($perawatByCi, static fn(array $left, array $right): int => $right['ci'] <=> $left['ci']);

    $perawatByWp = $processed;
    usort($perawatByWp, static fn(array $left, array $right): int => $right['wp'] <=> $left['wp']);

    foreach ($perawatByCi as $rank => &$row) {
        $row['rankT'] = $rank + 1;
    }
    unset($row);

    foreach ($perawatByWp as $rank => &$row) {
        $row['rankWP'] = $rank + 1;
    }
    unset($row);

    $silhouette = silhouette_score($vectors, $kmeans['labels']);

    return [
        'criteria' => $scoreFields,
        'weights' => $weights,
        'vectors' => $vectors,
        'kmeans' => $kmeans,
        'kmeansHistory' => $kmeans['history'],
        'clusterNames' => $clusterNames,
        'clusterCounts' => $clusterCounts,
        'perawat' => $processed,
        'perawatByCi' => $perawatByCi,
        'perawatByWp' => $perawatByWp,
        'silhouette' => $silhouette,
        'scoreFields' => $scoreFields,
    ];
}

function evaluate_candidate(array $scores, array $dashboard): array
{
    $weights = $dashboard['weights'];
    $centroids = $dashboard['kmeans']['centroids'] ?? [];

    $bestCluster = null;
    $bestDistance = PHP_FLOAT_MAX;
    foreach ($centroids as $clusterIndex => $centroid) {
        $distance = sqrt(euclidean_sq($scores, $centroid));
        if ($distance < $bestDistance) {
            $bestDistance = $distance;
            $bestCluster = $clusterIndex;
        }
    }

    $clusterName = 'K1';
    if ($bestCluster !== null && isset($dashboard['clusterNames'][$bestCluster])) {
        $clusterName = $dashboard['clusterNames'][$bestCluster];
    }

    $wp = 1.0;
    foreach ($scores as $index => $value) {
        $wp *= pow(max((float) $value, 0.0001), $weights[$index] ?? 0);
    }

    return [
        'cluster' => $clusterName,
        'wp' => $wp,
    ];
}
