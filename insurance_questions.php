<?php
require __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Insurance Conversation Starters</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style/css/style.css?v=1.4.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
</head>
<body class="account-page">

<button id="sidebarToggle" class="sidebar-toggle">☰</button>

<nav id="sidebar" class="sidebar">
  <ul>
    <li><a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
    <li><a href="log_sale.php"><i class="fa-solid fa-circle-plus"></i> Log Sale</a></li>
    <li><a href="account.php"><i class="fa-solid fa-user-cog"></i> Account</a></li>
    <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
  </ul>
</nav>

<div id="mainContent" class="main-content">
  <header>
    <h1>Insurance Conversation Starters</h1>
  </header>

  <section class="form-container">
    
    <div class="question-boxes">

      <div class="question-box">“What do you use your phone for most — work, family, or both?”</div>
      <div class="question-box">“Have you ever had a phone accident before — cracked screen or water damage?”</div>
      <div class="question-box">“Would being without your phone for a few days cause any issues?”</div>
      <div class="question-box">“How long do you usually keep your phones before upgrading?”</div>
      <div class="question-box">“Do you have anything to protect your new phone yet?”</div>
      <div class="question-box">“If anything happened to this phone tomorrow, what would you do?”</div>
      <div class="question-box">“Would you want a replacement the next day — or would you be okay waiting a few weeks?”</div>
    </div>

    <p style="margin-top: 2rem;"><a href="index.php" class="btn">← Back to Dashboard</a></p>
  </section>
</div>

<script>
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const mainCont = document.getElementById('mainContent');

  toggleBtn.addEventListener('click', () => {
    const open = sidebar.classList.toggle('open');
    mainCont.classList.toggle('shifted');
    toggleBtn.textContent = open ? '✖' : '☰';
  });
</script>
</body>
</html>
