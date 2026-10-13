<?php
if (!isset($base)) { $base = ''; }
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

function nav_link($base, $page, $label) {
    $active = (substr($_SERVER['SCRIPT_NAME'], -strlen($page)) === $page) ? ' active' : '';
    echo '<li class="nav-item"><a class="nav-link' . $active . '" href="' . $base . $page . '">' . $label . '</a></li>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo e(isset($title) ? $title : 'Library'); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?php echo $base; ?>assets/style.css" rel="stylesheet">
</head>
<body>
<header>
  <nav class="navbar navbar-expand-md navbar-dark">
    <div class="container">
      <a class="navbar-brand" href="<?php echo $base; ?>index.php">
        <img src="<?php echo $base; ?>img/pcnl.png" alt="PCNL logo">
        <span>
          <span class="t1">Reading While Yearning Library</span>
          <span class="t2">Philippine College of Northwestern Luzon</span>
        </span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="menu">
        <ul class="navbar-nav me-auto">
          <?php if ($role == 'admin') { ?>
            <?php nav_link($base, 'admin/dashboard.php', 'Dashboard'); ?>
            <?php nav_link($base, 'admin/books.php', 'Books'); ?>
            <?php nav_link($base, 'admin/loans.php', 'Loans'); ?>
            <?php nav_link($base, 'admin/members.php', 'Members'); ?>
            <?php nav_link($base, 'profile.php', 'Profile'); ?>
          <?php } elseif ($role == 'member') { ?>
            <?php nav_link($base, 'books.php', 'Books'); ?>
            <?php nav_link($base, 'my_loans.php', 'My Loans'); ?>
            <?php nav_link($base, 'profile.php', 'Profile'); ?>
          <?php } ?>
        </ul>
        <ul class="navbar-nav">
          <?php if ($role != '') { ?>
            <li class="nav-item"><span class="nav-link">Hi kaYearner!, <?php echo e($_SESSION['name']); ?></span></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>logout.php">Logout</a></li>
          <?php } else { ?>
            <?php nav_link($base, 'login.php', 'Login'); ?>
            <?php nav_link($base, 'register.php', 'Register'); ?>
          <?php } ?>
        </ul>
      </div>
    </div>
  </nav>
</header>
<main class="container py-4">
<?php
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-' . e($_SESSION['message_type']) . '">' . e($_SESSION['message']) . '</div>';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>
