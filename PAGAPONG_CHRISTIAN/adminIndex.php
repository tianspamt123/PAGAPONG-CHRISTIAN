<?php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$students = [];
$dbError = '';
require_once __DIR__ . '/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_student') {
    $name = trim($_POST['studentname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($name === '' || $email === '') {
        $dbError = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $dbError = 'Please enter a valid email address.';
    } else {
        try {
            $pdo = getPDO();
            ensureStudentsTable($pdo);
            $ins = $pdo->prepare('INSERT INTO students (studentname, email) VALUES (:name, :email)');
            $ins->execute(['name' => $name, 'email' => $email]);
            header('Location: adminIndex.php');
            exit;
        } catch (Exception $e) {
            $dbError = 'Insert failed: ' . $e->getMessage();
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_students') {
    $ids = $_POST['delete_ids'] ?? [];
    if (!is_array($ids)) $ids = [$ids];
    $filtered = array_values(array_filter($ids, function($v){ return ctype_digit((string)$v); }));
    if (empty($filtered)) {
        $dbError = 'No students selected for deletion.';
    } else {
        try {
            $pdo = getPDO();
            ensureStudentsTable($pdo);
            $placeholders = implode(',', array_fill(0, count($filtered), '?'));
            $stmt = $pdo->prepare("DELETE FROM students WHERE studentid IN ($placeholders)");
            $stmt->execute($filtered);
            header('Location: adminIndex.php');
            exit;
        } catch (Exception $e) {
            $dbError = 'Delete failed: ' . $e->getMessage();
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_student') {
    $id = $_POST['studentid'] ?? '';
    $name = trim($_POST['studentname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($id === '' || !ctype_digit((string)$id)) {
        $dbError = 'Invalid student id.';
    } elseif ($name === '' || $email === '') {
        $dbError = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $dbError = 'Please enter a valid email address.';
    } else {
        try {
            $pdo = getPDO();
            ensureStudentsTable($pdo);
            $upd = $pdo->prepare('UPDATE students SET studentname = :name, email = :email WHERE studentid = :id');
            $upd->execute(['name' => $name, 'email' => $email, 'id' => $id]);
            header('Location: adminIndex.php');
            exit;
        } catch (Exception $e) {
            $dbError = 'Update failed: ' . $e->getMessage();
        }
    }
}
try {
    $pdo = getPDO();
    ensureStudentsTable($pdo);
    $stmt = $pdo->query('SELECT studentid, studentname, email FROM students ORDER BY studentid ASC');
    $students = $stmt->fetchAll();
} catch (Exception $e) {
    if ($dbError === '') {
        $dbError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="container">
    <h1>Admin Dashboard</h1>
    <p>Welcome, Admin! Here you can manage the application.</p>
    <ul>
        <li><a href="#ManageStudent">Manage Student</a></li>
        <li><a href="#Student">List of Student</a></li>
        <li><a href="#Settings">Settings</a> | <a href="logout.php">Logout</a></li>
    </ul>

    <section class="Student">
        <?php if ($dbError): ?>
            <p style="color: red;">Database error: <?php echo htmlspecialchars($dbError); ?></p>
        <?php endif; ?>

    <!-- Add Student modal (hidden by default) -->
        <div id="addStudentModal" class="modal" aria-hidden="true">
            <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
                <h3 id="modalTitle">Add Student</h3>
                <form method="post" id="addStudentForm">
                    <input type="hidden" name="action" value="add_student">
                    <div class="form-row">
                        <label for="modalStudentName">Name</label>
                        <input id="modalStudentName" type="text" name="studentname" required>
                    </div>
                    <div class="form-row">
                        <label for="modalStudentEmail">Email</label>
                        <input id="modalStudentEmail" type="email" name="email" required>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn-primary">Add student</button>
                        <button type="button" id="cancelAdd" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Students modal (choose multiple to delete) -->
        <div id="deleteModal" class="modal" aria-hidden="true">
            <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
                <h3 id="deleteModalTitle">Delete Students</h3>
                <form method="post" id="deleteForm">
                    <input type="hidden" name="action" value="delete_students">
                    <div class="delete-list">
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $s): ?>
                                <div class="delete-row">
                                    <label>
                                        <input type="checkbox" name="delete_ids[]" value="<?php echo htmlspecialchars($s['studentid']); ?>">
                                        <?php echo htmlspecialchars($s['studentid'] . ' — ' . $s['studentname'] . ' <' . $s['email'] . '>'); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No students available to delete.</p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn-primary">Delete selected</button>
                        <button type="button" id="cancelDelete" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Student modal -->
        <div id="editStudentModal" class="modal" aria-hidden="true">
            <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
                <h3 id="editModalTitle">Edit Student</h3>
                <form method="post" id="editStudentForm">
                    <input type="hidden" name="action" value="edit_student">
                    <input type="hidden" name="studentid" id="editStudentId">
                    <div class="form-row">
                        <label for="editStudentName">Name</label>
                        <input id="editStudentName" type="text" name="studentname" required>
                    </div>
                    <div class="form-row">
                        <label for="editStudentEmail">Email</label>
                        <input id="editStudentEmail" type="email" name="email" required>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn-primary">Save changes</button>
                        <button type="button" id="cancelEdit" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students)): ?>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['studentid']); ?></td>
                            <td><?php echo htmlspecialchars($s['studentname']); ?></td>
                            <td><?php echo htmlspecialchars($s['email']); ?></td>
                            <td>
                                <button type="button" class="editBtn" data-id="<?php echo htmlspecialchars($s['studentid']); ?>" data-name="<?php echo htmlspecialchars($s['studentname']); ?>" data-email="<?php echo htmlspecialchars($s['email']); ?>">Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No students found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="student-actions">
            <button id="addstudent">Add Student</button>
            <button id="deleteStudent">Delete</button>
        </div>

    </section>
    <section class="Settings">
        <h2>Settings</h2>
        <p><a href="logout.php">Log out</a></p>
    </section>
    <script>
    // Modal behavior for Add Student
    (function(){
        var openBtn = document.getElementById('addstudent');
        var modal = document.getElementById('addStudentModal');
        var cancel = document.getElementById('cancelAdd');

    if (!openBtn || !modal) return;

        function showModal(){
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden','false');
            // focus first input
            var first = modal.querySelector('input[name="studentname"]');
            if(first) first.focus();
        }
        function hideModal(){
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden','true');
        }

        openBtn.addEventListener('click', function(e){
            e.preventDefault();
            showModal();
        });

    // Delete modal open/close
        var openDelete = document.getElementById('deleteStudent');
        var deleteModal = document.getElementById('deleteModal');
        var cancelDelete = document.getElementById('cancelDelete');
        if (openDelete && deleteModal) {
            openDelete.addEventListener('click', function(e){
                e.preventDefault();
                deleteModal.style.display = 'flex';
                deleteModal.setAttribute('aria-hidden','false');
            });
            if (cancelDelete) cancelDelete.addEventListener('click', function(){ deleteModal.style.display = 'none'; deleteModal.setAttribute('aria-hidden','true'); });
            deleteModal.addEventListener('click', function(e){ if (e.target === deleteModal) { deleteModal.style.display = 'none'; deleteModal.setAttribute('aria-hidden','true'); } });
        }
        // Edit modal open/close and prefill
        var editButtons = document.querySelectorAll('.editBtn');
        var editModal = document.getElementById('editStudentModal');
        var cancelEdit = document.getElementById('cancelEdit');
        if (editModal) {
            editButtons.forEach(function(btn){
                btn.addEventListener('click', function(){
                    var id = btn.getAttribute('data-id');
                    var name = btn.getAttribute('data-name');
                    var email = btn.getAttribute('data-email');
                    document.getElementById('editStudentId').value = id;
                    document.getElementById('editStudentName').value = name;
                    document.getElementById('editStudentEmail').value = email;
                    editModal.style.display = 'flex';
                    editModal.setAttribute('aria-hidden','false');
                });
            });
            if (cancelEdit) cancelEdit.addEventListener('click', function(){ editModal.style.display = 'none'; editModal.setAttribute('aria-hidden','true'); });
            editModal.addEventListener('click', function(e){ if (e.target === editModal) { editModal.style.display = 'none'; editModal.setAttribute('aria-hidden','true'); } });
        }

        if (cancel) cancel.addEventListener('click', function(){ hideModal(); });

        // click outside modal-content closes
        modal.addEventListener('click', function(e){
            if (e.target === modal) hideModal();
        });
    })();
    </script>
</body>
</html>
