<?php
require __DIR__ . '/auth.php';
include __DIR__ . '/db_conn.php';

// Fetch & cache first name
if (empty($_SESSION['first_name'])) {
    $stmt = $conn->prepare(
      "SELECT first_name FROM staff WHERE staff_id = ? LIMIT 1"
    );
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
  <title>Full Sales Timeline</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Your main stylesheet -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link ref="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous"/>
  <link rel="stylesheet" href="style/css/style.css?v=1.0.2">
</head>
<body class="timeline-page">

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

  <!-- Note: NO "shifted" class here -->
  <div id="mainContent" class="main-content">
    <header>

    </header>

    <!-- Chart card -->
    <div class="widget full-chart-widget">
      <h2>Sales Timeline</h2>
      <div class="full-chart-container">
        <canvas id="fullChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Build the chart
    const ctx = document.getElementById('fullChart').getContext('2d');
    fetch('get_sales_data.php')
      .then(r => r.json())
      .then(cfg => {
        cfg.options = cfg.options || {};
        cfg.options.maintainAspectRatio = false;

        const palette = ['#9C27B0','green','orange','blue','red','teal','magenta','brown'];
        cfg.data.datasets.forEach((ds,i) => {
          ds.borderColor     = ds.borderColor     || palette[i % palette.length];
          ds.backgroundColor = ds.backgroundColor || ds.borderColor.replace(/(rgba\([^,]+,[^,]+,[^,]+,)([^)]+)\)/,'$10.1)');
          ds.fill        = false;
          ds.tension     = 0;
          ds.pointRadius = 0;
        });

        new Chart(ctx, cfg);
      })
      .catch(console.error);
  </script>

  <!-- Sidebar toggle logic -->
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

  <!-- Floating Add Sale FAB -->
  <a href="log_sale.php" class="fab" aria-label="Log a New Sale">
    <i class="fa-solid fa-plus"></i>
  </a>

</body>
</html>
