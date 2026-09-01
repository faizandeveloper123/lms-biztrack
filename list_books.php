<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Library Books';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'AddBook') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $quantity = (int) ($_POST['quantity'] ?? 0);
        if ($title === '') {
            $error = 'Book title is required.';
        } else {
            $st2 = db_prepare("INSERT INTO books (title, author, category, quantity, available, status) VALUES (?, ?, ?, ?, ?, 1)");
            $st2->bind_param('sssii', $title, $author, $category, $quantity, $quantity);
            $st2->execute();
            $message = 'Book added successfully!';
        }
    }

    if ($action === 'DeleteBook') {
        $bid = (int) ($_POST['book_id'] ?? 0);
        $st2 = db_prepare("DELETE FROM books WHERE book_id=?");
        $st2->bind_param('i', $bid);
        $st2->execute();
        $message = 'Book deleted successfully!';
    }
}

$q = trim($_GET['q'] ?? '');
$books = [];
$sql = "SELECT * FROM books";
if ($q !== '') { $sql .= " WHERE title LIKE '%$q%' OR author LIKE '%$q%' OR category LIKE '%$q%'"; }
$sql .= " ORDER BY title";
$res = db_query($sql);
while ($row = $res->fetch_assoc()) {
    $res2 = db_query("SELECT COUNT(*) c FROM book_issues WHERE book_id={$row['book_id']} AND status='issued'");
    $row['issued'] = (int) ($res2->fetch_assoc()['c'] ?? 0);
    $books[] = $row;
}

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-book"></i> Library Books <span style="font-size:14px; color:#6B7280;">(<?php echo count($books); ?> books)</span></h3>
            <a href="<?php echo BASE_URL; ?>issue_return.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-exchange"></i> Issue / Return</a>
        </div>

        <form method="get" action="list_books.php" class="search-bar-student">
            <div class="form-group col-md-4" style="margin-bottom:0;">
                <input type="text" name="q" class="form-control" placeholder="Search by title, author or category..." value="<?php echo e($q); ?>">
            </div>
            <div class="form-group col-md-2" style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-search"></i> Search</button>
            </div>
        </form>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="list_books.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Add Book</h4>
                    <input type="hidden" name="action" value="AddBook">
                    <div class="form-group">
                        <label class="required">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Book title" required>
                    </div>
                    <div class="form-group">
                        <label>Author</label>
                        <input type="text" name="author" class="form-control" placeholder="Author name">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" class="form-control" placeholder="e.g. English">
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" class="form-control" value="5">
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;">Add Book</button>
                </form>
            </div>

            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead>
                            <tr><th>#</th><th>Title</th><th>Author</th><th>Category</th><th>Total</th><th>Issued</th><th>Available</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($books) === 0): ?>
                                <tr><td colspan="8" style="text-align:center; color:#6B7280; padding:30px;">No books found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($books as $b): ?>
                                <tr>
                                    <td><?php echo $b['book_id']; ?></td>
                                    <td><strong><?php echo e($b['title']); ?></strong></td>
                                    <td><?php echo e($b['author'] ?? '-'); ?></td>
                                    <td><span class="status-badge status-paid" style="background:#E0E7FF; color:#4338CA;"><?php echo e($b['category'] ?? '-'); ?></span></td>
                                    <td><?php echo $b['quantity']; ?></td>
                                    <td style="color:#F59E0B; font-weight:700;"><?php echo $b['issued']; ?></td>
                                    <td style="color:#16A34A; font-weight:700;"><?php echo $b['available']; ?></td>
                                    <td>
                                        <form method="post" action="list_books.php" style="display:inline;" onsubmit="return confirm('Delete this book?');">
                                            <input type="hidden" name="action" value="DeleteBook">
                                            <input type="hidden" name="book_id" value="<?php echo $b['book_id']; ?>">
                                            <button class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>