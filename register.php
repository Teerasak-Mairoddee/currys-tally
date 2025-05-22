<?php
session_start();


$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/db_conn.php';  // gives you $conn (mysqli)

    // 1) Trim & sanitize
    $first   = trim($_POST['First_Name']   ?? '');
    $last    = trim($_POST['Last_Name']    ?? '');
    $email   = trim($_POST['email']        ?? '');
    $phone   = trim($_POST['phone']        ?? '');
    $pass    = $_POST['password']          ?? '';
    $confirm = $_POST['confirmPassword']   ?? '';

    // 2) Basic validation
    if ($first === '') {
        $errors[] = 'First name is required.';
    }
    if ($last === '') {
        $errors[] = 'Last name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    } elseif (!preg_match('/^[^@]+@currys\.co\.uk$/i', $email)) {
        $errors[] = 'Not authorised to join.';
    }
    if ($pass === '' || $confirm === '') {
        $errors[] = 'Both password fields are required.';
    } elseif ($pass !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } elseif (strlen($pass) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    // 3) Insert if ok
    if (empty($errors)) {
        $pwHash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO users 
               (First_Name, Last_name, email, phone, password, date)
             VALUES (?,?,?,?,?, NOW())"
        );
        $stmt->bind_param('sssss', $first, $last, $email, $phone, $pwHash);

        if ($stmt->execute()) {
            $newUserId = $stmt->insert_id;
            $stmt->close();

            // Mirror into staff table
            $mirror = $conn->prepare(
              "INSERT INTO staff
                 (staff_id, first_name, last_name, email, created_at)
               VALUES (?, ?, ?, ?, NOW())"
            );
            $mirror->bind_param('isss', $newUserId, $first, $last, $email);
            $mirror->execute();
            $mirror->close();

            header('Location: login.php?registered=1');
            exit;
        } else {
            $errors[] = 'Registration failed: ' . $stmt->error;
            $stmt->close();
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign Up | Currys Tracker</title>

  <!-- Inter font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet"
  />

  <!-- Main stylesheet -->
  <link rel="stylesheet" href="style/css/style.css?v=1.1.1">
</head>
<body class="register-page">

  <div class="register-container">
    <h2 class="form-title">Create Your Currys Tracker Account</h2>

    <?php if ($errors): ?>
      <div class="alert error">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="form-register">
      <div class="form-group">
        <label for="First_Name">First Name</label>
        <input
          type="text"
          id="First_Name"
          name="First_Name"
          class="form-control"
          required
          value="<?= htmlspecialchars($_POST['First_Name'] ?? '', ENT_QUOTES) ?>"
        />
      </div>

      <div class="form-group">
        <label for="Last_Name">Last Name</label>
        <input
          type="text"
          id="Last_Name"
          name="Last_Name"
          class="form-control"
          required
          value="<?= htmlspecialchars($_POST['Last_Name'] ?? '', ENT_QUOTES) ?>"
        />
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-control"

          required
          value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"
        />
      </div>

      <div class="form-group">
        <label for="phone">Phone (optional)</label>
        <input
          type="tel"
          id="phone"
          name="phone"
          class="form-control"
          value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES) ?>"
        />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          class="form-control"

          required
        />
      </div>

      <div class="form-group">
        <label for="confirmPassword">Confirm Password</label>
        <input
          type="password"
          id="confirmPassword"
          name="confirmPassword"
          class="form-control"

          required
        />
      </div>

      <div class="form-actions">
        <button type="submit" class="btn">Sign Up</button>
        <a href="login.php" class="btn btn-secondary">Back to Login</a>
      </div>
    </form>
  </div>

</body>
</html>
