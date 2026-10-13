<?php

$base = '../';
include '../connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$errors = array();
$edit = array('id' => 0, 'title' => '', 'author' => '', 'category' => '', 'copies' => 1);

if (isset($_GET['edit'])) {
    $result = run_query($conn, "SELECT * FROM books WHERE id = ?", "i", array((int)$_GET['edit']));
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        $edit = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($_POST['action'] == 'delete') {
        $id = (int)$_POST['id'];
        $row = mysqli_fetch_assoc(run_query($conn, "SELECT COUNT(*) AS n FROM loans WHERE book_id = ?", "i", array($id)));
        if ($row['n'] > 0) {
            
            set_message("danger", "This book has loan records and cannot be deleted.");
        } else {
            run_query($conn, "DELETE FROM books WHERE id = ?", "i", array($id));
            set_message("success", "Book deleted.");
        }
        header("Location: books.php");
        exit;
    }

    $edit = array(
        'id'       => (int)$_POST['id'],
        'title'    => trim($_POST['title']),
        'author'   => trim($_POST['author']),
        'category' => trim($_POST['category']),
        'copies'   => $_POST['copies'],
    );

    if ($edit['title'] == "") {
        $errors[] = "Title is required.";
    }
    if ($edit['author'] == "") {
        $errors[] = "Author is required.";
    }
    $copies = filter_var($edit['copies'], FILTER_VALIDATE_INT);
    if ($copies === false || $copies < 1) {
        $errors[] = "Copies must be a whole number, 1 or more.";
    }

    if (count($errors) == 0 && $edit['id'] > 0) {
        $row = mysqli_fetch_assoc(run_query($conn, "SELECT COUNT(*) AS n FROM loans WHERE book_id = ? AND returned_on IS NULL", "i", array($edit['id'])));
        if ($copies < $row['n']) {
            $errors[] = "Copies cannot be lower than the books currently borrowed.";
        }
    }

    if (count($errors) == 0) {
        if ($edit['id'] > 0) {
            run_query($conn, "UPDATE books SET title = ?, author = ?, category = ?, copies = ? WHERE id = ?",
                      "sssii", array($edit['title'], $edit['author'], $edit['category'], $copies, $edit['id']));
        } else {
            run_query($conn, "INSERT INTO books (title, author, category, copies) VALUES (?, ?, ?, ?)",
                      "sssi", array($edit['title'], $edit['author'], $edit['category'], $copies));
        }
        set_message("success", "Book saved.");
        header("Location: books.php");
        exit;
    }
}

$titles = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM books"));
$copies_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(copies), 0) AS n FROM books"));
$borrowed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM loans WHERE returned_on IS NULL"));
$overdue = mysqli_fetch_assoc(run_query($conn, "SELECT COUNT(*) AS n FROM loans WHERE returned_on IS NULL AND due_on < ?", "s", array(date('Y-m-d'))));

$q = isset($_GET['q']) ? trim($_GET['q']) : "";
$like = "%" . $q . "%";
$books = run_query($conn,
    "SELECT b.*, b.copies - (SELECT COUNT(*) FROM loans l WHERE l.book_id = b.id AND l.returned_on IS NULL) AS available
     FROM books b
     WHERE b.title LIKE ? OR b.author LIKE ? OR b.category LIKE ?
     ORDER BY b.title",
    "sss", array($like, $like, $like));

$title = "Books";
include '../header.php';
?>
<h1 class="h3 mb-3">Books</h1>

<section class="row g-3 mb-4">
  <div class="col-6 col-md-3"><div class="card stat"><div class="card-body"><div class="text-muted small">Book titles</div><div class="num"><?php echo $titles['n']; ?></div></div></div></div>
  <div class="col-6 col-md-3"><div class="card stat"><div class="card-body"><div class="text-muted small">Copies on shelf</div><div class="num"><?php echo $copies_total['n'] - $borrowed['n']; ?></div></div></div></div>
  <div class="col-6 col-md-3"><div class="card stat"><div class="card-body"><div class="text-muted small">Borrowed now</div><div class="num"><?php echo $borrowed['n']; ?></div></div></div></div>
  <div class="col-6 col-md-3"><div class="card stat"><div class="card-body"><div class="text-muted small">Overdue</div><div class="num danger"><?php echo $overdue['n']; ?></div></div></div></div>
</section>

<section class="card mb-4" id="edit-book">
  <div class="card-header"><?php echo $edit['id'] > 0 ? 'Edit book' : 'Add a book'; ?></div>
  <div class="card-body">
    <?php foreach ($errors as $err) { ?>
      <div class="alert alert-danger py-2"><?php echo e($err); ?></div>
    <?php } ?>
    <form method="POST" class="row g-2 needs-validation" novalidate>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>">
      <div class="col-sm-4">
        <input type="text" name="title" class="form-control" placeholder="Title"<?php echo $edit['id'] > 0 ? ' autofocus' : ''; ?> value="<?php echo e($edit['title']); ?>" required>
      </div>
      <div class="col-sm-3">
        <input type="text" name="author" class="form-control" placeholder="Author" value="<?php echo e($edit['author']); ?>" required>
      </div>
      <div class="col-sm-2">
        <input type="text" name="category" class="form-control" placeholder="Category" value="<?php echo e($edit['category']); ?>">
      </div>
      <div class="col-sm-1">
        <input type="number" name="copies" class="form-control" placeholder="Copies" min="1" value="<?php echo e($edit['copies']); ?>" required>
      </div>
      <div class="col-sm-2">
        <button type="submit" class="btn btn-primary w-100">Save</button>
      </div>
    </form>
    <?php if ($edit['id'] > 0) { ?><a href="books.php" class="small">Cancel editing</a><?php } ?>
  </div>
</section>

<section>
  <form class="d-flex mb-3" method="GET">
    <input class="form-control me-2" type="search" name="q" placeholder="Search title, author, or category" value="<?php echo e($q); ?>">
    <button type="submit" class="btn btn-outline-primary">Search</button>
  </form>

  <?php if (mysqli_num_rows($books) == 0) { ?>
    <div class="alert alert-info">No books found.</div>
  <?php } else { ?>
  <div class="table-responsive">
    <table class="table align-middle bg-white">
      <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Available / Copies</th><th></th></tr></thead>
      <tbody>
      <?php while ($b = mysqli_fetch_assoc($books)) { ?>
        <tr>
          <td><strong><?php echo e($b['title']); ?></strong></td>
          <td><?php echo e($b['author']); ?></td>
          <td><?php echo e($b['category']); ?></td>
          <td><?php echo (int)$b['available']; ?> / <?php echo (int)$b['copies']; ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary btn-icon" href="books.php?edit=<?php echo $b['id']; ?>#edit-book" title="Edit" aria-label="Edit <?php echo e($b['title']); ?>" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></a>
            <form method="POST" class="d-inline confirm-delete" data-message="<?php echo e('Delete the book "' . $b['title'] . '" by ' . $b['author'] . '? This cannot be undone.'); ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Delete" aria-label="Delete <?php echo e($b['title']); ?>" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>
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
