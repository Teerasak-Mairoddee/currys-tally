﻿<?php
require __DIR__ . '/auth.php';
include __DIR__ . '/db_conn.php';

$uid     = $_SESSION['user_id'];
$errors  = [];
$success = false;

// Handle timeline color update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['line_color'])) {
    $color = trim($_POST['line_color']);
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
        $errors[] = 'Pick a valid hex color.';
    } else {
        $upd = $conn->prepare("UPDATE staff SET line_color = ? WHERE staff_id = ?");
        $upd->bind_param('si', $color, $uid);
        if ($upd->execute()) {
            $success = true;
        } else {
            $errors[] = 'Update failed: ' . $upd->error;
        }
        $upd->close();
    }
}

// Fetch current line_color
$stmt = $conn->prepare("SELECT line_color FROM staff WHERE staff_id = ? LIMIT 1");
$stmt->bind_param('i', $uid);
$stmt->execute();
$stmt->bind_result($currentColor);
$stmt->fetch();
$stmt->close();

// Build today's per-type summary including Accessories & Insurance
$types = ['Sim-Only','Post-Pay','Handset-Only','Insurance','Accessories','Upgrades'];
$summary = array_fill_keys($types, 0);
$today   = date('Y-m-d');

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
    if (isset($summary[$row['sale_type']])) {
        $summary[$row['sale_type']] = (int)$row['cnt'];
    }
}
$s->close();
$conn->close();

// Compute both strike rates: accessories ÷ phones, insurance ÷ phones
$phonesSold        = $summary['Post-Pay'] + $summary['Handset-Only'];
$accessories       = $summary['Accessories'];
$insuranceContracts= $summary['Insurance'];

$strikeRatePct     = $phonesSold > 0
    ? round($accessories       / $phonesSold * 100)
    : null;
$insuranceRatePct  = $phonesSold > 0
    ? round($insuranceContracts/ $phonesSold * 100)
    : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Account</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Fonts & Styles -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet"
  />
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    rel="stylesheet" crossorigin="anonymous"
  />
  <link rel="stylesheet" href="style/css/style.css?v=1.4.0">
</head>
<body class="account-page">

  <!-- Sidebar toggle button -->
  <button id="sidebarToggle" class="sidebar-toggle">☰</button>

  <!-- Slide-out sidebar -->
  <nav id="sidebar" class="sidebar">
    <ul>
      <li><a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
      <li><a href="log_sale.php"><i class="fa-solid fa-circle-plus"></i> Log Sale</a></li>
      <li><a href="account.php"><i class="fa-solid fa-user-cog"></i> Account</a></li>
      <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </nav>

  <div id="mainContent" class="main-content">
    <header>
      <h1>Account Settings</h1>
    </header>

    <!-- Today's User-Specific Summary + Two Strike Rates -->
    <main class="dashboard-grid account-summary">
      <?php foreach ($types as $type): ?>
        <div class="widget">
          <h2><?= htmlspecialchars($type, ENT_QUOTES) ?> Today</h2>
          <div class="value"><?= $summary[$type] ?></div>
        </div>
      <?php endforeach; ?>

      <div class="widget">
        <h2>Accessory Strike Rate</h2>
        <div class="value">
          <?= $strikeRatePct !== null ? $strikeRatePct . '%' : '–' ?>
        </div>
      </div>

      <div class="widget">
        <h2>Insurance Strike Rate</h2>
        <div class="value">
          <?= $insuranceRatePct !== null ? $insuranceRatePct . '%' : '–' ?>
        </div>
      </div>
    </main>

    <!-- Color Picker Form -->
    <section class="account-form">
      <?php if ($success): ?>
        <div class="alert success">Color updated!</div>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert error">
          <ul>
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

    <form method="POST" action="account.php" class="form-account">
      <div class="form-group">
        <label for="line_color">Timeline Color</label>
        <input
          type="color"
          id="line_color"
          name="line_color"
          value="<?= htmlspecialchars($currentColor, ENT_QUOTES) ?>"
        >
      </div>
      <button type="submit" class="btn">Save</button>
    </form>

      <p><a href="index.php" class="link-back">← Back to Dashboard</a></p>
    </section>
  </div>

  <!-- Sidebar toggle script -->
<script>
  const sidebar   = document.getElementById('sidebar'),
        toggleBtn = document.getElementById('sidebarToggle'),
        mainCont  = document.getElementById('mainContent');

  toggleBtn.addEventListener('click', () => {
    const open = sidebar.classList.toggle('open');
    mainCont.classList.toggle('shifted');
    toggleBtn.textContent = open ? '✖' : '☰';
  });
</script>

  <!-- Floating Back-to-Dashboard Button -->
  <a href="index.php" class="fab" aria-label="Back to Dashboard">
    <i class="fa-solid fa-house"></i>
  </a>
</body>
</html>