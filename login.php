<?php
include 'connection.php';
if (isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    $result = run_query($conn, "SELECT * FROM users WHERE email = ?", "s", array($email));
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($pass, $user['password'])) {
        session_regenerate_id(true);               
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Wrong email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body class="login-body">

  <header class="login-topbar">
    <div class="inner">
      <a class="brand" href="login.php">
        <img src="img/pcnl.png" alt="PCNL logo">
        <span>
          <div class="t1">Yearning Library</div>
          <div class="t2">Philippine College of Northwestern Luzon</div>
        </span>
      </a>
      <div class="dropdown">
        <button class="guest-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">Guest</button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="login.php">Login</a></li>
          <li><a class="dropdown-item" href="register.php">Create an account</a></li>
        </ul>
      </div>
    </div>
  </header>

  <div class="login-split">

    <section class="login-hero">
      <div>
        <img src="img/pcnl.png" alt="PCNL logo">
        <h2>Yearning Library Management System</h2>
        <p>Philippine College of Northwestern Luzon</p>
        <p>San Antonio, Agoo, La Union</p>
      </div>
    </section>

    <section class="login-side">
      <div>
        <div class="login-box">
          <h1>Sign in</h1>

          <?php if (isset($_SESSION['message'])) { ?>
            <div class="alert alert-<?php echo e($_SESSION['message_type']); ?> py-2"><?php echo e($_SESSION['message']); ?></div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
          <?php } ?>
          <?php if ($error != "") { ?>
            <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
          <?php } ?>

          <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" placeholder="Email address" required>
              <div class="invalid-feedback">Please enter your email.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" placeholder="Password" required>
              <div class="invalid-feedback">Please enter your password.</div>
            </div>
            <button type="submit" name="login" class="btn btn-login w-100">Log in</button>
          </form>
        </div>

        <div class="login-extra text-center mt-3">
          <p class="mb-2">New member? <a href="register.php">Create an account</a></p>
          <p class="mb-0">In case of emergency,<br>Contact: <a href="mailto:charlesdevera1022@gmail.com">charlesdevera1022@gmail.com</a><br>09621322353</p>
        </div>
      </div>
    </section>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/app.js"></script>
</body>
</html>
