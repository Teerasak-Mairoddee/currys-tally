<?php
session_start();
include __DIR__ . '/db_conn.php';  // gives $conn (mysqli)

// 1) Safely grab role (default to empty string)
$role = $_SESSION['role'] ?? '';

// 2) Guard: only logged-in admins or super-admins
if (empty($_SESSION['user_id']) || ! in_array($role, ['admin','super_admin'], true)) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied. You need admin privileges.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Panel | MMAFIA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=123" />
  <link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  integrity="sha512-papb1a47fV6j3D4xwP1ZY+..."
  crossorigin="anonymous"
/>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar-custom">
      <div class="navbar-custom__wrap">
        
        <a class="navbar-custom__brand" href="index.php">
          <img src="images/logo.png" alt="Company Logo" class="navbar-custom__logo">
        </a>
        <button class="navbar-custom__toggle" aria-label="Toggle menu">
          <span class="navbar-custom__toggle-icon"></span>
        </button>
        <div class="navbar-custom__menu">
          <ul class="navbar-custom__list">
            <li class="navbar-custom__item"><a class="navbar-custom__link" href="index.php">Home</a></li>
            <li class="navbar-custom__item"><a class="navbar-custom__link" href="./index.php#instructor-bio">Instructor Bio</a></li>
            <li class="navbar-custom__item"><a class="navbar-custom__link" href="./index.php#disciplines">Disciplines</a></li>
            <li class="navbar-custom__item"><a class="navbar-custom__link" href="./index.php#timetable">Timetable</a></li>
            <li class="navbar-custom__item"><a class="navbar-custom__link" href="./index.php#memberships">Memberships</a></li>
            <li class="navbar-custom__item"><a class="navbar-custom__link" href="./index.php#events">Events</a></li>
            <li class="navbar-custom__item"><a class="navbar-custom__link" href="./index.php#contact">Contact</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
              <li class="navbar-custom__item">
                <a class="navbar-custom__link" href="<?= htmlspecialchars($dashboard, ENT_QUOTES) ?>">
                  Hi, <?= htmlspecialchars($_SESSION['First_Name'], ENT_QUOTES) ?>
                </a>
              </li>
              <li class="navbar-custom__item"><a class="navbar-custom__link" href="logout.php">Logout</a></li>
            <?php else: ?>
              <li class="navbar-custom__item"><a class="navbar-custom__link" href="login.php">Login</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>

  <div class="main-container-admin-dashboard">


    <section id="admin-page" class="section-custom">

          <section id="admin-page" class="section-custom">
          <h2 class="section-custom__title">Admin Dashboard</h2>
          
          <div class="admin-tools-grid">
            <a href="generate_invite.php" class="admin-tool-card">
              <i class="fa fa-user-plus"></i>
              <span>Invite Admin</span>
            </a>
            <a href="admin_events.php" class="admin-tool-card">
              <i class="fa fa-calendar-alt"></i>
              <span>Manage Events</span>
            </a>
            <a href="admin_schedule.php" class="admin-tool-card">
              <i class="fa fa-clock"></i>
              <span>Manage Schedule</span>
            </a>
            <a href="admin_contact.php" class="admin-tool-card">
              <i class="fa fa-address-book"></i>
              <span>Manage Contact</span>
            </a>
            <a href="index.php" class="admin-tool-card">
              <i class="fa fa-home"></i>
              <span>Back to Home</span>
            </a>
          </div>
        </section>
        

    </section>

  </div>

</body>
</html>
