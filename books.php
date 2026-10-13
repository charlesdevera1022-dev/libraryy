<?php

include 'connection.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] != 'member') {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_id   = (int)$_POST['book_id'];
    $ok        = false;
    $message   = "";
    $available = 0;
    $due_on    = date('Y-m-d', strtotime('+' . LOAN_DAYS . ' days'));

    mysqli_begin_transaction($conn);

    $result = run_query($conn, "SELECT * FROM books WHERE id = ? FOR UPDATE", "i", array($book_id));
    $book = mysqli_fetch_assoc($result);

    if (!$book) {
        $message = "Book not found.";
    } else {
        $row = mysqli_fetch_assoc(run_query($conn, "SELECT COUNT(*) AS n FROM loans WHERE book_id = ? AND returned_on IS NULL", "i", array($book_id)));
        $copies_out = (int)$row['n'];

        $row = mysqli_fetch_assoc(run_query($conn, "SELECT COUNT(*) AS n FROM loans WHERE user_id = ? AND returned_on IS NULL", "i", array($user_id)));
        $my_loans = (int)$row['n'];

        $row = mysqli_fetch_assoc(run_query($conn, "SELECT COUNT(*) AS n FROM loans WHERE user_id = ? AND book_id = ? AND returned_on IS NULL", "ii", array($user_id, $book_id)));
        $already = (int)$row['n'];

        if ($copies_out >= $book['copies']) {
            $message = "No copies are available right now.";
        } elseif ($my_loans >= MAX_LOANS) {
            $message = "You can borrow up to " . MAX_LOANS . " books at a time. Return one first.";
        } elseif ($already > 0) {
            $message = "You already borrowed this book.";
        } else {
            run_query($conn, "INSERT INTO loans (book_id, user_id, borrowed_on, due_on) VALUES (?, ?, ?, ?)",
                      "iiss", array($book_id, $user_id, date('Y-m-d'), $due_on));
            $ok = true;
            $available = $book['copies'] - $copies_out - 1;
            $message = 'You borrowed "' . $book['title'] . '". Please return it by ' . $due_on . '.';
        }
    }

    if ($ok) {
        mysqli_commit($conn);
    } else {
        mysqli_rollback($conn);
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'fetch') {
        header('Content-Type: application/json');
        echo json_encode(array('ok' => $ok, 'message' => $message, 'available' => $available, 'due_on' => $due_on));
        exit;
    }
    set_message($ok ? "success" : "danger", $message);
    header("Location: books.php");
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : "";
$like = "%" . $q . "%";
$books = run_query($conn,
    "SELECT b.*, b.copies - (SELECT COUNT(*) FROM loans l WHERE l.book_id = b.id AND l.returned_on IS NULL) AS available
     FROM books b
     WHERE b.title LIKE ? OR b.author LIKE ? OR b.category LIKE ?
     ORDER BY b.title",
    "sss", array($like, $like, $like));

$mine = array();
$result = run_query($conn, "SELECT book_id, due_on FROM loans WHERE user_id = ? AND returned_on IS NULL", "i", array($user_id));
while ($row = mysqli_fetch_assoc($result)) {
    $mine[$row['book_id']] = $row['due_on'];
}

$title = "Books";
include 'header.php';
?>
<section>
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
    <h1 class="h3 mb-2">Yearner Books</h1>
    <form class="d-flex" method="GET">
      <input class="form-control me-2" type="search" name="q" placeholder="Search title, author, or category" value="<?php echo e($q); ?>">
      <button type="submit" class="btn btn-primary">Search</button>
    </form>
  </div>
  <p class="text-muted small mb-4">You can borrow up to <?php echo MAX_LOANS; ?> books at a time, for <?php echo LOAN_DAYS; ?> days each. Isuli mo a!!!</p>

  <?php if (mysqli_num_rows($books) == 0) { ?>
    <div class="alert alert-info">No books found.</div>
  <?php } else { ?>
  <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
    <?php while ($b = mysqli_fetch_assoc($books)) { ?>
      <div class="col">
        <article class="card book-card h-100">
          <div class="card-body d-flex">
            <div class="book-icon me-3"><?php echo e(first_letter($b['title'])); ?></div>
            <div>
              <h2 class="h6 mb-1"><?php echo e($b['title']); ?></h2>
              <div class="text-muted small"><?php echo e($b['author']); ?></div>
              <?php if ($b['category'] != "") { ?>
                <span class="badge bg-light text-dark border mt-2"><?php echo e($b['category']); ?></span>
              <?php } ?>
            </div>
          </div>
          <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <small class="text-muted"><span class="avail fw-semibold"><?php echo (int)$b['available']; ?></span> available</small>
            <div class="book-action">
              <?php if (isset($mine[$b['id']])) { ?>
                <span class="badge bg-primary">Borrowed</span> <small class="text-muted">due <?php echo e($mine[$b['id']]); ?></small>
              <?php } elseif ($b['available'] <= 0) { ?>
                <span class="badge bg-secondary">Not available</span>
              <?php } else { ?>
                <form method="POST" class="borrow-form">
                  <input type="hidden" name="book_id" value="<?php echo $b['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-primary">Borrow</button>
                </form>
              <?php } ?>
            </div>
          </div>
        </article>
      </div>
    <?php } ?>
  </div>
  <?php } ?>
</section>
<?php include 'footer.php'; ?>
