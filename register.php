<?php
include 'connection.php';

$errors = array();
$name = "";
$email = "";
$phone = "";

if (isset($_POST['register'])) {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $pass    = $_POST['password'];
    $confirm = $_POST['confirm'];

  
    if ($name == "") {
        $errors[] = "Name is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email.";
    }
    if ($phone != "" && !valid_phone($phone)) {
        $errors[] = "Phone number must be 11 digits and start with 09 (example: 09171234567).";
    }
    if (strlen($pass) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($pass != $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (count($errors) == 0) {
        $result = run_query($conn, "SELECT id FROM users WHERE email = ?", "s", array($email));
        if (mysqli_num_rows($result) > 0) {
            $errors[] = "That email is already registered.";
        }
    }

    if (count($errors) == 0) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        run_query($conn, "INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, 'member', ?)",
                  "ssss", array($name, $email, $hash, $phone));
        set_message("success", "Account created. You can log in now.");
        header("Location: login.php");
        exit;
    }
}

$title = "Register";
include 'header.php';
?>
<section class="row justify-content-center">
  <div class="col-md-7 col-lg-5">
    <h1 class="h3 mb-3 text-center">Create a member account</h1>
    <div class="card"><div class="card-body p-4">
      <?php foreach ($errors as $err) { ?>
        <div class="alert alert-danger py-2"><?php echo e($err); ?></div>
      <?php } ?>
      <form method="POST" class="needs-validation" novalidate>
        <div class="mb-3">
          <label class="form-label">Full name</label>
          <input type="text" name="name" class="form-control" value="<?php echo e($name); ?>" required>
          <div class="invalid-feedback">Please enter your name.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?php echo e($email); ?>" required>
          <div class="invalid-feedback">Please enter a valid email.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Phone number <small class="text-muted">(optional)</small></label>
          <input type="text" name="phone" class="form-control phone-input" value="<?php echo e($phone); ?>"
                 placeholder="09171234567" inputmode="numeric" maxlength="11" pattern="09[0-9]{9}">
          <div class="invalid-feedback">Must be 11 digits and start with 09.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" minlength="6" required>
          <div class="invalid-feedback">At least 6 characters.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Confirm password</label>
          <input type="password" name="confirm" class="form-control" required>
          <div class="invalid-feedback">Passwords must match.</div>
        </div>
        <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
      </form>
    </div></div>
  </div>
</section>
<?php include 'footer.php'; ?>
