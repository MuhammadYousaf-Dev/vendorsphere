<?php
include 'auth.php';

if ($_SESSION['role'] != 'Admin') {
    header("Location: dashboard.php");
    exit();
}
?>