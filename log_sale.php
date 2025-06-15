<?php 
require __DIR__ . '/auth.php';
include __DIR__ . '/db_conn.php';

$allowedContracts = ['Post-Pay', 'Handset-Only'];
$singleSaleTypes  = ['Sim-Only', 'Upgrades', 'Accessories', 'Insurance'];
$linkedTypes      = ['Insurance', 'Accessories'];
$allSaleTypes     = array_merge($allowedContracts, $singleSaleTypes);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = intval($_SESSION['user_id']);
    $sale_type = $_POST['sale_type'] ?? '';
    $linked_items = $_POST['linked_items'] ?? [];
    $contract_count = filter_var($_POST['contract_count'] ?? '', FILTER_VALIDATE_INT);
    $accessory_qty = filter_var($_POST['accessory_qty'] ?? 1, FILTER_VALIDATE_INT);

    if (!in_array($sale_type, $allSaleTypes)) {
        $_SESSION['flash_error'] = 'Invalid sale type.';
    } elseif ($contract_count === false || $contract_count < 1) {
        $_SESSION['flash_error'] = 'Enter a valid contract count.';
    } elseif ($accessory_qty !== false && ($accessory_qty < 1 || $accessory_qty > 5)) {
        $_SESSION['flash_error'] = 'Accessory quantity must be 1–5.';
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("
                INSERT INTO sales (staff_id, contract_value, sale_type, sold_at)
                VALUES (?, 1, ?, NOW())
            ");
            $stmt->bind_param('is', $staff_id, $sale_type);

            $linkStmt = $conn->prepare("
                INSERT INTO sales (staff_id, contract_value, sale_type, sold_at, sold_with)
                VALUES (?, 1, ?, NOW(), ?)
            ");

            for ($i = 0; $i < $contract_count; $i++) {
                $stmt->execute();
                $main_sale_id = $stmt->insert_id;

                if (in_array($sale_type, $allowedContracts)) {
                    foreach ($linked_items as $type) {
                        $qty = ($type === 'Accessories') ? $accessory_qty : 1;
                        if (in_array($type, $linkedTypes)) {
                            for ($j = 0; $j < $qty; $j++) {
                                $linkStmt->bind_param('isi', $staff_id, $type, $main_sale_id);
                                $linkStmt->execute();
                            }
                        }
                    }
                }
            }

            $stmt->close();
            $linkStmt->close();
            $conn->commit();

            $_SESSION['flash_success'] = "Logged {$contract_count} {$sale_type} sale(s)." . 
                (count($linked_items) ? " With add-ons." : "");
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_error'] = "Failed: " . $e->getMessage();
        }
    }

    $conn->close();
}

$icons = [
    'Sim-Only'     => 'fa-solid fa-signal',
    'Post-Pay'     => 'fa-solid fa-credit-card',
    'Upgrades'     => 'fa-solid fa-rotate',
    'Handset-Only' => 'fa-solid fa-mobile-screen-button',
    'Insurance'    => 'fa-solid fa-shield-halved',
    'Accessories'  => 'fa-solid fa-box-open'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Log a New Sale</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- ✅ Updated CSS version for cache busting -->
  <link rel="stylesheet" href="style/css/style.css?v=1.4.0">

  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>

  <button id="sidebarToggle" class="sidebar-toggle">☰</button>

  <nav id="sidebar" class="sidebar">
    <ul>
      <li><a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
      <li><a href="log_sale.php"><i class="fa-solid fa-circle-plus"></i> Log Sale</a></li>
      <li><a href="account.php"><i class="fa-solid fa-user-cog"></i> Account</a></li>
      <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </nav>

<div id="mainContent" class="main-content">
  <div class="form-container">
    <h1>Log a New Sale</h1>

    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="alert error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="alert success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="sale_type">Select Sale Type</label>
        <select name="sale_type" id="sale_type" required>
          <option value="">– Select –</option>
          <?php foreach ($allSaleTypes as $type): ?>
            <option value="<?= $type ?>" data-icon="<?= $icons[$type] ?? '' ?>"><?= $type ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="linked-items" class="form-group" style="display:none;">
        <label>Linked Add-ons (optional)</label>
        <div class="checkbox-group">
          <?php foreach ($linkedTypes as $type): ?>
            <label>
              <input type="checkbox" name="linked_items[]" value="<?= $type ?>" <?= $type === 'Accessories' ? 'id="acc-check"' : '' ?>> <?= $type ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div id="accessory-count" class="form-group" style="display:none; margin-top:0.5rem;">
          <label for="accessory_qty">Accessory Quantity</label>
          <input type="number" name="accessory_qty" min="1" max="5" value="1">
        </div>
      </div>

      <div class="form-group">
        <label for="contract_count">Amount Sold</label>
        <input type="number" name="contract_count" min="1" required>
      </div>

      <button type="submit" class="btn">Log Sale</button>
    </form>
  </div>
</div>

<script>
  $('#sale_type').select2({
    width: '100%',
    templateResult: function(option) {
      if (!option.id) return option.text;
      const icon = $(option.element).data('icon');
      return $(`<span><i class="${icon}"></i> ${option.text}</span>`);
    },
    templateSelection: function(option) {
      if (!option.id) return option.text;
      const icon = $(option.element).data('icon');
      return $(`<span><i class="${icon}"></i> ${option.text}</span>`);
    },
    minimumResultsForSearch: Infinity
  });

  $('#sale_type').on('change', function() {
    const allowed = <?= json_encode($allowedContracts) ?>;
    $('#linked-items').toggle(allowed.includes($(this).val()));
  });

  $(document).on('change', '#acc-check', function() {
    $('#accessory-count').toggle(this.checked);
  });

  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const mainCont = document.getElementById('mainContent');

  toggleBtn.addEventListener('click', () => {
    const open = sidebar.classList.toggle('open');
    mainCont.classList.toggle('shifted');
    toggleBtn.textContent = open ? '✖' : '☰';
  });
</script>

</body>
</html>
