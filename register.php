<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/db_conn.php';  // gives you $conn (mysqli)

    // 1) Trim & sanitize
    $first   = trim($_POST['First_Name']   ?? '');
    $last    = trim($_POST['Last_Name']    ?? '');
    $email   = filter_var($_POST['email']  ?? '', FILTER_VALIDATE_EMAIL);
    $phone   = trim($_POST['phone']        ?? '');
    $pass    = $_POST['password']          ?? '';
    $confirm = $_POST['confirmPassword']   ?? '';

    // 2) Validate
    if ($first === '')  $errors[] = 'First name is required.';
    if ($last === '')   $errors[] = 'Last name is required.';
    if (! $email)       $errors[] = 'A valid email is required.';
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
            // ───────────────────────────────────────────────
            // Mirror into staff table immediately after users INSERT
            $newUserId = $stmt->insert_id;
            $stmt->close();

            $mirror = $conn->prepare(
              "INSERT INTO staff
                 (staff_id, first_name, last_name, email, created_at)
               VALUES (?, ?, ?, ?, NOW())"
            );
            $mirror->bind_param(
              'isss',
              $newUserId,
              $first,
              $last,
              $email
            );
            $mirror->execute();
            $mirror->close();
            // ───────────────────────────────────────────────

            header('Location: login.php?registered=1');
            exit;
        } else {
            $errors[] = 'Registration failed: ' . $stmt->error;
        }
        $stmt->close();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign Up | MMAFIA</title>
  <!-- your CSS & fonts here -->
</head>
<body>

  <!-- Signup Section -->
  <section class="section-login">
    <h2 class="section-custom__title">Sign up to start tracking</h2>

    <?php if (!empty($errors)): ?>
      <div class="login-custom__errors">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="login-custom__form">
      <!-- your inputs here… -->
            <div class="form-custom__group">
        <label for="firstName" class="form-custom__label">First Name</label>
        <input
          type="text"
          id="firstName"
          name="First_Name"
          class="form-custom__input"
          required
          value="<?= htmlspecialchars($_POST['First_Name'] ?? '', ENT_QUOTES) ?>"
        >
      </div>

      <div class="form-custom__group">
        <label for="lastName" class="form-custom__label">Last Name</label>
        <input
          type="text"
          id="lastName"
          name="Last_Name"
          class="form-custom__input"
          required
          value="<?= htmlspecialchars($_POST['Last_Name'] ?? '', ENT_QUOTES) ?>"
        >
      </div>

      <div class="form-custom__group">
        <label for="email" class="form-custom__label">Email</label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-custom__input"
          placeholder="Enter your email"
          required
          value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"
        >
      </div>

      <div class="form-custom__group">
        <label for="phone" class="form-custom__label">Phone</label>
        <input
          type="tel"
          id="phone"
          name="phone"
          class="form-custom__input"
          placeholder="Optional"
          value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES) ?>"
        >
      </div>

      <div class="form-custom__group">
        <label for="password" class="form-custom__label">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          class="form-custom__input"
          placeholder="Enter password"
          required
        >
      </div>

      <div class="form-custom__group">
        <label for="confirmPassword" class="form-custom__label">Confirm Password</label>
        <input
          type="password"
          id="confirmPassword"
          name="confirmPassword"
          class="form-custom__input"
          placeholder="Confirm password"
          required
        >
      <div class="form-custom__actions">
        <button type="submit" class="membership-custom__btn">Sign Up</button>
        <a href="login.php" class="membership-custom__btn">Back to Login</a>
      </div>
    </form>
  </section>

</body>
</html>
