<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include __DIR__ . '/db_conn.php';  // gives $conn (mysqli)

// 1) Validate invite token from URL
$rawToken = $_GET['token'] ?? '';
if (!preg_match('/^[0-9a-f]{64}$/', $rawToken)) {
    die('Invalid or missing invite token.');
}
$hash = hash('sha256', $rawToken);

// … rest of your existing code unchanged …

if (empty($errors)) {
    // 4) Create admin account
    $pwHash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        "INSERT INTO admins (First_Name, Last_name, email, phone, password, date) VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $stmt->bind_param('sssss', $first, $last, $email, $phone, $pwHash);

    if ($stmt->execute()) {
        $newAdminId = $stmt->insert_id;
        $stmt->close();

        // 5) Mark invite as used
        $upd = $conn->prepare(
            "UPDATE admin_invites SET used_at = NOW(), used_by = ? WHERE id = ?"
        );
        $upd->bind_param('ii', $newAdminId, $invite['id']);
        $upd->execute();
        $upd->close();

        // 6) Redirect to login
        header('Location: login.php?registered=1');
        exit;
    } else {
        $errors[] = 'Registration failed: ' . $stmt->error;
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<!-- … your existing HTML form … -->
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sign Up | MMAFIA</title>
    <!-- Fonts & Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Anton&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="style.css" />
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous" />
</head>
<body style="background-color: #111; color: white; font-family: 'Anton', sans-serif;">
<div class="container py-5">
  <h1 class="text-center mb-4">Create Admin Account</h1>

  <!-- Show errors -->
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul>
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form class="signup-form mx-auto" style="max-width: 600px" method="POST" action="registeradmin.php?token=<?= htmlspecialchars($rawToken) ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

    <div class="mb-3 row">
      <label for="firstName" class="col-sm-3 col-form-label">First Name</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="firstName" name="First_Name" required value="<?= htmlspecialchars($_POST['First_Name'] ?? '') ?>" />
      </div>
    </div>

    <div class="mb-3 row">
      <label for="lastName" class="col-sm-3 col-form-label">Last Name</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="lastName" name="Last_Name" required value="<?= htmlspecialchars($_POST['Last_Name'] ?? '') ?>" />
      </div>
    </div>

    <div class="mb-3 row">
      <label for="email" class="col-sm-3 col-form-label">Email</label>
      <div class="col-sm-9">
        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
      </div>
    </div>

    <div class="mb-3 row">
      <label for="phone" class="col-sm-3 col-form-label">Phone</label>
      <div class="col-sm-9">
        <input type="tel" class="form-control" id="phone" name="phone" placeholder="Optional" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />
      </div>
    </div>

    <div class="mb-3 row">
      <label for="password" class="col-sm-3 col-form-label">Password</label>
      <div class="col-sm-9">
        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required />
      </div>
    </div>

    <div class="mb-3 row">
      <label for="confirmPassword" class="col-sm-3 col-form-label">Confirm</label>
      <div class="col-sm-9">
        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required />
      </div>
    </div>

    <div class="text-end">
      <button type="submit" class="btn btn-danger px-4">Sign Up</button>
      <a href="login.php" class="btn btn-danger px-4">Back to Login</a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>