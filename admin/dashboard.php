<?php
$base = '../';
include '../connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$today = date('Y-m-d');
$soon  = date('Y-m-d', strtotime('+3 days'));

$titles   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM books"));
$copies   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(copies), 0) AS n FROM books"));
$members  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users WHERE role = 'member'"));
$borrowed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM loans WHERE returned_on IS NULL"));
$overdue_count = mysqli_fetch_assoc(run_query($conn,
    "SELECT COUNT(*) AS n FROM loans WHERE returned_on IS NULL AND due_on < ?", "s", array($today)));

$overdue = run_query($conn,
    "SELECT l.id, l.due_on, DATEDIFF(?, l.due_on) AS days_late, b.title, u.name AS member, u.phone
     FROM loans l JOIN books b ON b.id = l.book_id JOIN users u ON u.id = l.user_id
     WHERE l.returned_on IS NULL AND l.due_on < ?
     ORDER BY l.due_on",
    "ss", array($today, $today));

$due_soon = run_query($conn,
    "SELECT l.due_on, b.title, u.name AS member
     FROM loans l JOIN books b ON b.id = l.book_id JOIN users u ON u.id = l.user_id
     WHERE l.returned_on IS NULL AND l.due_on >= ? AND l.due_on <= ?
     ORDER BY l.due_on",
    "ss", array($today, $soon));

$recent = run_query($conn,
    "SELECT l.borrowed_on, l.returned_on, b.title, u.name AS member
     FROM loans l JOIN books b ON b.id = l.book_id JOIN users u ON u.id = l.user_id
     ORDER BY l.id DESC LIMIT 5");

$title = "Dashboard";
include '../header.php';
?>
<h1 class="h3 mb-3">Dashboard</h1>

<section class="row g-3 mb-4">
  <div class="col-6 col-md"><div class="card stat"><div class="card-body"><div class="text-muted small">Book titles</div><div class="num"><?php echo (int)$titles['n']; ?></div></div></div></div>
  <div class="col-6 col-md"><div class="card stat"><div class="card-body"><div class="text-muted small">Copies on shelf</div><div class="num"><?php echo (int)($copies['n'] - $borrowed['n']); ?></div></div></div></div>
  <div class="col-6 col-md"><div class="card stat"><div class="card-body"><div class="text-muted small">Borrowed now</div><div class="num"><?php echo (int)$borrowed['n']; ?></div></div></div></div>
  <div class="col-6 col-md"><div class="card stat"><div class="card-body"><div class="text-muted small">Overdue</div><div class="num danger"><?php echo (int)$overdue_count['n']; ?></div></div></div></div>
  <div class="col-12 col-md"><div class="card stat"><div class="card-body"><div class="text-muted small">Members</div><div class="num"><?php echo (int)$members['n']; ?></div></div></div></div>
</section>

<section class="mb-4">
  <h2 class="h5 mb-2">Overdue books</h2>
  <?php if (mysqli_num_rows($overdue) == 0) { ?>
    <div class="alert alert-success">No overdue books. Everything is on time.</div>
  <?php } else { ?>
  <div class="table-responsive">
    <table class="table align-middle bg-white">
      <thead><tr><th>Book</th><th>Member</th><th>Phone</th><th>Due on</th><th>Days late</th></tr></thead>
      <tbody>
      <?php while ($o = mysqli_fetch_assoc($overdue)) { ?>
        <tr>
          <td><strong><?php echo e($o['title']); ?></strong></td>
          <td><?php echo e($o['member']); ?></td>
          <td><?php echo $o['phone'] ? e($o['phone']) : '<span class="text-muted">-</span>'; ?></td>
          <td><?php echo e($o['due_on']); ?></td>
          <td><span class="badge bg-danger"><?php echo (int)$o['days_late']; ?> day<?php echo $o['days_late'] == 1 ? '' : 's'; ?></span></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
  <p class="small mt-2"><a href="loans.php?status=overdue">Go to Loans to mark returns</a></p>
  <?php } ?>
</section>

<section class="row g-4">
  <div class="col-lg-6">
    <h2 class="h5 mb-2">Due in the next 3 days</h2>
    <?php if (mysqli_num_rows($due_soon) == 0) { ?>
      <div class="alert alert-info">Nothing due soon.</div>
    <?php } else { ?>
    <div class="table-responsive">
      <table class="table align-middle bg-white">
        <thead><tr><th>Book</th><th>Member</th><th>Due on</th></tr></thead>
        <tbody>
        <?php while ($d = mysqli_fetch_assoc($due_soon)) { ?>
          <tr>
            <td><?php echo e($d['title']); ?></td>
            <td><?php echo e($d['member']); ?></td>
            <td><?php echo e($d['due_on']); ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <?php } ?>
  </div>

  <div class="col-lg-6">
    <h2 class="h5 mb-2">Recent activity</h2>
    <?php if (mysqli_num_rows($recent) == 0) { ?>
      <div class="alert alert-info">No loans yet.</div>
    <?php } else { ?>
    <div class="table-responsive">
      <table class="table align-middle bg-white">
        <thead><tr><th>Book</th><th>Member</th><th>Borrowed</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = mysqli_fetch_assoc($recent)) { ?>
          <tr>
            <td><?php echo e($r['title']); ?></td>
            <td><?php echo e($r['member']); ?></td>
            <td><?php echo e($r['borrowed_on']); ?></td>
            <td><?php echo $r['returned_on'] ? '<span class="badge bg-secondary">Returned</span>' : '<span class="badge bg-primary">Borrowed</span>'; ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <?php } ?>
  </div>
</section>
<?php include '../footer.php'; ?>
