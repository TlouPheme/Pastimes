<?php
global $conn;
include 'includes/db.php';
include 'includes/auth.php';

require_login('login.php');

$user = current_user();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'profile';

    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
    } elseif ($action === 'password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();

        if (!$account || !password_verify($currentPassword, $account['password'])) {
            $message = 'Your current password is incorrect.';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 6) {
            $message = 'Your new password must be at least 6 characters.';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'The new passwords do not match.';
            $messageType = 'error';
        } else {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $passwordHash, $user['id']);

            if ($stmt->execute()) {
                $message = 'Password changed successfully.';
                $messageType = 'success';
            } else {
                $message = 'Your password could not be changed.';
                $messageType = 'error';
            }
        }
    } else {
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $message = 'Please enter your name.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare('UPDATE users SET name = ? WHERE id = ?');
            $stmt->bind_param('si', $name, $user['id']);

            if ($stmt->execute()) {
                $_SESSION['user']['name'] = $name;
                $user = current_user();
                $message = 'Account updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Your account could not be updated.';
                $messageType = 'error';
            }
        }
    }
}

$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM favorites WHERE user_id = ?');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$favoriteCount = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

$stmt = $conn->prepare('SELECT inquiries.*, products.name AS product_name, products.status AS product_status
    FROM inquiries
    LEFT JOIN products ON products.id = inquiries.product_id
    WHERE inquiries.user_id = ? OR inquiries.email = ?
    ORDER BY inquiries.created_at DESC');
$stmt->bind_param('is', $user['id'], $user['email']);
$stmt->execute();
$inquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<main>
    <section class="admin-page">
        <div class="admin-intro">
            <p class="eyebrow">Account</p>
            <h1>Your profile</h1>
            <p>Keep your account details current and track the items you have asked about.</p>
            <div class="admin-actions">
                <a class="button secondary" href="favorites.php">Favorites (<?php echo $favoriteCount; ?>)</a>
                <a class="button secondary" href="index.php#products">Back to Shop</a>
            </div>
        </div>

        <div class="category-panel">
            <?php if ($message): ?>
                <div class="form-message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form class="product-form flat-form" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="profile">
                <label>
                    <span>Name</span>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </label>
                <button type="submit">Save Account</button>
            </form>

            <div class="section-heading compact-heading">
                <h2>Change password</h2>
            </div>

            <form class="product-form flat-form" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="password">
                <label>
                    <span>Current password</span>
                    <input type="password" name="current_password" required>
                </label>
                <div class="form-grid">
                    <label>
                        <span>New password</span>
                        <input type="password" name="new_password" minlength="6" required>
                    </label>
                    <label>
                        <span>Confirm new password</span>
                        <input type="password" name="confirm_password" minlength="6" required>
                    </label>
                </div>
                <button type="submit">Change Password</button>
            </form>

            <div class="section-heading compact-heading">
                <h2>Your inquiries</h2>
            </div>

            <?php if ($inquiries): ?>
                <div class="inquiry-list">
                    <?php foreach ($inquiries as $inquiry): ?>
                        <article class="inquiry-card account-inquiry">
                            <div class="inquiry-main">
                                <div class="pill-row">
                                    <span class="status-pill <?php echo htmlspecialchars($inquiry['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($inquiry['status'])); ?>
                                    </span>
                                    <span class="status-pill <?php echo htmlspecialchars($inquiry['product_status'] ?? 'available'); ?>">
                                        <?php echo ucfirst(htmlspecialchars($inquiry['product_status'] ?? 'available')); ?>
                                    </span>
                                </div>
                                <h2><?php echo htmlspecialchars($inquiry['product_name'] ?? 'Deleted product'); ?></h2>
                                <?php if (!empty($inquiry['product_id'])): ?>
                                    <a class="back-link" href="product.php?id=<?php echo (int)$inquiry['product_id']; ?>">View product</a>
                                <?php endif; ?>
                                <p><?php echo nl2br(htmlspecialchars($inquiry['message'])); ?></p>
                                <p class="inquiry-contact"><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($inquiry['created_at']))); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state compact-empty">
                    <h2>No inquiries yet</h2>
                    <p>Messages you send from product pages will appear here.</p>
                </div>
            <?php endif; ?>

            <div class="section-heading compact-heading">
                <h2>Your orders</h2>
            </div>

            <?php if ($orders): ?>
                <div class="inquiry-list">
                    <?php foreach ($orders as $order): ?>
                        <article class="inquiry-card account-inquiry">
                            <div class="inquiry-main">
                                <div class="pill-row">
                                    <span class="status-pill <?php echo htmlspecialchars($order['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                    </span>
                                    <span class="category-pill"><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($order['created_at']))); ?></span>
                                </div>
                                <h2>Order #<?php echo (int)$order['id']; ?></h2>
                                <p>Total: R<?php echo number_format((float)$order['total'], 2); ?></p>
                                <p class="inquiry-contact"><?php echo ucfirst(htmlspecialchars($order['delivery_method'])); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state compact-empty">
                    <h2>No orders yet</h2>
                    <p>Checkout orders will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
