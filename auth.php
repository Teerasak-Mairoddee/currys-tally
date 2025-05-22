<?php
// auth.php
session_start();

// If no user is logged in, redirect immediately
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
