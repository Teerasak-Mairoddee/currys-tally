<?php
// login.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/db_conn.php'; // provides $conn

    // 1) Trim & validate inputs
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    if (!$email) {
        $errors[] = 'Please enter a valid email.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    // 2) Attempt login if no input errors
    if (empty($errors)) {
        function try_login($conn, $email, $password, $table, $roleName, $redirect) {
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
            $result = $stmt->get_result();
            $stmt->close();

            if ($result && $row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    // Set session
                    $_SESSION['user_id']    = $row['user_id'];
                    $_SESSION['First_Name'] = $row['First_Name'];
                    $_SESSION['Last_Name']  = $row['Last_name'];
                    $_SESSION['email']      = $row['email'];
                    $_SESSION['role']       = $roleName;
                    header('Location: ' . $redirect);
                    exit;
                }
            }
            return false;
        }

        // Admin first
        if (! try_login($conn, $email, $password, 'admins', 'admin', 'admin_index.php')) {
            // Regular user next
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
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login | MMAFIA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=123" />
</head>
<body>

     <!-- Navbar Removed add once refined on index-->

    

  

   

    <!-- Login Section -->
    <section class="section-custom section-login">
      <h2 class="section-custom__title">Login to start tracking</h2>

      <?php if (!empty($errors)): ?>
        <div class="login-custom__errors">
          <ul>
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" class="login-custom__form">
        <div class="form-custom__group">
          <label for="inputEmail" class="form-custom__label">Email</label>
          <input
            type="email"
            id="inputEmail"
            name="email"
            class="form-custom__input"
            placeholder="Enter your email"
            required
            value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"
          />
        </div>

        <div class="form-custom__group">
          <label for="inputPassword" class="form-custom__label">Password</label>
          <input
            type="password"
            id="inputPassword"
            name="password"
            class="form-custom__input"
            placeholder="Enter password"
            required
          />
        </div>

        <div class="form-custom__actions">
          <button type="submit" class="membership-custom__btn">Login</button>
          <a href="./register.php" class="membership-custom__btn">Register</a>
        </div>
      </form>
      
    </section>
 
</body>
</html>
