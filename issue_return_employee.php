<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Issue & Return Book (Staff)';

$message = '';
$error = '';

// Simple staff book issue tracking via settings (subject to minor data model)
$issues = [];
$res = db_query("SELECT * FROM settings WHERE setting_key LIKE 'staff_book_issue_%' ORDER BY setting_key DESC");
while ($row = $res->fetch_assoc()) { $issues[] = $row; }

$books = [];
$res = db_query("SELECT * FROM books WHERE status=1 ORDER BY title");
while ($row = $res->fetch_assoc()) { $books[] = $row; }

$employees = [];
$res = db_query("SELECT emp_id, first_name, last_name FROM employees WHERE status=1 ORDER BY first_name");
while ($row = $res->fetch_assoc()) { $employees[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'IssueBook') {
        $emp_id = (int) ($_POST['emp_id'] ?? 0);
        $book_id = (int) ($_POST['book_id'] ?? 0);
        if ($emp_id <= 0 || $book_id <= 0) {
            $error = 'Employee aur book select karein.';
        } else {
            $book = db_query("SELECT * FROM books WHERE book_id=$book_id")->fetch_assoc();
            if (!$book || (int)$book['available'] <= 0) {
                $error = 'Ye book available nahi hai.';
            } else {
                $emp = db_query("SELECT first_name FROM employees WHERE emp_id=$emp_id")->fetch_assoc();
                $key = 'staff_book_issue_' . time() . '_' . rand(100, 999);
                $val = $book_id . '|' . $emp_id . '|' . $book['title'] . '|' . $emp['first_name'] . '|' . date('Y-m-d') . '|issued';
                $st2 = db_prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $st2->bind_param('ss', $key, $val);
                $st2->execute();
                db_query("UPDATE books SET available = available - 1 WHERE book_id=$book_id");
                $message = "Book issued to {$emp['first_name']}.";
            }
        }
    }

    if ($action === 'ReturnBook') {
        $key = trim($_POST['key'] ?? '');
        if ($key !== '') {
            $row = db_query("SELECT * FROM settings WHERE setting_key='" . str_replace("'", "''", $key) . "'")->fetch_assoc();
            if ($row) {
                $parts = explode('|', $row['setting_value']);
                $book_id = (int)$parts[0];
                $newval = $parts[0] . '|' . $parts[1] . '|' . $parts[2] . '|' . $parts[3] . '|' . date('Y-m-d') . '|returned';
                $st2 = db_prepare("UPDATE settings SET setting_value=? WHERE setting_key=?");
                $st2->bind_param('ss', $newval, $key);
                $st2->execute();
                db_query("UPDATE books SET available = available + 1 WHERE book_id=$book_id");
                $message = 'Book returned successfully!';
            }
        }
    }
}

$parsed = [];
foreach ($issues as $i) {
    $parts = explode('|', $i['setting_value']);
    $parsed[] = [
        'key' => $i['setting_key'],
        'book_id' => $parts[0], 'emp_id' => $parts[1],
        'book' => $parts[2], 'emp' => $parts[3],
        'date' => $parts[4], 'status' => $parts[5]
    ];
}

include __DIR__ . '/includes/header.php';
?>
<div class="main-content">
    <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 4px;">
            <h3 style="font-size:18px; font-weight:800; color:#111827; margin:0;"><i class="fa fa-book"></i> Issue &amp; Return Book (Staff)</h3>
        </div>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="issue_return_employee.php" style="background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:16px;">
                    <h4 style="font-size:15px; font-weight:800; margin:0 0 12px;">Issue Book to Staff</h4>
                    <input type="hidden" name="action" value="IssueBook">
                    <div class="form-group">
                        <label class="required">Staff Member</label>
                        <select name="emp_id" class="form-control" required>
                            <option value="">Select Staff</option>
                            <?php foreach ($employees as $e): ?><option value="<?php echo $e['emp_id']; ?>"><?php echo e($e['first_name'] . ' ' . $e['last_name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">Book</label>
                        <select name="book_id" class="form-control" required>
                            <option value="">Select Book (available)</option>
                            <?php foreach ($books as $b): if ($b['available'] > 0): ?>
                                <option value="<?php echo $b['book_id']; ?>"><?php echo e($b['title']); ?> (<?php echo $b['available']; ?>)</option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;"><i class="fa fa-bookmark"></i> Issue Book</button>
                </form>
            </div>

            <div class="col-md-8">
                <div style="overflow-x:auto; background:#fff; border:1px solid #E5E7EB; border-radius:14px;">
                    <table class="table table-striped table-bordered" style="width:100%; background:#fff; margin-bottom:0;">
                        <thead><tr><th>#</th><th>Book</th><th>Staff</th><th>Issue Date</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            <?php if (count($parsed) === 0): ?><tr><td colspan="6" style="text-align:center; color:#6B7280; padding:25px;">Koi staff book issue nahi hai.</td></tr><?php endif; ?>
                            <?php foreach ($parsed as $idx => $p): ?>
                                <tr>
                                    <td><?php echo $idx + 1; ?></td>
                                    <td><strong><?php echo e($p['book']); ?></strong></td>
                                    <td><?php echo e($p['emp']); ?></td>
                                    <td><?php echo $p['date'] ? date('d M Y', strtotime($p['date'])) : '-'; ?></td>
                                    <td><span class="status-badge status-<?php echo $p['status'] == 'issued' ? 'pending' : 'present'; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                                    <td>
                                        <?php if ($p['status'] == 'issued'): ?>
                                            <form method="post" action="issue_return_employee.php" style="display:inline;">
                                                <input type="hidden" name="action" value="ReturnBook">
                                                <input type="hidden" name="key" value="<?php echo e($p['key']); ?>">
                                                <button class="btn btn-primary btn-xs" style="color:#fff;"><i class="fa fa-undo"></i> Return</button>
                                            </form>
                                        <?php endif; ?>
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