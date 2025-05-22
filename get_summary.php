<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error'=>'Not logged in']);
    exit;
}
include __DIR__ . '/db_conn.php';

// count today's contracts per type
$sql = "
  SELECT sale_type, COUNT(*) AS total
    FROM sales
   WHERE DATE(sold_at) = CURDATE()
   GROUP BY sale_type
";
$res = $conn->query($sql);
if (!$res) {
    http_response_code(500);
    echo json_encode(['error'=>'Query failed']);
    exit;
}

// default all four to zero
$response = [
  'Sim-Only'     => 0,
  'Post-Pay'     => 0,
  'Handset-Only' => 0,
  'Insurance'    => 0
];

while ($row = $res->fetch_assoc()) {
    $response[$row['sale_type']] = (int)$row['total'];
}

echo json_encode($response);
