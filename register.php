<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/db_conn.php';  // gives you $conn (mysqli)

    // 1) Trim & sanitize
    $first   = trim($_POST['First_Name']   ?? '');
    $last    = trim($_POST['Last_Name']    ?? '');
    $email   = trim($_POST['email']       ?? '');
    $phone   = trim($_POST['phone']       ?? '');
    $pass    = $_POST['password']         ?? '';
    $confirm = $_POST['confirmPassword']  ?? '';

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
        $errors[] = 'You must register with a @currys.co.uk email address.';
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
  <link rel="stylesheet" href="style.css?v=123" />
</head>
<body>

  <section class="section-login">
    <h2>Create Your Currys Tracker Account</h2>

    <?php if (!empty($errors)): ?>
      <div class="errors">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="form-login">
      <label>
        First Name
        <input type="text" name="First_Name" required value="<?= htmlspecialchars($_POST['First_Name'] ?? '', ENT_QUOTES) ?>">
      </label>

      <label>
        Last Name
        <input type="text" name="Last_Name" required value="<?= htmlspecialchars($_POST['Last_Name'] ?? '', ENT_QUOTES) ?>">
      </label>

      <label>
        Currys Email (@currys.co.uk)
        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>">
      </label>

      <label>
        Phone (optional)
        <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES) ?>">
      </label>

      <label>
        Password
        <input type="password" name="password" required>
      </label>

      <label>
        Confirm Password
        <input type="password" name="confirmPassword" required>
      </label>

      <button type="submit">Sign Up</button>
      <a href="login.php">Back to Login</a>
    </form>
  </section>

</body>
</html>
