<?php
require __DIR__ . '/auth.php';      // enforces login
include __DIR__ . '/db_conn.php';   // provides $conn

$allowedTypes = ['Sim-Only','Post-Pay','Handset-Only','Insurance'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id       = intval($_SESSION['user_id']);
    $contract_count = filter_var($_POST['contract_count'] ?? '', FILTER_VALIDATE_INT);
    $sale_type      = $_POST['sale_type'] ?? '';

    if ($contract_count === false || $contract_count < 1) {
        $_SESSION['flash_error'] = 'Enter a valid number of contracts.';
    } elseif (! in_array($sale_type, $allowedTypes, true)) {
        $_SESSION['flash_error'] = 'Select a valid sale type.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO sales (staff_id, contract_value, sale_type, sold_at)
            VALUES (?, 1, ?, NOW())
        ");
        $stmt->bind_param('is', $staff_id, $sale_type);
        for ($i = 0; $i < $contract_count; $i++) {
            if (! $stmt->execute()) {
                $_SESSION['flash_error'] = 'Failed to log sale: ' . $stmt->error;
                break;
            }
        }
        $stmt->close();

        if (empty($_SESSION['flash_error'])) {
            $_SESSION['flash_success'] = "Logged {$contract_count} {$sale_type} sale(s).";
            header('Location: index.php');
            exit;
        }
    }
    $conn->close();
}
?>

<?php 
  $icons = [
    'Sim-Only'     => 'fa-signal',
    'Post-Pay'     => 'fa-credit-card',
    'Handset-Only' => 'fa-mobile-screen-button',
    'Insurance'    => 'fa-shield-halved'
  ];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Log a New Sale</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
    <!-- Your main stylesheet -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link ref="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous"/>
  <link rel="stylesheet" href="style/css/style.css?v=1.0.2">

  <link
  href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
  rel="stylesheet"
/>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  crossorigin="anonymous"
/>

</head>
<body>

  <!-- Sidebar toggle & nav -->
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

  <div id="mainContent" class="main-content">
    <header>
      <h1>Log a New Sale</h1>
    </header>

    <!-- Flash messages -->
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="widget" style="background:#ffe5e5;color:#a00;">
        <?= htmlspecialchars($_SESSION['flash_error'],ENT_QUOTES) ?>
      </div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="widget" style="background:#e5ffe5;color:#060;">
        <?= htmlspecialchars($_SESSION['flash_success'],ENT_QUOTES) ?>
      </div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Form in a centered widget -->
    <main class="dashboard-grid" style="max-width:400px;margin:1rem auto;">
      <div class="widget">
        <form method="POST" action="log_sale.php" style="width:100%;">

          <div class="form-custom__group" style="margin-bottom:1.5rem;">
            <label class="form-custom__label" for="sale_type">Type of Sale:</label>
            <select id="sale_type" name="sale_type" class="form-custom__input" required>
              <option value="">– Select –</option>
              <?php foreach ($allowedTypes as $type): ?>
                <option
                  value="<?= htmlspecialchars($type) ?>"
                  data-icon="<?= $icons[$type] ?>"
                >
                  <?= htmlspecialchars($type) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-custom__group" style="margin-bottom:1rem;">
            <label class="form-custom__label" for="contract_count">Amount Sold:</label>
            <input
              type="number"
              id="contract_count"
              name="contract_count"
              class="form-custom__input"
              min="1" required
            >
          </div>
          
          <button type="submit" class="log-sale-btn" style="width:100%;">Submit</button>
        </form>
      </div>
    </main>



    <footer>
      <p><a href="index.php">← Back to Dashboard</a></p>
    </footer>
  </div>

  <!-- Sidebar toggle script -->
  <script>
    const sidebar = document.getElementById('sidebar'),
          toggle = document.getElementById('sidebarToggle'),
          mainC   = document.getElementById('mainContent');
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      mainC.classList.toggle('shifted');
      toggle.textContent = sidebar.classList.contains('open') ? '✖' : '☰';
    });
  </script>

  <script>
  function formatWithIcon(option) {
    if (!option.id) {
      return option.text;  // placeholder
    }
    // Grab the data-icon attr from the <option>
    const iconClass = $(option.element).data('icon');
    return $('<span><i class="fa ' + iconClass + '"></i> ' + option.text + '</span>');
  }

  $(document).ready(function() {
    $('#sale_type').select2({
      width: '100%',
      templateResult:  formatWithIcon,
      templateSelection: formatWithIcon,
      minimumResultsForSearch: Infinity  // hide search
    });
  });
</script>


</body>
</html>
