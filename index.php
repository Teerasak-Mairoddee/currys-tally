<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include __DIR__ . '/db_conn.php';  // gives you $conn (mysqli)

// Fetch and cache first name
if (empty($_SESSION['first_name'])) {
    $stmt = $conn->prepare(
        "SELECT first_name FROM staff WHERE staff_id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($fetchedFirstName);
    if ($stmt->fetch()) {
        $_SESSION['first_name'] = $fetchedFirstName;
    } else {
        $_SESSION['first_name'] = 'Team Member';
    }
    $stmt->close();
}
$firstName = $_SESSION['first_name'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- External stylesheet -->
  <link rel="stylesheet" href="style/css/style.css">
</head>
<body>

</head>
<body>
  <header><h1>Welcome, <?= htmlspecialchars($firstName,ENT_QUOTES) ?>!</h1></header>

  <main class="dashboard-grid">
    <div class="widget">
      <h2>Sim-Only Contracts</h2>
      <div class="value" id="totalSimOnly">–</div>
    </div>
    <div class="widget">
      <h2>Post-Pay Contracts</h2>
      <div class="value" id="totalPostPay">–</div>
    </div>
    <div class="widget">
      <h2>Handset-Only Purchases</h2>
      <div class="value" id="totalHandsetOnly">–</div>
    </div>
    <div class="widget">
      <h2>Insurance Contracts</h2>
      <div class="value" id="totalInsurance">–</div>
    </div>

    <div class="widget">
      <h2>Sales Timeline</h2>
      <canvas id="myChart"></canvas>
    </div>
  </main>

  <!-- … footer … -->

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="scripts/generate_data.js?v=1.0.4"></script>
  <script>
    fetch('./get_summary.php')
      .then(r => r.json())
      .then(data => {
        document.getElementById('totalSimOnly').textContent     = data['Sim-Only'];
        document.getElementById('totalPostPay').textContent     = data['Post-Pay'];
        document.getElementById('totalHandsetOnly').textContent = data['Handset-Only'];
        document.getElementById('totalInsurance').textContent   = data['Insurance'];
      })
      .catch(console.error);
  </script>
</body>
</html>
