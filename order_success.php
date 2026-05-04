<?php
global $conn;
include 'includes/db.php';
include 'includes/auth.php';

require_login('login.php');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user = current_user();
$order = null;

if ($id) {
    $stmt = $conn->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $user['id']);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
}

include 'includes/header.php';
?>

<main>
    <?php if ($order): ?>
        <section class="empty-state">
            <p class="eyebrow">Order placed</p>
            <h1>Thanks, <?php echo htmlspecialchars($order['name']); ?>.</h1>
            <p>Your order #<?php echo (int)$order['id']; ?> has been received. Total: R<?php echo number_format((float)$order['total'], 2); ?>.</p>
            <a class="button primary" href="account.php">View Account</a>
        </section>
    <?php else: ?>
        <section class="empty-state">
            <h1>Order not found</h1>
            <p>The order link may be incorrect.</p>
            <a class="button primary" href="index.php">Back to Shop</a>
        </section>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
