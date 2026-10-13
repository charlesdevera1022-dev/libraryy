<?php
include 'connection.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
} elseif ($_SESSION['role'] == 'admin') {
    header("Location: admin/dashboard.php");
} else {
    header("Location: books.php");
}
exit;
?>
