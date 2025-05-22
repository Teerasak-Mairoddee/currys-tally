<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
include __DIR__.'/db_conn.php';

$uid = $_SESSION['user_id'];
$errors = [];
$success = false;

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $color = trim($_POST['line_color'] ?? '');
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
      $errors[] = 'Update failed.';
    }
    $upd->close();
  }
}

// Fetch current color
$stmt = $conn->prepare(
  "SELECT line_color FROM staff WHERE staff_id = ? LIMIT 1"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$stmt->bind_result($currentColor);
$stmt->fetch();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Account</title>
  <link rel="stylesheet" href="style/css/style.css">
</head>
<body>
  <header><h1>Account Settings</h1></header>
  <main class="account-form">
    <?php if ($success): ?>
      <p class="success">Color updated!</p>
    <?php endif; ?>
    <?php if ($errors): ?>
      <ul class="errors">
        <?php foreach($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
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
  </main>
</body>
</html>
