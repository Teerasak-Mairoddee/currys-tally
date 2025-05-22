<?php
require __DIR__ . '/auth.php';
include __DIR__ . '/db_conn.php';

$uid     = $_SESSION['user_id'];
$errors  = [];
$success = false;

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

$stmt = $conn->prepare("SELECT line_color FROM staff WHERE staff_id = ? LIMIT 1");
$stmt->bind_param('i', $uid);
$stmt->execute();
$stmt->bind_result($currentColor);
$stmt->fetch();
$stmt->close();

// build today’s summary
$summary = array_fill_keys(['Sim-Only','Post-Pay','Handset-Only','Insurance'], 0);
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
    <!-- Your main stylesheet -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link ref="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous"/>
  <link rel="stylesheet" href="style/css/style.css?v=1.0.2">
</head>
<body>
  <!-- Sidebar toggle button -->
  <button id="sidebarToggle" class="sidebar-toggle">☰</button>


<!-- Slide-out sidebar -->
<nav id="sidebar" class="sidebar">
  <ul>
    <li>
      <a href="index.php">
        <i class="fa-solid fa-house"></i>
        <span>Dashboard</span>
      </a>
    </li>
    <li>
      <a href="log_sale.php">
        <i class="fa-solid fa-circle-plus"></i>
        <span>Log Sale</span>
      </a>
    </li>
    <li>
      <a href="account.php">
        <i class="fa-solid fa-user-cog"></i>
        <span>Account</span>
      </a>
    </li>
    <li>
      <a href="logout.php">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>Logout</span>
      </a>
    </li>
  </ul>
</nav>

  <header><h1>Account Settings</h1></header>

  <!-- Summary Widgets -->
  <main class="dashboard-grid account-summary">
    <?php foreach ($summary as $type => $count): ?>
      <div class="widget">
        <h2><?= htmlspecialchars($type) ?> Today</h2>
        <div class="value"><?= $count ?></div>
      </div>
    <?php endforeach; ?>
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
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="account.php" class="form-account">
      <div class="form-group">
        <label for="line_color">Timeline Color</label>
        <input type="color" id="line_color" name="line_color"
               value="<?= htmlspecialchars($currentColor, ENT_QUOTES) ?>">
      </div>
      <button type="submit" class="btn">Save</button>
    </form>

    <p><a href="index.php" class="link-back">← Back to Dashboard</a></p>
  </section>

    <script>
    // summary fetch
    fetch('./get_summary.php')
      .then(r => r.json())
      .then(data => {
        document.getElementById('totalSimOnly').textContent     = data['Sim-Only'];
        document.getElementById('totalPostPay').textContent     = data['Post-Pay'];
        document.getElementById('totalHandsetOnly').textContent = data['Handset-Only'];
        document.getElementById('totalInsurance').textContent   = data['Insurance'];
      })
      .catch(console.error);

    // sidebar toggle
    const sidebar   = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mainCont  = document.getElementById('mainContent');

    toggleBtn.addEventListener('click', () => {
      const isOpen = sidebar.classList.toggle('open');
      mainCont.classList.toggle('shifted');

      // Swap the button text between “☰” and “✖”
      toggleBtn.textContent = isOpen ? '✖' : '☰';
    });
  </script>

  <!-- Floating Add‐Sale Button -->
<a href="log_sale.php" class="fab" aria-label="Log a New Sale">
  <i class="fa-solid fa-plus"></i>
</a>

</body>
</html>
