<?php
header('Content-Type: application/json');

// 1) Include shared DB connection; provides $conn (mysqli)
include __DIR__ . '/db_conn.php';
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connect failed: ' . $conn->connect_error]);
    exit;
}

// 2) Aggregate sales per staff per hour for today (hours 9–20)
$sql = "
  WITH hourly AS (
    SELECT
      staff_id,
      HOUR(sold_at) AS hour_bucket,
      COUNT(*)      AS cnt
    FROM sales
    WHERE DATE(sold_at) = CURDATE()
    GROUP BY staff_id, hour_bucket
  ),
  all_hours AS (
    SELECT s.staff_id, h AS hour_bucket
    FROM staff s
    CROSS JOIN (
      SELECT 9 AS h UNION ALL SELECT 10 UNION ALL SELECT 11
      UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14
      UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17
      UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20
    ) AS hours
  )
  SELECT
    ah.staff_id,
    ah.hour_bucket,
    COALESCE(h.cnt, 0) AS this_hour
  FROM all_hours ah
  LEFT JOIN hourly h
    ON h.staff_id    = ah.staff_id
   AND h.hour_bucket = ah.hour_bucket
  ORDER BY ah.staff_id, ah.hour_bucket
";
$result = $conn->query($sql);
if (!$result) {
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

// 3) Build a map: staff_id => array of hourly counts
$raw = [];
while ($row = $result->fetch_assoc()) {
    $sid = (int)$row['staff_id'];
    $raw[$sid][] = (int)$row['this_hour'];
}

// 4) Fetch staff names **and** colors in one query
$staff_names  = [];
$staff_colors = [];
$nameRes = $conn->query(
    "SELECT staff_id,
            CONCAT(first_name, ' ', last_name) AS name,
            line_color
     FROM staff"
);
while ($nr = $nameRes->fetch_assoc()) {
    $sid = (int)$nr['staff_id'];
    $staff_names[$sid]  = $nr['name'];
    $staff_colors[$sid] = $nr['line_color'];
}

// 5) Prepare the Chart.js config
$labels   = ['09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00'];
$datasets = [];

foreach ($raw as $staff_id => $counts) {
    // compute cumulative totals
    $cum = [];
    $sum = 0;
    foreach ($counts as $c) {
        $sum  += $c;
        $cum[] = $sum;
    }

    // pick this staff member’s color (or fallback)
    $color = isset($staff_colors[$staff_id])
      ? $staff_colors[$staff_id]
      : '#007bff';
    // make a 10%-opacity background in hex (add "1A" suffix)
    $bg = $color . '1A';

    $datasets[] = [
        'label'           => $staff_names[$staff_id] ?? "Staff #{$staff_id}",
        'data'            => $cum,
        'borderWidth'     => 2,
        'borderColor'     => $color,
        'backgroundColor' => $bg,
        'fill'            => false,
        'tension'         => 0,
        'pointRadius'     => 0,
        'pointHoverRadius'=> 0
    ];
}

$config = [
    'type'    => 'line',
    'data'    => [
        'labels'   => $labels,
        'datasets' => $datasets
    ],
    'options' => [
        'responsive' => true,
        'scales'     => [
            'x' => [
                'title' => ['display' => true, 'text' => 'Time of Day'],
                'grid'  => ['display' => false]
            ],
            'y' => [
                'beginAtZero' => true,
                'grid'        => ['display' => true]
            ]
        ],
        'plugins' => [
            'legend'  => ['position' => 'top'],
            'tooltip' => ['mode' => 'index', 'intersect' => false]
        ]
    ]
];

// 6) Output JSON
echo json_encode($config);
