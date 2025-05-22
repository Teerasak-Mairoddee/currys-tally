<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['user_id'])) {
  http_response_code(401); echo json_encode(['error'=>'Not logged in']); exit;
}
include __DIR__ . '/db_conn.php';

// 1) Grab date from query (YYYY-MM-DD), default to today
$date = $_GET['date'] ?? date('Y-m-d');
if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
  http_response_code(400); echo json_encode(['error'=>'Invalid date']); exit;
}

// 2) Parameterized query
$stmt = $conn->prepare("
  SELECT sale_type, COUNT(*) AS total
    FROM sales
   WHERE DATE(sold_at) = ?
   GROUP BY sale_type
");
$stmt->bind_param('s', $date);
$stmt->execute();
$res = $stmt->get_result();

// 3) Build response
$response = ['Sim-Only'=>0,'Post-Pay'=>0,'Handset-Only'=>0,'Insurance'=>0];
while ($row = $res->fetch_assoc()) {
  $response[$row['sale_type']] = (int)$row['total'];
}

echo json_encode($response);
