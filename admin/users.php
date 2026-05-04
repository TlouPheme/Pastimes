<?php
global $conn;
include '../includes/db.php';
include '../includes/auth.php';

require_admin('../index.php');

$currentUser = current_user();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
    } elseif ($id && $action === 'role') {
        $role = $_POST['role'] ?? '';

        if ($id === (int)$currentUser['id'] && $role !== 'admin') {
            $message = 'You cannot demote your own admin account.';
            $messageType = 'error';
        } elseif (in_array($role, ['admin', 'customer'], true)) {
            $stmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
            $stmt->bind_param('si', $role, $id);
            $stmt->execute();
            $message = 'User role updated.';
            $messageType = 'success';
        }
    } elseif ($id && $action === 'verification') {
        $verificationStatus = $_POST['verification_status'] ?? '';

        if (in_array($verificationStatus, ['pending', 'verified'], true)) {
            $stmt = $conn->prepare('UPDATE users SET verification_status = ? WHERE id = ?');
            $stmt->bind_param('si', $verificationStatus, $id);
            $stmt->execute();
            $message = 'User verification status updated.';
            $messageType = 'success';
        }
    } elseif ($id && $action === 'delete') {
        if ($id === (int)$currentUser['id']) {
            $message = 'You cannot delete your own account here.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                $message = 'User deleted successfully.';
                $messageType = 'success';
            } else {
                $message = 'User could not be deleted.';
                $messageType = 'error';
            }
        }
    }
}

$result = $conn->query('SELECT users.*,
    COUNT(DISTINCT favorites.id) AS favorite_count,
    COUNT(DISTINCT inquiries.id) AS inquiry_count
    FROM users
    LEFT JOIN favorites ON favorites.user_id = users.id
    LEFT JOIN inquiries ON inquiries.user_id = users.id OR inquiries.email = users.email
    GROUP BY users.id
    ORDER BY users.created_at DESC');
$users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

include '../includes/header.php';
?>

<main>
    <section class="admin-list-page">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>Users</h1>
            </div>
            <a class="button secondary" href="dashboard.php">Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="form-message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($users): ?>
            <div class="user-list">
                <?php foreach ($users as $row): ?>
                    <article class="user-row">
                        <div class="user-copy">
                            <div class="pill-row">
                                <span class="status-pill <?php echo htmlspecialchars($row['role']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($row['role'])); ?>
                                </span>
                                <span class="status-pill <?php echo htmlspecialchars($row['verification_status'] ?? 'verified'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($row['verification_status'] ?? 'verified')); ?>
                                </span>
                                <span class="category-pill"><?php echo htmlspecialchars(date('M j, Y', strtotime($row['created_at']))); ?></span>
                            </div>
                            <h2><?php echo htmlspecialchars($row['name']); ?></h2>
                            <p><?php echo htmlspecialchars($row['email']); ?></p>
                            <p><?php echo (int)$row['favorite_count']; ?> favorites &middot; <?php echo (int)$row['inquiry_count']; ?> inquiries</p>
                        </div>

                        <div class="user-actions">
                            <form class="inline-form" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="role">
                                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                <label>
                                    <span>Role</span>
                                    <select name="role">
                                        <option value="customer" <?php echo $row['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="admin" <?php echo $row['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </label>
                                <button type="submit">Save</button>
                            </form>

                            <form class="inline-form" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="verification">
                                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                <label>
                                    <span>Verification</span>
                                    <select name="verification_status">
                                        <option value="pending" <?php echo ($row['verification_status'] ?? 'verified') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="verified" <?php echo ($row['verification_status'] ?? 'verified') === 'verified' ? 'selected' : ''; ?>>Verified customer</option>
                                    </select>
                                </label>
                                <button type="submit">Verify</button>
                            </form>

                            <form method="POST" onsubmit="return confirm('Delete this user account?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                <button class="danger-button" type="submit">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>No users found</h2>
                <p>Registered accounts will appear here.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
