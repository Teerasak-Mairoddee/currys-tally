<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include __DIR__ . '/db_conn.php';

// the new set of types
$allowedTypes = ['Sim-Only','Post-Pay','Handset-Only','Insurance'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id       = intval($_SESSION['user_id']);
    $contract_count = filter_var($_POST['contract_count'] ?? '', FILTER_VALIDATE_INT);
    $sale_type      = $_POST['sale_type'] ?? '';

    // Validation
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
        }
    }

    $conn->close();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Log a New Sale</title>
</head>
<body>

  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div style="color:red"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div style="color:green"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <h1>Log a Sale</h1>
  <form method="POST" action="log_sale.php">
    <label>
      How Many Contract(s):
      <input type="number" name="contract_count" min="1" required>
    </label><br>
    <label>
      Type of Sale:
      <select name="sale_type" required>
        <option value="">-- Select --</option>
        <option>Sim-Only</option>
        <option>Post-Pay</option>
        <option>Handset-Only</option>
        <option>Insurance</option>
      </select>
    </label><br>
    <button type="submit">Submit</button>
  </form>

  <p><a href="index.php">Back to Dashboard</a></p>
</body>
</html>
