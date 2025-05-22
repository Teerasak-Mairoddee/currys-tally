<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
include __DIR__ . '/db_conn.php';  // gives you $conn

$uid     = $_SESSION['user_id'];
$errors  = [];
$success = false;

// — Handle color‐picker form submission —
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['line_color'])) {
  $color = trim($_POST['line_color']);
  if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
    $errors[] = 'Pick a valid hex color.';
  } else {
    $upd = $conn->prepare(
      "UPDATE staff SET line_color = ? WHERE staff_id = ?"
    );
    $upd->bind_param('si', $color, $uid);
    if ($upd->execute()) {
      $success = true;
    } else {
      $errors[] = 'Update failed: ' . $upd->error;
    }
    $upd->close();
  }
}

// — Fetch current color for the picker —
$stmt = $conn->prepare(
  "SELECT line_color FROM staff WHERE staff_id = ? LIMIT 1"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$stmt->bind_result($currentColor);
$stmt->fetch();
$stmt->close();

// — Fetch today's per‐type totals for this user —
$summary = [
  'Sim-Only'     => 0,
  'Post-Pay'     => 0,
  'Handset-Only' => 0,
  'Insurance'    => 0
];
$today = date('Y-m-d');
$s = $conn->prepare("
  SELECT sale_type, COUNT(*) AS cnt
    FROM sales
   WHERE staff_id = ?
     AND DATE(sold_at) = ?
   GROUP BY sale_type
");
$s->bind_param('is', $uid, $today);
$s->execute();
$res = $s->get_result();
while ($row = $res->fetch_assoc()) {
  $summary[$row['sale_type']] = (int)$row['cnt'];
}
$s->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Account</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style/css/style.css">
  <style>
    .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(150px,1fr)); gap:1rem; margin:1rem 0; }
    .widget { background:#fff; padding:1rem; border-radius:4px; box-shadow:0 2px 5px rgba(0,0,0,0.1); text-align:center; }
    .widget h2 { margin:0 0.5rem 0.5rem; font-size:1rem; color:#333; }
    .widget .value { font-size:2rem; font-weight:bold; color:#007bff; }
  </style>
</head>
<body>
  <header><h1>Account Settings</h1></header>

  <!-- Today's User-Specific Summary Widgets -->
  <main class="dashboard-grid">
    <div class="widget">
      <h2>Sim-Only Today</h2>
      <div class="value"><?= $summary['Sim-Only'] ?></div>
    </div>
    <div class="widget">
      <h2>Post-Pay Today</h2>
      <div class="value"><?= $summary['Post-Pay'] ?></div>
    </div>
    <div class="widget">
      <h2>Handset-Only Today</h2>
      <div class="value"><?= $summary['Handset-Only'] ?></div>
    </div>
    <div class="widget">
      <h2>Insurance Today</h2>
      <div class="value"><?= $summary['Insurance'] ?></div>
    </div>
  </main>

  <!-- Color Picker Form -->
  <section class="account-form">
    <?php if ($success): ?>
      <p class="success">Color updated!</p>
    <?php endif; ?>
    <?php if ($errors): ?>
      <ul class="errors">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="POST" action="account.php">
      <label for="line_color">Timeline Color:</label>
      <input
        type="color"
        id="line_color"
        name="line_color"
        value="<?= htmlspecialchars($currentColor, ENT_QUOTES) ?>"
      >
      <button type="submit">Save</button>
    </form>

    <p><a href="index.php">← Back to Dashboard</a></p>
  </section>
</body>
</html>
