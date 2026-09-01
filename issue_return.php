<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Issue / Return Books';

$message = '';
$error = '';

$books = [];
$res = db_query("SELECT book_id, title FROM books WHERE status=1 AND available > 0 ORDER BY title");
while ($row = $res->fetch_assoc()) { $books[] = $row; }

$students = [];
$res = db_query("SELECT student_id, first_name, father_name FROM students WHERE status=1 ORDER BY first_name");
while ($row = $res->fetch_assoc()) { $students[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'IssueBook') {
        $book_id = (int) ($_POST['book_id'] ?? 0);
        $student_id = (int) ($_POST['student_id'] ?? 0);
        $issue_date = trim($_POST['issue_date'] ?? date('Y-m-d'));
        $due_date = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+14 days')));

        $bk = db_query("SELECT * FROM books WHERE book_id=$book_id")->fetch_assoc();
        if (!$bk) {
            $error = 'Book not found.';
        } elseif ((int)$bk['available'] <= 0) {
            $error = 'No copies available to issue.';
        } else {
            $st2 = db_prepare("INSERT INTO book_issues (book_id, student_id, issue_date, due_date, status) VALUES (?, ?, ?, ?, 'issued')");
            $st2->bind_param('iiss', $book_id, $student_id, $issue_date, $due_date);
            $st2->execute();
            $st3 = db_prepare("UPDATE books SET available = available - 1 WHERE book_id=?");
            $st3->bind_param('i', $book_id);
            $st3->execute();
            $message = 'Book issued successfully!';
        }
    }

    if ($action === 'ReturnBook') {
        $issue_id = (int) ($_POST['issue_id'] ?? 0);
        $ret = db_query("SELECT * FROM book_issues WHERE issue_id=$issue_id")->fetch_assoc();
        if ($ret) {
            $st2 = db_prepare("UPDATE book_issues SET return_date=?, status='returned' WHERE issue_id=?");
            $rd = date('Y-m-d');
            $st2->bind_param('si', $rd, $issue_id);
            $st2->execute();
            $st3 = db_prepare("UPDATE books SET available = available + 1 WHERE book_id=?");
            $bid = $ret['book_id'];
            $st3->bind_param('i', $bid);
            $st3->execute();
            $message = 'Book returned successfully!';
        }
    }
}

$issues = [];
$res = db_query("SELECT bi.*, b.title, s.first_name, s.father_name, s.roll_no
                 FROM book_issues bi
                 JOIN books b ON bi.book_id=b.book_id
                 LEFT JOIN students s ON bi.student_id=s.student_id
                 WHERE bi.status='issued' ORDER BY bi.issue_date DESC");
while ($row = $res->fetch_assoc()) { $issues[] = $row; }

$history = [];
$res = db_query("SELECT bi.*, b.title, s.first_name FROM book_issues bi
                 JOIN books b ON bi.book_id=b.book_id
                 LEFT JOIN students s ON bi.student_id=s.student_id
                 WHERE bi.status='returned' ORDER BY bi.return_date DESC LIMIT 50");
while ($row = $res->fetch_assoc()) { $history[] = $row; }

include __DIR__ . '/includes/header.php';
?>
<style>
.search-bar-student { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px; margin-bottom:16px; }
.overdue { color:#DC2626; font-weight:700; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-exchange"></i> Issue / Return Books</h3>
            <a href="<?php echo BASE_URL; ?>list_books.php" class="btn btn-primary" style="color:#fff;"><i class="fa fa-book"></i> Books</a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="issue_return.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 14px;">Issue Book</h4>
                    <input type="hidden" name="action" value="IssueBook">
                    <div class="form-group">
                        <label>Book</label>
                        <select name="book_id" class="form-control">
                            <?php foreach ($books as $b): ?>
                                <option value="<?php echo $b['book_id']; ?>"><?php echo e($b['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Student</label>
                        <select name="student_id" class="form-control">
                            <?php foreach ($students as $s): ?>
                                <option value="<?php echo $s['student_id']; ?>"><?php echo e($s['first_name']); ?> — <?php echo e($s['father_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Issue Date</label>
                        <input type="date" name="issue_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;"><i class="fa fa-plus"></i> Issue Book</button>
                </form>
            </div>

            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px; margin-bottom:14px;">
                    <h4 style="font-size:15px; font-weight:800; padding:14px 16px; margin:0; border-bottom:1px solid #F3F4F6;">Issued Books</h4>
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead>
                            <tr><th>#</th><th>Book</th><th>Student</th><th>Issue Date</th><th>Due Date</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($issues) === 0): ?>
                                <tr><td colspan="6" style="text-align:center; color:#6B7280; padding:30px;">No books currently issued.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($issues as $i): $od = ($i['due_date'] && $i['due_date'] < date('Y-m-d')); ?>
                                <tr>
                                    <td><?php echo $i['issue_id']; ?></td>
                                    <td><strong><?php echo e($i['title']); ?></strong></td>
                                    <td><?php echo e($i['first_name']); ?><br><small style="color:#6B7280;"><?php echo e($i['father_name']); ?></small></td>
                                    <td><?php echo date('d M Y', strtotime($i['issue_date'])); ?></td>
                                    <td class="<?php echo $od ? 'overdue' : ''; ?>"><?php echo date('d M Y', strtotime($i['due_date'])); ?> <?php echo $od ? '(Overdue)' : ''; ?></td>
                                    <td>
                                        <form method="post" action="issue_return.php" style="display:inline;">
                                            <input type="hidden" name="action" value="ReturnBook">
                                            <input type="hidden" name="issue_id" value="<?php echo $i['issue_id']; ?>">
                                            <button class="btn btn-success btn-xs"><i class="fa fa-rotate-left"></i> Return</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
            <h4 style="font-size:15px; font-weight:800; padding:14px 16px; margin:0; border-bottom:1px solid #F3F4F6;">Return History</h4>
            <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                <thead>
                    <tr><th>#</th><th>Book</th><th>Student</th><th>Issue Date</th><th>Return Date</th></tr>
                </thead>
                <tbody>
                    <?php if (count($history) === 0): ?>
                        <tr><td colspan="5" style="text-align:center; color:#6B7280; padding:30px;">No returned books yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?php echo $h['issue_id']; ?></td>
                            <td><?php echo e($h['title']); ?></td>
                            <td><?php echo e($h['first_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($h['issue_date'])); ?></td>
                            <td><?php echo date('d M Y', strtotime($h['return_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>