<?php

$base = '../';
include '../connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $today = date('Y-m-d');

    run_query($conn, "UPDATE loans SET returned_on = ? WHERE id = ? AND returned_on IS NULL", "si", array($today, $id));
    $ok = mysqli_affected_rows($conn) > 0;
    $message = $ok ? "Book marked as returned." : "This loan is already returned.";

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'fetch') {
        header('Content-Type: application/json');
        echo json_encode(array('ok' => $ok, 'message' => $message, 'returned_on' => $today));
        exit;
    }
    set_message($ok ? "success" : "danger", $message);
    header("Location: loans.php");
    exit;
}

$status = isset($_GET['status']) ? $_GET['status'] : 'active';

$sql = "SELECT l.*, b.title, u.name AS member
        FROM loans l JOIN books b ON b.id = l.book_id JOIN users u ON u.id = l.user_id";
$types = "";
$params = array();

if ($status == 'active') {
    $sql .= " WHERE l.returned_on IS NULL";
} elseif ($status == 'overdue') {
    $sql .= " WHERE l.returned_on IS NULL AND l.due_on < ?";
    $types = "s";
    $params[] = date('Y-m-d');
} elseif ($status == 'returned') {
    $sql .= " WHERE l.returned_on IS NOT NULL";
}
$sql .= " ORDER BY l.due_on";
$loans = run_query($conn, $sql, $types, $params);

$tabs = array('active' => 'Borrowed', 'overdue' => 'Overdue', 'returned' => 'Returned', 'all' => 'All');

$title = "Loans";
include '../header.php';
?>
<section>
  <h1 class="h3 mb-3">Loans</h1>

  <ul class="nav nav-pills mb-3">
    <?php foreach ($tabs as $key => $label) { ?>
      <li class="nav-item"><a class="nav-link <?php echo $status == $key ? 'active' : ''; ?>" href="loans.php?status=<?php echo $key; ?>"><?php echo $label; ?></a></li>
    <?php } ?>
  </ul>

  <?php if (mysqli_num_rows($loans) == 0) { ?>
    <div class="alert alert-info">No loans found.</div>
  <?php } else { ?>
  <div class="table-responsive">
    <table class="table align-middle bg-white">
      <thead><tr><th>Book</th><th>Member</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php while ($l = mysqli_fetch_assoc($loans)) { ?>
        <tr>
          <td><?php echo e($l['title']); ?></td>
          <td><?php echo e($l['member']); ?></td>
          <td><?php echo e($l['borrowed_on']); ?></td>
          <td><?php echo e($l['due_on']); ?></td>
          <td class="returned-cell"><?php echo $l['returned_on'] === null ? '-' : e($l['returned_on']); ?></td>
          <td class="status-cell"><?php echo loan_badge($l); ?></td>
          <td class="text-end">
            <?php if ($l['returned_on'] === null) { ?>
              <form method="POST" class="return-form">
                <input type="hidden" name="id" value="<?php echo $l['id']; ?>">
                <button type="submit" class="btn btn-sm btn-success">Returned</button>
              </form>
            <?php } ?>
          </td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
  <?php } ?>
</section>
<?php include '../footer.php'; ?>
