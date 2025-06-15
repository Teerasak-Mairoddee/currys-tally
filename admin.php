<?php
require __DIR__ . '/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel</title>
</head>
<body>
  <h1>Welcome to the Admin Panel</h1>
  <!-- Admin-only content here -->
</body>
</html>
