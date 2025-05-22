<?php
header('Content-Type: application/json');
session_start();

// 1) Include shared DB connection
include __DIR__ . '/db_conn.php';
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connect failed']);
    exit;
}

// 2) Grab and validate the date parameter (YYYY-MM-DD), defaulting to today
$date = $_GET['date'] ?? date('Y-m-d');
if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

// 3) Prepare the parameterized SQL (CTE) using the date placeholder
$sql = "
  WITH hourly AS (
    SELECT
      staff_id,
      HOUR(sold_at) AS hour_bucket,
      COUNT(*)      AS cnt
    FROM sales
    WHERE DATE(sold_at) = ?
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

// 4) Prepare, bind and execute
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('s', $date);
$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

// 5) Build raw counts map
$raw = [];
while ($row = $result->fetch_assoc()) {
    $sid = (int)$row['staff_id'];
    $raw[$sid][] = (int)$row['this_hour'];
}
$stmt->close();

// 6) Fetch staff names & colors
$staff_names  = [];
$staff_colors = [];
$res2 = $conn->query("SELECT staff_id, CONCAT(first_name,' ',last_name) AS name, line_color FROM staff");
while ($r = $res2->fetch_assoc()) {
    $sid = (int)$r['staff_id'];
    $staff_names[$sid]  = $r['name'];
    $staff_colors[$sid] = $r['line_color'];
}

// 7) Build Chart.js datasets
$labels   = ['09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00'];
$datasets = [];

foreach ($raw as $staff_id => $counts) {
    $cum = [];
    $sum = 0;
    foreach ($counts as $c) {
        $sum  += $c;
        $cum[] = $sum;
    }
    $color = $staff_colors[$staff_id] ?? '#007bff';
    $bg    = $color . '1A';  // 10% opacity

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

// 8) Output the full Chart.js config
echo json_encode([
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
]);
