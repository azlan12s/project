<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) $_POST['id'];
    $self = (int) current_user()['id'];
    if (isset($_POST['role']) && $id !== $self) {
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
        db_query("UPDATE users SET role = '$role' WHERE id = $id");
        flash_set('ok', 'Role updated.');
    }
    if (isset($_POST['delete']) && $id !== $self) {
        db_query("DELETE FROM users WHERE id = $id");
        flash_set('ok', 'User removed.');
    }
    redirect('/admin/users.php');
}

$users = db_all("SELECT * FROM users ORDER BY id ASC");
$page_title = 'Users';
$active = 'users';
require __DIR__ . '/../includes/admin_header.php';
?>
        <h2 style="margin-top:0">User management</h2>
        <table class="table">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u) { ?>
                <tr>
                    <td><?php echo e($u['name']); ?></td>
                    <td><?php echo e($u['email']); ?></td>
                    <td><?php echo e($u['role']); ?></td>
                    <td>
                        <form method="post" style="display:flex;gap:8px;flex-wrap:wrap">
                            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                            <select name="role">
                                <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>user</option>
                                <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                            </select>
                            <button class="btn" type="submit">Save role</button>
                            <?php if ((int) $u['id'] !== (int) current_user()['id']) { ?>
                                <button class="btn-danger" name="delete" value="1" onclick="return confirm('Delete user?')">Delete</button>
                            <?php } ?>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
