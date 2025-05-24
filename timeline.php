<?php
require __DIR__ . '/auth.php';
include __DIR__ . '/db_conn.php';

// 1) Read & validate the date from the URL (or default to today)
$date = $_GET['date'] ?? date('Y-m-d');
if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

// 2) Fetch & cache the first name
if (empty($_SESSION['first_name'])) {
    $stmt = $conn->prepare("SELECT first_name FROM staff WHERE staff_id = ? LIMIT 1");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($fn);
    $stmt->fetch();
    $_SESSION['first_name'] = $fn ?: 'Team Member';
    $stmt->close();
}
$firstName = $_SESSION['first_name'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Timeline for <?= htmlspecialchars($date) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- Fonts & Styles -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet"
  />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    crossorigin="anonymous"
  />
  <link rel="stylesheet" href="style/css/style.css?v=1.1.4">
</head>
<body class="timeline-page">

  <!-- Sidebar toggle & nav (same as index.php) -->
  <button id="sidebarToggle" class="sidebar-toggle">☰</button>
  <nav id="sidebar" class="sidebar">
    <ul>
      <li><a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
      <li><a href="log_sale.php"><i class="fa-solid fa-circle-plus"></i> Log Sale</a></li>
      <li><a href="account.php"><i class="fa-solid fa-user-cog"></i> Account</a></li>
      <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </nav>

  <!-- Main content (no 'shifted' by default) -->
  <div id="mainContent" class="main-content">
    <header>
      <h1>Sales Timeline for <?= htmlspecialchars($date) ?></h1>
      <p><a href="index.php">← Back to Dashboard</a></p>
    </header>

    <div class="widget full-chart-widget">
      <h2>Sales Timeline</h2>
      <div class="full-chart-container">
        <canvas id="fullChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart.js + logic -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // 1) Grab the dateParam straight from PHP echo to avoid JS parsing errors
    const dateParam = <?= json_encode($date) ?>;

    // 2) Prepare the canvas context
    const ctx = document.getElementById('fullChart').getContext('2d');

    // 3) Fetch & render
    fetch(`get_sales_data.php?date=${dateParam}`)
      .then(res => res.json())
      .then(cfg => {
        cfg.options = cfg.options || {};
        cfg.options.maintainAspectRatio = false;

        // apply a palette
        const palette = ['#9C27B0','green','orange','blue','red','teal','magenta','brown'];
        cfg.data.datasets.forEach((ds,i) => {
          ds.borderColor     = ds.borderColor     || palette[i % palette.length];
          ds.backgroundColor = ds.backgroundColor || ds.borderColor.replace(/(rgba\([^,]+,[^,]+,[^,]+,)([^)]+)\)/,'$10.1)');
          ds.fill         = false;
          ds.tension      = 0;
          ds.pointRadius  = 0;
        });

        new Chart(ctx, cfg);
      })
      .catch(console.error);
  </script>

  <!-- Sidebar toggle script -->
  <script>
    const sidebar   = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mainCont  = document.getElementById('mainContent');
    toggleBtn.addEventListener('click', () => {
      const open = sidebar.classList.toggle('open');
      mainCont.classList.toggle('shifted');
      toggleBtn.textContent = open ? '✖' : '☰';
    });
  </script>

  <!-- Floating FAB back to dashboard -->
  <a href="index.php" class="fab" aria-label="Back to Dashboard">
    <i class="fa-solid fa-house"></i>
  </a>

</body>
</html>
