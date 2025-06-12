<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
include __DIR__ . '/db_conn.php';

// — Fetch and cache first name —
if (empty($_SESSION['first_name'])) {
    $stmt = $conn->prepare("SELECT first_name FROM staff WHERE staff_id = ? LIMIT 1");
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

  <!-- Fonts & Icons -->
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

  <!-- Main CSS -->
  <link rel="stylesheet" href="style/css/style.css?v=1.0.2">
</head>
<body>

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

  <!-- Main content -->
  <div id="mainContent" class="main-content">
    <header>
      <h1>Welcome, <?= htmlspecialchars($firstName, ENT_QUOTES) ?>!</h1>
      <div class="date-nav">
        <button id="prevDay" class="date-btn" title="Previous Day">
          <i class="fa-solid fa-chevron-left"></i>
        </button>
        <span id="currentDate" class="date-label">--/--/----</span>
        <button id="nextDay" class="date-btn" title="Next Day">
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </header>

    <main class="dashboard-grid">
      <!-- 1) Raw summary widgets -->
      <div class="widget">
        <h2>Post-Pay Contracts</h2>
        <div class="value" id="totalPostPay">–</div>
      </div>

      <div class="widget">
        <h2>Upgrades</h2>
        <div class="value" id="totalUpgrades">–</div>
      </div>

      <div class="widget">
        <h2>Sim-Only Contracts</h2>
        <div class="value" id="totalSimOnly">–</div>
      </div>

      <div class="widget">
        <h2>Insurance Contracts</h2>
        <div class="value" id="totalInsurance">–</div>
      </div>



      <div class="widget">
        <h2>Handset-Only Purchases</h2>
        <div class="value" id="totalHandsetOnly">–</div>
      </div>

      <div class="widget">
        <h2>Accessories Sold</h2>
        <div class="value" id="totalAccessories">–</div>
      </div>

      <!-- 2) Strike rate widgets -->
      <div class="widget">
        <h2>Accessory Strike Rate</h2>
        <div class="value" id="strikeRate">–</div>
      </div>
      <div class="widget">
        <h2>Insurance Strike Rate</h2>
        <div class="value" id="insuranceStrikeRate">–</div>
      </div>

      <!-- 3) Timeline widget (click through) -->
      <a id="timelineLink" href="timeline.php?date=<?= date('Y-m-d') ?>"
         class="widget timeline-widget clickable">
        <h2>Sales Timeline</h2>
        <canvas id="myChart"></canvas>
      </a>

      <!-- 4) Leaderboard -->
      <div class="widget leaderboard">
        <h2>Top Contracts Today</h2>
        <ol id="leaderboardList" class="leaderboard-list">
          <li>No sales logged today.</li>
        </ol>
      </div>

      <!-- 5) Log-sale button spans full width -->
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

  <!-- Dashboard logic -->
  <script>
    // Helpers
    let currentDate = new Date();
    function fmtDisplay(d) {
      const dd = String(d.getDate()).padStart(2,'0');
      const mm = String(d.getMonth()+1).padStart(2,'0');
      return `${dd}/${mm}/${d.getFullYear()}`;
    }
    function fmtQuery(d) {
      return d.toISOString().slice(0,10);
    }

    // Update date label + timeline link
    function updateDateUI() {
      document.getElementById('currentDate').textContent = fmtDisplay(currentDate);
      document.getElementById('timelineLink').href =
        `timeline.php?date=${fmtQuery(currentDate)}`;
    }

    // Generate Chart on #myChart for a given date
    function generateChart(dateParam) {
      fetch(`get_sales_data.php?date=${dateParam}`)
        .then(r => r.json())
        .then(cfg => {
          cfg.options = cfg.options || {};
          //cfg.options.maintainAspectRatio = false;
          const ctx = document.getElementById('myChart').getContext('2d');
          if (window.dashboardChart) window.dashboardChart.destroy();
          window.dashboardChart = new Chart(ctx, cfg);
        });
    }

    // Fetch summary, strike rates, leaderboard, chart
    function shiftDay(delta) {
      currentDate.setDate(currentDate.getDate() + delta);
      updateDateUI();

      // 1) Summary + strike rates
      fetch(`get_summary.php?date=${fmtQuery(currentDate)}`)
        .then(r => r.json())
        .then(data => {
          const sim    = data['Sim-Only']     || 0;
          const post   = data['Post-Pay']     || 0;
          const hand   = data['Handset-Only'] || 0;
          const ins    = data['Insurance']    || 0;
          const acc    = data['Accessories']  || 0;
          const upg = data['Upgrades'] || 0;

          const phones = post + hand;

          // populate totals
          document.getElementById('totalSimOnly').textContent     = sim;
          document.getElementById('totalPostPay').textContent     = post;
          document.getElementById('totalHandsetOnly').textContent = hand;
          document.getElementById('totalInsurance').textContent   = ins;
          document.getElementById('totalAccessories').textContent = acc;
          document.getElementById('totalUpgrades').textContent = upg;


          // accessory strike
          document.getElementById('strikeRate').textContent =
            phones > 0 ? Math.round(acc/phones*100)+'%' : '–';

          // insurance strike
          document.getElementById('insuranceStrikeRate').textContent =
            phones > 0 ? Math.round(ins/phones*100)+'%' : '–';
        });

      // 2) Leaderboard
      fetch(`get_leaderboard.php?date=${fmtQuery(currentDate)}`)
        .then(r => r.json())
        .then(list => {
          const ol = document.getElementById('leaderboardList');
          ol.innerHTML = '';
          if (!list.length) {
            ol.innerHTML = '<li>No sales logged today.</li>';
          } else {
            list.forEach(e => {
              const li = document.createElement('li');
              li.textContent = `${e.name}: ${e.cnt}`;
              ol.appendChild(li);
            });
          }
        });

      // 3) Chart
      generateChart(fmtQuery(currentDate));
    }

    // Wire ◀ / ▶
    document.getElementById('prevDay').onclick = ()=>shiftDay(-1);
    document.getElementById('nextDay').onclick = ()=>shiftDay( 1);

    // Initial load
    updateDateUI();
    shiftDay(0);

    // Sidebar toggle
    const sidebar   = document.getElementById('sidebar'),
          toggleBtn = document.getElementById('sidebarToggle'),
          mainCont  = document.getElementById('mainContent');
    toggleBtn.addEventListener('click', () => {
      const open = sidebar.classList.toggle('open');
      mainCont.classList.toggle('shifted');
      toggleBtn.textContent = open ? '✖' : '☰';
    });
  </script>

  <!-- Floating Add-Sale Button -->
  <a href="log_sale.php" class="fab" aria-label="Log a New Sale">
    <i class="fa-solid fa-plus"></i>
  </a>
</body>
</html>
