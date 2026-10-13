<?php
include 'connection.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$uid = (int)$_SESSION['user_id'];
$profile_errors = array();
$password_errors = array();

$result = run_query($conn, "SELECT id, name, email, phone, password, created_at FROM users WHERE id = ?", "i", array($uid));
$user = mysqli_fetch_assoc($result);
if (!$user) {
    header("Location: logout.php");
    exit;
}

$form = array('name' => $user['name'], 'email' => $user['email'], 'phone' => (string)$user['phone']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($_POST['action'] == 'profile') {
        $form = array(
            'name'  => trim($_POST['name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone']),
        );

        if ($form['name'] == "") {
            $profile_errors[] = "Name is required.";
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $profile_errors[] = "Enter a valid email.";
        }
        if ($form['phone'] != "" && !valid_phone($form['phone'])) {
            $profile_errors[] = "Phone number must be 11 digits and start with 09 (example: 09171234567).";
        }
        if (count($profile_errors) == 0) {
            $dup = run_query($conn, "SELECT id FROM users WHERE email = ? AND id <> ?", "si", array($form['email'], $uid));
            if (mysqli_num_rows($dup) > 0) {
                $profile_errors[] = "That email is already used by another account.";
            }
        }
        if (count($profile_errors) == 0) {
            $phone = ($form['phone'] == "") ? null : $form['phone'];
            run_query($conn, "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?",
                      "sssi", array($form['name'], $form['email'], $phone, $uid));
            $_SESSION['name'] = $form['name'];
            set_message("success", "Profile updated.");
            header("Location: profile.php");
            exit;
        }
    }

    if ($_POST['action'] == 'password') {
        $current = $_POST['current'];
        $new     = $_POST['password'];
        $confirm = $_POST['confirm'];

        if (!password_verify($current, $user['password'])) {
            $password_errors[] = "Current password is wrong.";
        }
        if (strlen($new) < 6) {
            $password_errors[] = "New password must be at least 6 characters.";
        }
        if ($new != $confirm) {
            $password_errors[] = "New passwords do not match.";
        }
        if (count($password_errors) == 0 && $current === $new) {
            $password_errors[] = "New password must be different from the current one.";
        }
        if (count($password_errors) == 0) {
            run_query($conn, "UPDATE users SET password = ? WHERE id = ?",
                      "si", array(password_hash($new, PASSWORD_DEFAULT), $uid));
            set_message("success", "Password changed.");
            header("Location: profile.php");
            exit;
        }
    }
}

$stats = mysqli_fetch_assoc(run_query($conn,
    "SELECT COUNT(*) AS total, SUM(returned_on IS NULL) AS active FROM loans WHERE user_id = ?", "i", array($uid)));

$title = "My Profile";
include 'header.php';
?>
<h1 class="h3 mb-3">My profile</h1>

<div class="row g-4">
  <div class="col-lg-6">
    <section class="card">
      <div class="card-header">Account details</div>
      <div class="card-body">
        <?php foreach ($profile_errors as $err) { ?>
          <div class="alert alert-danger py-2"><?php echo e($err); ?></div>
        <?php } ?>
        <form method="POST" class="needs-validation" novalidate>
          <input type="hidden" name="action" value="profile">
          <div class="mb-3">
            <label class="form-label">Full name</label>
            <input type="text" name="name" class="form-control" value="<?php echo e($form['name']); ?>" required>
            <div class="invalid-feedback">Name is required.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo e($form['email']); ?>" required>
            <div class="invalid-feedback">Enter a valid email.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone number <small class="text-muted">(optional)</small></label>
            <input type="text" name="phone" class="form-control phone-input" value="<?php echo e($form['phone']); ?>"
                   placeholder="09171234567" inputmode="numeric" maxlength="11" pattern="09[0-9]{9}">
            <div class="invalid-feedback">Must be 11 digits and start with 09.</div>
          </div>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </form>
        <hr>
        <div class="small text-muted">
          Role: <?php echo e(ucfirst($_SESSION['role'])); ?> &middot;
          Member since <?php echo e(date('M j, Y', strtotime($user['created_at']))); ?>
          <?php if ($_SESSION['role'] == 'member') { ?>
            &middot; <?php echo (int)$stats['active']; ?> borrowed now, <?php echo (int)$stats['total']; ?> loans in total
          <?php } ?>
        </div>
      </div>
    </section>
  </div>

  <div class="col-lg-6">
    <section class="card">
      <div class="card-header">Change password</div>
      <div class="card-body">
        <?php foreach ($password_errors as $err) { ?>
          <div class="alert alert-danger py-2"><?php echo e($err); ?></div>
        <?php } ?>
        <form method="POST" class="needs-validation" novalidate>
          <input type="hidden" name="action" value="password">
          <div class="mb-3">
            <label class="form-label">Current password</label>
            <input type="password" name="current" class="form-control" required>
            <div class="invalid-feedback">Please enter your current password.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">New password</label>
            <input type="password" name="password" class="form-control" minlength="6" required>
            <div class="invalid-feedback">At least 6 characters.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm new password</label>
            <input type="password" name="confirm" class="form-control" required>
            <div class="invalid-feedback">Passwords must match.</div>
          </div>
          <button type="submit" class="btn btn-primary">Change password</button>
        </form>
      </div>
    </section>
  </div>
</div>
<?php include 'footer.php'; ?>
