<?php
include 'connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'member') {
    header("Location: login.php");
    exit;
}

$loans = run_query($conn,
    "SELECT l.*, b.title, b.author
     FROM loans l JOIN books b ON b.id = l.book_id
     WHERE l.user_id = ?
     ORDER BY (l.returned_on IS NULL) DESC, l.due_on DESC",
    "i", array($_SESSION['user_id']));

$title = "My Loans";
include 'header.php';
?>
<section>
  <h1 class="h3 mb-3">My loans</h1>

  <?php if (mysqli_num_rows($loans) == 0) { ?>
    <div class="alert alert-info">You have not borrowed any books yet. <a href="books.php">Browse books</a>.</div>
  <?php } else { ?>
  <div class="table-responsive">
    <table class="table align-middle bg-white">
      <thead><tr><th>Book</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Status</th></tr></thead>
      <tbody>
      <?php while ($l = mysqli_fetch_assoc($loans)) { ?>
        <tr>
          <td><strong><?php echo e($l['title']); ?></strong><br><small class="text-muted"><?php echo e($l['author']); ?></small></td>
          <td><?php echo e($l['borrowed_on']); ?></td>
          <td><?php echo e($l['due_on']); ?></td>
          <td><?php echo $l['returned_on'] === null ? '-' : e($l['returned_on']); ?></td>
          <td><?php echo loan_badge($l); ?></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
  <small class="text-muted">Return books to the librarian. The admin marks them as returned.</small>
  <?php } ?>
</section>
<?php include 'footer.php'; ?>
