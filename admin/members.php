<?php
$base = '../';
include '../connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$errors = array();
$edit = null;

if (isset($_GET['edit'])) {
    $result = run_query($conn, "SELECT id, name, email, phone FROM users WHERE id = ? AND role = 'member'", "i", array((int)$_GET['edit']));
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        $edit = $row;
        $edit['phone'] = (string)$edit['phone'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($_POST['action'] == 'delete') {
        run_query($conn, "DELETE FROM users WHERE id = ? AND role = 'member'", "i", array((int)$_POST['id']));
        set_message("success", "Member deleted.");
        header("Location: members.php");
        exit;
    }

    $edit = array(
        'id'    => (int)$_POST['id'],
        'name'  => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
    );

    if ($edit['name'] == "") {
        $errors[] = "Name is required.";
    }
    if (!filter_var($edit['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email.";
    }
    if ($edit['phone'] != "" && !valid_phone($edit['phone'])) {
        $errors[] = "Phone number must be 11 digits and start with 09 (example: 09171234567).";
    }

    if (count($errors) == 0) {
        $dup = run_query($conn, "SELECT id FROM users WHERE email = ? AND id <> ?", "si", array($edit['email'], $edit['id']));
        if (mysqli_num_rows($dup) > 0) {
            $errors[] = "That email is already used by another account.";
        }
    }

    if (count($errors) == 0) {
        $phone = ($edit['phone'] == "") ? null : $edit['phone'];
        run_query($conn, "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = 'member'",
                  "sssi", array($edit['name'], $edit['email'], $phone, $edit['id']));
        set_message("success", "Member updated.");
        header("Location: members.php");
        exit;
    }
}

$q = isset($_GET['q']) ? trim($_GET['q']) : "";
$like = "%" . $q . "%";
$members = run_query($conn,
    "SELECT u.*,
            (SELECT COUNT(*) FROM loans l WHERE l.user_id = u.id AND l.returned_on IS NULL) AS borrowing,
            (SELECT COUNT(*) FROM loans l WHERE l.user_id = u.id) AS total_loans
     FROM users u
     WHERE u.role = 'member' AND (u.name LIKE ? OR u.email LIKE ?)
     ORDER BY u.name",
    "ss", array($like, $like));

$title = "Members";
include '../header.php';
?>
<section>
  <h1 class="h3 mb-3">Members</h1>

  <?php if ($edit) { ?>
  <div class="card mb-4" id="edit-member">
    <div class="card-header">Edit member</div>
    <div class="card-body">
      <?php foreach ($errors as $err) { ?>
        <div class="alert alert-danger py-2"><?php echo e($err); ?></div>
      <?php } ?>
      <form method="POST" class="row g-2 needs-validation" novalidate>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>">
        <div class="col-sm-3">
          <input type="text" name="name" class="form-control" placeholder="Full name" value="<?php echo e($edit['name']); ?>" required>
          <div class="invalid-feedback">Name is required.</div>
        </div>
        <div class="col-sm-4">
          <input type="email" name="email" class="form-control" placeholder="Email" value="<?php echo e($edit['email']); ?>" required>
          <div class="invalid-feedback">Enter a valid email.</div>
        </div>
        <div class="col-sm-3">
          <input type="text" name="phone" class="form-control phone-input" placeholder="09171234567" value="<?php echo e($edit['phone']); ?>"
                 inputmode="numeric" maxlength="11" pattern="09[0-9]{9}">
          <div class="invalid-feedback">Must be 11 digits and start with 09.</div>
        </div>
        <div class="col-sm-2">
          <button type="submit" class="btn btn-primary w-100">Save</button>
        </div>
      </form>
      <a href="members.php" class="small">Cancel editing</a>
    </div>
  </div>
  <?php } ?>

  <form class="d-flex mb-3" method="GET">
    <input class="form-control me-2" type="search" name="q" placeholder="Search name or email" value="<?php echo e($q); ?>">
    <button type="submit" class="btn btn-outline-primary">Search</button>
  </form>

  <?php if (mysqli_num_rows($members) == 0) { ?>
    <div class="alert alert-info">No members found.</div>
  <?php } else { ?>
  <div class="table-responsive">
    <table class="table align-middle bg-white">
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Borrowing now</th><th>All loans</th><th></th></tr></thead>
      <tbody>
      <?php while ($m = mysqli_fetch_assoc($members)) { ?>
        <tr>
          <td><?php echo e($m['name']); ?></td>
          <td><?php echo e($m['email']); ?></td>
          <td><?php echo e($m['phone']); ?></td>
          <td><?php echo (int)$m['borrowing']; ?></td>
          <td><?php echo (int)$m['total_loans']; ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary btn-icon" href="members.php?edit=<?php echo $m['id']; ?>#edit-member" title="Edit" aria-label="Edit <?php echo e($m['name']); ?>" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></a>
            <?php
              $msg = 'Delete member "' . $m['name'] . '" and their loan history?';
              if ($m['borrowing'] > 0) {
                  $msg .= ' They still have ' . (int)$m['borrowing'] . ' unreturned book(s).';
              }
              $msg .= ' This cannot be undone.';
            ?>
            <form method="POST" class="d-inline confirm-delete" data-message="<?php echo e($msg); ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Delete" aria-label="Delete <?php echo e($m['name']); ?>" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
  <?php } ?>
</section>
<?php include '../footer.php'; ?>
