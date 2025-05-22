<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function for login
function try_login($conn, $email, $password, $table, $role, $redirect) {
    $stmt = $conn->prepare(
        "SELECT user_id, First_Name, Last_name, email, password
           FROM `$table`
          WHERE email = ?
          LIMIT 1"
    );
    if (! $stmt) {
        throw new Exception('DB error: ' . $conn->error);
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    // fetch the row
    $row = $res ? $res->fetch_assoc() : null;
    if (! $row) {
        return false;
    }

    // verify password
    if (! password_verify($password, $row['password'])) {
        return false;
    }

    // success
    $_SESSION['user_id']    = $row['user_id'];
    $_SESSION['First_Name'] = $row['First_Name'];
    $_SESSION['Last_Name']  = $row['Last_name'];
    $_SESSION['email']      = $row['email'];
    $_SESSION['role']       = $role;
    header("Location: $redirect");
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/db_conn.php';

    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (! $email) {
        $errors[] = 'Please enter a valid email.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        // try admin login first
        if (! try_login($conn, $email, $password, 'admins', 'admin', 'admin_index.php')) {
            // then regular users
            if (! try_login($conn, $email, $password, 'users', 'user', 'index.php')) {
                $errors[] = 'Invalid email or password.';
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login | Currys Tracker</title>

  <!-- Inter font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet"
  />

  <!-- Main stylesheet -->
  <link rel="stylesheet" href="style/css/style.css?v=1.1.0">
</head>
<body class="login-page">

  <div class="login-container">
    <h2 class="login-title">Login to start tracking</h2>

    <?php if ($errors): ?>
      <div class="alert error">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="login-form" action="login.php">
      <div class="form-group">
        <label for="inputEmail">Email</label>
        <input
          type="email"
          id="inputEmail"
          name="email"
          class="form-control"

          required
          value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"
        />
      </div>

      <div class="form-group">
        <label for="inputPassword">Password</label>
        <input
          type="password"
          id="inputPassword"
          name="password"
          class="form-control"

          required
        />
      </div>

      <div class="form-actions">
        <button type="submit" class="btn">Login</button>
        <a href="register.php" class="btn btn-secondary">Register</a>
      </div>
    </form>
  </div>

</body>
</html>
