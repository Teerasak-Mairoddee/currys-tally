<?php
require __DIR__ . '/auth.php';
include __DIR__ . '/db_conn.php';

$date = $_GET['date'] ?? date('Y-m-d');
if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
  http_response_code(400);
  exit(json_encode(['error'=>'Invalid date']));
}

// Top 5 by Sim-Only+Post-Pay
$stmt = $conn->prepare("
  SELECT CONCAT(s.first_name,' ',s.last_name) AS name,
         SUM(CASE WHEN sale_type IN('Sim-Only','Post-Pay') THEN 1 ELSE 0 END) AS cnt
    FROM sales
    JOIN staff s USING(staff_id)
   WHERE DATE(sold_at)=?
   GROUP BY staff_id
   HAVING cnt>0
   ORDER BY cnt DESC
   LIMIT 5
");
$stmt->bind_param('s',$date);
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while($r=$res->fetch_assoc()) {
  $out[] = ['name'=>$r['name'],'cnt'=>(int)$r['cnt']];
}
echo json_encode($out);
