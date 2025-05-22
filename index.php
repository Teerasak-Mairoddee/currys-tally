<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
include __DIR__ . '/db_conn.php';  // gives you $conn (mysqli)

// — Fetch and cache first name —
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

// — Fetch today’s leaderboard: sum of Sim-Only + Post-Pay per staff —
$leaderboard = [];
$today = date('Y-m-d');
$lb = $conn->prepare("
    SELECT
      s.staff_id,
      CONCAT(s.first_name, ' ', s.last_name) AS name,
      SUM(CASE WHEN sale_type IN ('Sim-Only','Post-Pay') THEN 1 ELSE 0 END) AS cnt
    FROM sales
    JOIN staff s ON sales.staff_id = s.staff_id
    WHERE DATE(sold_at) = ?
    GROUP BY s.staff_id
    HAVING cnt > 0
    ORDER BY cnt DESC
    LIMIT 5
");
$lb->bind_param('s', $today);
$lb->execute();
$res = $lb->get_result();
while ($row = $res->fetch_assoc()) {
    $leaderboard[] = $row;
}
$lb->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
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

  <!-- Main content -->
  <div id="mainContent" class="main-content">
    <header>
      <h1>Welcome, <?= htmlspecialchars($firstName, ENT_QUOTES) ?>!</h1>
      <div class="date-nav">
          <button id="prevDay" class="date-btn" title="Previous Day">
            <i class="fa-solid fa-chevron-left"></i>
          </button>
          <span id="currentDate" class="date-label">01/06/2025</span>
          <button id="nextDay" class="date-btn" title="Next Day">
            <i class="fa-solid fa-chevron-right"></i>
          </button>
      </div>

    </header>

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

      <div class="widget timeline-widget">
        <h2>Sales Timeline</h2>
        <canvas id="myChart"></canvas>
      </div>

      <div class="widget leaderboard">
        <h2>Top Contracts Today</h2>
        <ol class="leaderboard-list">
          <?php if (empty($leaderboard)): ?>
            <li>No sales logged today.</li>
          <?php else: ?>
            <?php foreach ($leaderboard as $entry): ?>
              <li>
                <?= htmlspecialchars($entry['name'], ENT_QUOTES) ?>: <?= intval($entry['cnt']) ?>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ol>
      </div>
        <a href="log_sale.php" class="widget full-width log-sale-btn">
          <i class="fa-solid fa-plus-circle fa-3x"></i>
        </a>
    </main>

    <footer>
      <p>&copy; 2025 Teerasak Mairoddee</p>
    </footer>
  </div>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Your chart & summary scripts -->
  <script src="scripts/generate_data.js?v=1.0.4"></script>
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
